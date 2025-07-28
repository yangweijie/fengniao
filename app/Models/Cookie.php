<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cookie extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'account',
        'cookie_data',
        'expires_at',
        'last_used_at',
        'is_valid'
    ];

    protected $casts = [
        'cookie_data' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_valid' => 'boolean'
    ];

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function willExpireSoon(int $hours = 24): bool
    {
        return $this->expires_at && $this->expires_at->isBefore(now()->addHours($hours));
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function markAsInvalid(): void
    {
        $this->update(['is_valid' => false]);
    }

    public static function findForDomain(string $domain, ?string $account = null): ?self
    {
        return static::where('domain', $domain)
            ->where('account', $account)
            ->where('is_valid', true)
            ->first();
    }
}
