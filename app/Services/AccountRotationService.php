<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Services\CookieManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AccountRotationService
{
    protected CookieManager $cookieManager;

    public function __construct(CookieManager $cookieManager)
    {
        $this->cookieManager = $cookieManager;
    }

    /**
     * 获取下一个可用账号
     */
    public function getNextAccount(Task $task): ?array
    {
        $accounts = $this->getTaskAccounts($task);
        
        if (empty($accounts)) {
            return null;
        }

        // 如果只有一个账号，直接返回
        if (count($accounts) === 1) {
            return $accounts[0];
        }

        // 获取轮换状态
        $rotationKey = "account_rotation_task_{$task->id}";
        $currentIndex = Cache::get($rotationKey, 0);

        // 查找下一个可用账号
        $maxAttempts = count($accounts);
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $account = $accounts[$currentIndex];
            
            // 检查账号是否可用
            if ($this->isAccountAvailable($task, $account)) {
                // 更新轮换索引
                $nextIndex = ($currentIndex + 1) % count($accounts);
                Cache::put($rotationKey, $nextIndex, now()->addHours(24));
                
                Log::info("选择账号进行轮换", [
                    'task_id' => $task->id,
                    'account' => $account['username'],
                    'index' => $currentIndex
                ]);
                
                return $account;
            }

            // 尝试下一个账号
            $currentIndex = ($currentIndex + 1) % count($accounts);
            $attempts++;
        }

        Log::warning("没有找到可用账号", ['task_id' => $task->id]);
        return null;
    }

    /**
     * 获取任务的所有账号配置
     */
    protected function getTaskAccounts(Task $task): array
    {
        $loginConfig = $task->login_config ?? [];
        
        // 检查是否配置了多账号
        if (isset($loginConfig['accounts']) && is_array($loginConfig['accounts'])) {
            return $loginConfig['accounts'];
        }

        // 兼容单账号配置
        if (isset($loginConfig['username_env']) && isset($loginConfig['password_env'])) {
            $username = $this->getEnvValue($task, $loginConfig['username_env']);
            $password = $this->getEnvValue($task, $loginConfig['password_env']);
            
            if ($username && $password) {
                return [[
                    'username' => $username,
                    'password' => $password,
                    'username_env' => $loginConfig['username_env'],
                    'password_env' => $loginConfig['password_env']
                ]];
            }
        }

        return [];
    }

    /**
     * 检查账号是否可用
     */
    protected function isAccountAvailable(Task $task, array $account): bool
    {
        $username = $account['username'];
        
        // 检查账号是否被锁定
        $lockKey = "account_locked_{$task->domain}_{$username}";
        if (Cache::has($lockKey)) {
            Log::info("账号被锁定，跳过", [
                'domain' => $task->domain,
                'username' => $username
            ]);
            return false;
        }

        // 检查账号使用频率限制
        $usageKey = "account_usage_{$task->domain}_{$username}";
        $usageCount = Cache::get($usageKey, 0);
        $maxUsagePerHour = $account['max_usage_per_hour'] ?? 10;
        
        if ($usageCount >= $maxUsagePerHour) {
            Log::info("账号使用频率超限，跳过", [
                'domain' => $task->domain,
                'username' => $username,
                'usage_count' => $usageCount,
                'max_usage' => $maxUsagePerHour
            ]);
            return false;
        }

        // 检查Cookie有效性
        if ($task->domain) {
            $isValid = $this->cookieManager->isCookieValid($task->domain, $username);
            if (!$isValid) {
                Log::info("账号Cookie无效", [
                    'domain' => $task->domain,
                    'username' => $username
                ]);
                // Cookie无效不代表账号不可用，可能需要重新登录
            }
        }

        return true;
    }

    /**
     * 记录账号使用
     */
    public function recordAccountUsage(Task $task, array $account): void
    {
        $username = $account['username'];
        $usageKey = "account_usage_{$task->domain}_{$username}";
        
        // 增加使用计数
        $currentCount = Cache::get($usageKey, 0);
        Cache::put($usageKey, $currentCount + 1, now()->addHour());
        
        Log::info("记录账号使用", [
            'domain' => $task->domain,
            'username' => $username,
            'usage_count' => $currentCount + 1
        ]);
    }

    /**
     * 锁定账号
     */
    public function lockAccount(Task $task, array $account, int $lockMinutes = 60, string $reason = ''): void
    {
        $username = $account['username'];
        $lockKey = "account_locked_{$task->domain}_{$username}";
        
        Cache::put($lockKey, [
            'locked_at' => now(),
            'reason' => $reason,
            'task_id' => $task->id
        ], now()->addMinutes($lockMinutes));
        
        Log::warning("账号已锁定", [
            'domain' => $task->domain,
            'username' => $username,
            'lock_minutes' => $lockMinutes,
            'reason' => $reason
        ]);
    }

    /**
     * 解锁账号
     */
    public function unlockAccount(Task $task, array $account): void
    {
        $username = $account['username'];
        $lockKey = "account_locked_{$task->domain}_{$username}";
        
        Cache::forget($lockKey);
        
        Log::info("账号已解锁", [
            'domain' => $task->domain,
            'username' => $username
        ]);
    }

    /**
     * 获取账号状态
     */
    public function getAccountStatus(Task $task): array
    {
        $accounts = $this->getTaskAccounts($task);
        $status = [];

        foreach ($accounts as $account) {
            $username = $account['username'];
            
            // 检查锁定状态
            $lockKey = "account_locked_{$task->domain}_{$username}";
            $lockInfo = Cache::get($lockKey);
            
            // 检查使用计数
            $usageKey = "account_usage_{$task->domain}_{$username}";
            $usageCount = Cache::get($usageKey, 0);
            
            // 检查Cookie状态
            $cookieValid = false;
            if ($task->domain) {
                $cookieValid = $this->cookieManager->isCookieValid($task->domain, $username);
            }

            $status[] = [
                'username' => $username,
                'is_locked' => !empty($lockInfo),
                'lock_info' => $lockInfo,
                'usage_count' => $usageCount,
                'cookie_valid' => $cookieValid,
                'is_available' => $this->isAccountAvailable($task, $account)
            ];
        }

        return $status;
    }

    /**
     * 重置账号轮换状态
     */
    public function resetRotation(Task $task): void
    {
        $rotationKey = "account_rotation_task_{$task->id}";
        Cache::forget($rotationKey);
        
        Log::info("重置账号轮换状态", ['task_id' => $task->id]);
    }

    /**
     * 清理过期的使用记录
     */
    public function cleanExpiredUsageRecords(): int
    {
        // 这个方法需要根据具体的缓存实现来清理
        // 由于使用了TTL，记录会自动过期
        Log::info("清理过期的账号使用记录");
        return 0;
    }

    /**
     * 获取环境变量值
     */
    protected function getEnvValue(Task $task, string $envKey): ?string
    {
        // 优先从任务环境变量获取
        if (isset($task->env_vars[$envKey])) {
            return $task->env_vars[$envKey];
        }
        
        // 从系统环境变量获取
        return env($envKey);
    }

    /**
     * 批量检查账号状态
     */
    public function checkAllAccountsHealth(Task $task): array
    {
        $accounts = $this->getTaskAccounts($task);
        $healthReport = [
            'total_accounts' => count($accounts),
            'available_accounts' => 0,
            'locked_accounts' => 0,
            'high_usage_accounts' => 0,
            'invalid_cookie_accounts' => 0
        ];

        foreach ($accounts as $account) {
            $username = $account['username'];
            
            if ($this->isAccountAvailable($task, $account)) {
                $healthReport['available_accounts']++;
            }
            
            $lockKey = "account_locked_{$task->domain}_{$username}";
            if (Cache::has($lockKey)) {
                $healthReport['locked_accounts']++;
            }
            
            $usageKey = "account_usage_{$task->domain}_{$username}";
            $usageCount = Cache::get($usageKey, 0);
            $maxUsage = $account['max_usage_per_hour'] ?? 10;
            
            if ($usageCount >= $maxUsage * 0.8) { // 80%阈值
                $healthReport['high_usage_accounts']++;
            }
            
            if ($task->domain && !$this->cookieManager->isCookieValid($task->domain, $username)) {
                $healthReport['invalid_cookie_accounts']++;
            }
        }

        return $healthReport;
    }
}
