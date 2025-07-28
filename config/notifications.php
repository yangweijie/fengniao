<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 默认通知渠道
    |--------------------------------------------------------------------------
    |
    | 指定默认使用的通知渠道
    |
    */
    'default_channels' => [
        'email',
        'dingtalk'
    ],

    /*
    |--------------------------------------------------------------------------
    | 邮件通知配置
    |--------------------------------------------------------------------------
    */
    'email' => [
        'enabled' => env('NOTIFICATION_EMAIL_ENABLED', true),
        'default_to' => env('NOTIFICATION_EMAIL_TO', 'admin@example.com'),
        'from_name' => env('NOTIFICATION_EMAIL_FROM_NAME', 'Dusk自动化平台'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 钉钉通知配置
    |--------------------------------------------------------------------------
    */
    'dingtalk' => [
        'enabled' => env('NOTIFICATION_DINGTALK_ENABLED', false),
        'webhook' => env('NOTIFICATION_DINGTALK_WEBHOOK'),
        'secret' => env('NOTIFICATION_DINGTALK_SECRET'),
        'at_all' => env('NOTIFICATION_DINGTALK_AT_ALL', false),
        'at_mobiles' => env('NOTIFICATION_DINGTALK_AT_MOBILES', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | 企业微信通知配置
    |--------------------------------------------------------------------------
    */
    'wechat_work' => [
        'enabled' => env('NOTIFICATION_WECHAT_WORK_ENABLED', false),
        'webhook' => env('NOTIFICATION_WECHAT_WORK_WEBHOOK'),
        'mentioned_list' => env('NOTIFICATION_WECHAT_WORK_MENTIONED_LIST', ''),
        'mentioned_mobile_list' => env('NOTIFICATION_WECHAT_WORK_MENTIONED_MOBILE_LIST', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Server酱通知配置
    |--------------------------------------------------------------------------
    */
    'server_chan' => [
        'enabled' => env('NOTIFICATION_SERVER_CHAN_ENABLED', false),
        'sendkey' => env('NOTIFICATION_SERVER_CHAN_SENDKEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack通知配置
    |--------------------------------------------------------------------------
    */
    'slack' => [
        'enabled' => env('NOTIFICATION_SLACK_ENABLED', false),
        'webhook' => env('NOTIFICATION_SLACK_WEBHOOK'),
        'channel' => env('NOTIFICATION_SLACK_CHANNEL', '#general'),
        'username' => env('NOTIFICATION_SLACK_USERNAME', 'Dusk自动化平台'),
        'icon_emoji' => env('NOTIFICATION_SLACK_ICON_EMOJI', ':robot_face:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram通知配置
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'enabled' => env('NOTIFICATION_TELEGRAM_ENABLED', false),
        'bot_token' => env('NOTIFICATION_TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('NOTIFICATION_TELEGRAM_CHAT_ID'),
        'parse_mode' => env('NOTIFICATION_TELEGRAM_PARSE_MODE', 'HTML'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 重试配置
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'max_attempts' => env('NOTIFICATION_RETRY_MAX_ATTEMPTS', 3),
        'delay_seconds' => env('NOTIFICATION_RETRY_DELAY_SECONDS', 5),
        'backoff_multiplier' => env('NOTIFICATION_RETRY_BACKOFF_MULTIPLIER', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知规则配置
    |--------------------------------------------------------------------------
    */
    'rules' => [
        // 任务成功通知规则
        'task_success' => [
            'enabled' => env('NOTIFICATION_TASK_SUCCESS_ENABLED', true),
            'channels' => ['email'],
            'conditions' => [
                'execution_time_threshold' => 300, // 执行时间超过5分钟才通知
            ],
        ],

        // 任务失败通知规则
        'task_failure' => [
            'enabled' => env('NOTIFICATION_TASK_FAILURE_ENABLED', true),
            'channels' => ['email', 'dingtalk'],
            'conditions' => [
                'immediate' => true, // 立即通知
            ],
        ],

        // 系统告警通知规则
        'system_alert' => [
            'enabled' => env('NOTIFICATION_SYSTEM_ALERT_ENABLED', true),
            'channels' => ['email', 'dingtalk', 'wechat_work'],
            'conditions' => [
                'severity_threshold' => 'warning', // 警告级别以上才通知
            ],
        ],

        // 性能警告通知规则
        'performance_warning' => [
            'enabled' => env('NOTIFICATION_PERFORMANCE_WARNING_ENABLED', true),
            'channels' => ['email'],
            'conditions' => [
                'cooldown_minutes' => 30, // 30分钟内不重复通知
            ],
        ],

        // 浏览器实例异常通知规则
        'browser_instance_error' => [
            'enabled' => env('NOTIFICATION_BROWSER_ERROR_ENABLED', true),
            'channels' => ['dingtalk'],
            'conditions' => [
                'error_count_threshold' => 3, // 连续3次错误才通知
            ],
        ],

        // 每日报告通知规则
        'daily_report' => [
            'enabled' => env('NOTIFICATION_DAILY_REPORT_ENABLED', true),
            'channels' => ['email'],
            'schedule' => '0 9 * * *', // 每天上午9点发送
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知限流配置
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => env('NOTIFICATION_RATE_LIMITING_ENABLED', true),
        'max_per_minute' => env('NOTIFICATION_RATE_LIMIT_PER_MINUTE', 10),
        'max_per_hour' => env('NOTIFICATION_RATE_LIMIT_PER_HOUR', 100),
        'max_per_day' => env('NOTIFICATION_RATE_LIMIT_PER_DAY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知队列配置
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => env('NOTIFICATION_QUEUE_ENABLED', true),
        'connection' => env('NOTIFICATION_QUEUE_CONNECTION', 'redis'),
        'queue_name' => env('NOTIFICATION_QUEUE_NAME', 'notifications'),
        'delay_seconds' => env('NOTIFICATION_QUEUE_DELAY', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知日志配置
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('NOTIFICATION_LOGGING_ENABLED', true),
        'log_channel' => env('NOTIFICATION_LOG_CHANNEL', 'daily'),
        'log_level' => env('NOTIFICATION_LOG_LEVEL', 'info'),
        'log_failed_only' => env('NOTIFICATION_LOG_FAILED_ONLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知存储配置
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'enabled' => env('NOTIFICATION_STORAGE_ENABLED', true),
        'driver' => env('NOTIFICATION_STORAGE_DRIVER', 'database'),
        'retention_days' => env('NOTIFICATION_STORAGE_RETENTION_DAYS', 30),
        'cleanup_enabled' => env('NOTIFICATION_STORAGE_CLEANUP_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知模板配置
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'custom_templates_path' => storage_path('app/notification-templates'),
        'cache_templates' => env('NOTIFICATION_CACHE_TEMPLATES', true),
        'template_cache_ttl' => env('NOTIFICATION_TEMPLATE_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知测试配置
    |--------------------------------------------------------------------------
    */
    'testing' => [
        'enabled' => env('NOTIFICATION_TESTING_ENABLED', false),
        'test_channels' => ['email'],
        'test_recipient' => env('NOTIFICATION_TEST_RECIPIENT', 'test@example.com'),
        'test_interval_minutes' => env('NOTIFICATION_TEST_INTERVAL', 60),
    ],
];
