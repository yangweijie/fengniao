<?php

namespace Tests\Feature;

use App\Services\ScriptConverter;
use Tests\TestCase;

class ScriptConverterTest extends TestCase
{
    protected ScriptConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new ScriptConverter();
    }

    public function test_javascript_click_conversion(): void
    {
        $script = 'document.querySelector("#button").click();';
        $result = $this->converter->convert($script, 'javascript');

        $this->assertStringContainsString('$browser->click(\'#button\');', $result['converted_script']);
        $this->assertTrue(is_array($result['conversion_notes']));
    }

    public function test_javascript_input_conversion(): void
    {
        $script = 'document.querySelector("#input").value = "test";';
        $result = $this->converter->convert($script, 'javascript');

        $this->assertStringContainsString('$browser->type(\'#input\', \'test\');', $result['converted_script']);
    }

    public function test_python_selenium_conversion(): void
    {
        $script = 'driver.find_element(By.ID, "button").click();';
        $result = $this->converter->convert($script, 'python');

        $this->assertStringContainsString('$browser->click(\'button\');', $result['converted_script']);
    }

    public function test_script_validation(): void
    {
        $invalidScript = 'document.querySelector("#test").click(';

        $result = $this->converter->convert($invalidScript, 'javascript');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('语法错误', $result['error']);
    }

    public function test_unsupported_language(): void
    {
        $result = $this->converter->convert('test script', 'ruby');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('不支持的语言', $result['error']);
    }

    public function test_batch_conversion(): void
    {
        $scripts = [
            ['script' => 'document.querySelector("#btn1").click();', 'language' => 'javascript'],
            ['script' => 'driver.find_element(By.ID, "btn2").click();', 'language' => 'python']
        ];

        $results = $this->converter->batchConvert($scripts);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertTrue($results[1]['success']);
    }

    public function test_script_optimization(): void
    {
        $script = "setTimeout(function(){}, 1000);\nsetTimeout(function(){}, 2000);";
        $result = $this->converter->convert($script, 'javascript');

        // 应该合并连续的pause操作
        $this->assertStringContainsString('$browser->pause(3);', $result['converted_script']);
    }

    public function test_conversion_warnings(): void
    {
        $script = 'alert("test"); localStorage.setItem("key", "value");';
        $result = $this->converter->convert($script, 'javascript');

        $this->assertNotEmpty($result['warnings']);
        $this->assertContains('JavaScript alert可能需要手动处理', $result['warnings']);
    }
}
