<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BrowserInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'status',
        'primary_domain',
        'is_exclusive',
        'active_tabs',
        'resource_usage',
        'last_activity_at'
    ];

    protected $casts = [
        'active_tabs' => 'array',
        'resource_usage' => 'array',
        'is_exclusive' => 'boolean',
        'last_activity_at' => 'datetime'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function isIdle(): bool
    {
        return $this->status === 'idle';
    }

    public function isBusy(): bool
    {
        return $this->status === 'busy';
    }

    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    public function getActiveTabCount(): int
    {
        return count($this->active_tabs ?? []);
    }

    public function canAcceptNewTab(): bool
    {
        return !$this->is_exclusive && $this->isIdle() && $this->getActiveTabCount() < 5;
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
