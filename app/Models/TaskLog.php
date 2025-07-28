<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'execution_id',
        'level',
        'message',
        'context',
        'screenshot_path'
    ];

    protected $casts = [
        'context' => 'array'
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(TaskExecution::class, 'execution_id');
    }

    public function isError(): bool
    {
        return $this->level === 'error';
    }

    public function isWarning(): bool
    {
        return $this->level === 'warning';
    }

    public function hasScreenshot(): bool
    {
        return !empty($this->screenshot_path);
    }
}
