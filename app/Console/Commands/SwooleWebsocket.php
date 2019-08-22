<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class SwooleWebsocket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:websocket {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'start or stop the swoole websocket process';

    private $server;

    private $pidFile;

    private $logFile;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->pidFile = __DIR__ . '/../../../storage/swoole_websocket.pid';
        $this->logFile = __DIR__ . '/../../../storage/logs/swoole_websocket.log';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //获取传递的操作
        $arg = $this->argument('action');

        switch ($arg) {
            case 'start':
                //检测进程是否已开启
                $pid = $this->getPid();
                if ($pid && \Swoole\Process::kill($pid, 0)) {
                    $this->error("\r\nprocess already exist!\r\n");
                    exit;
                }

                $this->server = new \swoole_websocket_server("0.0.0.0", 9501);
                $this->server->set([
                    'worker_num' => 8,
                    'daemonize' => 1,
                    'max_request' => 1000,
                    'dispatch_mode' => 2,
                    'pid_file' => $this->pidFile,
                    'log_file' => $this->logFile,
                ]);

                //绑定操作类回调函数
                $app = App::make('App\Handles\SwooleWebSocketHandle');

                $this->server->on('open', array($app, 'onOpen'));

                $this->server->on('message', array($app, 'onMessage'));

                $this->server->on('close', array($app, 'onClose'));

                $this->info("\r\nprocess created successful!\r\n");

                $this->server->start();
                break;

            case 'stop':
                if (!$pid = $this->getPid()) {
                    $this->error("\r\nprocess not started!\r\n");
                    exit;
                }
                if (\Swoole\Process::kill((int)$pid)) {
                    $this->info("\r\nprocess close successful!\r\n");
                    exit;
                }
                $this->info("\r\nprocess close failed!\r\n");
                break;

            default:
                $this->error("\r\noperation method does not exist!\r\n");
        }
    }

    //获取pid
    private function getPid()
    {
        return file_exists($this->pidFile) ? file_get_contents($this->pidFile) : false;
    }
}
