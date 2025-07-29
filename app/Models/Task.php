<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'cron_expression',
        'script_content',
        'workflow_data',
        'domain',
        'is_exclusive',
        'debug_mode',
        'login_config',
        'env_vars',
        'notification_config'
    ];

    protected $casts = [
        'workflow_data' => 'array',
        'login_config' => 'array',
        'env_vars' => 'array',
        'notification_config' => 'array',
        'is_exclusive' => 'boolean',
        'debug_mode' => 'boolean'
    ];

    public function executions(): HasMany
    {
        return $this->hasMany(TaskExecution::class);
    }

    public function latestExecution()
    {
        return $this->hasOne(TaskExecution::class)->latestOfMany();
    }

    public function isEnabled(): bool
    {
        return $this->status === 'enabled';
    }

    public function isBrowserTask(): bool
    {
        return $this->type === 'browser';
    }

    public function isApiTask(): bool
    {
        return $this->type === 'api';
    }
}
