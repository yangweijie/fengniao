<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Console\Command;

class ManagePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:manage
                            {action : 操作类型 (init|assign|revoke|list|stats|matrix)}
                            {--user= : 用户ID或邮箱}
                            {--role= : 角色名称}
                            {--permission= : 权限名称}
                            {--force : 强制执行操作}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '权限管理命令 - 初始化权限、分配角色、查看权限信息';

    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        parent::__construct();
        $this->permissionService = $permissionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        return match($action) {
            'init' => $this->initializePermissions(),
            'assign' => $this->assignRole(),
            'revoke' => $this->revokeRole(),
            'list' => $this->listPermissions(),
            'stats' => $this->showStats(),
            'matrix' => $this->showMatrix(),
            default => $this->error("不支持的操作: {$action}")
        };
    }

    /**
     * 初始化系统权限和角色
     */
    protected function initializePermissions(): int
    {
        $this->info('🔧 初始化系统权限和角色');

        if (!$this->option('force') && !$this->confirm('这将重置所有系统权限和角色，确认继续？')) {
            $this->info('操作已取消');
            return Command::SUCCESS;
        }

        try {
            $this->permissionService->initializeSystemPermissions();
            $this->info('✅ 系统权限和角色初始化完成');

            // 显示统计信息
            $stats = $this->permissionService->getPermissionStats();
            $this->table(
                ['项目', '数量'],
                [
                    ['权限总数', $stats['total_permissions']],
                    ['系统权限', $stats['system_permissions']],
                    ['角色总数', $stats['total_roles']],
                    ['活跃角色', $stats['active_roles']]
                ]
            );

        } catch (\Exception $e) {
            $this->error('❌ 初始化失败: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 分配角色给用户
     */
    protected function assignRole(): int
    {
        $userInput = $this->option('user');
        $roleName = $this->option('role');

        if (!$userInput) {
            $userInput = $this->ask('请输入用户ID或邮箱');
        }

        if (!$roleName) {
            $roles = Role::active()->pluck('display_name', 'name')->toArray();
            $roleName = $this->choice('请选择角色', $roles);
        }

        try {
            // 查找用户
            $user = is_numeric($userInput)
                ? User::findOrFail($userInput)
                : User::where('email', $userInput)->firstOrFail();

            // 查找角色
            $role = Role::where('name', $roleName)->firstOrFail();

            $this->info("准备为用户 '{$user->name}' ({$user->email}) 分配角色 '{$role->display_name}'");

            if ($user->hasRole($roleName)) {
                $this->warn('用户已经拥有此角色');
                return Command::SUCCESS;
            }

            if ($this->confirm('确认分配此角色？')) {
                $user->assignRole($role);
                $this->permissionService->logAudit($user, 'role_assigned', $user, null, ['role' => $roleName]);
                $this->info('✅ 角色分配成功');

                // 显示用户当前角色
                $this->displayUserRoles($user);
            }

        } catch (\Exception $e) {
            $this->error('❌ 角色分配失败: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 撤销用户角色
     */
    protected function revokeRole(): int
    {
        $userInput = $this->option('user');
        $roleName = $this->option('role');

        if (!$userInput) {
            $userInput = $this->ask('请输入用户ID或邮箱');
        }

        try {
            // 查找用户
            $user = is_numeric($userInput)
                ? User::findOrFail($userInput)
                : User::where('email', $userInput)->firstOrFail();

            if (!$roleName) {
                $userRoles = $user->roles()->pluck('display_name', 'name')->toArray();
                if (empty($userRoles)) {
                    $this->info('用户没有任何角色');
                    return Command::SUCCESS;
                }
                $roleName = $this->choice('请选择要撤销的角色', $userRoles);
            }

            $role = Role::where('name', $roleName)->firstOrFail();

            $this->info("准备撤销用户 '{$user->name}' ({$user->email}) 的角色 '{$role->display_name}'");

            if (!$user->hasRole($roleName)) {
                $this->warn('用户没有此角色');
                return Command::SUCCESS;
            }

            if ($this->confirm('确认撤销此角色？')) {
                $user->removeRole($role);
                $this->permissionService->logAudit($user, 'role_revoked', $user, ['role' => $roleName], null);
                $this->info('✅ 角色撤销成功');

                // 显示用户当前角色
                $this->displayUserRoles($user);
            }

        } catch (\Exception $e) {
            $this->error('❌ 角色撤销失败: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * 列出权限信息
     */
    protected function listPermissions(): int
    {
        $this->info('📋 系统权限列表');

        $tree = $this->permissionService->getPermissionTree();

        foreach ($tree as $module) {
            $this->newLine();
            $this->info("模块: {$module['display_name']}");
            $this->line(str_repeat('-', 50));

            $permissions = collect($module['permissions'])->map(function ($permission) {
                return [
                    $permission['name'],
                    $permission['display_name'],
                    $permission['description'] ?? 'N/A'
                ];
            })->toArray();

            $this->table(['权限名称', '显示名称', '描述'], $permissions);
        }

        return Command::SUCCESS;
    }

    /**
     * 显示权限统计
     */
    protected function showStats(): int
    {
        $this->info('📊 权限系统统计');

        $stats = $this->permissionService->getPermissionStats();

        $this->table(
            ['统计项', '数量'],
            [
                ['权限总数', $stats['total_permissions']],
                ['系统权限', $stats['system_permissions']],
                ['自定义权限', $stats['custom_permissions']],
                ['角色总数', $stats['total_roles']],
                ['活跃角色', $stats['active_roles']],
                ['系统角色', $stats['system_roles']],
                ['拥有角色的用户', $stats['users_with_roles']],
                ['最近审计日志', $stats['recent_audit_logs']]
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * 显示角色权限矩阵
     */
    protected function showMatrix(): int
    {
        $this->info('🔐 角色权限矩阵');

        $matrix = $this->permissionService->getRolePermissionMatrix();
        $permissions = Permission::pluck('display_name', 'name')->toArray();

        if (empty($matrix)) {
            $this->warn('没有找到角色权限数据');
            return Command::SUCCESS;
        }

        // 构建表格数据
        $headers = ['权限'];
        $roleNames = array_keys($matrix);

        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $headers[] = $role ? $role->display_name : $roleName;
        }

        $rows = [];
        foreach ($permissions as $permissionName => $displayName) {
            $row = [$displayName];

            foreach ($roleNames as $roleName) {
                $hasPermission = $matrix[$roleName][$permissionName] ?? false;
                $row[] = $hasPermission ? '✓' : '✗';
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }

    /**
     * 显示用户角色信息
     */
    protected function displayUserRoles(User $user): void
    {
        $this->newLine();
        $this->info("用户 '{$user->name}' 的角色信息:");

        $roles = $user->roles()->get();

        if ($roles->isEmpty()) {
            $this->line('该用户没有任何角色');
            return;
        }

        $roleData = $roles->map(function ($role) {
            return [
                $role->display_name,
                $role->name,
                $role->is_system ? '是' : '否',
                $role->is_active ? '活跃' : '禁用'
            ];
        })->toArray();

        $this->table(['角色名称', '角色标识', '系统角色', '状态'], $roleData);

        // 显示权限
        $permissions = $user->getAllPermissions();
        if (!empty($permissions)) {
            $this->newLine();
            $this->info('拥有的权限:');
            foreach ($permissions as $permission) {
                $permissionModel = Permission::where('name', $permission)->first();
                $displayName = $permissionModel ? $permissionModel->display_name : $permission;
                $this->line("  • {$displayName} ({$permission})");
            }
        }
    }
}
