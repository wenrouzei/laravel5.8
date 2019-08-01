<?php
/**
 * Created by PhpStorm.
 * User: wenrouzei
 * Date: 2019/7/17
 * Time: 22:28
 */

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\MongoDBHandler;
use Monolog\Processor\WebProcessor;

class CreateCustomLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger(''); // 创建 Logger

        $handler = new MongoDBHandler( // 创建 Handler
            new \MongoDB\Client($config['server']), // 创建 MongoDB 客户端（依赖 mongodb/mongodb）
            $config['database'],
            $config['collection']
        );
        $handler->setLevel($config['level']);

        $logger->pushHandler($handler); // 挂载 Handler
        $logger->pushProcessor(new WebProcessor($_SERVER)); // 记录额外的请求信息

        return $logger;
    }
}