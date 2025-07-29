<?php

namespace Tests;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DuskMacroTest extends DuskTestCase
{
    /**
     * 测试基础宏功能
     */
    public function testBasicMacros()
    {
        $this->browse(function (Browser $browser) {
            // 测试访问一个简单页面
            $browser->visit('https://httpbin.org/html')
                    ->waitForPageLoad();
            
            // 测试智能点击
            if ($browser->hasElement('h1')) {
                $text = $browser->waitAndGetText('h1');
                $this->assertNotEmpty($text);
            }
            
            // 测试截图功能
            $browser->screenshotWithTimestamp('macro_test');
            
            // 测试随机等待
            $browser->randomPause(100, 500);
        });
    }

    /**
     * 测试表单相关宏
     */
    public function testFormMacros()
    {
        $this->browse(function (Browser $browser) {
            // 访问表单测试页面
            $browser->visit('https://httpbin.org/forms/post')
                    ->waitForPageLoad();
            
            // 测试智能表单填写
            $browser->fillForm([
                'input[name="custname"]' => '测试用户',
                'input[name="custtel"]' => '13800138000',
                'input[name="custemail"]' => 'test@example.com',
                'select[name="size"]' => ['value' => 'medium']
            ]);
            
            // 测试智能输入
            $browser->smartType('textarea[name="comments"]', '这是一个测试评论');
            
            // 截图验证
            $browser->screenshotWithTimestamp('form_filled');
        });
    }

    /**
     * 测试等待相关宏
     */
    public function testWaitingMacros()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://httpbin.org/delay/2')
                    ->waitForPageLoad();
            
            // 测试等待任意元素
            $browser->waitForAnyElement(['body', 'html'], 10);
            
            // 测试元素存在检查
            $hasBody = $browser->hasElement('body');
            $this->assertTrue($hasBody);
            
            // 测试条件点击
            $browser->clickIfExists('non-existent-element');
        });
    }

    /**
     * 测试实用工具宏
     */
    public function testUtilityMacros()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://httpbin.org/html')
                    ->waitForPageLoad();
            
            // 测试获取属性
            if ($browser->hasElement('h1')) {
                $tagName = $browser->getAttribute('h1', 'tagName');
                $this->assertEquals('H1', strtoupper($tagName));
            }
            
            // 测试获取所有文本
            $allTexts = $browser->getAllText('p');
            $this->assertIsArray($allTexts);
            
            // 测试性能监控
            $metrics = $browser->measurePageLoad();
            $this->assertArrayHasKey('load_time_ms', $metrics);
            $this->assertArrayHasKey('timestamp', $metrics);
        });
    }

    /**
     * 测试错误处理
     */
    public function testErrorHandling()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://httpbin.org/html')
                    ->waitForPageLoad();
            
            // 测试智能点击不存在的元素
            try {
                $browser->smartClick('non-existent-element');
                $this->fail('应该抛出异常');
            } catch (\Exception $e) {
                $this->assertStringContains('无法找到可点击的元素', $e->getMessage());
            }
            
            // 测试条件操作
            $result = $browser->clickIfExists('non-existent-element');
            $this->assertFalse($result);
        });
    }
}
