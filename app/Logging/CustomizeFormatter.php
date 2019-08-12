<?php
/**
 * Created by PhpStorm.
 * User: wenrouzei
 * Date: 2019/8/2
 * Time: 1:51
 */

namespace App\Logging;


use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;

class CustomizeFormatter
{
    /**
     * 自定义给定的日志实例。
     *
     * @param  \Illuminate\Log\Logger $logger
     * @return void
     */
    public function __invoke($logger)
    {
        $logger->pushProcessor(new WebProcessor($_SERVER)); // 记录额外的请求信息
        $logger->pushProcessor(new MemoryPeakUsageProcessor()); // 内存峰值
        $logger->pushProcessor(new MemoryUsageProcessor()); // 内存使用
    }
}