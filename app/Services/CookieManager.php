<?php

namespace App\Services;

use App\Models\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class CookieManager
{
    public function saveCookies(string $domain, array $cookies, ?string $account = null): void
    {
        if (empty($cookies)) {
            return;
        }
        
        Log::info("保存Cookie", [
            'domain' => $domain,
            'account' => $account,
            'cookie_count' => count($cookies)
        ]);
        
        // 过滤和处理Cookie数据
        $filteredCookies = $this->filterCookies($cookies);
        
        if (empty($filteredCookies)) {
            Log::warning("没有有效的Cookie需要保存", ['domain' => $domain]);
            return;
        }
        
        // 计算过期时间（取最早过期的Cookie时间）
        $expiresAt = $this->calculateExpiryTime($filteredCookies);
        
        // 加密Cookie数据
        $encryptedData = Crypt::encrypt($filteredCookies);
        
        // 保存或更新Cookie记录
        Cookie::updateOrCreate(
            [
                'domain' => $domain,
                'account' => $account
            ],
            [
                'cookie_data' => $encryptedData,
                'expires_at' => $expiresAt,
                'last_used_at' => now(),
                'is_valid' => true
            ]
        );
        
        Log::info("Cookie保存成功", [
            'domain' => $domain,
            'account' => $account,
            'expires_at' => $expiresAt
        ]);
    }

    public function loadCookies(string $domain, ?string $account = null): array
    {
        $cookieRecord = Cookie::findForDomain($domain, $account);
        
        if (!$cookieRecord) {
            Log::info("未找到Cookie记录", ['domain' => $domain, 'account' => $account]);
            return [];
        }
        
        if (!$cookieRecord->is_valid) {
            Log::info("Cookie记录已标记为无效", ['domain' => $domain, 'account' => $account]);
            return [];
        }
        
        if ($cookieRecord->isExpired()) {
            Log::info("Cookie已过期", [
                'domain' => $domain,
                'account' => $account,
                'expired_at' => $cookieRecord->expires_at
            ]);
            
            // 标记为无效
            $cookieRecord->markAsInvalid();
            return [];
        }
        
        try {
            // 解密Cookie数据
            $cookies = Crypt::decrypt($cookieRecord->cookie_data);
            
            // 更新最后使用时间
            $cookieRecord->markAsUsed();
            
            Log::info("Cookie加载成功", [
                'domain' => $domain,
                'account' => $account,
                'cookie_count' => count($cookies)
            ]);
            
            return $cookies;
            
        } catch (\Exception $e) {
            Log::error("Cookie解密失败", [
                'domain' => $domain,
                'account' => $account,
                'error' => $e->getMessage()
            ]);
            
            // 标记为无效
            $cookieRecord->markAsInvalid();
            return [];
        }
    }

    public function isCookieValid(string $domain, ?string $account = null): bool
    {
        $cookieRecord = Cookie::findForDomain($domain, $account);
        
        return $cookieRecord && 
               $cookieRecord->is_valid && 
               !$cookieRecord->isExpired();
    }

    public function refreshCookies(string $domain, ?string $account = null): bool
    {
        // 这个方法将在后续实现，需要重新登录获取新Cookie
        Log::info("请求刷新Cookie", ['domain' => $domain, 'account' => $account]);
        
        // 暂时返回false，表示需要手动重新登录
        return false;
    }

    public function cleanExpiredCookies(): int
    {
        $expiredCount = Cookie::where('expires_at', '<', now())
            ->orWhere('is_valid', false)
            ->count();
            
        Cookie::where('expires_at', '<', now())
            ->orWhere('is_valid', false)
            ->delete();
            
        Log::info("清理过期Cookie", ['cleaned_count' => $expiredCount]);
        
        return $expiredCount;
    }

    public function getCookiesByDomain(string $domain): array
    {
        return Cookie::where('domain', $domain)
            ->where('is_valid', true)
            ->get()
            ->map(function ($cookie) {
                return [
                    'account' => $cookie->account,
                    'expires_at' => $cookie->expires_at,
                    'last_used_at' => $cookie->last_used_at,
                    'is_expired' => $cookie->isExpired(),
                    'will_expire_soon' => $cookie->willExpireSoon()
                ];
            })
            ->toArray();
    }

    protected function filterCookies(array $cookies): array
    {
        $filtered = [];
        
        foreach ($cookies as $cookie) {
            // 跳过无效的Cookie
            if (!isset($cookie['name']) || !isset($cookie['value'])) {
                continue;
            }
            
            // 跳过临时Cookie
            if (in_array($cookie['name'], ['_ga', '_gid', '_gat', '__utma', '__utmb', '__utmc', '__utmz'])) {
                continue;
            }
            
            // 只保留重要的Cookie字段
            $filtered[] = [
                'name' => $cookie['name'],
                'value' => $cookie['value'],
                'domain' => $cookie['domain'] ?? '',
                'path' => $cookie['path'] ?? '/',
                'expiry' => $cookie['expiry'] ?? null,
                'secure' => $cookie['secure'] ?? false,
                'httpOnly' => $cookie['httpOnly'] ?? false
            ];
        }
        
        return $filtered;
    }

    protected function calculateExpiryTime(array $cookies): ?Carbon
    {
        $earliestExpiry = null;
        
        foreach ($cookies as $cookie) {
            if (isset($cookie['expiry']) && $cookie['expiry']) {
                $expiry = Carbon::createFromTimestamp($cookie['expiry']);
                
                if (!$earliestExpiry || $expiry->lt($earliestExpiry)) {
                    $earliestExpiry = $expiry;
                }
            }
        }
        
        // 如果没有设置过期时间的Cookie，默认7天后过期
        return $earliestExpiry ?: now()->addDays(7);
    }

    public function deleteCookies(string $domain, ?string $account = null): bool
    {
        $deleted = Cookie::where('domain', $domain)
            ->where('account', $account)
            ->delete();
            
        Log::info("删除Cookie", [
            'domain' => $domain,
            'account' => $account,
            'deleted' => $deleted > 0
        ]);
        
        return $deleted > 0;
    }

    public function getStatistics(): array
    {
        $total = Cookie::count();
        $valid = Cookie::where('is_valid', true)->count();
        $expired = Cookie::where('expires_at', '<', now())->count();
        $expiringSoon = Cookie::where('expires_at', '>', now())
            ->where('expires_at', '<', now()->addDays(1))
            ->count();
            
        return [
            'total_cookies' => $total,
            'valid_cookies' => $valid,
            'expired_cookies' => $expired,
            'expiring_soon' => $expiringSoon,
            'domains' => Cookie::distinct('domain')->count('domain')
        ];
    }
}
