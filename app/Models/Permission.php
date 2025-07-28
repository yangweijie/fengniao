<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'action',
        'resource',
        'is_system'
    ];

    protected $casts = [
        'is_system' => 'boolean'
    ];

    /**
     * 权限关联的角色
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * 作用域：按模块筛选
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * 作用域：按动作筛选
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * 作用域：按资源筛选
     */
    public function scopeByResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * 作用域：系统权限
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * 作用域：非系统权限
     */
    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * 获取权限的完整标识
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->module, $this->resource, $this->action]);
        return implode('.', $parts) ?: $this->name;
    }

    /**
     * 检查权限是否被任何角色使用
     */
    public function isInUse(): bool
    {
        return $this->roles()->exists();
    }

    /**
     * 获取使用此权限的角色数量
     */
    public function getRoleCountAttribute(): int
    {
        return $this->roles()->count();
    }
}
