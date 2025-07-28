<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
        'is_active',
        'permissions_cache'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'permissions_cache' => 'array'
    ];

    /**
     * 角色关联的权限
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * 角色关联的用户
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('assigned_at', 'assigned_by')->withTimestamps();
    }

    /**
     * 检查角色是否有指定权限
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * 检查角色是否有任一权限
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * 检查角色是否有所有权限
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $rolePermissions = $this->permissions()->pluck('name')->toArray();
        return empty(array_diff($permissions, $rolePermissions));
    }

    /**
     * 给角色分配权限
     */
    public function givePermissionTo(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching([$permission->id]);
        $this->refreshPermissionsCache();
    }

    /**
     * 撤销角色权限
     */
    public function revokePermissionTo(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
        $this->refreshPermissionsCache();
    }

    /**
     * 同步角色权限
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = [];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permissionModel = Permission::where('name', $permission)->first();
                if ($permissionModel) {
                    $permissionIds[] = $permissionModel->id;
                }
            } elseif ($permission instanceof Permission) {
                $permissionIds[] = $permission->id;
            }
        }

        $this->permissions()->sync($permissionIds);
        $this->refreshPermissionsCache();
    }

    /**
     * 刷新权限缓存
     */
    public function refreshPermissionsCache(): void
    {
        $permissions = $this->permissions()->pluck('name')->toArray();
        $this->update(['permissions_cache' => $permissions]);
    }

    /**
     * 获取权限缓存
     */
    public function getCachedPermissions(): array
    {
        return $this->permissions_cache ?? [];
    }

    /**
     * 作用域：活跃角色
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 作用域：非系统角色
     */
    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * 作用域：系统角色
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
