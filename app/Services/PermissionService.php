<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    /**
     * 初始化系统权限和角色
     */
    public function initializeSystemPermissions(): void
    {
        DB::transaction(function () {
            // 创建系统权限
            $permissions = $this->getSystemPermissions();
            foreach ($permissions as $permissionData) {
                Permission::updateOrCreate(
                    ['name' => $permissionData['name']],
                    $permissionData
                );
            }

            // 创建系统角色
            $roles = $this->getSystemRoles();
            foreach ($roles as $roleData) {
                $role = Role::updateOrCreate(
                    ['name' => $roleData['name']],
                    $roleData
                );

                // 分配权限给角色
                if (isset($roleData['permissions'])) {
                    $permissionIds = Permission::whereIn('name', $roleData['permissions'])->pluck('id');
                    $role->permissions()->sync($permissionIds);
                    $role->refreshPermissionsCache();
                }
            }
        });
    }

    /**
     * 获取系统权限定义
     */
    protected function getSystemPermissions(): array
    {
        return [
            // 用户管理权限
            [
                'name' => 'users.view',
                'display_name' => '查看用户',
                'description' => '查看用户列表和详情',
                'module' => 'users',
                'action' => 'view',
                'resource' => 'user',
                'is_system' => true
            ],
            [
                'name' => 'users.create',
                'display_name' => '创建用户',
                'description' => '创建新用户',
                'module' => 'users',
                'action' => 'create',
                'resource' => 'user',
                'is_system' => true
            ],
            [
                'name' => 'users.edit',
                'display_name' => '编辑用户',
                'description' => '编辑用户信息',
                'module' => 'users',
                'action' => 'edit',
                'resource' => 'user',
                'is_system' => true
            ],
            [
                'name' => 'users.delete',
                'display_name' => '删除用户',
                'description' => '删除用户',
                'module' => 'users',
                'action' => 'delete',
                'resource' => 'user',
                'is_system' => true
            ],

            // 角色管理权限
            [
                'name' => 'roles.view',
                'display_name' => '查看角色',
                'description' => '查看角色列表和详情',
                'module' => 'roles',
                'action' => 'view',
                'resource' => 'role',
                'is_system' => true
            ],
            [
                'name' => 'roles.create',
                'display_name' => '创建角色',
                'description' => '创建新角色',
                'module' => 'roles',
                'action' => 'create',
                'resource' => 'role',
                'is_system' => true
            ],
            [
                'name' => 'roles.edit',
                'display_name' => '编辑角色',
                'description' => '编辑角色信息和权限',
                'module' => 'roles',
                'action' => 'edit',
                'resource' => 'role',
                'is_system' => true
            ],
            [
                'name' => 'roles.delete',
                'display_name' => '删除角色',
                'description' => '删除角色',
                'module' => 'roles',
                'action' => 'delete',
                'resource' => 'role',
                'is_system' => true
            ],

            // 任务管理权限
            [
                'name' => 'tasks.view',
                'display_name' => '查看任务',
                'description' => '查看任务列表和详情',
                'module' => 'tasks',
                'action' => 'view',
                'resource' => 'task',
                'is_system' => true
            ],
            [
                'name' => 'tasks.create',
                'display_name' => '创建任务',
                'description' => '创建新任务',
                'module' => 'tasks',
                'action' => 'create',
                'resource' => 'task',
                'is_system' => true
            ],
            [
                'name' => 'tasks.edit',
                'display_name' => '编辑任务',
                'description' => '编辑任务配置',
                'module' => 'tasks',
                'action' => 'edit',
                'resource' => 'task',
                'is_system' => true
            ],
            [
                'name' => 'tasks.delete',
                'display_name' => '删除任务',
                'description' => '删除任务',
                'module' => 'tasks',
                'action' => 'delete',
                'resource' => 'task',
                'is_system' => true
            ],
            [
                'name' => 'tasks.execute',
                'display_name' => '执行任务',
                'description' => '手动执行任务',
                'module' => 'tasks',
                'action' => 'execute',
                'resource' => 'task',
                'is_system' => true
            ],

            // 系统管理权限
            [
                'name' => 'system.monitor',
                'display_name' => '系统监控',
                'description' => '查看系统监控信息',
                'module' => 'system',
                'action' => 'monitor',
                'resource' => 'system',
                'is_system' => true
            ],
            [
                'name' => 'system.settings',
                'display_name' => '系统设置',
                'description' => '修改系统设置',
                'module' => 'system',
                'action' => 'settings',
                'resource' => 'system',
                'is_system' => true
            ],
            [
                'name' => 'logs.view',
                'display_name' => '查看日志',
                'description' => '查看系统日志',
                'module' => 'logs',
                'action' => 'view',
                'resource' => 'log',
                'is_system' => true
            ],
            [
                'name' => 'audit.view',
                'display_name' => '查看审计日志',
                'description' => '查看操作审计日志',
                'module' => 'audit',
                'action' => 'view',
                'resource' => 'audit_log',
                'is_system' => true
            ]
        ];
    }

    /**
     * 获取系统角色定义
     */
    protected function getSystemRoles(): array
    {
        return [
            [
                'name' => 'super_admin',
                'display_name' => '超级管理员',
                'description' => '拥有所有权限的超级管理员',
                'is_system' => true,
                'is_active' => true,
                'permissions' => [
                    'users.view', 'users.create', 'users.edit', 'users.delete',
                    'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                    'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete', 'tasks.execute',
                    'system.monitor', 'system.settings', 'logs.view', 'audit.view'
                ]
            ],
            [
                'name' => 'admin',
                'display_name' => '管理员',
                'description' => '系统管理员，拥有大部分管理权限',
                'is_system' => true,
                'is_active' => true,
                'permissions' => [
                    'users.view', 'users.create', 'users.edit',
                    'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.execute',
                    'system.monitor', 'logs.view'
                ]
            ],
            [
                'name' => 'operator',
                'display_name' => '操作员',
                'description' => '任务操作员，可以管理和执行任务',
                'is_system' => true,
                'is_active' => true,
                'permissions' => [
                    'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.execute',
                    'logs.view'
                ]
            ],
            [
                'name' => 'viewer',
                'display_name' => '查看者',
                'description' => '只读用户，只能查看信息',
                'is_system' => true,
                'is_active' => true,
                'permissions' => [
                    'tasks.view', 'logs.view'
                ]
            ]
        ];
    }

    /**
     * 检查用户权限
     */
    public function checkPermission(User $user, string $permission): bool
    {
        // 超级管理员拥有所有权限
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission($permission);
    }

    /**
     * 获取用户权限列表
     */
    public function getUserPermissions(User $user): array
    {
        $cacheKey = "user_permissions_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
            if ($user->isSuperAdmin()) {
                return Permission::pluck('name')->toArray();
            }
            
            return $user->getAllPermissions();
        });
    }

    /**
     * 清除用户权限缓存
     */
    public function clearUserPermissionsCache(User $user): void
    {
        Cache::forget("user_permissions_{$user->id}");
    }

    /**
     * 记录审计日志
     */
    public function logAudit(User $user, string $action, $model = null, array $oldValues = null, array $newValues = null, array $context = []): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
            'performed_at' => now()
        ]);
    }

    /**
     * 获取权限统计
     */
    public function getPermissionStats(): array
    {
        return [
            'total_permissions' => Permission::count(),
            'system_permissions' => Permission::system()->count(),
            'custom_permissions' => Permission::nonSystem()->count(),
            'total_roles' => Role::count(),
            'active_roles' => Role::active()->count(),
            'system_roles' => Role::system()->count(),
            'users_with_roles' => User::whereHas('roles')->count(),
            'recent_audit_logs' => AuditLog::recent()->count()
        ];
    }

    /**
     * 获取角色权限矩阵
     */
    public function getRolePermissionMatrix(): array
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        
        $matrix = [];
        
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            
            foreach ($permissions as $permission) {
                $matrix[$role->name][$permission->name] = in_array($permission->name, $rolePermissions);
            }
        }
        
        return $matrix;
    }

    /**
     * 批量分配角色
     */
    public function batchAssignRoles(array $userIds, array $roleNames, ?int $assignedBy = null): array
    {
        $results = [];
        
        DB::transaction(function () use ($userIds, $roleNames, $assignedBy, &$results) {
            foreach ($userIds as $userId) {
                try {
                    $user = User::findOrFail($userId);
                    $user->syncRoles($roleNames, $assignedBy);
                    
                    $this->logAudit(
                        User::find($assignedBy) ?? $user,
                        'role_assigned',
                        $user,
                        null,
                        ['roles' => $roleNames],
                        ['batch_operation' => true]
                    );
                    
                    $results[$userId] = ['success' => true];
                } catch (\Exception $e) {
                    $results[$userId] = ['success' => false, 'error' => $e->getMessage()];
                }
            }
        });
        
        return $results;
    }

    /**
     * 获取权限树结构
     */
    public function getPermissionTree(): array
    {
        $permissions = Permission::all();
        $tree = [];
        
        foreach ($permissions as $permission) {
            $module = $permission->module ?: 'other';
            
            if (!isset($tree[$module])) {
                $tree[$module] = [
                    'name' => $module,
                    'display_name' => ucfirst($module),
                    'permissions' => []
                ];
            }
            
            $tree[$module]['permissions'][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'description' => $permission->description
            ];
        }
        
        return array_values($tree);
    }
}
