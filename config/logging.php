<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use App\Logging\CreateElasticSearchLogger;
use App\Logging\CreateCustomLogger;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'custom' => [
            'driver' => 'daily',
            'tap' => [App\Logging\CustomizeFormatter::class],
            'path' => storage_path('logs/custom.log'),
            'level' => 'debug',
            'days' => 5,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'mongodb' => [ // 此处可以根据需求调整
            'driver' => 'custom', // 此处必须为 `custom`
            'via' => CreateCustomLogger::class, // 当 `driver` 设置为 custom 时，使用 `via` 配置项所指向的工厂类创建 logger

            // 以下 env 配置名可以根据需求调整
            'server' => env('LOG_MONGO_SERVER', 'mongodb://localhost:27017'),
            'database' => env('LOG_MONGO_DB', 'logs'),
            'collection' => env('LOG_MONGO_COLLECTION', 'logs'),
            'level' => env('LOG_MONGO_LEVEL', 'debug'), // 日志级别
        ],

        'elasticsearch' => [ // 此处可以根据需求调整
            'driver' => 'custom', // 此处必须为 `custom`
            'via' => CreateElasticSearchLogger::class, // 当 `driver` 设置为 custom 时，使用 `via` 配置项所指向的工厂类创建 logger

            // 以下 env 配置名可以根据需求调整
            'host' => env('LOG_ELASTIC_SEARCH_HOST', '127.0.0.1'),
            'port' => env('LOG_ELASTIC_SEARCH_PORT', '9200'),
            'level' => env('LOG_MONGO_LEVEL', 'debug'), // 日志级别
        ],
    ],

];
