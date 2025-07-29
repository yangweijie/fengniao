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
        'screenshot_path',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'context' => 'array'
    ];

    /**
     * 指示模型是否应该自动维护时间戳
     */
    public $timestamps = true;

    /**
     * 获取created_at属性，保持微秒精度
     */
    public function getCreatedAtAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // 如果值包含微秒，使用createFromFormat解析
        if (preg_match('/\.\d{6}/', $value)) {
            try {
                return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s.u', $value);
            } catch (\Exception $e) {
                // 如果解析失败，回退到默认处理
                return $this->asDateTime($value);
            }
        }

        return $this->asDateTime($value);
    }

    /**
     * 获取updated_at属性，保持微秒精度
     */
    public function getUpdatedAtAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // 如果值包含微秒，使用createFromFormat解析
        if (preg_match('/\.\d{6}/', $value)) {
            try {
                return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s.u', $value);
            } catch (\Exception $e) {
                // 如果解析失败，回退到默认处理
                return $this->asDateTime($value);
            }
        }

        return $this->asDateTime($value);
    }

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
