<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class ScriptConverter
{
    protected array $supportedLanguages = ['javascript', 'python'];
    protected array $conversionRules = [];
    protected array $validationRules = [];

    public function __construct()
    {
        $this->initializeConversionRules();
        $this->initializeValidationRules();
    }

    /**
     * 转换脚本到Dusk格式
     */
    public function convert(string $script, string $language): array
    {
        try {
            $language = strtolower($language);

            if (!in_array($language, $this->supportedLanguages)) {
                return [
                    'success' => false,
                    'error' => "不支持的语言: {$language}",
                    'original_script' => $script,
                    'original_language' => $language,
                    'converted_script' => '',
                    'conversion_notes' => [],
                    'warnings' => []
                ];
            }

            // 验证脚本语法
            $validation = $this->validateScript($script, $language);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => "脚本语法错误: " . implode(', ', $validation['errors']),
                    'original_script' => $script,
                    'original_language' => $language,
                    'converted_script' => '',
                    'conversion_notes' => [],
                    'warnings' => $validation['errors']
                ];
            }

            // 执行转换
            $convertedScript = $this->performConversion($script, $language);

            // 优化转换后的脚本
            $optimizedScript = $this->optimizeScript($convertedScript);

            return [
                'success' => true,
                'original_script' => $script,
                'original_language' => $language,
                'converted_script' => $optimizedScript,
                'conversion_notes' => $this->getConversionNotes($script, $language),
                'warnings' => $this->getConversionWarnings($script, $language)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'original_script' => $script,
                'original_language' => $language,
                'converted_script' => '',
                'conversion_notes' => [],
                'warnings' => []
            ];
        }
    }

    /**
     * 验证脚本语法
     */
    public function validateScript(string $script, string $language): array
    {
        $errors = [];
        $warnings = [];

        switch ($language) {
            case 'javascript':
                $errors = array_merge($errors, $this->validateJavaScript($script));
                break;
            case 'python':
                $errors = array_merge($errors, $this->validatePython($script));
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 执行脚本转换
     */
    protected function performConversion(string $script, string $language): string
    {
        switch ($language) {
            case 'javascript':
                return $this->convertJavaScript($script);
            case 'python':
                return $this->convertPython($script);
            default:
                throw new Exception("不支持的转换语言: {$language}");
        }
    }

    /**
     * 转换JavaScript脚本
     */
    protected function convertJavaScript(string $script): string
    {
        $duskScript = '';
        $lines = explode("\n", $script);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '//') === 0) {
                continue;
            }

            $convertedLine = $this->convertJavaScriptLine($line);
            if ($convertedLine) {
                $duskScript .= $convertedLine . "\n";
            }
        }

        return $duskScript;
    }

    /**
     * 转换JavaScript单行代码
     */
    protected function convertJavaScriptLine(string $line): ?string
    {
        // 点击操作
        if (preg_match('/document\.querySelector\([\'"](.+?)[\'"]\)\.click\(\)/', $line, $matches)) {
            return "\$browser->click('{$matches[1]}');";
        }

        // 输入操作
        if (preg_match('/document\.querySelector\([\'"](.+?)[\'"]\)\.value\s*=\s*[\'"](.+?)[\'"]/', $line, $matches)) {
            return "\$browser->type('{$matches[1]}', '{$matches[2]}');";
        }

        // 页面导航
        if (preg_match('/window\.location\.href\s*=\s*[\'"](.+?)[\'"]/', $line, $matches)) {
            return "\$browser->visit('{$matches[1]}');";
        }

        // 等待操作
        if (preg_match('/setTimeout\(.+?,\s*(\d+)\)/', $line, $matches)) {
            $seconds = intval($matches[1]) / 1000;
            return "\$browser->pause({$seconds});";
        }

        // 元素等待
        if (preg_match('/waitForSelector\([\'"](.+?)[\'"]\)/', $line, $matches)) {
            return "\$browser->waitFor('{$matches[1]}');";
        }

        return null;
    }

    /**
     * 转换Python脚本
     */
    protected function convertPython(string $script): string
    {
        $duskScript = '';
        $lines = explode("\n", $script);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            $convertedLine = $this->convertPythonLine($line);
            if ($convertedLine) {
                $duskScript .= $convertedLine . "\n";
            }
        }

        return $duskScript;
    }

    /**
     * 转换Python单行代码
     */
    protected function convertPythonLine(string $line): ?string
    {
        // Selenium点击操作
        if (preg_match('/driver\.find_element\(.+?,\s*[\'"](.+?)[\'"]\)\.click\(\)/', $line, $matches)) {
            return "\$browser->click('{$matches[1]}');";
        }

        // Selenium输入操作
        if (preg_match('/driver\.find_element\(.+?,\s*[\'"](.+?)[\'"]\)\.send_keys\([\'"](.+?)[\'"]\)/', $line, $matches)) {
            return "\$browser->type('{$matches[1]}', '{$matches[2]}');";
        }

        // 页面导航
        if (preg_match('/driver\.get\([\'"](.+?)[\'"]\)/', $line, $matches)) {
            return "\$browser->visit('{$matches[1]}');";
        }

        // 等待操作
        if (preg_match('/time\.sleep\((\d+(?:\.\d+)?)\)/', $line, $matches)) {
            return "\$browser->pause({$matches[1]});";
        }

        // 显式等待
        if (preg_match('/WebDriverWait.+?\.until\(.+?presence_of_element_located\(.+?,\s*[\'"](.+?)[\'"]\)/', $line, $matches)) {
            return "\$browser->waitFor('{$matches[1]}');";
        }

        return null;
    }

    /**
     * 验证JavaScript语法
     */
    protected function validateJavaScript(string $script): array
    {
        $errors = [];

        // 检查基本语法错误
        if (substr_count($script, '{') !== substr_count($script, '}')) {
            $errors[] = '大括号不匹配';
        }

        if (substr_count($script, '(') !== substr_count($script, ')')) {
            $errors[] = '小括号不匹配';
        }

        // 检查常见的语法错误
        if (preg_match('/\bfunction\s+\w+\s*\([^)]*\)\s*(?!{)/', $script)) {
            $errors[] = '函数定义缺少函数体';
        }

        return $errors;
    }

    /**
     * 验证Python语法
     */
    protected function validatePython(string $script): array
    {
        $errors = [];

        // 检查基本语法错误
        if (substr_count($script, '(') !== substr_count($script, ')')) {
            $errors[] = '小括号不匹配';
        }

        if (substr_count($script, '[') !== substr_count($script, ']')) {
            $errors[] = '方括号不匹配';
        }

        // 检查缩进问题
        $lines = explode("\n", $script);
        $indentLevel = 0;
        foreach ($lines as $lineNum => $line) {
            if (trim($line) === '') continue;
            
            $currentIndent = strlen($line) - strlen(ltrim($line));
            if (preg_match('/:\s*$/', trim($line))) {
                $indentLevel = $currentIndent + 4;
            } elseif ($currentIndent < $indentLevel && trim($line) !== '') {
                $indentLevel = $currentIndent;
            }
        }

        return $errors;
    }

    /**
     * 优化转换后的脚本
     */
    protected function optimizeScript(string $script): string
    {
        // 移除重复的空行
        $script = preg_replace('/\n\s*\n\s*\n/', "\n\n", $script);
        
        // 合并连续的相同操作
        $script = $this->mergeConsecutiveOperations($script);
        
        // 添加适当的注释
        $script = $this->addOptimizationComments($script);

        return trim($script);
    }

    /**
     * 合并连续的相同操作
     */
    protected function mergeConsecutiveOperations(string $script): string
    {
        $lines = explode("\n", $script);
        $optimized = [];
        $lastPause = null;

        foreach ($lines as $line) {
            // 合并连续的pause操作
            if (preg_match('/\$browser->pause\((\d+(?:\.\d+)?)\);/', $line, $matches)) {
                if ($lastPause !== null) {
                    $totalPause = $lastPause + floatval($matches[1]);
                    array_pop($optimized);
                    $optimized[] = "\$browser->pause({$totalPause});";
                    $lastPause = $totalPause;
                } else {
                    $optimized[] = $line;
                    $lastPause = floatval($matches[1]);
                }
            } else {
                $optimized[] = $line;
                $lastPause = null;
            }
        }

        return implode("\n", $optimized);
    }

    /**
     * 添加优化注释
     */
    protected function addOptimizationComments(string $script): string
    {
        $lines = explode("\n", $script);
        $commented = [];

        foreach ($lines as $line) {
            if (preg_match('/\$browser->visit\(/', $line)) {
                $commented[] = "// 页面导航";
                $commented[] = $line;
            } elseif (preg_match('/\$browser->click\(/', $line)) {
                if (empty($commented) || !preg_match('/\/\/ 点击操作/', end($commented))) {
                    $commented[] = "// 点击操作";
                }
                $commented[] = $line;
            } elseif (preg_match('/\$browser->type\(/', $line)) {
                if (empty($commented) || !preg_match('/\/\/ 输入操作/', end($commented))) {
                    $commented[] = "// 输入操作";
                }
                $commented[] = $line;
            } else {
                $commented[] = $line;
            }
        }

        return implode("\n", $commented);
    }

    /**
     * 获取转换说明
     */
    protected function getConversionNotes(string $script, string $language): array
    {
        $notes = [];

        switch ($language) {
            case 'javascript':
                if (strpos($script, 'querySelector') !== false) {
                    $notes[] = 'querySelector选择器已转换为Dusk选择器';
                }
                if (strpos($script, 'setTimeout') !== false) {
                    $notes[] = 'setTimeout已转换为pause操作';
                }
                break;
            case 'python':
                if (strpos($script, 'find_element') !== false) {
                    $notes[] = 'Selenium find_element已转换为Dusk选择器';
                }
                if (strpos($script, 'time.sleep') !== false) {
                    $notes[] = 'time.sleep已转换为pause操作';
                }
                break;
        }

        return $notes;
    }

    /**
     * 获取转换警告
     */
    protected function getConversionWarnings(string $script, string $language): array
    {
        $warnings = [];

        // 检查不支持的功能
        if (strpos($script, 'alert') !== false) {
            $warnings[] = 'JavaScript alert可能需要手动处理';
        }

        if (strpos($script, 'confirm') !== false) {
            $warnings[] = 'JavaScript confirm需要手动转换';
        }

        if (strpos($script, 'localStorage') !== false) {
            $warnings[] = 'localStorage操作需要手动转换';
        }

        return $warnings;
    }

    /**
     * 初始化转换规则
     */
    protected function initializeConversionRules(): void
    {
        $this->conversionRules = [
            'javascript' => [
                'click' => '/document\.querySelector\([\'"](.+?)[\'"]\)\.click\(\)/',
                'type' => '/document\.querySelector\([\'"](.+?)[\'"]\)\.value\s*=\s*[\'"](.+?)[\'"]/',
                'visit' => '/window\.location\.href\s*=\s*[\'"](.+?)[\'"]/',
                'pause' => '/setTimeout\(.+?,\s*(\d+)\)/',
                'waitFor' => '/waitForSelector\([\'"](.+?)[\'"]\)/'
            ],
            'python' => [
                'click' => '/driver\.find_element\(.+?,\s*[\'"](.+?)[\'"]\)\.click\(\)/',
                'type' => '/driver\.find_element\(.+?,\s*[\'"](.+?)[\'"]\)\.send_keys\([\'"](.+?)[\'"]\)/',
                'visit' => '/driver\.get\([\'"](.+?)[\'"]\)/',
                'pause' => '/time\.sleep\((\d+(?:\.\d+)?)\)/',
                'waitFor' => '/WebDriverWait.+?\.until\(.+?presence_of_element_located\(.+?,\s*[\'"](.+?)[\'"]\)/'
            ]
        ];
    }

    /**
     * 初始化验证规则
     */
    protected function initializeValidationRules(): void
    {
        $this->validationRules = [
            'javascript' => [
                'brackets' => true,
                'functions' => true,
                'syntax' => true
            ],
            'python' => [
                'brackets' => true,
                'indentation' => true,
                'syntax' => true
            ]
        ];
    }

    /**
     * 获取支持的语言列表
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * 批量转换脚本
     */
    public function batchConvert(array $scripts): array
    {
        $results = [];

        foreach ($scripts as $index => $scriptData) {
            try {
                $result = $this->convert($scriptData['script'], $scriptData['language']);
                $result['index'] = $index;
                $result['success'] = true;
                $results[] = $result;
            } catch (Exception $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'original_script' => $scriptData['script'],
                    'original_language' => $scriptData['language']
                ];
            }
        }

        return $results;
    }
}
