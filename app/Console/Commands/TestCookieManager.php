<?php

namespace App\Console\Commands;

use App\Services\CookieManager;
use App\Services\CookieImportExportService;
use Illuminate\Console\Command;

class TestCookieManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cookie-manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试Cookie管理功能';

    /**
     * Execute the console command.
     */
    public function handle(CookieManager $cookieManager, CookieImportExportService $importExport)
    {
        $this->info('开始测试Cookie管理功能...');

        // 测试数据
        $testDomain = 'example.com';
        $testAccount = 'test_user';
        $testCookies = [
            [
                'name' => 'session_id',
                'value' => 'abc123def456',
                'domain' => $testDomain,
                'path' => '/',
                'expiry' => time() + 3600,
                'secure' => true,
                'httpOnly' => true
            ],
            [
                'name' => 'user_token',
                'value' => 'xyz789uvw012',
                'domain' => $testDomain,
                'path' => '/',
                'expiry' => time() + 7200,
                'secure' => false,
                'httpOnly' => false
            ]
        ];

        try {
            // 1. 测试保存Cookie
            $this->info('1. 测试保存Cookie...');
            $cookieManager->saveCookies($testDomain, $testCookies, $testAccount);
            $this->info('✓ Cookie保存成功');

            // 2. 测试加载Cookie
            $this->info('2. 测试加载Cookie...');
            $loadedCookies = $cookieManager->loadCookies($testDomain, $testAccount);
            $this->info("✓ 加载了 " . count($loadedCookies) . " 个Cookie");

            // 3. 测试Cookie有效性检查
            $this->info('3. 测试Cookie有效性...');
            $isValid = $cookieManager->isCookieValid($testDomain, $testAccount);
            $this->info($isValid ? '✓ Cookie有效' : '✗ Cookie无效');

            // 4. 测试统计信息
            $this->info('4. 测试统计信息...');
            $stats = $cookieManager->getStatistics();
            $this->table(
                ['统计项', '数值'],
                [
                    ['总Cookie数', $stats['total_cookies']],
                    ['有效Cookie', $stats['valid_cookies']],
                    ['过期Cookie', $stats['expired_cookies']],
                    ['即将过期', $stats['expiring_soon']],
                    ['管理域名', $stats['domains']]
                ]
            );

            // 5. 测试导出功能
            $this->info('5. 测试导出功能...');
            $exportData = $importExport->exportCookies($testDomain);
            $this->info("✓ 导出了 " . count($exportData) . " 个域名的Cookie");

            // 6. 测试浏览器格式导出
            $this->info('6. 测试浏览器格式导出...');
            $browserFormat = $importExport->exportForBrowser($testDomain, $testAccount);
            $this->info("✓ 导出了 " . count($browserFormat) . " 个浏览器格式Cookie");

            // 7. 测试备份功能
            $this->info('7. 测试备份功能...');
            $backupPath = $importExport->backupAllCookies();
            $this->info("✓ 备份文件创建: " . basename($backupPath));

            // 8. 测试按域名查询
            $this->info('8. 测试按域名查询...');
            $domainCookies = $cookieManager->getCookiesByDomain($testDomain);
            $this->info("✓ 找到 " . count($domainCookies) . " 个该域名的Cookie记录");

            // 9. 清理测试数据
            $this->info('9. 清理测试数据...');
            $cookieManager->deleteCookies($testDomain, $testAccount);
            $this->info('✓ 测试数据清理完成');

            $this->info('🎉 Cookie管理功能测试完成！');

        } catch (\Exception $e) {
            $this->error("测试失败: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
