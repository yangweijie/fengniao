<?php

namespace Tests\Integration;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\BrowserInstance;
use App\Services\TaskScheduler;
use App\Services\DuskExecutor;

class TaskExecutionFlowTest extends IntegrationTestCase
{
    /**
     * 测试完整的任务执行流程
     */
    public function test_complete_task_execution_flow(): void
    {
        // 创建测试任务
        $task = $this->createTestTask([
            'name' => '完整流程测试任务',
            'url' => 'https://httpbin.org/get',
            'config' => [
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://httpbin.org/get'],
                    ['type' => 'wait', 'seconds' => 2],
                    ['type' => 'screenshot', 'name' => 'flow_test']
                ]
            ]
        ]);

        // 执行任务
        $this->taskScheduler->executeTask($task);

        // 等待任务完成
        $completed = $this->waitForTaskCompletion($task, 30);
        $this->assertTrue($completed, '任务执行超时');

        // 验证任务执行结果
        $this->assertTaskExecutedSuccessfully($task);

        // 验证执行记录
        $execution = $task->executions()->latest()->first();
        $this->assertNotNull($execution->start_time);
        $this->assertNotNull($execution->end_time);
        $this->assertGreaterThan(0, $execution->end_time->diffInSeconds($execution->start_time));

        // 验证日志记录
        $logs = $execution->logs;
        $this->assertGreaterThan(0, $logs->count(), '没有生成执行日志');

