<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Services\WorkflowEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExecuteWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflow:execute
                            {workflow : 工作流文件路径或JSON数据}
                            {--task-id= : 关联的任务ID}
                            {--convert : 仅转换为Dusk脚本，不执行}
                            {--output= : 转换脚本的输出路径}
                            {--validate : 仅验证工作流结构}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '执行工作流或将工作流转换为Dusk脚本';

    protected WorkflowEngine $workflowEngine;

    public function __construct(WorkflowEngine $workflowEngine)
    {
        parent::__construct();
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workflowInput = $this->argument('workflow');
        $taskId = $this->option('task-id');
        $convert = $this->option('convert');
        $output = $this->option('output');
        $validate = $this->option('validate');

        try {
            // 解析工作流数据
            $workflowData = $this->parseWorkflowData($workflowInput);

            if ($validate) {
                return $this->validateWorkflow($workflowData);
            }

            if ($convert) {
                return $this->convertWorkflow($workflowData, $output);
            }

            return $this->executeWorkflow($workflowData, $taskId);

        } catch (\Exception $e) {
            $this->error("操作失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 解析工作流数据
     */
    protected function parseWorkflowData(string $input): array
    {
        // 检查是否是文件路径
        if (file_exists($input)) {
            $content = File::get($input);
            $this->info("从文件加载工作流: {$input}");
        } else {
            $content = $input;
            $this->info("解析工作流JSON数据");
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('工作流数据JSON格式错误: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * 验证工作流
     */
    protected function validateWorkflow(array $workflowData): int
    {
        $this->info('🔍 验证工作流结构');

        try {
            // 这里应该调用WorkflowEngine的验证方法
            // 简化实现
            $this->validateWorkflowStructure($workflowData);

            $this->info('✅ 工作流结构验证通过');

            // 显示工作流信息
            $this->displayWorkflowInfo($workflowData);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ 工作流验证失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 转换工作流
     */
    protected function convertWorkflow(array $workflowData, ?string $output): int
    {
        $this->info('🔄 转换工作流为Dusk脚本');

        try {
            $duskScript = $this->workflowEngine->convertWorkflowToDuskScript($workflowData);

            if ($output) {
                // 确保输出目录存在
                $outputDir = dirname($output);
                if (!is_dir($outputDir)) {
                    mkdir($outputDir, 0755, true);
                }

                File::put($output, $duskScript);
                $this->info("✅ Dusk脚本已保存到: {$output}");
            } else {
                $this->info('✅ 转换完成，Dusk脚本内容:');
                $this->line(str_repeat('=', 80));
                $this->line($duskScript);
                $this->line(str_repeat('=', 80));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ 转换失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 执行工作流
     */
    protected function executeWorkflow(array $workflowData, ?string $taskId): int
    {
        $this->info('🚀 开始执行工作流');

        try {
            // 创建或获取任务执行记录
            $execution = $this->createTaskExecution($taskId, $workflowData);

            $this->info("执行ID: {$execution->id}");
            if ($execution->task) {
                $this->info("关联任务: {$execution->task->name}");
            }

            // 显示工作流信息
            $this->displayWorkflowInfo($workflowData);

            if (!$this->confirm('确认执行此工作流？')) {
                $this->info('用户取消执行');
                return Command::SUCCESS;
            }

            // 执行工作流
            $result = $this->workflowEngine->executeWorkflow($workflowData, $execution);

            // 显示执行结果
            $this->displayExecutionResult($result);

            return $result['success'] ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('❌ 执行失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 验证工作流结构
     */
    protected function validateWorkflowStructure(array $workflowData): void
    {
        if (!isset($workflowData['nodes']) || !is_array($workflowData['nodes'])) {
            throw new \Exception('工作流缺少nodes数组');
        }

        if (!isset($workflowData['edges']) || !is_array($workflowData['edges'])) {
            throw new \Exception('工作流缺少edges数组');
        }

        if (empty($workflowData['nodes'])) {
            throw new \Exception('工作流至少需要一个节点');
        }

        // 检查节点类型
        $supportedTypes = $this->workflowEngine->getSupportedNodeTypes();
        $hasStartNode = false;

        foreach ($workflowData['nodes'] as $node) {
            if (!isset($node['type'])) {
                throw new \Exception('节点缺少type属性');
            }

            if (!in_array($node['type'], $supportedTypes)) {
                throw new \Exception("不支持的节点类型: {$node['type']}");
            }

            if ($node['type'] === 'start') {
                $hasStartNode = true;
            }
        }

        if (!$hasStartNode) {
            throw new \Exception('工作流必须包含开始节点');
        }
    }

    /**
     * 显示工作流信息
     */
    protected function displayWorkflowInfo(array $workflowData): void
    {
        $this->info('📋 工作流信息:');
        $this->table(
            ['属性', '值'],
            [
                ['ID', $workflowData['id'] ?? 'N/A'],
                ['名称', $workflowData['name'] ?? 'N/A'],
                ['描述', $workflowData['description'] ?? 'N/A'],
                ['节点数量', count($workflowData['nodes'])],
                ['连接数量', count($workflowData['edges'])],
                ['创建时间', $workflowData['created_at'] ?? 'N/A']
            ]
        );

        // 显示节点统计
        $nodeStats = [];
        foreach ($workflowData['nodes'] as $node) {
            $type = $node['type'];
            $nodeStats[$type] = ($nodeStats[$type] ?? 0) + 1;
        }

        $this->info('📊 节点类型统计:');
        $this->table(
            ['节点类型', '数量'],
            collect($nodeStats)->map(function ($count, $type) {
                return [ucfirst($type), $count];
            })->toArray()
        );
    }

    /**
     * 创建任务执行记录
     */
    protected function createTaskExecution(?string $taskId, array $workflowData): TaskExecution
    {
        if ($taskId) {
            $task = Task::find($taskId);
            if (!$task) {
                throw new \Exception("任务不存在: {$taskId}");
            }
        } else {
            // 创建临时任务
            $task = new Task([
                'name' => '工作流执行 - ' . ($workflowData['name'] ?? 'Unnamed'),
                'type' => 'browser',
                'config' => json_encode($workflowData),
                'status' => 'active'
            ]);
            $task->save();
        }

        $execution = new TaskExecution([
            'task_id' => $task->id,
            'status' => 'running',
            'start_time' => now(),
            'config' => json_encode($workflowData)
        ]);
        $execution->save();

        return $execution;
    }

    /**
     * 显示执行结果
     */
    protected function displayExecutionResult(array $result): void
    {
        $this->newLine();
        $this->info('📊 执行结果:');

        $statusColor = $result['success'] ? 'green' : 'red';
        $statusText = $result['success'] ? '成功' : '失败';

        $this->line("状态: <fg={$statusColor}>{$statusText}</>");
        $this->line("执行时间: {$result['execution_time']}秒");
        $this->line("执行节点数: " . count($result['nodes_executed']));
        $this->line("截图数量: " . count($result['screenshots']));

        if (!empty($result['errors'])) {
            $this->newLine();
            $this->error('❌ 错误信息:');
            foreach ($result['errors'] as $error) {
                $this->line("• {$error['message']}");
            }
        }

        if (!empty($result['nodes_executed'])) {
            $this->newLine();
            $this->info('📋 节点执行详情:');
            $this->table(
                ['节点ID', '类型', '状态', '时间'],
                array_map(function ($node) {
                    return [
                        $node['id'],
                        $node['type'],
                        $node['success'] ? '成功' : '失败',
                        $node['timestamp']->format('H:i:s')
                    ];
                }, $result['nodes_executed'])
            );
        }

        if (!empty($result['screenshots'])) {
            $this->newLine();
            $this->info('📸 截图列表:');
            foreach ($result['screenshots'] as $screenshot) {
                $this->line("• {$screenshot['description']} - {$screenshot['path']}");
            }
        }
    }
}
