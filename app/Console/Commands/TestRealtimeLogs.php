<?php

namespace App\Console\Commands;

use App\Models\TaskExecution;
use App\Services\LogManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestRealtimeLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:realtime-logs {--count=10 : 生成日志数量} {--interval=1 : 间隔秒数}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试实时日志功能，生成模拟日志数据';

    /**
     * Execute the console command.
     */
    public function handle(LogManager $logManager)
    {
        $count = (int) $this->option('count');
        $interval = (int) $this->option('interval');

        $this->info("开始生成 {$count} 条模拟日志，间隔 {$interval} 秒...");

        // 临时禁用外键约束
        DB::statement('PRAGMA foreign_keys=OFF');

        // 创建测试执行记录
        $execution = new TaskExecution();
        $execution->id = 888888;
        $execution->task_id = 888888;
        $execution->status = 'running';
        $execution->start_time = now();
        $execution->exists = true;

        $levels = ['debug', 'info', 'warning', 'error'];
        $messages = [
            'debug' => [
                '开始处理数据',
                '连接数据库成功',
                '加载配置文件',
                '初始化组件完成'
            ],
            'info' => [
                '任务执行开始',
                '处理用户请求',
                '发送通知邮件',
                '保存数据成功',
                '任务执行完成'
            ],
            'warning' => [
                '网络连接不稳定',
                '磁盘空间不足',
                'API调用超时',
                '缓存过期'
            ],
            'error' => [
                '数据库连接失败',
                '文件读取错误',
                '权限验证失败',
                '系统异常'
            ]
        ];

        try {
            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            for ($i = 1; $i <= $count; $i++) {
                $level = $levels[array_rand($levels)];
                $message = $messages[$level][array_rand($messages[$level])];

                // 添加序号和时间戳
                $message = "[{$i}/{$count}] {$message}";

                // 随机添加上下文信息
                $context = null;
                if (rand(1, 3) === 1) {
                    $context = [
                        'step' => $i,
                        'timestamp' => now()->toISOString(),
                        'memory_usage' => memory_get_usage(true),
                        'random_data' => \Illuminate\Support\Str::random(10)
                    ];
                }

                // 记录日志
                $logManager->log($execution, $level, $message, $context);

                $progressBar->advance();

                // 间隔等待
                if ($interval > 0 && $i < $count) {
                    sleep($interval);
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info('✅ 模拟日志生成完成！');
            $this->info("📊 生成了 {$count} 条日志记录");
            $this->info("🔗 查看实时日志: http://localhost:8000/logs/realtime/{$execution->id}");

            // 显示统计信息
            $stats = $logManager->getLogStatistics(1);
            $this->table(
                ['级别', '数量'],
                collect($stats['by_level'])->map(function ($count, $level) {
                    return [ucfirst($level), $count];
                })->toArray()
            );

        } catch (\Exception $e) {
            $this->error("生成日志失败: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
