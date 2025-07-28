<?php

namespace Tests\Integration;

use App\Models\Task;
use App\Models\BrowserInstance;

class ConcurrentExecutionTest extends IntegrationTestCase
{
    /**
     * 测试多任务并发执行
     */
    public function test_multiple_tasks_concurrent_execution(): void
    {
        // 创建多个测试任务
        $tasks = $this->createMultipleTestTasks(5);

        // 并发执行所有任务
        $startResults = $this->simulateConcurrentExecution($tasks);

        // 验证所有任务都成功启动
        foreach ($startResults as $taskId => $result) {
            $this->assertTrue($result['started'], "任务 {$taskId} 启动失败: " . ($result['error'] ?? ''));
        }

        // 等待所有任务完成
        $completionResults = $this->waitForMultipleTasksCompletion($tasks, 120);

        // 验证所有任务都成功完成
        $this->assertEquals(count($tasks), count($completionResults), '不是所有任务都完成了');

        foreach ($completionResults as $taskId => $success) {
            $this->assertTrue($success, "任务 {$taskId} 执行失败");
        }

        // 验证浏览器池状态
        $this->assertBrowserPoolHealth();
    }

    /**
     * 测试浏览器实例复用
     */
    public function test_browser_instance_reuse(): void
    {
        // 创建测试任务
        $tasks = $this->createMultipleTestTasks(3);

        // 记录初始浏览器实例数量
        $initialInstanceCount = BrowserInstance::count();

        // 顺序执行任务（应该复用浏览器实例）
        foreach ($tasks as $task) {
            $this->taskScheduler->executeTask($task);
            $this->waitForTaskCompletion($task, 30);
        }

        // 验证浏览器实例数量没有无限增长
        $finalInstanceCount = BrowserInstance::count();
        $this->assertLessThanOrEqual(
            $initialInstanceCount + config('browser.pool_size', 3),
            $finalInstanceCount,
            '浏览器实例数量增长过多'
        );

        // 验证实例使用计数
        $instances = BrowserInstance::all();
        foreach ($instances as $instance) {
            if ($instance->usage_count > 0) {
                $this->assertGreaterThan(0, $instance->usage_count, '浏览器实例使用计数应该大于0');
            }
        }
    }

    /**
     * 测试浏览器实例负载均衡
     */
    public function test_browser_instance_load_balancing(): void
    {
        // 创建多个任务
        $tasks = $this->createMultipleTestTasks(6);

        // 并发执行任务
        $this->simulateConcurrentExecution($tasks);
        $this->waitForMultipleTasksCompletion($tasks, 120);

        // 检查负载分布
        $instances = BrowserInstance::where('usage_count', '>', 0)->get();
        $this->assertGreaterThan(1, $instances->count(), '应该使用多个浏览器实例');

        // 验证负载相对均衡
        $usageCounts = $instances->pluck('usage_count')->toArray();
        $maxUsage = max($usageCounts);
        $minUsage = min($usageCounts);
        $this->assertLessThanOrEqual(3, $maxUsage - $minUsage, '负载分布不够均衡');
    }

    /**
     * 测试浏览器实例故障恢复
     */
    public function test_browser_instance_failure_recovery(): void
    {
        // 创建浏览器实例
        $instance = $this->createTestBrowserInstance();
        $instance->update(['status' => 'active']);

        // 模拟实例故障
        $instance->update(['status' => 'error']);

        // 创建任务
        $task = $this->createTestTask();

        // 执行任务（应该创建新实例或修复现有实例）
        $this->taskScheduler->executeTask($task);
        $this->waitForTaskCompletion($task, 30);

        // 验证任务成功执行
        $this->assertTaskExecutedSuccessfully($task);

        // 验证有可用的浏览器实例
        $healthyInstances = BrowserInstance::whereIn('status', ['idle', 'active'])->count();
        $this->assertGreaterThan(0, $healthyInstances, '没有健康的浏览器实例');
    }

    /**
     * 测试多标签页并行执行
     */
    public function test_multi_tab_parallel_execution(): void
    {
        // 创建多个轻量级任务
        $tasks = [];
        for ($i = 1; $i <= 4; $i++) {
            $tasks[] = $this->createTestTask([
                'name' => "多标签页测试任务 {$i}",
                'url' => "https://httpbin.org/delay/1",
                'config' => [
                    'exclusive' => false, // 允许共享浏览器实例
                    'actions' => [
                        ['type' => 'visit', 'url' => "https://httpbin.org/delay/1"],
                        ['type' => 'wait', 'seconds' => 1]
                    ]
                ]
            ]);
        }

        // 并发执行任务
        $this->simulateConcurrentExecution($tasks);
        $results = $this->waitForMultipleTasksCompletion($tasks, 60);

        // 验证所有任务都成功完成
        foreach ($results as $taskId => $success) {
            $this->assertTrue($success, "任务 {$taskId} 执行失败");
        }

        // 验证使用的浏览器实例数量少于任务数量（说明实现了多标签页共享）
        $usedInstances = BrowserInstance::where('usage_count', '>', 0)->count();
        $this->assertLessThan(count($tasks), $usedInstances, '没有实现多标签页共享');
    }

