<?php
/**
 * Created by PhpStorm.
 * User: wenrouzei
 * Date: 2019/8/22
 * Time: 10:04
 */

namespace App\Handles;


use Illuminate\Support\Facades\Log;

class SwooleWebSocketHandle
{
    /**
     * @var array
     */
    protected static $timers;

    public function onOpen(\Swoole\WebSocket\Server $server, $request)
    {
        static::$timers[$request->fd] = \Swoole\Timer::tick(10000, function () use ($server, $request) {
            $server->push($request->fd, '{"type":"ping"}');
        });
//        echo "server: handshake success with fd{$request->fd}\n";
        Log::channel('custom')->info($request->fd . ' open', ['$timers' => static::$timers, 'pid' => $server->worker_pid, 'manager_pid' => $server->manager_pid, 'master_pid' => $server->master_pid]);
    }

    public function onMessage(\Swoole\WebSocket\Server $server, $frame)
    {
//        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        $server->push($frame->fd, '{"type":"success"}');
        Log::channel('custom')->info($frame->fd . ' message：' . $frame->data, ['$timers' => static::$timers, 'pid' => $server->worker_pid, 'manager_pid' => $server->manager_pid, 'master_pid' => $server->master_pid]);
    }

    public function onClose(\Swoole\WebSocket\Server $server, $fd)
    {
        if (isset(static::$timers[$fd])) \Swoole\Timer::clear(static::$timers[$fd]);
//        echo "client {$fd} closed\n";
        Log::channel('custom')->info($fd . ' close', ['$timers' => static::$timers, 'pid' => $server->worker_pid, 'manager_pid' => $server->manager_pid, 'master_pid' => $server->master_pid]);
    }
}