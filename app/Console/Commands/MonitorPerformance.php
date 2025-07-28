<?php

namespace App\Console\Commands;

use App\Services\PerformanceMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:performance
                            {--type=all : 监控类型 (system|browser|tasks|health|all)}
                            {--watch : 持续监控模式}
                            {--interval=30 : 监控间隔（秒）}
                            {--alert : 启用告警}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '性能监控命令 - 监控系统资源、浏览器实例和任务执行状态';

    protected PerformanceMonitor $monitor;

    public function __construct(PerformanceMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $watch = $this->option('watch');
        $interval = (int) $this->option('interval');
        $alert = $this->option('alert');

        $this->info('🚀 启动性能监控系统');
        $this->info("监控类型: {$type}");

        if ($watch) {
            $this->info("持续监控模式 - 间隔: {$interval}秒");
            $this->info('按 Ctrl+C 停止监控');
            $this->newLine();

            $this->watchMode($type, $interval, $alert);
        } else {
            $this->singleCheck($type, $alert);
        }

        return Command::SUCCESS;
    }

    /**
     * 单次检查模式
     */
    protected function singleCheck(string $type, bool $alert): void
    {
        switch ($type) {
            case 'system':
                $this->displaySystemMetrics();
                break;
            case 'browser':
                $this->displayBrowserMetrics();
                break;
            case 'tasks':
                $this->displayTaskMetrics();
                break;
            case 'health':
                $this->displayHealthCheck($alert);
                break;
            case 'all':
            default:
                $this->displayAllMetrics($alert);
                break;
        }
    }

    /**
     * 持续监控模式
     */
    protected function watchMode(string $type, int $interval, bool $alert): void
    {
        while (true) {
            // 清屏
            if (PHP_OS_FAMILY !== 'Windows') {
                system('clear');
            }

            $this->info('📊 性能监控面板 - ' . now()->format('Y-m-d H:i:s'));
            $this->line(str_repeat('=', 80));

            $this->singleCheck($type, $alert);

            $this->newLine();
            $this->info("下次更新: " . now()->addSeconds($interval)->format('H:i:s'));

            sleep($interval);
        }
    }

    /**
     * 显示系统指标
     */
    protected function displaySystemMetrics(): void
    {
        $this->info('💻 系统资源监控');
        $this->line(str_repeat('-', 50));

        $metrics = $this->monitor->collectSystemMetrics();

        // CPU使用率
        $cpuColor = $metrics['cpu'] > 80 ? 'red' : ($metrics['cpu'] > 60 ? 'yellow' : 'green');
        $this->line("CPU使用率: <fg={$cpuColor}>{$metrics['cpu']}%</>");

        // 内存使用
        $memColor = $metrics['memory']['usage_percentage'] > 85 ? 'red' :
                   ($metrics['memory']['usage_percentage'] > 70 ? 'yellow' : 'green');
        $this->line("内存使用: <fg={$memColor}>{$metrics['memory']['usage_percentage']}%</> ({$metrics['memory']['formatted']['used']}/{$metrics['memory']['formatted']['limit']})");

        // 磁盘使用
        $diskColor = $metrics['disk']['usage_percentage'] > 90 ? 'red' :
                    ($metrics['disk']['usage_percentage'] > 80 ? 'yellow' : 'green');
        $this->line("磁盘使用: <fg={$diskColor}>{$metrics['disk']['usage_percentage']}%</> ({$metrics['disk']['formatted']['used']}/{$metrics['disk']['formatted']['total']})");

        // 系统负载
        $this->line("系统负载: {$metrics['load_average']['1min']} / {$metrics['load_average']['5min']} / {$metrics['load_average']['15min']}");

        // 进程数
        $this->line("进程数量: {$metrics['processes']}");

        $this->newLine();
    }

    /**
     * 显示浏览器指标
     */
    protected function displayBrowserMetrics(): void
    {
        $this->info('🌐 浏览器实例监控');
        $this->line(str_repeat('-', 50));

        $stats = $this->monitor->monitorBrowserInstances();

        $this->line("总实例数: {$stats['total_instances']}");
        $this->line("活跃实例: <fg=green>{$stats['active_instances']}</>");
        $this->line("空闲实例: <fg=yellow>{$stats['idle_instances']}</>");
        $this->line("异常实例: <fg=red>{$stats['unhealthy_instances']}</>");

        if (!empty($stats['instances'])) {
            $this->newLine();
            $this->table(
                ['ID', '端口', '状态', '健康分数', '响应时间', '内存使用', '使用次数'],
                array_map(function ($instance) {
                    return [
                        $instance['id'],
                        $instance['port'],
                        $instance['status'],
                        $instance['health_score'],
                        isset($instance['response_time']) ? $instance['response_time'] . 'ms' : 'N/A',
                        isset($instance['memory_usage']) ? $instance['memory_usage'] . 'MB' : 'N/A',
                        $instance['usage_count']
                    ];
                }, $stats['instances'])
            );
        }

        $this->newLine();
    }

    /**
     * 显示任务指标
     */
    protected function displayTaskMetrics(): void
    {
        $this->info('📋 任务执行统计 (24小时)');
        $this->line(str_repeat('-', 50));

        $stats = $this->monitor->analyzeTaskExecutionStats();

        $this->line("总执行次数: {$stats['total_executions']}");
        $this->line("成功执行: <fg=green>{$stats['successful_executions']}</>");
        $this->line("失败执行: <fg=red>{$stats['failed_executions']}</>");

        $successColor = $stats['success_rate'] > 90 ? 'green' : ($stats['success_rate'] > 70 ? 'yellow' : 'red');
        $this->line("成功率: <fg={$successColor}>{$stats['success_rate']}%</>");

        $this->line("平均执行时间: {$stats['average_duration']}秒");

        if (!empty($stats['top_failing_tasks'])) {
            $this->newLine();
            $this->warn('失败率最高的任务:');
            $this->table(
                ['任务名称', '总执行', '失败次数', '失败率'],
                array_map(function ($task) {
                    return [
                        $task->name,
                        $task->total_executions,
                        $task->failed_executions,
                        $task->failure_rate . '%'
                    ];
                }, $stats['top_failing_tasks'])
            );
        }

        $this->newLine();
    }

    /**
     * 显示健康检查
     */
    protected function displayHealthCheck(bool $alert): void
    {
        $this->info('🏥 系统健康检查');
        $this->line(str_repeat('-', 50));

        $health = $this->monitor->performHealthCheck();

        // 总体状态
        $statusColor = match($health['overall_status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white'
        };

        $this->line("总体状态: <fg={$statusColor}>" . strtoupper($health['overall_status']) . "</>");
        $this->line("健康分数: {$health['score']}/100");

        // 各项检查
        $this->newLine();
        $this->info('详细检查结果:');

        foreach ($health['checks'] as $checkName => $check) {
            $checkColor = match($check['status']) {
                'healthy' => 'green',
                'warning' => 'yellow',
                'critical' => 'red',
                default => 'white'
            };

            $this->line("  " . ucfirst(str_replace('_', ' ', $checkName)) . ": <fg={$checkColor}>{$check['status']}</> ({$check['score']}/100)");
        }

        // 告警信息
        if (!empty($health['alerts'])) {
            $this->newLine();
            $this->error('🚨 告警信息:');
            foreach ($health['alerts'] as $alert) {
                $this->line("  • {$alert}");
            }

            if ($alert) {
                $this->sendAlert($health['alerts']);
            }
        }

        // 建议
        if (!empty($health['recommendations'])) {
            $this->newLine();
            $this->warn('💡 优化建议:');
            foreach ($health['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }

        $this->newLine();
    }

    /**
     * 显示所有指标
     */
    protected function displayAllMetrics(bool $alert): void
    {
        $this->displaySystemMetrics();
        $this->displayBrowserMetrics();
        $this->displayTaskMetrics();
        $this->displayHealthCheck($alert);
    }

    /**
     * 发送告警
     */
    protected function sendAlert(array $alerts): void
    {
        // 这里可以集成邮件、钉钉等通知服务
        $this->warn('告警已记录到日志');

        foreach ($alerts as $alert) {
            Log::warning('性能监控告警', [
                'alert' => $alert,
                'timestamp' => now()
            ]);
        }
    }
}
