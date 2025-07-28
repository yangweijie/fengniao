<?php

namespace App\Services;

use App\Models\BrowserInstance;
use App\Models\Task;
use App\Models\TaskExecution;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PerformanceMonitor
{
    protected array $metrics = [];
    protected array $thresholds = [
        'cpu_usage' => 80,
        'memory_usage' => 85,
        'disk_usage' => 90,
        'browser_response_time' => 30,
        'task_failure_rate' => 20
    ];

    /**
     * 收集系统性能指标
     */
    public function collectSystemMetrics(): array
    {
        $metrics = [
            'timestamp' => now(),
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'load_average' => $this->getLoadAverage(),
            'network' => $this->getNetworkStats(),
            'processes' => $this->getProcessCount()
        ];

        // 缓存指标数据
        $this->cacheMetrics('system', $metrics);
        
        return $metrics;
    }

    /**
     * 监控浏览器实例状态
     */
    public function monitorBrowserInstances(): array
    {
        $instances = BrowserInstance::all();
        $stats = [
            'total_instances' => $instances->count(),
            'active_instances' => 0,
            'idle_instances' => 0,
            'unhealthy_instances' => 0,
            'instances' => []
        ];

        foreach ($instances as $instance) {
            $instanceMetrics = $this->collectBrowserInstanceMetrics($instance);
            $stats['instances'][] = $instanceMetrics;

            switch ($instanceMetrics['status']) {
                case 'active':
                    $stats['active_instances']++;
                    break;
                case 'idle':
                    $stats['idle_instances']++;
                    break;
                case 'unhealthy':
                    $stats['unhealthy_instances']++;
                    break;
            }
        }

        $this->cacheMetrics('browser_instances', $stats);
        
        return $stats;
    }

    /**
     * 收集单个浏览器实例指标
     */
    protected function collectBrowserInstanceMetrics(BrowserInstance $instance): array
    {
        $metrics = [
            'id' => $instance->id,
            'port' => $instance->port,
            'status' => $instance->status,
            'created_at' => $instance->created_at,
            'last_used_at' => $instance->last_used_at,
            'usage_count' => $instance->usage_count,
            'health_score' => 100
        ];

        // 检查端口是否有效
        if (!$instance->port) {
            $metrics['status'] = 'unhealthy';
            $metrics['health_score'] = 0;
            $metrics['issues'] = ['端口未配置'];
            return $metrics;
        }

        // 检查进程是否存在
        $processExists = $this->checkBrowserProcess($instance->port);
        if (!$processExists) {
            $metrics['status'] = 'unhealthy';
            $metrics['health_score'] = 0;
            $metrics['issues'] = ['进程不存在'];
        }

        // 检查响应时间
        $responseTime = $this->checkBrowserResponseTime($instance->port);
        $metrics['response_time'] = $responseTime;

        if ($responseTime > $this->thresholds['browser_response_time']) {
            $metrics['health_score'] -= 30;
            $metrics['issues'][] = '响应时间过长';
        }

        // 检查内存使用
        $memoryUsage = $this->getBrowserMemoryUsage($instance->port);
        $metrics['memory_usage'] = $memoryUsage;
        
        if ($memoryUsage > 500) { // MB
            $metrics['health_score'] -= 20;
            $metrics['issues'][] = '内存使用过高';
        }

        return $metrics;
    }

    /**
     * 分析任务执行统计
     */
    public function analyzeTaskExecutionStats(int $hours = 24): array
    {
        $startTime = now()->subHours($hours);
        
        $stats = [
            'period_hours' => $hours,
            'total_executions' => 0,
            'successful_executions' => 0,
            'failed_executions' => 0,
            'average_duration' => 0,
            'success_rate' => 0,
            'failure_rate' => 0,
            'top_failing_tasks' => [],
            'performance_trends' => []
        ];

        // 基础统计
        $executions = TaskExecution::where('created_at', '>=', $startTime)->get();
        $stats['total_executions'] = $executions->count();
        
        if ($stats['total_executions'] > 0) {
            $successful = $executions->where('status', 'completed')->count();
            $failed = $executions->where('status', 'failed')->count();
            
            $stats['successful_executions'] = $successful;
            $stats['failed_executions'] = $failed;
            $stats['success_rate'] = round(($successful / $stats['total_executions']) * 100, 2);
            $stats['failure_rate'] = round(($failed / $stats['total_executions']) * 100, 2);
            
            // 平均执行时间
            $completedExecutions = $executions->whereNotNull('end_time');
            if ($completedExecutions->count() > 0) {
                $totalDuration = $completedExecutions->sum(function ($execution) {
                    return $execution->end_time->diffInSeconds($execution->start_time);
                });
                $stats['average_duration'] = round($totalDuration / $completedExecutions->count(), 2);
            }
        }

        // 失败率最高的任务
        $stats['top_failing_tasks'] = $this->getTopFailingTasks($startTime);
        
        // 性能趋势
        $stats['performance_trends'] = $this->getPerformanceTrends($startTime);

        $this->cacheMetrics('task_execution_stats', $stats);
        
        return $stats;
    }

    /**
     * 系统健康检查
     */
    public function performHealthCheck(): array
    {
        $health = [
            'overall_status' => 'healthy',
            'score' => 100,
            'checks' => [],
            'alerts' => [],
            'recommendations' => []
        ];

        // 系统资源检查
        $systemMetrics = $this->collectSystemMetrics();
        $health['checks']['system_resources'] = $this->checkSystemResources($systemMetrics);
        
        // 浏览器实例检查
        $browserStats = $this->monitorBrowserInstances();
        $health['checks']['browser_instances'] = $this->checkBrowserInstances($browserStats);
        
        // 任务执行检查
        $taskStats = $this->analyzeTaskExecutionStats();
        $health['checks']['task_execution'] = $this->checkTaskExecution($taskStats);
        
        // 数据库连接检查
        $health['checks']['database'] = $this->checkDatabaseConnection();
        
        // 队列状态检查
        $health['checks']['queue'] = $this->checkQueueStatus();

        // 计算总体健康分数
        $totalScore = 0;
        $checkCount = 0;
        
        foreach ($health['checks'] as $check) {
            $totalScore += $check['score'];
            $checkCount++;
            
            if ($check['status'] !== 'healthy') {
                $health['alerts'] = array_merge($health['alerts'], $check['alerts'] ?? []);
            }
            
            if (!empty($check['recommendations'])) {
                $health['recommendations'] = array_merge($health['recommendations'], $check['recommendations']);
            }
        }
        
        $health['score'] = $checkCount > 0 ? round($totalScore / $checkCount) : 0;
        
        if ($health['score'] < 70) {
            $health['overall_status'] = 'critical';
        } elseif ($health['score'] < 85) {
            $health['overall_status'] = 'warning';
        }

        $this->cacheMetrics('health_check', $health);
        
        return $health;
    }

    /**
     * 获取CPU使用率
     */
    protected function getCpuUsage(): float
    {
        if (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux') {
            $load = sys_getloadavg();
            return $load ? round($load[0] * 100 / $this->getCpuCores(), 2) : 0;
        }
        
        return 0; // Windows不支持
    }

    /**
     * 获取内存使用情况
     */
    protected function getMemoryUsage(): array
    {
        $memoryLimit = $this->parseMemorySize(ini_get('memory_limit'));
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        return [
            'used' => $memoryUsage,
            'peak' => $memoryPeak,
            'limit' => $memoryLimit,
            'usage_percentage' => $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0,
            'formatted' => [
                'used' => $this->formatBytes($memoryUsage),
                'peak' => $this->formatBytes($memoryPeak),
                'limit' => $this->formatBytes($memoryLimit)
            ]
        ];
    }

    /**
     * 获取磁盘使用情况
     */
    protected function getDiskUsage(): array
    {
        $path = storage_path();
        $totalBytes = disk_total_space($path);
        $freeBytes = disk_free_space($path);
        $usedBytes = $totalBytes - $freeBytes;
        
        return [
            'total' => $totalBytes,
            'used' => $usedBytes,
            'free' => $freeBytes,
            'usage_percentage' => $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 2) : 0,
            'formatted' => [
                'total' => $this->formatBytes($totalBytes),
                'used' => $this->formatBytes($usedBytes),
                'free' => $this->formatBytes($freeBytes)
            ]
        ];
    }

    /**
     * 获取系统负载
     */
    protected function getLoadAverage(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0] ?? 0,
                '5min' => $load[1] ?? 0,
                '15min' => $load[2] ?? 0
            ];
        }
        
        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }

    /**
     * 获取网络统计
     */
    protected function getNetworkStats(): array
    {
        // 简化的网络统计
        return [
            'connections' => $this->getActiveConnections(),
            'status' => 'active'
        ];
    }

    /**
     * 获取进程数量
     */
    protected function getProcessCount(): int
    {
        if (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux') {
            $output = shell_exec('ps aux | wc -l');
            return (int) trim($output);
        }
        
        return 0;
    }

    /**
     * 检查浏览器进程
     */
    protected function checkBrowserProcess(int $port): bool
    {
        if (PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux') {
            $output = shell_exec("lsof -i :{$port} 2>/dev/null");
            return !empty(trim($output));
        }
        
        return true; // Windows假设存在
    }

    /**
     * 检查浏览器响应时间
     */
    protected function checkBrowserResponseTime(int $port): float
    {
        $start = microtime(true);
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            
            $result = @file_get_contents("http://localhost:{$port}/json/version", false, $context);
            $responseTime = (microtime(true) - $start) * 1000; // 转换为毫秒
            
            return $result !== false ? round($responseTime, 2) : 999999;
        } catch (\Exception) {
            return 999999; // 表示无响应
        }
    }

    /**
     * 获取浏览器内存使用
     */
    protected function getBrowserMemoryUsage(int $port): float
    {
        // 简化实现，实际应该通过Chrome DevTools Protocol获取
        // $port 参数保留用于未来扩展
        return rand(100, 800); // MB
    }

    /**
     * 获取失败率最高的任务
     */
    protected function getTopFailingTasks(\DateTime $startTime): array
    {
        return DB::table('task_executions')
            ->join('tasks', 'task_executions.task_id', '=', 'tasks.id')
            ->where('task_executions.created_at', '>=', $startTime)
            ->select('tasks.name', 'tasks.id')
            ->selectRaw('COUNT(*) as total_executions')
            ->selectRaw('SUM(CASE WHEN task_executions.status = "failed" THEN 1 ELSE 0 END) as failed_executions')
            ->selectRaw('ROUND((SUM(CASE WHEN task_executions.status = "failed" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as failure_rate')
            ->groupBy('tasks.id', 'tasks.name')
            ->having('failed_executions', '>', 0)
            ->orderBy('failure_rate', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * 获取性能趋势
     */
    protected function getPerformanceTrends(\DateTime $startTime): array
    {
        $hours = [];
        $current = clone $startTime;

        while ($current <= now()) {
            $nextHour = clone $current;
            $nextHour->add(new \DateInterval('PT1H'));

            $executions = TaskExecution::whereBetween('created_at', [$current, $nextHour])->get();

            $hours[] = [
                'hour' => $current->format('Y-m-d H:00'),
                'total' => $executions->count(),
                'successful' => $executions->where('status', 'completed')->count(),
                'failed' => $executions->where('status', 'failed')->count(),
                'average_duration' => $executions->whereNotNull('end_time')->avg(function ($execution) {
                    return $execution->end_time->diffInSeconds($execution->start_time);
                }) ?: 0
            ];

            $current->add(new \DateInterval('PT1H'));
        }

        return $hours;
    }

    /**
     * 检查系统资源
     */
    protected function checkSystemResources(array $metrics): array
    {
        $check = [
            'status' => 'healthy',
            'score' => 100,
            'alerts' => [],
            'recommendations' => []
        ];

        // CPU检查
        if ($metrics['cpu'] > $this->thresholds['cpu_usage']) {
            $check['status'] = 'warning';
            $check['score'] -= 20;
            $check['alerts'][] = "CPU使用率过高: {$metrics['cpu']}%";
            $check['recommendations'][] = '考虑减少并发任务数量或升级硬件';
        }

        // 内存检查
        if ($metrics['memory']['usage_percentage'] > $this->thresholds['memory_usage']) {
            $check['status'] = 'warning';
            $check['score'] -= 25;
            $check['alerts'][] = "内存使用率过高: {$metrics['memory']['usage_percentage']}%";
            $check['recommendations'][] = '考虑增加内存或优化内存使用';
        }

        // 磁盘检查
        if ($metrics['disk']['usage_percentage'] > $this->thresholds['disk_usage']) {
            $check['status'] = 'critical';
            $check['score'] -= 30;
            $check['alerts'][] = "磁盘使用率过高: {$metrics['disk']['usage_percentage']}%";
            $check['recommendations'][] = '清理磁盘空间或扩展存储';
        }

        return $check;
    }

    /**
     * 检查浏览器实例
     */
    protected function checkBrowserInstances(array $stats): array
    {
        $check = [
            'status' => 'healthy',
            'score' => 100,
            'alerts' => [],
            'recommendations' => []
        ];

        if ($stats['unhealthy_instances'] > 0) {
            $check['status'] = 'warning';
            $check['score'] -= 15 * $stats['unhealthy_instances'];
            $check['alerts'][] = "发现 {$stats['unhealthy_instances']} 个不健康的浏览器实例";
            $check['recommendations'][] = '重启不健康的浏览器实例';
        }

        if ($stats['total_instances'] === 0) {
            $check['status'] = 'critical';
            $check['score'] = 0;
            $check['alerts'][] = '没有可用的浏览器实例';
            $check['recommendations'][] = '启动浏览器实例';
        }

        return $check;
    }

    /**
     * 检查任务执行
     */
    protected function checkTaskExecution(array $stats): array
    {
        $check = [
            'status' => 'healthy',
            'score' => 100,
            'alerts' => [],
            'recommendations' => []
        ];

        if ($stats['failure_rate'] > $this->thresholds['task_failure_rate']) {
            $check['status'] = 'warning';
            $check['score'] -= 20;
            $check['alerts'][] = "任务失败率过高: {$stats['failure_rate']}%";
            $check['recommendations'][] = '检查失败任务的日志并修复问题';
        }

        return $check;
    }

    /**
     * 检查数据库连接
     */
    protected function checkDatabaseConnection(): array
    {
        $check = [
            'status' => 'healthy',
            'score' => 100,
            'alerts' => [],
            'recommendations' => []
        ];

        try {
            DB::connection()->getPdo();
        } catch (\Exception) {
            $check['status'] = 'critical';
            $check['score'] = 0;
            $check['alerts'][] = '数据库连接失败';
            $check['recommendations'][] = '检查数据库服务状态';
        }

        return $check;
    }

    /**
     * 检查队列状态
     */
    protected function checkQueueStatus(): array
    {
        $check = [
            'status' => 'healthy',
            'score' => 100,
            'alerts' => [],
            'recommendations' => []
        ];

        // 简化的队列检查
        try {
            $queueSize = Cache::get('queue_size', 0);
            if ($queueSize > 100) {
                $check['status'] = 'warning';
                $check['score'] -= 15;
                $check['alerts'][] = "队列积压过多: {$queueSize} 个任务";
                $check['recommendations'][] = '增加队列工作进程';
            }
        } catch (\Exception) {
            $check['status'] = 'warning';
            $check['score'] -= 10;
            $check['alerts'][] = '无法获取队列状态';
        }

        return $check;
    }

    /**
     * 缓存指标数据
     */
    protected function cacheMetrics(string $type, array $metrics): void
    {
        $key = "performance_metrics_{$type}";
        Cache::put($key, $metrics, now()->addMinutes(5));
        
        // 保存历史数据
        $historyKey = "performance_history_{$type}";
        $history = Cache::get($historyKey, []);
        $history[] = $metrics;
        
        // 只保留最近24小时的数据
        $history = array_slice($history, -288); // 24小时 * 12个5分钟间隔
        Cache::put($historyKey, $history, now()->addDay());
    }

    /**
     * 获取CPU核心数
     */
    protected function getCpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            return (int) shell_exec('sysctl -n hw.ncpu');
        } elseif (PHP_OS_FAMILY === 'Linux') {
            return (int) shell_exec('nproc');
        }
        
        return 1;
    }

    /**
     * 解析内存大小
     */
    protected function parseMemorySize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;
        
        switch ($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }
        
        return $size;
    }

    /**
     * 格式化字节数
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 获取活跃连接数
     */
    protected function getActiveConnections(): int
    {
        // 简化实现
        return rand(10, 100);
    }

    /**
     * 获取缓存的指标数据
     */
    public function getCachedMetrics(string $type): ?array
    {
        return Cache::get("performance_metrics_{$type}");
    }

    /**
     * 获取历史指标数据
     */
    public function getHistoricalMetrics(string $type): array
    {
        return Cache::get("performance_history_{$type}", []);
    }

    /**
     * 设置阈值
     */
    public function setThreshold(string $metric, float $value): void
    {
        $this->thresholds[$metric] = $value;
    }

    /**
     * 获取所有阈值
     */
    public function getThresholds(): array
    {
        return $this->thresholds;
    }
}
