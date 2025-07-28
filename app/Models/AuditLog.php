<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'context',
        'performed_at'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'context' => 'array',
        'performed_at' => 'datetime'
    ];

    /**
     * 审计日志关联的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 审计日志关联的模型（多态关联）
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * 作用域：按用户筛选
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 作用域：按动作筛选
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * 作用域：按模型类型筛选
     */
    public function scopeByModelType($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * 作用域：按时间范围筛选
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    /**
     * 作用域：最近的记录
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
    }

    /**
     * 获取变更摘要
     */
    public function getChangesSummary(): array
    {
        $summary = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $summary[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }
        }

        return $summary;
    }

    /**
     * 获取用户名称
     */
    public function getUserNameAttribute(): string
    {
        return $this->user ? $this->user->name : '系统';
    }

    /**
     * 获取模型名称
     */
    public function getModelNameAttribute(): string
    {
        if (!$this->model_type) {
            return '未知';
        }

        $modelClass = class_basename($this->model_type);
        return match($modelClass) {
            'Task' => '任务',
            'User' => '用户',
            'Role' => '角色',
            'Permission' => '权限',
            'TaskExecution' => '任务执行',
            default => $modelClass
        };
    }

    /**
     * 获取动作描述
     */
    public function getActionDescriptionAttribute(): string
    {
        return match($this->action) {
            'created' => '创建',
            'updated' => '更新',
            'deleted' => '删除',
            'restored' => '恢复',
            'login' => '登录',
            'logout' => '登出',
            'role_assigned' => '分配角色',
            'role_revoked' => '撤销角色',
            'permission_granted' => '授予权限',
            'permission_revoked' => '撤销权限',
            default => $this->action
        };
    }
}
