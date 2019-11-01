<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PcntlDispatchOriginalMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pcntl:dispatch-original-messages {action} {workerCount?} {--daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '使用多进程分发redis队列中的原始数据到laravel的队列任务中处理';

    protected $workerCount = 5;

    protected $processChildIds = [];

    protected $pidFile;

    protected $shouldQuit = false;

    /**
     * Status starting.
     *
     * @var int
     */
    const STATUS_STARTING = 1;

    /**
     * Status running.
     *
     * @var int
     */
    const STATUS_RUNNING = 2;

    /**
     * Status shutdown.
     *
     * @var int
     */
    const STATUS_SHUTDOWN = 4;

    /**
     * Status reloading.
     *
     * @var int
     */
    const STATUS_RELOADING = 8;


    protected $status = self::STATUS_STARTING;


    protected $masterPid;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->parseCommand();
    }

    /**
     * 解析命令
     */
    protected function parseCommand()
    {
        try {
            $this->init();
            $action = $this->argument('action');
            switch ($action) {
                case 'start':
                    pcntl_async_signals(true);
                    //检测进程是否已开启
                    $pid = $this->getPid();
                    $masterIsAlive = $pid && posix_kill($pid, 0) && posix_getpid() != $pid;
                    if ($masterIsAlive) {
                        $this->error("\r\n" . $this->signature . " process already exist!\r\n");
                        exit;
                    }
                    if ($this->option('daemon')) $this->daemon();
                    $this->installSignal();
                    $this->saveMasterPid();
                    $this->forkWorkers();
                    $this->monitorWorkers();
                    break;
                case 'stop':
                    $pid = $this->getPid();
                    $masterIsAlive = $pid && posix_kill($pid, 0) && posix_getpid() != $pid;
                    if (!$masterIsAlive) {
                        $this->error("\r\n" . $this->signature . " process already exit!\r\n");
                        exit;
                    }
                    posix_kill($pid, SIGINT);
                    break;
                case 'reload':
                    $pid = $this->getPid();
                    $masterIsAlive = $pid && posix_kill($pid, 0) && posix_getpid() != $pid;
                    if (!$masterIsAlive) {
                        $this->error("\r\n" . $this->signature . " process already exit!\r\n");
                        exit;
                    }
                    posix_kill($pid, SIGUSR1);
                    break;
                default:
                    $this->error($this->signature . " action(" . $action . ") does not exist!");
                    break;
            }
        } catch (\Throwable $throwable) {
            $this->error('启动命令行 ' . $this->signature . " action(" . $action . ") 抛出异常或错误：" . $throwable->getMessage());
            Log::error($this->signature . " action(" . $action . ") 抛出错误!", ['$throwable' => $throwable]);
        }
    }

    /**
     * 初始化
     */
    protected function init()
    {
        if (strpos(strtolower(PHP_OS), 'win') === 0) {
            exit("not support windows.\n");
        }

        if (!extension_loaded('pcntl')) {
            exit("Please install pcntl extension.\n");
        }

        if (!extension_loaded('posix')) {
            exit("Please install posix extension.\n");
        }
        $this->pidFile = storage_path('logs/' . str_replace(DIRECTORY_SEPARATOR, '_', __FILE__) . '.pid');
        if (intval($workerCount = $this->argument('workerCount'))) $this->workerCount = $workerCount;
        $this->status = self::STATUS_STARTING;
    }

    /**
     * 开启守护进程
     */
    protected function daemon()
    {
        $pid = pcntl_fork();
        if (-1 === $pid) {
            die('fork fail');
        } elseif ($pid > 0) {
            exit(0);
        }
        if (-1 === posix_setsid()) {
            die("setsid fail");
        }
        // Fork again avoid SVR4 system regain the control of terminal.
        $pid = pcntl_fork();
        if (-1 === $pid) {
            die("fork fail");
        } elseif (0 !== $pid) {
            exit(0);
        }
    }

    /**
     * 创建所有进程
     * @throws \Exception
     */
    protected function forkWorkers()
    {
        while (count($this->processChildIds) < $this->workerCount) {
            $this->forkOneWorker();
        }
    }

    /**
     * 主进程管理子进程
     * @throws \Exception
     */
    protected function monitorWorkers()
    {
        //子进程完成之后要退出
        while (count($this->processChildIds) > 0) {
            foreach ($this->processChildIds as $key => $pid) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);
                // If the process has already exited
                if ($res == -1 || $res > 0) {
                    Log::debug('子进程退出', ['$pid' => $pid, 'pcntl_wifexited' => pcntl_wifexited($status), 'pcntl_wifsignaled' => pcntl_wifsignaled($status), '$res' => $res]);
                    unset($this->processChildIds[$key]);
                }
            }
            if ($this->status == self::STATUS_RELOADING) $this->forkWorkers();
        }
        Log::debug('主进程退出', ['$masterPid' => $this->masterPid, 'pid' => posix_getpid()]);
        @unlink($this->pidFile);
    }

    /**
     * 创建一个进程
     * @throws \Exception
     */
    protected function forkOneWorker()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new \Exception("forkOneWorker fail");
        } elseif ($pid > 0) {
            $this->processChildIds[] = $pid;
        } else {
            while (true) {
                $this->handleStop();
                $this->handleTask();
            }
            exit();
        }
    }

    protected function handleStop()
    {
        if ($this->shouldQuit) exit();
    }

    /**
     * Save pid.
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function saveMasterPid()
    {
        $this->masterPid = posix_getpid();
        if (false === file_put_contents($this->pidFile, $this->masterPid) || (file_get_contents($this->pidFile) != $this->masterPid)) {
            $this->error("\r\n" . $this->signature . " 保存主进程id失败!\r\n");
            exit;
        }
    }

    //获取pid
    protected function getPid()
    {
        return file_exists($this->pidFile) ? file_get_contents($this->pidFile) : false;
    }

    /**
     * 安装信号
     */
    protected function installSignal()
    {
        // stop
        pcntl_signal(SIGINT, array($this, 'signalHandler'), false);
        // graceful stop
        pcntl_signal(SIGTERM, array($this, 'signalHandler'), false);
        // reload
        pcntl_signal(SIGUSR1, array($this, 'signalHandler'), false);
        // graceful reload
        pcntl_signal(SIGQUIT, array($this, 'signalHandler'), false);
        // status
        pcntl_signal(SIGUSR2, array($this, 'signalHandler'), false);
        // connection status
        pcntl_signal(SIGIO, array($this, 'signalHandler'), false);
        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }

    /**
     * 信号处理器
     * @param $signal
     */
    protected function signalHandler($signal)
    {
        Log::debug('receive signal: ' . $signal, ['pid' => posix_getpid()]);
        switch ($signal) {
            //Graceful stop.
            case SIGTERM:
                // Stop.
            case SIGINT:
                if ($this->masterPid === posix_getpid()) {
                    Log::debug('master receive signal(' . $signal . ') for close', ['master_pid' => posix_getpid()]);
                    foreach ($this->processChildIds as $pid) {
                        posix_kill($pid, SIGINT);
                    }
                    $this->status = self::STATUS_SHUTDOWN;
                    break;
                } else {
                    Log::debug('child receive signal(' . $signal . ') for close', ['child_pid' => posix_getpid()]);
                    $this->shouldQuit = true;
                }
                break;
            // Reload.
            case SIGQUIT:
            case SIGUSR1:
                if ($this->masterPid === posix_getpid()) {
                    Log::debug('master receive signal(' . $signal . ') for reload', ['master_pid' => posix_getpid()]);
                    foreach ($this->processChildIds as $pid) {
                        posix_kill($pid, SIGQUIT);
                    }
                    $this->status = self::STATUS_RELOADING;
                    break;
                } else {
                    Log::debug('child receive signal(' . $signal . ') for reload', ['child_pid' => posix_getpid()]);
                    $this->shouldQuit = true;
                }
                break;
        }
    }

    /**
     * 任务处理
     */
    protected function handleTask()
    {
        sleep(rand(3, 10));
        Log::debug('task finish', ['pid' => posix_getpid(), 'memory_get_usage()' => round(memory_get_usage() / 1024 / 1024, 2), 'memory_get_peak_usage' => round(memory_get_peak_usage() / 1024 / 1024, 2)]);
    }
}
