<?php
/**
 * Created by PhpStorm.
 * User: wenrouzei
 * Date: 2019/7/17
 * Time: 22:28
 */

namespace App\Logging;

use Elastica\Client;
use Monolog\Handler\ElasticSearchHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;

class CreateElasticSearchLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param array $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger(''); // 创建 Logger
        $client = new Client(['host' => $config['host'], 'port' => $config['port']]);
        $handler = new ElasticSearchHandler($client);
        $handler->setLevel($config['level']);

        $logger->pushHandler($handler); // 挂载 Handler
        $logger->pushProcessor(new WebProcessor($_SERVER)); // 记录额外的请求信息

        return $logger;
    }
}