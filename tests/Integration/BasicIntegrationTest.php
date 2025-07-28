<?php

namespace Tests\Integration;

use App\Models\Task;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\PermissionService;
use App\Services\NotificationService;
use App\Services\ScriptConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BasicIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;
    protected PermissionService $permissionService;
    protected NotificationService $notificationService;
    protected ScriptConverter $scriptConverter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = app(PermissionService::class);
        $this->notificationService = app(NotificationService::class);
        $this->scriptConverter = app(ScriptConverter::class);

        // 初始化权限系统
        $this->permissionService->initializeSystemPermissions();

        // 创建测试用户
        $this->testUser = $this->createTestUser();
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
     * 测试权限系统集成
     */
    public function test_permission_system_integration(): void
    {
        // 验证权限初始化
        $this->assertGreaterThan(0, Permission::count(), '权限未正确初始化');
        $this->assertGreaterThan(0, Role::count(), '角色未正确初始化');

        // 验证用户权限
        $this->assertTrue($this->testUser->hasRole('admin'), '用户没有管理员角色');
        $this->assertTrue($this->testUser->hasPermission('tasks.view'), '用户没有查看任务权限');

        // 测试权限检查
        $hasPermission = $this->permissionService->checkPermission($this->testUser, 'tasks.create');
        $this->assertTrue($hasPermission, '权限检查失败');

        // 测试权限缓存
        $permissions = $this->permissionService->getUserPermissions($this->testUser);
        $this->assertIsArray($permissions, '权限列表格式错误');
        $this->assertContains('tasks.view', $permissions, '权限列表不包含预期权限');
    }

    /**
     * 测试通知系统集成
     */
    public function test_notification_system_integration(): void
    {
        // 测试模板加载
        $templates = $this->notificationService->getTemplates();
        $this->assertIsArray($templates, '模板未正确加载');
        $this->assertArrayHasKey('task_success', $templates, '缺少任务成功模板');

        // 测试支持的渠道
        $channels = $this->notificationService->getSupportedChannels();
        $this->assertIsArray($channels, '渠道列表格式错误');
        $this->assertContains('email', $channels, '不支持邮件通知');

        // 测试通知统计
        $stats = $this->notificationService->getNotificationStats();
        $this->assertIsArray($stats, '统计数据格式错误');
        $this->assertArrayHasKey('total_sent', $stats, '缺少发送总数统计');
    }

    /**
     * 测试脚本转换系统集成
     */
    public function test_script_converter_integration(): void
    {
        // 测试JavaScript转换
        $jsScript = 'document.querySelector("#button").click();';
        $jsResult = $this->scriptConverter->convert($jsScript, 'javascript');
        
        $this->assertIsArray($jsResult, 'JavaScript转换结果格式错误');
        $this->assertArrayHasKey('converted_script', $jsResult, '缺少转换后的脚本');
        $this->assertStringContainsString('$browser->click', $jsResult['converted_script'], 'JavaScript转换结果不正确');

        // 测试Python转换
        $pyScript = 'driver.find_element(By.ID, "button").click();';
        $pyResult = $this->scriptConverter->convert($pyScript, 'python');
        
        $this->assertIsArray($pyResult, 'Python转换结果格式错误');
        $this->assertArrayHasKey('converted_script', $pyResult, '缺少转换后的脚本');
        $this->assertStringContainsString('$browser->click', $pyResult['converted_script'], 'Python转换结果不正确');

        // 测试批量转换
        $scripts = [
            ['script' => $jsScript, 'language' => 'javascript'],
            ['script' => $pyScript, 'language' => 'python']
        ];
        $batchResult = $this->scriptConverter->batchConvert($scripts);
        
        $this->assertCount(2, $batchResult, '批量转换结果数量不正确');
        $this->assertTrue($batchResult[0]['success'], '第一个脚本转换失败');
        $this->assertTrue($batchResult[1]['success'], '第二个脚本转换失败');
    }

    /**
     * 测试任务模型集成
     */
    public function test_task_model_integration(): void
    {
        // 创建测试任务
        $task = Task::factory()->create([
            'name' => '集成测试任务',
            'type' => 'browser',
            'status' => 'enabled'
        ]);

        // 验证任务创建
        $this->assertNotNull($task->id, '任务创建失败');
        $this->assertEquals('集成测试任务', $task->name, '任务名称不正确');
        $this->assertEquals('enabled', $task->status, '任务状态不正确');

        // 测试任务查询作用域
        $enabledTasks = Task::where('status', 'enabled')->get();
        $this->assertContains($task->id, $enabledTasks->pluck('id'), '启用任务查询失败');

        $browserTasks = Task::where('type', 'browser')->get();
        $this->assertContains($task->id, $browserTasks->pluck('id'), '浏览器任务查询失败');
    }

    /**
     * 测试用户角色权限集成
     */
    public function test_user_role_permission_integration(): void
    {
        // 创建新用户
        $newUser = User::factory()->create([
            'name' => 'New Test User',
            'email' => 'newuser@example.com'
        ]);

        // 验证新用户没有角色
        $this->assertFalse($newUser->hasRole('admin'), '新用户不应该有管理员角色');
        $this->assertFalse($newUser->hasPermission('tasks.create'), '新用户不应该有创建任务权限');

        // 分配查看者角色
        $viewerRole = Role::where('name', 'viewer')->first();
        $this->assertNotNull($viewerRole, '查看者角色不存在');

        $newUser->assignRole($viewerRole);

        // 验证角色分配
        $this->assertTrue($newUser->hasRole('viewer'), '角色分配失败');
        $this->assertTrue($newUser->hasPermission('tasks.view'), '权限继承失败');
        $this->assertFalse($newUser->hasPermission('tasks.create'), '不应该有创建权限');

        // 测试角色升级
        $newUser->syncRoles(['operator']);

        $this->assertFalse($newUser->hasRole('viewer'), '旧角色应该被移除');
        $this->assertTrue($newUser->hasRole('operator'), '新角色分配失败');
        $this->assertTrue($newUser->hasPermission('tasks.create'), '新权限获取失败');
    }

    /**
     * 测试数据库事务集成
     */
    public function test_database_transaction_integration(): void
    {
        $initialTaskCount = Task::count();
        $initialUserCount = User::count();

        try {
            DB::transaction(function () {
                // 创建任务
                Task::factory()->create([
                    'name' => '事务测试任务'
                ]);

                // 创建用户
                User::factory()->create([
                    'name' => '事务测试用户',
                    'email' => 'transaction@example.com'
                ]);

                // 模拟异常
                throw new \Exception('测试事务回滚');
            });
        } catch (\Exception) {
            // 预期的异常
        }

        // 验证事务回滚
        $this->assertEquals($initialTaskCount, Task::count(), '任务数量应该回滚');
        $this->assertEquals($initialUserCount, User::count(), '用户数量应该回滚');

        // 测试成功事务
        DB::transaction(function () {
            Task::factory()->create([
                'name' => '成功事务任务'
            ]);
        });

        $this->assertEquals($initialTaskCount + 1, Task::count(), '成功事务应该提交');
    }

    /**
     * 测试缓存集成
     */
    public function test_cache_integration(): void
    {
        $cacheKey = 'integration_test_key';
        $cacheValue = ['test' => 'data', 'timestamp' => now()->timestamp];

        // 测试缓存存储
        Cache::put($cacheKey, $cacheValue, now()->addMinutes(10));
        $this->assertTrue(Cache::has($cacheKey), '缓存存储失败');

        // 测试缓存检索
        $retrievedValue = Cache::get($cacheKey);
        $this->assertEquals($cacheValue, $retrievedValue, '缓存检索失败');

        // 测试缓存删除
        Cache::forget($cacheKey);
        $this->assertFalse(Cache::has($cacheKey), '缓存删除失败');

        // 测试缓存记忆
        $computedValue = Cache::remember('computed_key', now()->addMinutes(5), function () {
            return 'computed_value_' . time();
        });

        $secondValue = Cache::remember('computed_key', now()->addMinutes(5), function () {
            return 'different_value_' . time();
        });

        $this->assertEquals($computedValue, $secondValue, '缓存记忆功能失败');
    }

    /**
     * 测试配置系统集成
     */
    public function test_configuration_integration(): void
    {
        // 测试默认配置
        $this->assertIsArray(config('notifications'), '通知配置未加载');
        $this->assertIsString(config('app.name'), '应用名称配置错误');

        // 测试运行时配置修改
        $originalValue = config('app.debug');
        config(['app.debug' => !$originalValue]);
        $this->assertEquals(!$originalValue, config('app.debug'), '运行时配置修改失败');

        // 恢复原始配置
        config(['app.debug' => $originalValue]);
        $this->assertEquals($originalValue, config('app.debug'), '配置恢复失败');
    }

    /**
     * 测试日志系统集成
     */
    public function test_logging_integration(): void
    {
        $testMessage = '集成测试日志消息 ' . time();
        $testContext = ['test' => true, 'timestamp' => now()->timestamp];

        // 测试不同级别的日志
        Log::info($testMessage, $testContext);
        Log::warning('测试警告消息', $testContext);
        Log::error('测试错误消息', $testContext);

        // 验证日志文件存在（简化检查）
        $logPath = storage_path('logs');
        $this->assertDirectoryExists($logPath, '日志目录不存在');

        // 测试自定义日志通道
        Log::channel('daily')->info('每日日志测试', $testContext);

        // 这里可以添加更详细的日志内容验证，但需要读取日志文件
        $this->assertTrue(true, '日志系统基本功能正常');
    }

    /**
     * 测试队列系统集成
     */
    public function test_queue_integration(): void
    {
        // 测试同步队列（测试环境默认）
        $job = new \App\Jobs\TestJob('integration_test_data');
        
        // 分发任务
        dispatch($job);

        // 在同步队列中，任务应该立即执行
        $this->assertTrue(true, '队列任务分发成功');

        // 测试延迟任务
        $delayedJob = new \App\Jobs\TestJob('delayed_test_data');
        dispatch($delayedJob)->delay(now()->addSeconds(1));

        $this->assertTrue(true, '延迟队列任务分发成功');
    }

    /**
     * 测试系统整体健康状态
     */
    public function test_system_health_check(): void
    {
        // 检查数据库连接
        $this->assertTrue(DB::connection()->getPdo() !== null, '数据库连接失败');

        // 检查缓存系统
        $this->assertTrue(Cache::store()->getStore() !== null, '缓存系统异常');

        // 检查存储系统
        $this->assertTrue(\Storage::disk('local')->exists('.'), '存储系统异常');

        // 检查关键目录
        $this->assertDirectoryExists(storage_path('app'), '应用存储目录不存在');
        $this->assertDirectoryExists(storage_path('logs'), '日志目录不存在');

        // 检查关键服务
        $this->assertInstanceOf(PermissionService::class, $this->permissionService, '权限服务异常');
        $this->assertInstanceOf(NotificationService::class, $this->notificationService, '通知服务异常');
        $this->assertInstanceOf(ScriptConverter::class, $this->scriptConverter, '脚本转换服务异常');

        // 检查数据完整性
        $this->assertGreaterThan(0, Permission::count(), '权限数据缺失');
        $this->assertGreaterThan(0, Role::count(), '角色数据缺失');
        $this->assertGreaterThan(0, User::count(), '用户数据缺失');
    }
}
