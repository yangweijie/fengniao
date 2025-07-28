<?php

namespace App\Services;

use App\Models\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class CookieImportExportService
{
    protected CookieManager $cookieManager;

    public function __construct(CookieManager $cookieManager)
    {
        $this->cookieManager = $cookieManager;
    }

    /**
     * 导出Cookie到JSON文件
     */
    public function exportCookies(?string $domain = null): array
    {
        $query = Cookie::where('is_valid', true);
        
        if ($domain) {
            $query->where('domain', $domain);
        }
        
        $cookies = $query->get();
        
        $exportData = [];
        
        foreach ($cookies as $cookie) {
            try {
                // 解密Cookie数据
                $cookieData = Crypt::decrypt($cookie->cookie_data);
                
                $exportData[] = [
                    'domain' => $cookie->domain,
                    'account' => $cookie->account,
                    'cookies' => $cookieData,
                    'expires_at' => $cookie->expires_at?->toISOString(),
                    'exported_at' => now()->toISOString()
                ];
                
            } catch (\Exception $e) {
                Log::warning("导出Cookie失败", [
                    'domain' => $cookie->domain,
                    'account' => $cookie->account,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $exportData;
    }

    /**
     * 从JSON数据导入Cookie
     */
    public function importCookies(array $data, bool $overwrite = false): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($data as $item) {
            try {
                $domain = $item['domain'] ?? null;
                $account = $item['account'] ?? null;
                $cookies = $item['cookies'] ?? [];
                $expiresAt = isset($item['expires_at']) ? Carbon::parse($item['expires_at']) : null;
                
                if (!$domain || empty($cookies)) {
                    $errors[] = "无效的Cookie数据: 缺少域名或Cookie内容";
                    continue;
                }
                
                // 检查是否已存在
                $existing = Cookie::findForDomain($domain, $account);
                
                if ($existing && !$overwrite) {
                    $skipped++;
                    continue;
                }
                
                // 保存Cookie
                $this->cookieManager->saveCookies($domain, $cookies, $account);
                
                // 更新过期时间
                if ($expiresAt) {
                    $cookieRecord = Cookie::findForDomain($domain, $account);
                    if ($cookieRecord) {
                        $cookieRecord->update(['expires_at' => $expiresAt]);
                    }
                }
                
                $imported++;
                
            } catch (\Exception $e) {
                $errors[] = "导入失败: " . $e->getMessage();
            }
        }
        
        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * 从浏览器导出的JSON文件导入Cookie
     */
    public function importFromBrowserExport(array $browserCookies, string $domain, ?string $account = null): bool
    {
        try {
            // 转换浏览器导出格式到内部格式
            $convertedCookies = [];
            
            foreach ($browserCookies as $cookie) {
                $convertedCookies[] = [
                    'name' => $cookie['name'] ?? '',
                    'value' => $cookie['value'] ?? '',
                    'domain' => $cookie['domain'] ?? $domain,
                    'path' => $cookie['path'] ?? '/',
                    'expiry' => isset($cookie['expirationDate']) ? (int)$cookie['expirationDate'] : null,
                    'secure' => $cookie['secure'] ?? false,
                    'httpOnly' => $cookie['httpOnly'] ?? false
                ];
            }
            
            $this->cookieManager->saveCookies($domain, $convertedCookies, $account);
            
            Log::info("从浏览器导入Cookie成功", [
                'domain' => $domain,
                'account' => $account,
                'cookie_count' => count($convertedCookies)
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("从浏览器导入Cookie失败", [
                'domain' => $domain,
                'account' => $account,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 导出为浏览器可导入的格式
     */
    public function exportForBrowser(string $domain, ?string $account = null): array
    {
        $cookie = Cookie::findForDomain($domain, $account);
        
        if (!$cookie || !$cookie->is_valid) {
            return [];
        }
        
        try {
            $cookieData = Crypt::decrypt($cookie->cookie_data);
            
            $browserFormat = [];
            
            foreach ($cookieData as $item) {
                $browserFormat[] = [
                    'name' => $item['name'],
                    'value' => $item['value'],
                    'domain' => $item['domain'] ?: $domain,
                    'path' => $item['path'] ?: '/',
                    'expirationDate' => $item['expiry'] ?? null,
                    'secure' => $item['secure'] ?? false,
                    'httpOnly' => $item['httpOnly'] ?? false,
                    'sameSite' => 'no_restriction'
                ];
            }
            
            return $browserFormat;
            
        } catch (\Exception $e) {
            Log::error("导出浏览器格式Cookie失败", [
                'domain' => $domain,
                'account' => $account,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * 备份所有Cookie
     */
    public function backupAllCookies(): string
    {
        $exportData = $this->exportCookies();
        
        $filename = 'cookie_backup_' . now()->format('Y-m-d_H-i-s') . '.json';
        $path = storage_path("app/backups/{$filename}");
        
        // 确保备份目录存在
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        Log::info("Cookie备份完成", [
            'filename' => $filename,
            'cookie_count' => count($exportData)
        ]);
        
        return $path;
    }

    /**
     * 从备份文件恢复Cookie
     */
    public function restoreFromBackup(string $backupPath, bool $overwrite = false): array
    {
        if (!file_exists($backupPath)) {
            throw new \Exception("备份文件不存在: {$backupPath}");
        }
        
        $content = file_get_contents($backupPath);
        $data = json_decode($content, true);
        
        if (!$data) {
            throw new \Exception("无效的备份文件格式");
        }
        
        return $this->importCookies($data, $overwrite);
    }

    /**
     * 清理无效的Cookie备份文件
     */
    public function cleanOldBackups(int $keepDays = 30): int
    {
        $backupDir = storage_path('app/backups');
        
        if (!is_dir($backupDir)) {
            return 0;
        }
        
        $files = glob($backupDir . '/cookie_backup_*.json');
        $cleaned = 0;
        $cutoffTime = now()->subDays($keepDays);
        
        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));
            
            if ($fileTime->lt($cutoffTime)) {
                unlink($file);
                $cleaned++;
            }
        }
        
        Log::info("清理旧备份文件", [
            'cleaned_count' => $cleaned,
            'keep_days' => $keepDays
        ]);
        
        return $cleaned;
    }
}
