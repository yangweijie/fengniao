<?php

namespace Tests\Integration;

use App\Models\Task;
use App\Models\User;
use App\Models\Role;
use App\Models\BrowserInstance;
use App\Services\BrowserPoolManager;
use App\Services\TaskScheduler;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $testUser;
    protected BrowserPoolManager $browserPool;
    protected TaskScheduler $taskScheduler;
    protected PermissionService $permissionService;

    protected function setUp(): void
    {
        parent::setUp();

        // 初始化服务
        $this->browserPool = app(BrowserPoolManager::class);
        $this->taskScheduler = app(TaskScheduler::class);
        $this->permissionService = app(PermissionService::class);

        // 初始化权限系统
        $this->permissionService->initializeSystemPermissions();

        // 创建测试用户
        $this->testUser = $this->createTestUser();

        // 设置测试环境
        $this->setupTestEnvironment();
    }

    protected function tearDown(): void
    {
        // 清理测试环境
        $this->cleanupTestEnvironment();

        parent::tearDown();
    }

    /**
     * 创建测试用户
     */
    protected function createTestUser(): User
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        // 分配管理员角色
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }

        return $user;
    }

    /**
     * 创建测试任务
     */
    protected function createTestTask(array $attributes = []): Task
    {
        return Task::factory()->create(array_merge([
            'name' => 'Test Task',
            'type' => 'browser',
            'url' => 'https://httpbin.org/get',
            'config' => [
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://httpbin.org/get'],
                    ['type' => 'wait', 'seconds' => 1],
                    ['type' => 'screenshot', 'name' => 'test']
                ]
            ],
            'schedule' => '*/5 * * * *',
            'status' => 'active',
            'created_by' => $this->testUser->id
        ], $attributes));
    }

    /**
     * 创建多个测试任务
     */
    protected function createMultipleTestTasks(int $count = 3): array
    {
        $tasks = [];
        for ($i = 1; $i <= $count; $i++) {
            $tasks[] = $this->createTestTask([
                'name' => "Test Task {$i}",
                'url' => "https://httpbin.org/delay/{$i}"
            ]);
        }
        return $tasks;
    }

    /**
     * 创建测试浏览器实例
     */
    protected function createTestBrowserInstance(): BrowserInstance
    {
        return BrowserInstance::create([
            'port' => $this->getAvailablePort(),
            'status' => 'idle',
            'created_at' => now(),
            'last_used_at' => now(),
            'usage_count' => 0
        ]);
    }

    /**
     * 获取可用端口
     */
    protected function getAvailablePort(): int
    {
        $basePort = 9222;
        $maxAttempts = 100;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $port = $basePort + $i;
            if ($this->isPortAvailable($port)) {
                return $port;
            }
        }

        throw new \Exception('无法找到可用端口');
    }

    /**
     * 检查端口是否可用
     */
    protected function isPortAvailable(int $port): bool
    {
        $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if ($connection) {
            fclose($connection);
            return false;
        }
        return true;
    }

    /**
     * 设置测试环境
     */
    protected function setupTestEnvironment(): void
    {
        // 设置测试配置
        config([
            'browser.pool_size' => 2,
            'browser.max_tabs_per_instance' => 3,
            'browser.instance_timeout' => 30,
            'queue.default' => 'sync', // 使用同步队列进行测试
        ]);

        // 清理现有的浏览器实例
        BrowserInstance::truncate();
    }

    /**
     * 清理测试环境
     */
    protected function cleanupTestEnvironment(): void
    {
        try {
            // 清理浏览器实例
            $instances = BrowserInstance::all();
            foreach ($instances as $instance) {
                if ($instance->port) {
                    // 尝试终止浏览器进程
                    shell_exec("pkill -f 'chrome.*--remote-debugging-port={$instance->port}' 2>/dev/null");
                }
            }
            BrowserInstance::truncate();

            // 清理测试文件
            $this->cleanupTestFiles();

        } catch (\Exception $e) {
            // 忽略清理错误
        }
    }

    /**
     * 清理测试文件
     */
    protected function cleanupTestFiles(): void
    {
        $testDirs = [
            storage_path('app/screenshots/test'),
            storage_path('app/logs/test'),
            storage_path('app/temp/test')
        ];

        foreach ($testDirs as $dir) {
            if (is_dir($dir)) {
                $this->deleteDirectory($dir);
            }
        }
    }

    /**
     * 递归删除目录
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * 等待任务执行完成
     */
    protected function waitForTaskCompletion(Task $task, int $timeoutSeconds = 60): bool
    {
        $startTime = time();
        
        while (time() - $startTime < $timeoutSeconds) {
            $execution = $task->executions()->latest()->first();
            
            if ($execution && in_array($execution->status, ['completed', 'failed'])) {
                return $execution->status === 'completed';
            }
            
            sleep(1);
        }
        
        return false;
    }

    /**
     * 等待多个任务执行完成
     */
    protected function waitForMultipleTasksCompletion(array $tasks, int $timeoutSeconds = 120): array
    {
        $results = [];
        $startTime = time();
        
        while (time() - $startTime < $timeoutSeconds && count($results) < count($tasks)) {
            foreach ($tasks as $task) {
                if (isset($results[$task->id])) {
                    continue;
                }
                
                $execution = $task->executions()->latest()->first();
                if ($execution && in_array($execution->status, ['completed', 'failed'])) {
                    $results[$task->id] = $execution->status === 'completed';
                }
            }
            
            sleep(1);
        }
        
        return $results;
    }

    /**
     * 断言任务执行成功
     */
    protected function assertTaskExecutedSuccessfully(Task $task): void
    {
        $execution = $task->executions()->latest()->first();
        
        $this->assertNotNull($execution, '任务执行记录不存在');
        $this->assertEquals('completed', $execution->status, '任务执行未成功完成');
        $this->assertNotNull($execution->end_time, '任务执行结束时间未设置');
    }

    /**
     * 断言任务执行失败
     */
    protected function assertTaskExecutionFailed(Task $task): void
    {
        $execution = $task->executions()->latest()->first();
        
        $this->assertNotNull($execution, '任务执行记录不存在');
        $this->assertEquals('failed', $execution->status, '任务执行状态不是失败');
    }

    /**
     * 断言浏览器实例状态
     */
    protected function assertBrowserInstanceStatus(BrowserInstance $instance, string $expectedStatus): void
    {
        $instance->refresh();
        $this->assertEquals($expectedStatus, $instance->status, "浏览器实例状态不匹配，期望: {$expectedStatus}, 实际: {$instance->status}");
    }

    /**
     * 断言浏览器池状态
     */
    protected function assertBrowserPoolHealth(): void
    {
        $stats = $this->browserPool->getPoolStats();
        
        $this->assertGreaterThan(0, $stats['total_instances'], '浏览器池中没有实例');
        $this->assertEquals(0, $stats['error_instances'], '浏览器池中存在错误实例');
    }

    /**
     * 模拟并发执行
     */
    protected function simulateConcurrentExecution(array $tasks): array
    {
        $results = [];
        
        // 启动所有任务
        foreach ($tasks as $task) {
            try {
                $this->taskScheduler->executeTask($task);
                $results[$task->id] = ['started' => true, 'error' => null];
            } catch (\Exception $e) {
                $results[$task->id] = ['started' => false, 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    /**
     * 获取测试统计信息
     */
    protected function getTestStats(): array
    {
        return [
            'total_tasks' => Task::count(),
            'total_executions' => \App\Models\TaskExecution::count(),
            'successful_executions' => \App\Models\TaskExecution::where('status', 'completed')->count(),
            'failed_executions' => \App\Models\TaskExecution::where('status', 'failed')->count(),
            'browser_instances' => BrowserInstance::count(),
            'active_instances' => BrowserInstance::where('status', 'active')->count(),
            'idle_instances' => BrowserInstance::where('status', 'idle')->count(),
            'error_instances' => BrowserInstance::where('status', 'error')->count(),
        ];
    }

    /**
     * 打印测试统计信息
     */
    protected function printTestStats(): void
    {
        $stats = $this->getTestStats();
        
        echo "\n=== 测试统计信息 ===\n";
        foreach ($stats as $key => $value) {
            echo sprintf("%-20s: %s\n", $key, $value);
        }
        echo "==================\n";
    }

    /**
     * 创建性能测试数据
     */
    protected function createPerformanceTestData(int $taskCount = 10): array
    {
        $tasks = [];
        
        for ($i = 1; $i <= $taskCount; $i++) {
            $tasks[] = $this->createTestTask([
                'name' => "Performance Test Task {$i}",
                'url' => 'https://httpbin.org/delay/1',
                'config' => [
                    'actions' => [
                        ['type' => 'visit', 'url' => 'https://httpbin.org/delay/1'],
                        ['type' => 'wait', 'seconds' => 1],
                        ['type' => 'screenshot', 'name' => "perf_test_{$i}"]
                    ]
                ]
            ]);
        }
        
        return $tasks;
    }

    /**
     * 测试内存使用情况
     */
    protected function measureMemoryUsage(callable $callback): array
    {
        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);
        
        $startTime = microtime(true);
        $result = $callback();
        $endTime = microtime(true);
        
        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);
        
        return [
            'result' => $result,
            'execution_time' => $endTime - $startTime,
            'memory_used' => $memoryAfter - $memoryBefore,
            'peak_memory' => $peakAfter - $peakBefore,
            'memory_before' => $memoryBefore,
            'memory_after' => $memoryAfter
        ];
    }
}
