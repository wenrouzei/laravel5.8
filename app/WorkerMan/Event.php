<?php
/**
 * Created by PhpStorm.
 * User: wenrouzei
 * Date: 2019/8/12
 * Time: 23:13
 */

namespace App\WorkerMan;


use App\User;
use GatewayWorker\BusinessWorker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Event
{
    /**
     * @param $businessWorker
     */
    public static function onWorkerStart(BusinessWorker $businessWorker)
    {
        Log::channel('custom')->debug('worker-id：' . $businessWorker->workerId);
    }

    public static function onConnect($clientId)
    {
        Log::channel('custom')->info('connect：' . $clientId);
    }

    public static function onWebSocketConnect($clientId, $data)
    {
        Log::channel('custom')->info('ws connect：' . $clientId, ['$data' => $data]);
    }

    public static function onMessage($clientId, $message)
    {
        $user = new User();
        $user->name = Str::random(10);
        $user->email = Str::random(10) . '@' . Str::random(3) . '.com';
        $user->password = Str::random(16);
        $user->save();
        Log::channel('custom')->info($clientId . ' message：' . $message);
    }

    public static function onClose($clientId)
    {
        Log::channel('custom')->debug('close：' . $clientId);
    }
}