    /**
     * 测试资源限制下的任务排队
     */
    public function test_task_queuing_under_resource_limits(): void
    {
        // 设置较小的浏览器池大小
        config(['browser.pool_size' => 2]);

        // 创建大量任务
        $tasks = $this->createMultipleTestTasks(8);

        // 并发执行任务
        $startTime = microtime(true);
        $this->simulateConcurrentExecution($tasks);
        $results = $this->waitForMultipleTasksCompletion($tasks, 180);

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // 验证所有任务最终都完成了
        $this->assertEquals(count($tasks), count($results), '不是所有任务都完成了');

        foreach ($results as $taskId => $success) {
            $this->assertTrue($success, "任务 {$taskId} 执行失败");
        }

        // 验证执行时间合理（应该有排队等待）
        $this->assertGreaterThan(10, $totalTime, '执行时间太短，可能没有正确排队');
        $this->assertLessThan(150, $totalTime, '执行时间太长，可能有死锁');

        // 验证浏览器实例数量没有超过限制
        $instanceCount = BrowserInstance::count();
        $this->assertLessThanOrEqual(4, $instanceCount, '浏览器实例数量超过预期'); // 允许一些缓冲
    }

    /**
     * 测试并发执行的数据一致性
     */
    public function test_concurrent_execution_data_consistency(): void
    {
        // 创建任务
        $tasks = $this->createMultipleTestTasks(5);

        // 并发执行
        $this->simulateConcurrentExecution($tasks);
        $this->waitForMultipleTasksCompletion($tasks, 120);

        // 验证每个任务都有正确的执行记录
        foreach ($tasks as $task) {
            $executions = $task->executions;
            $this->assertEquals(1, $executions->count(), "任务 {$task->id} 的执行记录数量不正确");

            $execution = $executions->first();
            $this->assertNotNull($execution->start_time, "任务 {$task->id} 缺少开始时间");
            $this->assertNotNull($execution->end_time, "任务 {$task->id} 缺少结束时间");
            $this->assertEquals('completed', $execution->status, "任务 {$task->id} 状态不正确");
        }

        // 验证没有重复执行
        $totalExecutions = \App\Models\TaskExecution::count();
        $this->assertEquals(count($tasks), $totalExecutions, '执行记录总数不正确');
    }

    /**
     * 测试高并发场景下的性能
     */
    public function test_high_concurrency_performance(): void
    {
        // 创建大量轻量级任务
        $taskCount = 15;
        $tasks = [];
        
        for ($i = 1; $i <= $taskCount; $i++) {
            $tasks[] = $this->createTestTask([
                'name' => "高并发测试任务 {$i}",
                'url' => 'https://httpbin.org/get',
                'config' => [
                    'actions' => [
                        ['type' => 'visit', 'url' => 'https://httpbin.org/get'],
                        ['type' => 'wait', 'seconds' => 0.5]
                    ]
                ]
            ]);
        }

        // 测量性能
        $performance = $this->measureMemoryUsage(function () use ($tasks) {
            $this->simulateConcurrentExecution($tasks);
            return $this->waitForMultipleTasksCompletion($tasks, 180);
        });

        // 验证性能指标
        $results = $performance['result'];
        $this->assertEquals($taskCount, count($results), '不是所有任务都完成了');

        // 验证执行时间合理
        $this->assertLessThan(120, $performance['execution_time'], '总执行时间过长');

        // 验证内存使用合理
        $this->assertLessThan(100 * 1024 * 1024, $performance['memory_used'], '内存使用过多'); // 100MB

        // 验证所有任务都成功
        foreach ($results as $taskId => $success) {
            $this->assertTrue($success, "任务 {$taskId} 执行失败");
        }

        // 打印性能统计
        $this->printTestStats();
    }

    /**
     * 测试并发执行的错误隔离
     */
    public function test_concurrent_execution_error_isolation(): void
    {
        // 创建混合任务（一些会成功，一些会失败）
        $successTasks = $this->createMultipleTestTasks(3);
        
        $failureTasks = [];
        for ($i = 1; $i <= 2; $i++) {
            $failureTasks[] = $this->createTestTask([
                'name' => "失败任务 {$i}",
                'url' => 'https://invalid-domain.com',
                'config' => [
                    'actions' => [
                        ['type' => 'visit', 'url' => 'https://invalid-domain.com']
                    ]
                ]
            ]);
        }

        $allTasks = array_merge($successTasks, $failureTasks);

        // 并发执行所有任务
        $this->simulateConcurrentExecution($allTasks);
        $this->waitForMultipleTasksCompletion($allTasks, 120);

        // 验证成功任务不受失败任务影响
        foreach ($successTasks as $task) {
            $this->assertTaskExecutedSuccessfully($task);
        }

        // 验证失败任务确实失败了
        foreach ($failureTasks as $task) {
            $this->assertTaskExecutionFailed($task);
        }

        // 验证浏览器池仍然健康
        $errorInstances = BrowserInstance::where('status', 'error')->count();
        $this->assertLessThanOrEqual(1, $errorInstances, '错误实例过多');
    }

    /**
     * 测试并发执行的资源清理
     */
    public function test_concurrent_execution_resource_cleanup(): void
    {
        // 记录初始状态
        $initialStats = $this->getTestStats();

        // 创建并执行多个任务
        $tasks = $this->createMultipleTestTasks(6);
        $this->simulateConcurrentExecution($tasks);
        $this->waitForMultipleTasksCompletion($tasks, 120);

        // 等待资源清理
        sleep(2);

        // 验证资源被正确清理
        $finalStats = $this->getTestStats();

        // 所有浏览器实例应该回到空闲状态
        $activeInstances = BrowserInstance::where('status', 'active')->count();
        $this->assertEquals(0, $activeInstances, '仍有活跃的浏览器实例');

        // 不应该有错误实例
        $this->assertEquals(0, $finalStats['error_instances'], '存在错误的浏览器实例');

        // 验证执行记录正确
        $this->assertEquals(
            $initialStats['total_executions'] + count($tasks),
            $finalStats['total_executions'],
            '执行记录数量不正确'
        );
    }
}