        // 验证截图文件
        $screenshotLogs = $logs->where('level', 'info')->where('message', 'like', '%screenshot%');
        $this->assertGreaterThan(0, $screenshotLogs->count(), '没有截图日志');
    }

    /**
     * 测试任务执行失败处理
     */
    public function test_task_execution_failure_handling(): void
    {
        // 创建会失败的任务
        $task = $this->createTestTask([
            'name' => '失败测试任务',
            'url' => 'https://invalid-domain-that-does-not-exist.com',
            'config' => [
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://invalid-domain-that-does-not-exist.com'],
                    ['type' => 'wait', 'seconds' => 1]
                ]
            ]
        ]);

        // 执行任务
        $this->taskScheduler->executeTask($task);

        // 等待任务完成（失败）
        $this->waitForTaskCompletion($task, 30);

        // 验证任务执行失败
        $this->assertTaskExecutionFailed($task);

        // 验证错误日志
        $execution = $task->executions()->latest()->first();
        $errorLogs = $execution->logs()->where('level', 'error')->get();
        $this->assertGreaterThan(0, $errorLogs->count(), '没有记录错误日志');
    }

    /**
     * 测试任务重试机制
     */
    public function test_task_retry_mechanism(): void
    {
        // 创建需要重试的任务
        $task = $this->createTestTask([
            'name' => '重试测试任务',
            'url' => 'https://httpbin.org/status/500',
            'config' => [
                'retry_attempts' => 3,
                'retry_delay' => 1,
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://httpbin.org/status/500']
                ]
            ]
        ]);

        // 执行任务
        $this->taskScheduler->executeTask($task);

        // 等待任务完成
        $this->waitForTaskCompletion($task, 60);

        // 验证重试次数
        $execution = $task->executions()->latest()->first();
        $retryLogs = $execution->logs()->where('message', 'like', '%retry%')->get();
        $this->assertGreaterThan(0, $retryLogs->count(), '没有重试日志');
    }

    /**
     * 测试任务超时处理
     */
    public function test_task_timeout_handling(): void
    {
        // 创建会超时的任务
        $task = $this->createTestTask([
            'name' => '超时测试任务',
            'url' => 'https://httpbin.org/delay/10',
            'config' => [
                'timeout' => 5, // 5秒超时
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://httpbin.org/delay/10']
                ]
            ]
        ]);

        // 执行任务
        $this->taskScheduler->executeTask($task);

        // 等待任务完成
        $this->waitForTaskCompletion($task, 30);

        // 验证任务因超时失败
        $execution = $task->executions()->latest()->first();
        $this->assertEquals('failed', $execution->status);

        // 验证超时日志
        $timeoutLogs = $execution->logs()->where('message', 'like', '%timeout%')->get();
        $this->assertGreaterThan(0, $timeoutLogs->count(), '没有超时日志');
    }

    /**
     * 测试任务调度
     */
    public function test_task_scheduling(): void
    {
        // 创建定时任务
        $task = $this->createTestTask([
            'name' => '调度测试任务',
            'schedule' => '* * * * *', // 每分钟执行
            'status' => 'active'
        ]);

        // 检查任务是否应该运行
        $shouldRun = $this->taskScheduler->shouldRunTask($task);
        $this->assertTrue($shouldRun, '任务应该被调度执行');

        // 更新任务的下次运行时间
        $nextRunTime = $this->taskScheduler->calculateNextRunTime($task);
        $this->assertNotNull($nextRunTime, '下次运行时间计算失败');
        $this->assertGreaterThan(now(), $nextRunTime, '下次运行时间应该在未来');
    }

    /**
     * 测试任务状态管理
     */
    public function test_task_status_management(): void
    {
        $task = $this->createTestTask(['status' => 'active']);

        // 测试禁用任务
        $task->update(['status' => 'inactive']);
        $shouldRun = $this->taskScheduler->shouldRunTask($task);
        $this->assertFalse($shouldRun, '禁用的任务不应该被执行');

        // 测试启用任务
        $task->update(['status' => 'active']);
        $shouldRun = $this->taskScheduler->shouldRunTask($task);
        $this->assertTrue($shouldRun, '启用的任务应该被执行');
    }

    /**
     * 测试任务执行统计
     */
    public function test_task_execution_statistics(): void
    {
        $task = $this->createTestTask();

        // 执行任务多次
        for ($i = 0; $i < 3; $i++) {
            $this->taskScheduler->executeTask($task);
            $this->waitForTaskCompletion($task, 30);
        }

        // 验证执行统计
        $executions = $task->executions;
        $this->assertEquals(3, $executions->count(), '执行次数不正确');

        $successfulExecutions = $executions->where('status', 'completed');
        $this->assertGreaterThan(0, $successfulExecutions->count(), '没有成功的执行');

        // 验证平均执行时间
        $avgDuration = $executions->avg(function ($execution) {
            return $execution->end_time ? $execution->end_time->diffInSeconds($execution->start_time) : 0;
        });
        $this->assertGreaterThan(0, $avgDuration, '平均执行时间应该大于0');
    }

    /**
     * 测试任务执行上下文
     */
    public function test_task_execution_context(): void
    {
        $task = $this->createTestTask([
            'config' => [
                'variables' => [
                    'test_var' => 'test_value',
                    'user_id' => $this->testUser->id
                ],
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://httpbin.org/get'],
                    ['type' => 'screenshot', 'name' => 'context_test']
                ]
            ]
        ]);

        // 执行任务
        $this->taskScheduler->executeTask($task);
        $this->waitForTaskCompletion($task, 30);

        // 验证执行上下文
        $execution = $task->executions()->latest()->first();
        $this->assertNotNull($execution->config);

        $config = json_decode($execution->config, true);
        $this->assertArrayHasKey('variables', $config);
        $this->assertEquals('test_value', $config['variables']['test_var']);
    }

    /**
     * 测试任务执行资源清理
     */
    public function test_task_execution_resource_cleanup(): void
    {
        $task = $this->createTestTask();

        // 记录执行前的资源状态
        $initialStats = $this->getTestStats();

        // 执行任务
        $this->taskScheduler->executeTask($task);
        $this->waitForTaskCompletion($task, 30);

        // 验证资源被正确清理
        $finalStats = $this->getTestStats();

        // 浏览器实例应该回到空闲状态
        $this->assertEquals(
            $initialStats['idle_instances'],
            $finalStats['idle_instances'],
            '浏览器实例没有正确回到空闲状态'
        );

        // 不应该有错误实例
        $this->assertEquals(0, $finalStats['error_instances'], '存在错误的浏览器实例');
    }

    /**
     * 测试任务执行日志记录
     */
    public function test_task_execution_logging(): void
    {
        $task = $this->createTestTask([
            'config' => [
                'log_level' => 'debug',
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://httpbin.org/get'],
                    ['type' => 'wait', 'seconds' => 1],
                    ['type' => 'screenshot', 'name' => 'logging_test']
                ]
            ]
        ]);

        // 执行任务
        $this->taskScheduler->executeTask($task);
        $this->waitForTaskCompletion($task, 30);

        // 验证日志记录
        $execution = $task->executions()->latest()->first();
        $logs = $execution->logs;

        // 应该有不同级别的日志
        $this->assertGreaterThan(0, $logs->where('level', 'info')->count(), '缺少info级别日志');
        $this->assertGreaterThan(0, $logs->where('level', 'debug')->count(), '缺少debug级别日志');

        // 验证日志内容
        $visitLog = $logs->where('message', 'like', '%visit%')->first();
        $this->assertNotNull($visitLog, '缺少页面访问日志');

        $screenshotLog = $logs->where('message', 'like', '%screenshot%')->first();
        $this->assertNotNull($screenshotLog, '缺少截图日志');
    }

    /**
     * 测试任务执行性能
     */
    public function test_task_execution_performance(): void
    {
        $task = $this->createTestTask([
            'name' => '性能测试任务',
            'config' => [
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://httpbin.org/get'],
                    ['type' => 'wait', 'seconds' => 1]
                ]
            ]
        ]);

        // 测量执行性能
        $performance = $this->measureMemoryUsage(function () use ($task) {
            $this->taskScheduler->executeTask($task);
            return $this->waitForTaskCompletion($task, 30);
        });

        // 验证性能指标
        $this->assertTrue($performance['result'], '任务执行失败');
        $this->assertLessThan(30, $performance['execution_time'], '任务执行时间过长');
        $this->assertLessThan(50 * 1024 * 1024, $performance['memory_used'], '内存使用过多'); // 50MB

        // 验证执行时间记录
        $execution = $task->executions()->latest()->first();
        $actualDuration = $execution->end_time->diffInSeconds($execution->start_time);
        $this->assertLessThan(25, $actualDuration, '实际执行时间过长');
    }
}
