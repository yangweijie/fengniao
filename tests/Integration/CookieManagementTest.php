<?php

namespace Tests\Integration;

use App\Models\Cookie;
use App\Models\Task;
use App\Services\CookieManager;

class CookieManagementTest extends IntegrationTestCase
{
    protected CookieManager $cookieManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cookieManager = app(CookieManager::class);
    }

    /**
     * 测试Cookie存储和检索
     */
    public function test_cookie_storage_and_retrieval(): void
    {
        $domain = 'example.com';
        $cookieData = [
            'name' => 'test_cookie',
            'value' => 'test_value',
            'domain' => $domain,
            'path' => '/',
            'expires' => now()->addDays(7)->timestamp,
            'secure' => false,
            'httpOnly' => true
        ];

        // 存储Cookie
        $this->cookieManager->storeCookie($domain, $cookieData);

        // 检索Cookie
        $retrievedCookies = $this->cookieManager->getCookiesForDomain($domain);
        
        $this->assertCount(1, $retrievedCookies);
        $this->assertEquals($cookieData['name'], $retrievedCookies[0]['name']);
        $this->assertEquals($cookieData['value'], $retrievedCookies[0]['value']);
    }

    /**
     * 测试Cookie有效性检测
     */
    public function test_cookie_validity_check(): void
    {
        $domain = 'example.com';
        
        // 创建有效Cookie
        $validCookie = [
            'name' => 'valid_cookie',
            'value' => 'valid_value',
            'domain' => $domain,
            'expires' => now()->addDays(1)->timestamp
        ];
        $this->cookieManager->storeCookie($domain, $validCookie);

        // 创建过期Cookie
        $expiredCookie = [
            'name' => 'expired_cookie',
            'value' => 'expired_value',
            'domain' => $domain,
            'expires' => now()->subDays(1)->timestamp
        ];
        $this->cookieManager->storeCookie($domain, $expiredCookie);

        // 检查有效性
        $validCookies = $this->cookieManager->getValidCookiesForDomain($domain);
        
        $this->assertCount(1, $validCookies);
        $this->assertEquals('valid_cookie', $validCookies[0]['name']);
    }

    /**
     * 测试Cookie自动刷新
     */
    public function test_cookie_auto_refresh(): void
    {
        $domain = 'httpbin.org';
        $task = $this->createTestTask([
            'url' => 'https://httpbin.org/cookies/set/test_cookie/test_value',
            'config' => [
                'cookie_management' => true,
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://httpbin.org/cookies/set/test_cookie/test_value'],
                    ['type' => 'wait', 'seconds' => 2],
                    ['type' => 'visit', 'url' => 'https://httpbin.org/cookies']
                ]
            ]
        ]);

        // 执行任务（应该自动收集Cookie）
        $this->taskScheduler->executeTask($task);
        $this->waitForTaskCompletion($task, 30);

        // 验证Cookie被收集
        $cookies = $this->cookieManager->getCookiesForDomain($domain);
        $this->assertGreaterThan(0, count($cookies), '没有收集到Cookie');

        // 查找测试Cookie
        $testCookie = collect($cookies)->firstWhere('name', 'test_cookie');
        $this->assertNotNull($testCookie, '没有找到测试Cookie');
        $this->assertEquals('test_value', $testCookie['value']);
    }

    /**
     * 测试多域名Cookie管理
     */
    public function test_multi_domain_cookie_management(): void
    {
        $domains = ['example.com', 'test.com', 'demo.org'];
        
        // 为每个域名存储Cookie
        foreach ($domains as $index => $domain) {
            $cookieData = [
                'name' => "cookie_{$index}",
                'value' => "value_{$index}",
                'domain' => $domain,
                'expires' => now()->addDays(1)->timestamp
            ];
            $this->cookieManager->storeCookie($domain, $cookieData);
        }

        // 验证每个域名的Cookie独立存储
        foreach ($domains as $index => $domain) {
            $cookies = $this->cookieManager->getCookiesForDomain($domain);
            $this->assertCount(1, $cookies);
            $this->assertEquals("cookie_{$index}", $cookies[0]['name']);
        }

        // 验证跨域名不会混淆
        $allCookies = $this->cookieManager->getAllCookies();
        $this->assertCount(3, $allCookies);
    }

    /**
     * 测试Cookie清理功能
     */
    public function test_cookie_cleanup(): void
    {
        $domain = 'cleanup-test.com';
        
        // 创建多个Cookie，包括过期的
        $cookies = [
            [
                'name' => 'valid_cookie',
                'value' => 'valid_value',
                'domain' => $domain,
                'expires' => now()->addDays(1)->timestamp
            ],
            [
                'name' => 'expired_cookie_1',
                'value' => 'expired_value_1',
                'domain' => $domain,
                'expires' => now()->subDays(1)->timestamp
            ],
            [
                'name' => 'expired_cookie_2',
                'value' => 'expired_value_2',
                'domain' => $domain,
                'expires' => now()->subDays(2)->timestamp
            ]
        ];

        foreach ($cookies as $cookie) {
            $this->cookieManager->storeCookie($domain, $cookie);
        }

        // 执行清理
        $cleanedCount = $this->cookieManager->cleanupExpiredCookies();
        
        $this->assertEquals(2, $cleanedCount, '清理的过期Cookie数量不正确');

        // 验证只剩下有效Cookie
        $remainingCookies = $this->cookieManager->getCookiesForDomain($domain);
        $this->assertCount(1, $remainingCookies);
        $this->assertEquals('valid_cookie', $remainingCookies[0]['name']);
    }

    /**
     * 测试Cookie在任务执行中的使用
     */
    public function test_cookie_usage_in_task_execution(): void
    {
        $domain = 'httpbin.org';
        
        // 预先存储Cookie
        $cookieData = [
            'name' => 'auth_token',
            'value' => 'test_auth_token_123',
            'domain' => $domain,
            'path' => '/',
            'expires' => now()->addDays(1)->timestamp
        ];
        $this->cookieManager->storeCookie($domain, $cookieData);

        // 创建使用Cookie的任务
        $task = $this->createTestTask([
            'url' => 'https://httpbin.org/cookies',
            'config' => [
                'use_cookies' => true,
                'actions' => [
                    ['type' => 'visit', 'url' => 'https://httpbin.org/cookies'],
                    ['type' => 'wait', 'seconds' => 2]
                ]
            ]
        ]);

        // 执行任务
        $this->taskScheduler->executeTask($task);
        $this->waitForTaskCompletion($task, 30);

        // 验证任务成功执行
        $this->assertTaskExecutedSuccessfully($task);

        // 验证Cookie被正确使用（通过日志检查）
        $execution = $task->executions()->latest()->first();
        $logs = $execution->logs;
        
        $cookieLog = $logs->where('message', 'like', '%cookie%')->first();
        $this->assertNotNull($cookieLog, '没有找到Cookie相关日志');
    }

    /**
     * 测试Cookie同步机制
     */
    public function test_cookie_synchronization(): void
    {
        $domain = 'sync-test.com';
        
        // 模拟从浏览器获取的Cookie
        $browserCookies = [
            [
                'name' => 'session_id',
                'value' => 'abc123',
                'domain' => $domain,
                'expires' => now()->addHours(2)->timestamp
            ],
            [
                'name' => 'user_pref',
                'value' => 'dark_mode',
                'domain' => $domain,
                'expires' => now()->addDays(30)->timestamp
            ]
        ];

        // 同步Cookie
        $this->cookieManager->syncCookiesFromBrowser($domain, $browserCookies);

        // 验证Cookie被正确同步
        $storedCookies = $this->cookieManager->getCookiesForDomain($domain);
        $this->assertCount(2, $storedCookies);

        $sessionCookie = collect($storedCookies)->firstWhere('name', 'session_id');
        $this->assertNotNull($sessionCookie);
        $this->assertEquals('abc123', $sessionCookie['value']);

        // 测试更新现有Cookie
        $updatedBrowserCookies = [
            [
                'name' => 'session_id',
                'value' => 'xyz789', // 更新值
                'domain' => $domain,
                'expires' => now()->addHours(2)->timestamp
            ]
        ];

        $this->cookieManager->syncCookiesFromBrowser($domain, $updatedBrowserCookies);

        // 验证Cookie值被更新
        $updatedCookies = $this->cookieManager->getCookiesForDomain($domain);
        $updatedSessionCookie = collect($updatedCookies)->firstWhere('name', 'session_id');
        $this->assertEquals('xyz789', $updatedSessionCookie['value']);
    }

    /**
     * 测试Cookie导出和导入
     */
    public function test_cookie_export_and_import(): void
    {
        $domain = 'export-test.com';
        
        // 创建测试Cookie
        $originalCookies = [
            [
                'name' => 'cookie1',
                'value' => 'value1',
                'domain' => $domain,
                'expires' => now()->addDays(1)->timestamp
            ],
            [
                'name' => 'cookie2',
                'value' => 'value2',
                'domain' => $domain,
                'expires' => now()->addDays(2)->timestamp
            ]
        ];

        foreach ($originalCookies as $cookie) {
            $this->cookieManager->storeCookie($domain, $cookie);
        }

        // 导出Cookie
        $exportedData = $this->cookieManager->exportCookies($domain);
        $this->assertIsArray($exportedData);
        $this->assertCount(2, $exportedData);

        // 清除现有Cookie
        $this->cookieManager->clearCookiesForDomain($domain);
        $this->assertCount(0, $this->cookieManager->getCookiesForDomain($domain));

        // 导入Cookie
        $this->cookieManager->importCookies($domain, $exportedData);

        // 验证导入成功
        $importedCookies = $this->cookieManager->getCookiesForDomain($domain);
        $this->assertCount(2, $importedCookies);

        foreach ($originalCookies as $originalCookie) {
            $importedCookie = collect($importedCookies)->firstWhere('name', $originalCookie['name']);
            $this->assertNotNull($importedCookie);
            $this->assertEquals($originalCookie['value'], $importedCookie['value']);
        }
    }

    /**
     * 测试Cookie安全性
     */
    public function test_cookie_security(): void
    {
        $domain = 'secure-test.com';
        
        // 创建包含敏感信息的Cookie
        $secureCookie = [
            'name' => 'auth_token',
            'value' => 'sensitive_auth_token_12345',
            'domain' => $domain,
            'secure' => true,
            'httpOnly' => true,
            'expires' => now()->addHours(1)->timestamp
        ];

        $this->cookieManager->storeCookie($domain, $secureCookie);

        // 验证Cookie被正确存储
        $storedCookies = $this->cookieManager->getCookiesForDomain($domain);
        $this->assertCount(1, $storedCookies);

        $storedCookie = $storedCookies[0];
        $this->assertTrue($storedCookie['secure'], 'Secure标志未正确设置');
        $this->assertTrue($storedCookie['httpOnly'], 'HttpOnly标志未正确设置');

        // 测试Cookie值加密（如果实现了加密功能）
        $cookieModel = Cookie::where('domain', $domain)->where('name', 'auth_token')->first();
        $this->assertNotNull($cookieModel);
        
        // 验证存储的值不是明文（如果实现了加密）
        if (method_exists($this->cookieManager, 'isEncrypted')) {
            $this->assertTrue($this->cookieManager->isEncrypted($cookieModel->value));
        }
    }

    /**
     * 测试Cookie统计和监控
     */
    public function test_cookie_statistics_and_monitoring(): void
    {
        $domains = ['stats1.com', 'stats2.com', 'stats3.com'];
        
        // 为每个域名创建不同数量的Cookie
        foreach ($domains as $index => $domain) {
            $cookieCount = $index + 1;
            for ($i = 0; $i < $cookieCount; $i++) {
                $cookieData = [
                    'name' => "cookie_{$i}",
                    'value' => "value_{$i}",
                    'domain' => $domain,
                    'expires' => now()->addDays(1)->timestamp
                ];
                $this->cookieManager->storeCookie($domain, $cookieData);
            }
        }

        // 获取统计信息
        $stats = $this->cookieManager->getCookieStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_cookies', $stats);
        $this->assertArrayHasKey('domains_count', $stats);
        $this->assertArrayHasKey('expired_cookies', $stats);

        $this->assertEquals(6, $stats['total_cookies']); // 1+2+3 = 6
        $this->assertEquals(3, $stats['domains_count']);
        $this->assertEquals(0, $stats['expired_cookies']);

        // 测试按域名统计
        $domainStats = $this->cookieManager->getCookieStatisticsByDomain();
        $this->assertCount(3, $domainStats);
        
        foreach ($domains as $index => $domain) {
            $this->assertEquals($index + 1, $domainStats[$domain]);
        }
    }

    /**
     * 测试Cookie性能
     */
    public function test_cookie_performance(): void
    {
        $domain = 'performance-test.com';
        $cookieCount = 100;

        // 测量批量存储性能
        $storagePerformance = $this->measureMemoryUsage(function () use ($domain, $cookieCount) {
            for ($i = 0; $i < $cookieCount; $i++) {
                $cookieData = [
                    'name' => "perf_cookie_{$i}",
                    'value' => "perf_value_{$i}",
                    'domain' => $domain,
                    'expires' => now()->addDays(1)->timestamp
                ];
                $this->cookieManager->storeCookie($domain, $cookieData);
            }
        });

        // 验证存储性能
        $this->assertLessThan(5, $storagePerformance['execution_time'], 'Cookie存储性能过慢');
        $this->assertLessThan(10 * 1024 * 1024, $storagePerformance['memory_used'], 'Cookie存储内存使用过多');

        // 测量检索性能
        $retrievalPerformance = $this->measureMemoryUsage(function () use ($domain) {
            return $this->cookieManager->getCookiesForDomain($domain);
        });

        // 验证检索性能
        $this->assertLessThan(1, $retrievalPerformance['execution_time'], 'Cookie检索性能过慢');
        $this->assertCount($cookieCount, $retrievalPerformance['result'], 'Cookie检索数量不正确');

        // 测量清理性能
        $cleanupPerformance = $this->measureMemoryUsage(function () {
            return $this->cookieManager->cleanupExpiredCookies();
        });

        $this->assertLessThan(2, $cleanupPerformance['execution_time'], 'Cookie清理性能过慢');
    }
}
