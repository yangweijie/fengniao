<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'status',
        'start_time',
        'end_time',
        'duration',
        'browser_instance_id',
        'tab_id',
        'error_message',
        'screenshots'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'screenshots' => 'array'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class, 'execution_id');
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
