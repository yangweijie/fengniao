<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Exception;

class NotificationService
{
    protected array $channels = [
        'email' => 'sendEmailNotification',
        'dingtalk' => 'sendDingTalkNotification',
        'wechat_work' => 'sendWeChatWorkNotification',
        'server_chan' => 'sendServerChanNotification',
        'slack' => 'sendSlackNotification',
        'telegram' => 'sendTelegramNotification'
    ];

    protected array $templates = [];
    protected array $retryConfig = [
        'max_attempts' => 3,
        'delay_seconds' => 5,
        'backoff_multiplier' => 2
    ];

    public function __construct()
    {
        $this->loadTemplates();
    }

    /**
     * 发送通知到多个渠道
     */
    public function sendNotification(array $channels, string $template, array $data = [], array $options = []): array
    {
        $results = [];
        
        foreach ($channels as $channel => $config) {
            if (is_numeric($channel)) {
                // 如果是数字索引，说明只传了渠道名
                $channel = $config;
                $config = [];
            }

            try {
                $result = $this->sendToChannel($channel, $template, $data, array_merge($config, $options));
                $results[$channel] = $result;
            } catch (Exception $e) {
                $results[$channel] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'timestamp' => now()
                ];
                
                Log::error("通知发送失败", [
                    'channel' => $channel,
                    'template' => $template,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * 发送到指定渠道
     */
    public function sendToChannel(string $channel, string $template, array $data = [], array $options = []): array
    {
        if (!isset($this->channels[$channel])) {
            throw new Exception("不支持的通知渠道: {$channel}");
        }

        $method = $this->channels[$channel];
        $templateData = $this->getTemplate($template, $data);
        
        // 实现重试机制
        return $this->executeWithRetry(function () use ($method, $templateData, $options) {
            return $this->$method($templateData, $options);
        }, $options['retry'] ?? $this->retryConfig);
    }

    /**
     * 发送邮件通知
     */
    protected function sendEmailNotification(array $templateData, array $options = []): array
    {
        $to = $options['to'] ?? config('notifications.email.default_to');
        $subject = $templateData['subject'] ?? '系统通知';
        $content = $templateData['content'];

        if (!$to) {
            throw new Exception('邮件通知缺少收件人地址');
        }

        try {
            Mail::raw($content, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            return [
                'success' => true,
                'channel' => 'email',
                'recipient' => $to,
                'timestamp' => now()
            ];
        } catch (Exception $e) {
            throw new Exception("邮件发送失败: " . $e->getMessage());
        }
    }

    /**
     * 发送钉钉通知
     */
    protected function sendDingTalkNotification(array $templateData, array $options = []): array
    {
        $webhook = $options['webhook'] ?? config('notifications.dingtalk.webhook');
        $secret = $options['secret'] ?? config('notifications.dingtalk.secret');

        if (!$webhook) {
            throw new Exception('钉钉通知缺少Webhook地址');
        }

        $timestamp = round(microtime(true) * 1000);
        $sign = '';
        
        if ($secret) {
            $stringToSign = $timestamp . "\n" . $secret;
            $sign = base64_encode(hash_hmac('sha256', $stringToSign, $secret, true));
        }

        $url = $webhook;
        if ($sign) {
            $url .= "&timestamp={$timestamp}&sign=" . urlencode($sign);
        }

        $payload = [
            'msgtype' => 'text',
            'text' => [
                'content' => $templateData['content']
            ]
        ];

        // 支持@所有人或@特定用户
        if (isset($options['at_all']) && $options['at_all']) {
            $payload['at'] = ['isAtAll' => true];
        } elseif (isset($options['at_mobiles'])) {
            $payload['at'] = ['atMobiles' => $options['at_mobiles']];
        }

        $response = Http::post($url, $payload);

        if (!$response->successful()) {
            throw new Exception("钉钉通知发送失败: HTTP {$response->status()}");
        }

        $result = $response->json();
        if ($result['errcode'] !== 0) {
            throw new Exception("钉钉通知发送失败: {$result['errmsg']}");
        }

        return [
            'success' => true,
            'channel' => 'dingtalk',
            'response' => $result,
            'timestamp' => now()
        ];
    }

    /**
     * 发送企业微信通知
     */
    protected function sendWeChatWorkNotification(array $templateData, array $options = []): array
    {
        $webhook = $options['webhook'] ?? config('notifications.wechat_work.webhook');

        if (!$webhook) {
            throw new Exception('企业微信通知缺少Webhook地址');
        }

        $payload = [
            'msgtype' => 'text',
            'text' => [
                'content' => $templateData['content']
            ]
        ];

        // 支持@所有人或@特定用户
        if (isset($options['mentioned_list'])) {
            $payload['text']['mentioned_list'] = $options['mentioned_list'];
        }

        if (isset($options['mentioned_mobile_list'])) {
            $payload['text']['mentioned_mobile_list'] = $options['mentioned_mobile_list'];
        }

        $response = Http::post($webhook, $payload);

        if (!$response->successful()) {
            throw new Exception("企业微信通知发送失败: HTTP {$response->status()}");
        }

        $result = $response->json();
        if ($result['errcode'] !== 0) {
            throw new Exception("企业微信通知发送失败: {$result['errmsg']}");
        }

        return [
            'success' => true,
            'channel' => 'wechat_work',
            'response' => $result,
            'timestamp' => now()
        ];
    }

    /**
     * 发送Server酱通知
     */
    protected function sendServerChanNotification(array $templateData, array $options = []): array
    {
        $sendkey = $options['sendkey'] ?? config('notifications.server_chan.sendkey');

        if (!$sendkey) {
            throw new Exception('Server酱通知缺少SendKey');
        }

        $url = "https://sctapi.ftqq.com/{$sendkey}.send";
        
        $payload = [
            'title' => $templateData['title'] ?? $templateData['subject'] ?? '系统通知',
            'desp' => $templateData['content']
        ];

        $response = Http::post($url, $payload);

        if (!$response->successful()) {
            throw new Exception("Server酱通知发送失败: HTTP {$response->status()}");
        }

        $result = $response->json();
        if ($result['code'] !== 0) {
            throw new Exception("Server酱通知发送失败: {$result['message']}");
        }

        return [
            'success' => true,
            'channel' => 'server_chan',
            'response' => $result,
            'timestamp' => now()
        ];
    }

    /**
     * 发送Slack通知
     */
    protected function sendSlackNotification(array $templateData, array $options = []): array
    {
        $webhook = $options['webhook'] ?? config('notifications.slack.webhook');

        if (!$webhook) {
            throw new Exception('Slack通知缺少Webhook地址');
        }

        $payload = [
            'text' => $templateData['content'],
            'username' => $options['username'] ?? 'Dusk自动化平台',
            'icon_emoji' => $options['icon_emoji'] ?? ':robot_face:'
        ];

        if (isset($options['channel'])) {
            $payload['channel'] = $options['channel'];
        }

        $response = Http::post($webhook, $payload);

        if (!$response->successful()) {
            throw new Exception("Slack通知发送失败: HTTP {$response->status()}");
        }

        return [
            'success' => true,
            'channel' => 'slack',
            'timestamp' => now()
        ];
    }

    /**
     * 发送Telegram通知
     */
    protected function sendTelegramNotification(array $templateData, array $options = []): array
    {
        $botToken = $options['bot_token'] ?? config('notifications.telegram.bot_token');
        $chatId = $options['chat_id'] ?? config('notifications.telegram.chat_id');

        if (!$botToken || !$chatId) {
            throw new Exception('Telegram通知缺少Bot Token或Chat ID');
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        $payload = [
            'chat_id' => $chatId,
            'text' => $templateData['content'],
            'parse_mode' => $options['parse_mode'] ?? 'HTML'
        ];

        $response = Http::post($url, $payload);

        if (!$response->successful()) {
            throw new Exception("Telegram通知发送失败: HTTP {$response->status()}");
        }

        $result = $response->json();
        if (!$result['ok']) {
            throw new Exception("Telegram通知发送失败: {$result['description']}");
        }

        return [
            'success' => true,
            'channel' => 'telegram',
            'response' => $result,
            'timestamp' => now()
        ];
    }

    /**
     * 获取模板数据
     */
    protected function getTemplate(string $template, array $data = []): array
    {
        if (!isset($this->templates[$template])) {
            // 如果没有找到模板，使用默认格式
            return [
                'subject' => $data['subject'] ?? '系统通知',
                'title' => $data['title'] ?? '系统通知',
                'content' => $data['message'] ?? $data['content'] ?? '无内容'
            ];
        }

        $templateData = $this->templates[$template];
        
        // 替换模板变量
        foreach ($templateData as $key => $value) {
            if (is_string($value)) {
                $templateData[$key] = $this->replaceVariables($value, $data);
            }
        }

        return $templateData;
    }

    /**
     * 替换模板变量
     */
    protected function replaceVariables(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($data) {
            $key = $matches[1];
            return $data[$key] ?? $matches[0];
        }, $template);
    }

    /**
     * 执行重试机制
     */
    protected function executeWithRetry(callable $callback, array $retryConfig): array
    {
        $maxAttempts = $retryConfig['max_attempts'] ?? $this->retryConfig['max_attempts'];
        $delay = $retryConfig['delay_seconds'] ?? $this->retryConfig['delay_seconds'];
        $backoffMultiplier = $retryConfig['backoff_multiplier'] ?? $this->retryConfig['backoff_multiplier'];

        $lastException = null;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $result = $callback();
                $result['attempt'] = $attempt;
                return $result;
            } catch (Exception $e) {
                $lastException = $e;
                
                if ($attempt < $maxAttempts) {
                    sleep($delay);
                    $delay *= $backoffMultiplier;
                }
            }
        }

        throw new Exception("通知发送失败，已重试{$maxAttempts}次: " . $lastException->getMessage());
    }

    /**
     * 加载通知模板
     */
    protected function loadTemplates(): void
    {
        $this->templates = [
            'task_success' => [
                'subject' => '任务执行成功',
                'title' => '✅ 任务执行成功',
                'content' => "任务「{{task_name}}」执行成功\n\n执行时间: {{execution_time}}\n执行ID: {{execution_id}}\n\n详情请查看管理后台。"
            ],
            'task_failure' => [
                'subject' => '任务执行失败',
                'title' => '❌ 任务执行失败',
                'content' => "任务「{{task_name}}」执行失败\n\n错误信息: {{error_message}}\n执行时间: {{execution_time}}\n执行ID: {{execution_id}}\n\n请及时处理。"
            ],
            'system_alert' => [
                'subject' => '系统告警',
                'title' => '🚨 系统告警',
                'content' => "系统告警: {{alert_type}}\n\n详细信息: {{alert_message}}\n告警时间: {{alert_time}}\n\n请及时检查系统状态。"
            ],
            'performance_warning' => [
                'subject' => '性能警告',
                'title' => '⚠️ 性能警告',
                'content' => "性能指标异常: {{metric_name}}\n\n当前值: {{current_value}}\n阈值: {{threshold}}\n检测时间: {{check_time}}\n\n建议及时优化。"
            ],
            'browser_instance_error' => [
                'subject' => '浏览器实例异常',
                'title' => '🌐 浏览器实例异常',
                'content' => "浏览器实例异常\n\n实例ID: {{instance_id}}\n端口: {{port}}\n错误信息: {{error_message}}\n发生时间: {{error_time}}\n\n请检查浏览器状态。"
            ],
            'daily_report' => [
                'subject' => '每日执行报告',
                'title' => '📊 每日执行报告',
                'content' => "今日任务执行统计\n\n总执行次数: {{total_executions}}\n成功次数: {{successful_executions}}\n失败次数: {{failed_executions}}\n成功率: {{success_rate}}%\n\n详细报告请查看管理后台。"
            ]
        ];
    }

    /**
     * 添加自定义模板
     */
    public function addTemplate(string $name, array $template): void
    {
        $this->templates[$name] = $template;
    }

    /**
     * 获取所有模板
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * 获取支持的通知渠道
     */
    public function getSupportedChannels(): array
    {
        return array_keys($this->channels);
    }

    /**
     * 测试通知渠道
     */
    public function testChannel(string $channel, array $options = []): array
    {
        $testData = [
            'task_name' => '测试任务',
            'execution_time' => now()->toDateTimeString(),
            'execution_id' => 'test-' . uniqid()
        ];

        return $this->sendToChannel($channel, 'task_success', $testData, $options);
    }

    /**
     * 批量发送通知
     */
    public function sendBulkNotifications(array $notifications): array
    {
        $results = [];
        
        foreach ($notifications as $index => $notification) {
            $channels = $notification['channels'] ?? [];
            $template = $notification['template'] ?? 'task_success';
            $data = $notification['data'] ?? [];
            $options = $notification['options'] ?? [];

            try {
                $result = $this->sendNotification($channels, $template, $data, $options);
                $results[$index] = [
                    'success' => true,
                    'results' => $result
                ];
            } catch (Exception $e) {
                $results[$index] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * 获取通知发送统计
     */
    public function getNotificationStats(int $days = 7): array
    {
        $cacheKey = "notification_stats_{$days}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($days) {
            // 这里应该从数据库或日志中统计实际数据
            // 简化实现，返回模拟数据
            return [
                'period_days' => $days,
                'total_sent' => rand(100, 1000),
                'successful' => rand(90, 95),
                'failed' => rand(5, 10),
                'by_channel' => [
                    'email' => rand(20, 50),
                    'dingtalk' => rand(30, 60),
                    'wechat_work' => rand(10, 30),
                    'server_chan' => rand(5, 20)
                ],
                'by_template' => [
                    'task_success' => rand(40, 60),
                    'task_failure' => rand(10, 20),
                    'system_alert' => rand(5, 15),
                    'daily_report' => $days
                ]
            ];
        });
    }
}
