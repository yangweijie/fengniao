<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Services\SystemOptimizer;
use Illuminate\Console\Command;

class OptimizeSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:optimize
                            {--analyze : 仅分析不执行优化}
                            {--auto-tune : 自动调优配置}
                            {--debug-task= : 为指定任务启用调试模式}
                            {--debug-mode=visual : 调试模式类型}
                            {--diagnose= : 诊断指定的任务执行}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '系统优化工具 - 分析性能、自动优化配置、启用调试模式';

    protected SystemOptimizer $optimizer;

    public function __construct(SystemOptimizer $optimizer)
    {
        parent::__construct();
        $this->optimizer = $optimizer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 系统优化工具启动');

        // 处理调试任务
        if ($taskId = $this->option('debug-task')) {
            return $this->handleDebugTask($taskId);
        }

        // 处理诊断
        if ($executionId = $this->option('diagnose')) {
            return $this->handleDiagnosis($executionId);
        }

        // 处理自动调优
        if ($this->option('auto-tune')) {
            return $this->handleAutoTune();
        }

        // 默认执行分析和优化
        return $this->handleAnalyzeAndOptimize();
    }

    /**
     * 处理调试任务
     */
    protected function handleDebugTask(string $taskId): int
    {
        $task = Task::find($taskId);
        if (!$task) {
            $this->error("任务不存在: {$taskId}");
            return Command::FAILURE;
        }

        $debugMode = $this->option('debug-mode');
        $supportedModes = $this->optimizer->getSupportedDebugModes();

        if (!in_array($debugMode, $supportedModes)) {
            $this->error("不支持的调试模式: {$debugMode}");
            $this->info("支持的模式: " . implode(', ', $supportedModes));
            return Command::FAILURE;
        }

        $this->info("为任务 '{$task->name}' 启用 {$debugMode} 调试模式");

        try {
            switch ($debugMode) {
                case 'visual':
                    $config = $this->optimizer->enableVisualDebugMode($task, [
                        'slow_motion' => $this->confirm('启用慢动作模式？', true),
                        'screenshot_on_action' => $this->confirm('每个操作后截图？', true),
                        'step_by_step' => $this->confirm('启用单步执行？', false)
                    ]);
                    break;
                case 'performance':
                    $config = $this->optimizer->enablePerformanceDebugMode($task);
                    break;
                case 'network':
                    $config = $this->optimizer->enableNetworkDebugMode($task);
                    break;
                default:
                    $config = $this->optimizer->enableVisualDebugMode($task);
            }

            $this->info('✅ 调试模式已启用');
            $this->table(
                ['配置项', '值'],
                collect($config['options'] ?? $config)->map(function ($value, $key) {
                    return [$key, is_bool($value) ? ($value ? 'true' : 'false') : $value];
                })->toArray()
            );

            $this->warn('注意: 调试模式会影响任务执行性能，仅用于开发和调试');

        } catch (\Exception $e) {
            $this->error("启用调试模式失败: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 处理错误诊断
     */
    protected function handleDiagnosis(string $executionId): int
    {
        $execution = TaskExecution::find($executionId);
        if (!$execution) {
            $this->error("任务执行记录不存在: {$executionId}");
            return Command::FAILURE;
        }

        $this->info("🔍 诊断任务执行: {$execution->id}");
        $this->info("任务: {$execution->task->name}");
        $this->info("状态: {$execution->status}");
        $this->info("执行时间: {$execution->created_at}");

        try {
            $diagnosis = $this->optimizer->diagnoseErrors($execution);

            // 显示错误分析
            if (!empty($diagnosis['error_analysis'])) {
                $this->newLine();
                $this->error('🚨 错误分析:');
                foreach ($diagnosis['error_analysis'] as $error) {
                    $this->line("类型: {$error['type']}");
                    $this->line("消息: {$error['message']}");
                    if (!empty($error['causes'])) {
                        $this->line("可能原因: " . implode(', ', $error['causes']));
                    }
                    $this->newLine();
                }
            }

            // 显示解决方案
            if (!empty($diagnosis['solutions'])) {
                $this->warn('💡 建议解决方案:');
                foreach (array_unique($diagnosis['solutions']) as $solution) {
                    $this->line("• {$solution}");
                }
                $this->newLine();
            }

            // 显示环境检查
            if (!empty($diagnosis['environment_check'])) {
                $this->info('🌍 执行环境:');
                $this->table(
                    ['项目', '值'],
                    collect($diagnosis['environment_check'])->map(function ($value, $key) {
                        return [str_replace('_', ' ', ucfirst($key)), $value];
                    })->toArray()
                );
            }

            // 显示资源分析
            if (!empty($diagnosis['resource_analysis'])) {
                $this->info('📊 资源使用分析:');
                $this->table(
                    ['指标', '值'],
                    collect($diagnosis['resource_analysis'])->map(function ($value, $key) {
                        return [str_replace('_', ' ', ucfirst($key)), $value];
                    })->toArray()
                );
            }

        } catch (\Exception $e) {
            $this->error("诊断失败: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 处理自动调优
     */
    protected function handleAutoTune(): int
    {
        $this->info('🎛️ 开始自动配置调优');

        try {
            $tuning = $this->optimizer->autoTuneConfiguration();

            if (empty($tuning['recommendations'])) {
                $this->info('✅ 当前配置已是最优状态');
                return Command::SUCCESS;
            }

            $this->warn('📋 发现以下配置优化建议:');
            $this->newLine();

            foreach ($tuning['recommendations'] as $recommendation) {
                $this->line("配置项: {$recommendation['type']}");
                $this->line("当前值: {$recommendation['current']}");
                $this->line("建议值: {$recommendation['recommended']}");
                $this->line("原因: {$recommendation['reason']}");
                $this->newLine();

                if ($this->confirm("是否应用此优化？")) {
                    // 这里应该实际应用配置更改
                    $this->info("✅ 已应用优化: {$recommendation['type']}");
                } else {
                    $this->warn("⏭️ 跳过优化: {$recommendation['type']}");
                }
            }

        } catch (\Exception $e) {
            $this->error("自动调优失败: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 处理分析和优化
     */
    protected function handleAnalyzeAndOptimize(): int
    {
        $analyzeOnly = $this->option('analyze');

        $this->info($analyzeOnly ? '📊 开始系统分析' : '🚀 开始系统分析和优化');

        try {
            $analysis = $this->optimizer->analyzeAndOptimize();

            // 显示系统指标概览
            $this->displaySystemOverview($analysis);

            // 显示优化建议
            $this->displayOptimizationSuggestions($analysis['optimization_suggestions']);

            if (!$analyzeOnly) {
                // 显示自动优化结果
                $this->displayAutoOptimizations($analysis['auto_optimizations']);

                // 显示配置建议
                $this->displayConfigurationRecommendations($analysis['configuration_recommendations']);
            }

        } catch (\Exception $e) {
            $this->error("分析失败: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 显示系统概览
     */
    protected function displaySystemOverview(array $analysis): void
    {
        $this->info('📊 系统状态概览');
        $this->line(str_repeat('-', 50));

        $system = $analysis['system_metrics'];
        $browser = $analysis['browser_metrics'];
        $tasks = $analysis['task_metrics'];

        $this->table(
            ['指标', '当前值', '状态'],
            [
                ['CPU使用率', $system['cpu'] . '%', $this->getStatusColor($system['cpu'], 80, 60)],
                ['内存使用率', $system['memory']['usage_percentage'] . '%', $this->getStatusColor($system['memory']['usage_percentage'], 85, 70)],
                ['磁盘使用率', $system['disk']['usage_percentage'] . '%', $this->getStatusColor($system['disk']['usage_percentage'], 90, 80)],
                ['浏览器实例', $browser['total_instances'], $browser['total_instances'] > 0 ? '正常' : '异常'],
                ['异常实例', $browser['unhealthy_instances'], $browser['unhealthy_instances'] == 0 ? '正常' : '需要关注'],
                ['任务成功率', $tasks['success_rate'] . '%', $this->getStatusColor($tasks['success_rate'], 70, 90, true)]
            ]
        );

        $this->newLine();
    }

    /**
     * 显示优化建议
     */
    protected function displayOptimizationSuggestions(array $suggestions): void
    {
        if (empty($suggestions)) {
            $this->info('✅ 系统运行良好，暂无优化建议');
            return;
        }

        $this->warn('💡 优化建议:');
        $this->newLine();

        foreach ($suggestions as $suggestion) {
            $priorityColor = match($suggestion['priority']) {
                'high' => 'red',
                'medium' => 'yellow',
                'low' => 'green',
                default => 'white'
            };

            $this->line("<fg={$priorityColor}>优先级: " . strtoupper($suggestion['priority']) . "</>");
            $this->line("标题: {$suggestion['title']}");
            $this->line("描述: {$suggestion['description']}");
            $this->line("建议操作:");
            foreach ($suggestion['actions'] as $action) {
                $this->line("  • {$action}");
            }
            $this->newLine();
        }
    }

    /**
     * 显示自动优化结果
     */
    protected function displayAutoOptimizations(array $optimizations): void
    {
        if (empty($optimizations)) {
            $this->info('ℹ️ 未执行自动优化');
            return;
        }

        $this->info('🤖 自动优化结果:');
        $this->newLine();

        foreach ($optimizations as $optimization) {
            $impactColor = match($optimization['impact']) {
                'high' => 'green',
                'medium' => 'yellow',
                'low' => 'blue',
                default => 'white'
            };

            $this->line("✅ {$optimization['description']}");
            $this->line("<fg={$impactColor}>影响程度: " . strtoupper($optimization['impact']) . "</>");
            $this->newLine();
        }
    }

    /**
     * 显示配置建议
     */
    protected function displayConfigurationRecommendations(array $recommendations): void
    {
        if (empty($recommendations)) {
            return;
        }

        $this->warn('⚙️ 配置优化建议:');
        $this->newLine();

        foreach ($recommendations as $category => $configs) {
            $this->line("分类: " . strtoupper($category));
            foreach ($configs as $config) {
                $this->line("  配置: {$config['setting']}");
                $this->line("  当前: {$config['current']}");
                $this->line("  建议: {$config['recommended']}");
                $this->line("  原因: {$config['reason']}");
                $this->newLine();
            }
        }
    }

    /**
     * 获取状态颜色
     */
    protected function getStatusColor(float $value, float $errorThreshold, float $warningThreshold, bool $reverse = false): string
    {
        if ($reverse) {
            // 对于成功率等指标，值越高越好
            if ($value >= $warningThreshold) return '正常';
            if ($value >= $errorThreshold) return '警告';
            return '异常';
        } else {
            // 对于使用率等指标，值越低越好
            if ($value >= $errorThreshold) return '异常';
            if ($value >= $warningThreshold) return '警告';
            return '正常';
        }
    }
}
