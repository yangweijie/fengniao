<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\BrowserInstance;
use App\Services\PerformanceMonitor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SystemOptimizer
{
    protected PerformanceMonitor $performanceMonitor;
    protected array $optimizationRules = [];
    protected array $debugModes = ['visual', 'performance', 'network', 'console'];

    public function __construct(PerformanceMonitor $performanceMonitor)
    {
        $this->performanceMonitor = $performanceMonitor;
        $this->initializeOptimizationRules();
    }

    /**
     * 分析系统性能并提供优化建议
     */
    public function analyzeAndOptimize(): array
    {
        $analysis = [
            'timestamp' => now(),
            'system_metrics' => $this->performanceMonitor->collectSystemMetrics(),
            'browser_metrics' => $this->performanceMonitor->monitorBrowserInstances(),
            'task_metrics' => $this->performanceMonitor->analyzeTaskExecutionStats(),
            'optimization_suggestions' => [],
            'auto_optimizations' => [],
            'configuration_recommendations' => []
        ];

        // 分析并生成建议
        $analysis['optimization_suggestions'] = $this->generateOptimizationSuggestions($analysis);
        
        // 执行自动优化
        $analysis['auto_optimizations'] = $this->performAutoOptimizations($analysis);
        
        // 配置建议
        $analysis['configuration_recommendations'] = $this->generateConfigurationRecommendations($analysis);

        return $analysis;
    }

    /**
     * 启用可视化调试模式
     */
    public function enableVisualDebugMode(Task $task, array $options = []): array
    {
        $debugConfig = [
            'task_id' => $task->id,
            'mode' => 'visual',
            'enabled' => true,
            'options' => array_merge([
                'slow_motion' => true,
                'highlight_elements' => true,
                'screenshot_on_action' => true,
                'pause_on_error' => true,
                'step_by_step' => false,
                'show_console_logs' => true
            ], $options),
            'created_at' => now()
        ];

        // 缓存调试配置
        Cache::put("debug_config_{$task->id}", $debugConfig, now()->addHours(24));

        return $debugConfig;
    }

    /**
     * 性能分析调试
     */
    public function enablePerformanceDebugMode(Task $task): array
    {
        $debugConfig = [
            'task_id' => $task->id,
            'mode' => 'performance',
            'enabled' => true,
            'metrics' => [
                'execution_time' => true,
                'memory_usage' => true,
                'network_requests' => true,
                'dom_operations' => true,
                'javascript_errors' => true
            ],
            'thresholds' => [
                'max_execution_time' => 300, // 5分钟
                'max_memory_usage' => 512, // 512MB
                'max_network_requests' => 100
            ],
            'created_at' => now()
        ];

        Cache::put("debug_config_{$task->id}", $debugConfig, now()->addHours(24));

        return $debugConfig;
    }

    /**
     * 网络调试模式
     */
    public function enableNetworkDebugMode(Task $task): array
    {
        $debugConfig = [
            'task_id' => $task->id,
            'mode' => 'network',
            'enabled' => true,
            'capture' => [
                'requests' => true,
                'responses' => true,
                'headers' => true,
                'timing' => true,
                'failed_requests' => true
            ],
            'filters' => [
                'min_duration' => 1000, // 只记录超过1秒的请求
                'status_codes' => [400, 401, 403, 404, 500, 502, 503, 504]
            ],
            'created_at' => now()
        ];

        Cache::put("debug_config_{$task->id}", $debugConfig, now()->addHours(24));

        return $debugConfig;
    }

    /**
     * 自动配置调优
     */
    public function autoTuneConfiguration(): array
    {
        $currentMetrics = $this->performanceMonitor->collectSystemMetrics();
        $recommendations = [];
        $applied = [];

        // 基于系统资源调整浏览器池大小
        $browserPoolSize = $this->calculateOptimalBrowserPoolSize($currentMetrics);
        if ($browserPoolSize !== Config::get('browser.pool_size', 3)) {
            $recommendations[] = [
                'type' => 'browser_pool_size',
                'current' => Config::get('browser.pool_size', 3),
                'recommended' => $browserPoolSize,
                'reason' => '基于当前系统资源优化浏览器池大小'
            ];
        }

        // 调整任务并发数
        $maxConcurrency = $this->calculateOptimalConcurrency($currentMetrics);
        if ($maxConcurrency !== Config::get('queue.max_concurrent_tasks', 5)) {
            $recommendations[] = [
                'type' => 'max_concurrency',
                'current' => Config::get('queue.max_concurrent_tasks', 5),
                'recommended' => $maxConcurrency,
                'reason' => '基于CPU和内存使用率调整并发数'
            ];
        }

        // 调整超时设置
        $timeoutSettings = $this->calculateOptimalTimeouts($currentMetrics);
        foreach ($timeoutSettings as $setting => $value) {
            $currentValue = Config::get("timeouts.{$setting}", 30);
            if ($value !== $currentValue) {
                $recommendations[] = [
                    'type' => "timeout_{$setting}",
                    'current' => $currentValue,
                    'recommended' => $value,
                    'reason' => '基于网络性能优化超时设置'
                ];
            }
        }

        return [
            'recommendations' => $recommendations,
            'applied' => $applied,
            'analysis_time' => now()
        ];
    }

    /**
     * 错误诊断工具
     */
    public function diagnoseErrors(TaskExecution $execution): array
    {
        $diagnosis = [
            'execution_id' => $execution->id,
            'task_id' => $execution->task_id,
            'status' => $execution->status,
            'error_analysis' => [],
            'possible_causes' => [],
            'solutions' => [],
            'prevention_tips' => []
        ];

        if ($execution->status === 'failed') {
            // 分析错误日志
            $logs = $execution->logs()->where('level', 'error')->get();
            
            foreach ($logs as $log) {
                $errorAnalysis = $this->analyzeError($log->message, $log->context);
                $diagnosis['error_analysis'][] = $errorAnalysis;
                $diagnosis['possible_causes'] = array_merge($diagnosis['possible_causes'], $errorAnalysis['causes']);
                $diagnosis['solutions'] = array_merge($diagnosis['solutions'], $errorAnalysis['solutions']);
            }

            // 系统环境检查
            $diagnosis['environment_check'] = $this->checkEnvironmentForErrors($execution);
            
            // 资源使用分析
            $diagnosis['resource_analysis'] = $this->analyzeResourceUsageDuringExecution($execution);
        }

        return $diagnosis;
    }

    /**
     * 生成优化建议
     */
    protected function generateOptimizationSuggestions(array $analysis): array
    {
        $suggestions = [];

        // CPU优化建议
        if ($analysis['system_metrics']['cpu'] > 80) {
            $suggestions[] = [
                'category' => 'cpu',
                'priority' => 'high',
                'title' => 'CPU使用率过高',
                'description' => 'CPU使用率超过80%，建议减少并发任务数量',
                'actions' => [
                    '减少浏览器实例数量',
                    '降低任务并发执行数',
                    '优化任务执行逻辑',
                    '考虑升级硬件'
                ]
            ];
        }

        // 内存优化建议
        if ($analysis['system_metrics']['memory']['usage_percentage'] > 85) {
            $suggestions[] = [
                'category' => 'memory',
                'priority' => 'high',
                'title' => '内存使用率过高',
                'description' => '内存使用率超过85%，可能影响系统稳定性',
                'actions' => [
                    '增加系统内存',
                    '优化浏览器内存使用',
                    '定期重启浏览器实例',
                    '清理无用的缓存数据'
                ]
            ];
        }

        // 浏览器实例优化
        if ($analysis['browser_metrics']['unhealthy_instances'] > 0) {
            $suggestions[] = [
                'category' => 'browser',
                'priority' => 'medium',
                'title' => '浏览器实例异常',
                'description' => "发现{$analysis['browser_metrics']['unhealthy_instances']}个异常浏览器实例",
                'actions' => [
                    '重启异常的浏览器实例',
                    '检查浏览器进程状态',
                    '优化浏览器启动参数',
                    '增加健康检查频率'
                ]
            ];
        }

        // 任务执行优化
        if ($analysis['task_metrics']['failure_rate'] > 20) {
            $suggestions[] = [
                'category' => 'tasks',
                'priority' => 'high',
                'title' => '任务失败率过高',
                'description' => "任务失败率为{$analysis['task_metrics']['failure_rate']}%，需要优化",
                'actions' => [
                    '分析失败任务的错误日志',
                    '优化任务重试机制',
                    '检查目标网站的稳定性',
                    '调整任务执行策略'
                ]
            ];
        }

        return $suggestions;
    }

    /**
     * 执行自动优化
     */
    protected function performAutoOptimizations(array $analysis): array
    {
        $optimizations = [];

        // 自动清理异常浏览器实例
        if ($analysis['browser_metrics']['unhealthy_instances'] > 0) {
            $cleaned = $this->cleanupUnhealthyBrowserInstances();
            $optimizations[] = [
                'type' => 'browser_cleanup',
                'description' => "清理了{$cleaned}个异常浏览器实例",
                'impact' => 'medium'
            ];
        }

        // 自动清理缓存
        if ($analysis['system_metrics']['memory']['usage_percentage'] > 90) {
            $this->clearSystemCaches();
            $optimizations[] = [
                'type' => 'cache_cleanup',
                'description' => '清理了系统缓存以释放内存',
                'impact' => 'low'
            ];
        }

        // 自动调整队列工作进程
        if ($analysis['task_metrics']['total_executions'] > 100) {
            $adjustment = $this->adjustQueueWorkers($analysis);
            if ($adjustment) {
                $optimizations[] = $adjustment;
            }
        }

        return $optimizations;
    }

    /**
     * 生成配置建议
     */
    protected function generateConfigurationRecommendations(array $analysis): array
    {
        $recommendations = [];

        // 浏览器配置建议
        $browserConfig = $this->analyzeBrowserConfiguration($analysis);
        if (!empty($browserConfig)) {
            $recommendations['browser'] = $browserConfig;
        }

        // 队列配置建议
        $queueConfig = $this->analyzeQueueConfiguration($analysis);
        if (!empty($queueConfig)) {
            $recommendations['queue'] = $queueConfig;
        }

        // 缓存配置建议
        $cacheConfig = $this->analyzeCacheConfiguration($analysis);
        if (!empty($cacheConfig)) {
            $recommendations['cache'] = $cacheConfig;
        }

        return $recommendations;
    }

    /**
     * 计算最优浏览器池大小
     */
    protected function calculateOptimalBrowserPoolSize(array $metrics): int
    {
        $cpuUsage = $metrics['cpu'];
        $memoryUsage = $metrics['memory']['usage_percentage'];
        
        // 基础大小
        $baseSize = 3;
        
        // 根据CPU使用率调整
        if ($cpuUsage < 50) {
            $baseSize += 2;
        } elseif ($cpuUsage > 80) {
            $baseSize -= 1;
        }
        
        // 根据内存使用率调整
        if ($memoryUsage < 60) {
            $baseSize += 1;
        } elseif ($memoryUsage > 85) {
            $baseSize -= 2;
        }
        
        return max(1, min(10, $baseSize));
    }

    /**
     * 计算最优并发数
     */
    protected function calculateOptimalConcurrency(array $metrics): int
    {
        $cpuCores = $this->getCpuCores();
        $cpuUsage = $metrics['cpu'];
        $memoryUsage = $metrics['memory']['usage_percentage'];
        
        // 基础并发数为CPU核心数
        $baseConcurrency = $cpuCores;
        
        // 根据资源使用情况调整
        if ($cpuUsage < 50 && $memoryUsage < 70) {
            $baseConcurrency = $cpuCores * 2;
        } elseif ($cpuUsage > 80 || $memoryUsage > 85) {
            $baseConcurrency = max(1, $cpuCores / 2);
        }
        
        return (int) $baseConcurrency;
    }

    /**
     * 计算最优超时设置
     */
    protected function calculateOptimalTimeouts(array $metrics): array
    {
        // 基于网络性能和系统负载调整超时设置
        $baseTimeout = 30;
        $loadAverage = $metrics['load_average']['1min'];
        
        if ($loadAverage > 5) {
            $baseTimeout *= 1.5;
        } elseif ($loadAverage < 1) {
            $baseTimeout *= 0.8;
        }
        
        return [
            'page_load' => (int) $baseTimeout,
            'element_wait' => (int) ($baseTimeout * 0.5),
            'script_execution' => (int) ($baseTimeout * 2)
        ];
    }

    /**
     * 分析错误
     */
    protected function analyzeError(string $message, ?array $context): array
    {
        $analysis = [
            'message' => $message,
            'type' => 'unknown',
            'causes' => [],
            'solutions' => []
        ];

        // 常见错误模式匹配
        if (strpos($message, 'timeout') !== false) {
            $analysis['type'] = 'timeout';
            $analysis['causes'] = ['网络延迟', '页面加载缓慢', '元素未及时出现'];
            $analysis['solutions'] = ['增加超时时间', '优化选择器', '添加显式等待'];
        } elseif (strpos($message, 'element not found') !== false) {
            $analysis['type'] = 'element_not_found';
            $analysis['causes'] = ['选择器错误', '页面结构变化', '元素动态加载'];
            $analysis['solutions'] = ['检查选择器', '添加等待条件', '使用更稳定的选择器'];
        } elseif (strpos($message, 'connection') !== false) {
            $analysis['type'] = 'connection_error';
            $analysis['causes'] = ['网络连接问题', '目标服务器异常', '代理设置错误'];
            $analysis['solutions'] = ['检查网络连接', '验证目标URL', '调整代理设置'];
        }

        return $analysis;
    }

    /**
     * 检查执行环境
     */
    protected function checkEnvironmentForErrors(TaskExecution $execution): array
    {
        return [
            'browser_instances' => BrowserInstance::where('status', 'active')->count(),
            'system_load' => sys_getloadavg()[0] ?? 0,
            'memory_usage' => memory_get_usage(true),
            'disk_space' => disk_free_space(storage_path()),
            'execution_time' => $execution->created_at->format('Y-m-d H:i:s')
        ];
    }

    /**
     * 分析执行期间的资源使用
     */
    protected function analyzeResourceUsageDuringExecution(TaskExecution $execution): array
    {
        // 简化实现，实际应该从监控历史数据中获取
        return [
            'peak_memory' => memory_get_peak_usage(true),
            'execution_duration' => $execution->end_time ? 
                $execution->end_time->diffInSeconds($execution->start_time) : null,
            'concurrent_executions' => TaskExecution::where('status', 'running')
                ->where('created_at', '<=', $execution->created_at)
                ->count()
        ];
    }

    /**
     * 清理异常浏览器实例
     */
    protected function cleanupUnhealthyBrowserInstances(): int
    {
        $unhealthyInstances = BrowserInstance::where('status', 'error')->get();
        $cleaned = 0;
        
        foreach ($unhealthyInstances as $instance) {
            try {
                // 尝试终止进程
                if ($instance->port) {
                    shell_exec("pkill -f 'chrome.*--remote-debugging-port={$instance->port}'");
                }
                
                // 更新状态或删除记录
                $instance->delete();
                $cleaned++;
            } catch (\Exception $e) {
                Log::warning("清理浏览器实例失败", [
                    'instance_id' => $instance->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $cleaned;
    }

    /**
     * 清理系统缓存
     */
    protected function clearSystemCaches(): void
    {
        // 清理应用缓存
        Cache::flush();
        
        // 清理日志文件（保留最近7天）
        $logPath = storage_path('logs');
        $files = glob($logPath . '/laravel-*.log');
        $cutoff = now()->subDays(7);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff->timestamp) {
                unlink($file);
            }
        }
    }

    /**
     * 调整队列工作进程
     */
    protected function adjustQueueWorkers(array $analysis): ?array
    {
        // 简化实现，实际应该与队列管理器集成
        $currentWorkers = 3; // 假设当前工作进程数
        $optimalWorkers = $this->calculateOptimalConcurrency($analysis['system_metrics']);
        
        if ($optimalWorkers !== $currentWorkers) {
            return [
                'type' => 'queue_workers',
                'description' => "调整队列工作进程从{$currentWorkers}到{$optimalWorkers}",
                'impact' => 'high'
            ];
        }
        
        return null;
    }

    /**
     * 分析浏览器配置
     */
    protected function analyzeBrowserConfiguration(array $analysis): array
    {
        $recommendations = [];
        
        if ($analysis['system_metrics']['memory']['usage_percentage'] > 80) {
            $recommendations[] = [
                'setting' => 'browser.memory_limit',
                'current' => '512MB',
                'recommended' => '256MB',
                'reason' => '系统内存使用率过高，建议降低浏览器内存限制'
            ];
        }
        
        return $recommendations;
    }

    /**
     * 分析队列配置
     */
    protected function analyzeQueueConfiguration(array $analysis): array
    {
        $recommendations = [];
        
        if ($analysis['task_metrics']['failure_rate'] > 15) {
            $recommendations[] = [
                'setting' => 'queue.retry_attempts',
                'current' => 3,
                'recommended' => 5,
                'reason' => '任务失败率较高，建议增加重试次数'
            ];
        }
        
        return $recommendations;
    }

    /**
     * 分析缓存配置
     */
    protected function analyzeCacheConfiguration(array $analysis): array
    {
        $recommendations = [];
        
        if ($analysis['system_metrics']['memory']['usage_percentage'] > 85) {
            $recommendations[] = [
                'setting' => 'cache.ttl',
                'current' => 3600,
                'recommended' => 1800,
                'reason' => '内存使用率过高，建议降低缓存TTL'
            ];
        }
        
        return $recommendations;
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
        
        return 2; // 默认值
    }

    /**
     * 初始化优化规则
     */
    protected function initializeOptimizationRules(): void
    {
        $this->optimizationRules = [
            'cpu_high' => [
                'threshold' => 80,
                'actions' => ['reduce_concurrency', 'optimize_tasks']
            ],
            'memory_high' => [
                'threshold' => 85,
                'actions' => ['clear_cache', 'restart_browsers']
            ],
            'disk_high' => [
                'threshold' => 90,
                'actions' => ['cleanup_logs', 'cleanup_temp']
            ]
        ];
    }

    /**
     * 获取调试配置
     */
    public function getDebugConfig(int $taskId): ?array
    {
        return Cache::get("debug_config_{$taskId}");
    }

    /**
     * 禁用调试模式
     */
    public function disableDebugMode(int $taskId): bool
    {
        return Cache::forget("debug_config_{$taskId}");
    }

    /**
     * 获取支持的调试模式
     */
    public function getSupportedDebugModes(): array
    {
        return $this->debugModes;
    }
}
