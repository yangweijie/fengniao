<?php

namespace App\Console\Commands;

use App\Models\Cookie;
use App\Services\CookieManager;
use Illuminate\Console\Command;

class RefreshCookies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cookies:refresh {--domain= : 指定域名} {--account= : 指定账号} {--force : 强制刷新所有Cookie}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '刷新即将过期的Cookie';

    /**
     * Execute the console command.
     */
    public function handle(CookieManager $cookieManager)
    {
        $domain = $this->option('domain');
        $account = $this->option('account');
        $force = $this->option('force');

        if ($domain && $account) {
            // 刷新指定的Cookie
            $this->info("刷新指定Cookie: {$domain} - {$account}");
            $result = $cookieManager->refreshCookies($domain, $account);

            if ($result) {
                $this->info('Cookie刷新成功');
            } else {
                $this->error('Cookie刷新失败');
            }

            return $result ? Command::SUCCESS : Command::FAILURE;
        }

        // 批量刷新
        $this->info('开始批量刷新Cookie...');

        $query = Cookie::where('is_valid', true);

        if ($force) {
            $this->info('强制刷新所有有效Cookie');
        } else {
            // 只刷新即将过期的Cookie（24小时内）
            $query->where(function ($q) {
                $q->where('expires_at', '<', now()->addHours(24))
                  ->where('expires_at', '>', now());
            });
            $this->info('刷新即将过期的Cookie（24小时内）');
        }

        $cookies = $query->get();

        if ($cookies->isEmpty()) {
            $this->info('没有需要刷新的Cookie');
            return Command::SUCCESS;
        }

        $this->info("找到 {$cookies->count()} 个需要刷新的Cookie");

        $successCount = 0;
        $failureCount = 0;

        $progressBar = $this->output->createProgressBar($cookies->count());
        $progressBar->start();

        foreach ($cookies as $cookie) {
            try {
                $result = $cookieManager->refreshCookies($cookie->domain, $cookie->account);

                if ($result) {
                    $successCount++;
                    $this->line("\n✓ {$cookie->domain}" . ($cookie->account ? " ({$cookie->account})" : ''));
                } else {
                    $failureCount++;
                    $this->line("\n✗ {$cookie->domain}" . ($cookie->account ? " ({$cookie->account})" : '') . ' - 刷新失败');
                }

            } catch (\Exception $e) {
                $failureCount++;
                $this->line("\n✗ {$cookie->domain}" . ($cookie->account ? " ({$cookie->account})" : '') . " - 异常: {$e->getMessage()}");
            }

            $progressBar->advance();

            // 避免请求过于频繁
            sleep(1);
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info("刷新完成！");
        $this->info("成功: {$successCount}");
        $this->info("失败: {$failureCount}");

        // 清理过期的Cookie
        $cleaned = $cookieManager->cleanExpiredCookies();
        if ($cleaned > 0) {
            $this->info("清理了 {$cleaned} 个过期Cookie");
        }

        return Command::SUCCESS;
    }
}
