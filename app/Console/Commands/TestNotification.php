<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class TestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test
                            {channel? : 通知渠道名称}
                            {--template=task_success : 使用的模板}
                            {--list : 列出所有支持的渠道}
                            {--templates : 列出所有可用模板}
                            {--stats : 显示通知统计信息}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试通知服务 - 发送测试通知或查看通知信息';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('list')) {
            return $this->listChannels();
        }

        if ($this->option('templates')) {
            return $this->listTemplates();
        }

        if ($this->option('stats')) {
            return $this->showStats();
        }

        $channel = $this->argument('channel');
        if (!$channel) {
            $channel = $this->choice(
                '请选择要测试的通知渠道',
                $this->notificationService->getSupportedChannels()
            );
        }

        return $this->testChannel($channel);
    }

    /**
     * 列出所有支持的通知渠道
     */
    protected function listChannels(): int
    {
        $this->info('📢 支持的通知渠道:');
        $this->newLine();

        $channels = $this->notificationService->getSupportedChannels();

        foreach ($channels as $channel) {
            $enabled = config("notifications.{$channel}.enabled", false);
            $status = $enabled ? '<fg=green>已启用</>' : '<fg=red>未启用</>';

            $this->line("• {$channel} - {$status}");
        }

        $this->newLine();
        $this->info('使用 php artisan notification:test <channel> 测试特定渠道');

        return Command::SUCCESS;
    }

    /**
     * 列出所有可用模板
     */
    protected function listTemplates(): int
    {
        $this->info('📝 可用的通知模板:');
        $this->newLine();

        $templates = $this->notificationService->getTemplates();

        $this->table(
            ['模板名称', '标题', '描述'],
            collect($templates)->map(function ($template, $name) {
                return [
                    $name,
                    $template['title'] ?? $template['subject'] ?? 'N/A',
                    mb_substr($template['content'], 0, 50) . '...'
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }

    /**
     * 显示通知统计信息
     */
    protected function showStats(): int
    {
        $this->info('📊 通知统计信息 (最近7天):');
        $this->newLine();

        $stats = $this->notificationService->getNotificationStats(7);

        // 总体统计
        $this->table(
            ['指标', '数量'],
            [
                ['总发送数', $stats['total_sent']],
                ['成功数', $stats['successful']],
                ['失败数', $stats['failed']],
                ['成功率', round(($stats['successful'] / $stats['total_sent']) * 100, 2) . '%']
            ]
        );

        // 按渠道统计
        $this->newLine();
        $this->info('按渠道统计:');
        $this->table(
            ['渠道', '发送数量'],
            collect($stats['by_channel'])->map(function ($count, $channel) {
                return [ucfirst($channel), $count];
            })->toArray()
        );

        // 按模板统计
        $this->newLine();
        $this->info('按模板统计:');
        $this->table(
            ['模板', '使用次数'],
            collect($stats['by_template'])->map(function ($count, $template) {
                return [ucfirst(str_replace('_', ' ', $template)), $count];
            })->toArray()
        );

        return Command::SUCCESS;
    }

    /**
     * 测试指定渠道
     */
    protected function testChannel(string $channel): int
    {
        $this->info("🧪 测试通知渠道: {$channel}");

        // 检查渠道是否支持
        if (!in_array($channel, $this->notificationService->getSupportedChannels())) {
            $this->error("不支持的通知渠道: {$channel}");
            return Command::FAILURE;
        }

        // 检查渠道是否启用
        $enabled = config("notifications.{$channel}.enabled", false);
        if (!$enabled) {
            $this->warn("通知渠道 {$channel} 未启用，请检查配置文件");
            if (!$this->confirm('是否继续测试？')) {
                return Command::SUCCESS;
            }
        }

        // 获取渠道特定的配置选项
        $options = $this->getChannelOptions($channel);

        try {
            $this->info('正在发送测试通知...');

            $result = $this->notificationService->testChannel($channel, $options);

            if ($result['success']) {
                $this->info('✅ 测试通知发送成功！');
                $this->displayResult($result);
            } else {
                $this->error('❌ 测试通知发送失败');
                if (isset($result['error'])) {
                    $this->line("错误信息: {$result['error']}");
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ 测试失败: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 获取渠道特定的配置选项
     */
    protected function getChannelOptions(string $channel): array
    {
        $options = [];

        switch ($channel) {
            case 'email':
                if ($this->confirm('是否指定收件人邮箱？')) {
                    $options['to'] = $this->ask('请输入收件人邮箱地址');
                }
                break;

            case 'dingtalk':
                if ($this->confirm('是否@所有人？')) {
                    $options['at_all'] = true;
                } elseif ($this->confirm('是否@特定用户？')) {
                    $mobiles = $this->ask('请输入手机号码（多个用逗号分隔）');
                    $options['at_mobiles'] = explode(',', $mobiles);
                }
                break;

            case 'wechat_work':
                if ($this->confirm('是否@特定用户？')) {
                    $users = $this->ask('请输入用户ID（多个用逗号分隔）');
                    $options['mentioned_list'] = explode(',', $users);
                }
                break;

            case 'slack':
                if ($this->confirm('是否指定频道？')) {
                    $options['channel'] = $this->ask('请输入频道名称（如 #general）');
                }
                break;
        }

        return $options;
    }

    /**
     * 显示发送结果
     */
    protected function displayResult(array $result): void
    {
        $this->newLine();
        $this->info('📋 发送结果详情:');

        $details = [
            ['渠道', $result['channel']],
            ['状态', $result['success'] ? '成功' : '失败'],
            ['发送时间', $result['timestamp']->format('Y-m-d H:i:s')],
        ];

        if (isset($result['attempt'])) {
            $details[] = ['重试次数', $result['attempt']];
        }

        if (isset($result['recipient'])) {
            $details[] = ['收件人', $result['recipient']];
        }

        $this->table(['项目', '值'], $details);

        if (isset($result['response'])) {
            $this->newLine();
            $this->info('📤 服务器响应:');
            $this->line(json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
