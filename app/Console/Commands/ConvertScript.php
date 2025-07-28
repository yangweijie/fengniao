<?php

namespace App\Console\Commands;

use App\Services\ScriptConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Exception;

class ConvertScript extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'script:convert
                            {input : 输入文件或目录路径}
                            {--output= : 输出文件或目录路径}
                            {--language= : 脚本语言 (javascript|python)}
                            {--format=php : 输出格式 (php|json)}
                            {--recursive : 递归处理目录}
                            {--overwrite : 覆盖已存在的文件}
                            {--dry-run : 预览模式，不实际写入文件}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '转换JavaScript或Python脚本到Laravel Dusk格式';

    protected ScriptConverter $converter;

    public function __construct(ScriptConverter $converter)
    {
        parent::__construct();
        $this->converter = $converter;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $input = $this->argument('input');
        $output = $this->option('output');
        $language = $this->option('language');
        $format = $this->option('format');
        $recursive = $this->option('recursive');
        $overwrite = $this->option('overwrite');
        $dryRun = $this->option('dry-run');

        // 验证输入路径
        if (!file_exists($input)) {
            $this->error("输入路径不存在: {$input}");
            return Command::FAILURE;
        }

        // 确定处理模式
        if (is_file($input)) {
            return $this->convertSingleFile($input, $output, $language, $format, $overwrite, $dryRun);
        } elseif (is_dir($input)) {
            return $this->convertDirectory($input, $output, $language, $format, $recursive, $overwrite, $dryRun);
        } else {
            $this->error("无效的输入路径: {$input}");
            return Command::FAILURE;
        }
    }

    /**
     * 转换单个文件
     */
    protected function convertSingleFile(string $input, ?string $output, ?string $language, string $format, bool $overwrite, bool $dryRun): int
    {
        try {
            // 自动检测语言
            if (!$language) {
                $language = $this->detectLanguage($input);
                if (!$language) {
                    $this->error("无法检测文件语言，请使用 --language 参数指定");
                    return Command::FAILURE;
                }
                $this->info("检测到语言: {$language}");
            }

            // 读取文件内容
            $script = File::get($input);

            // 执行转换
            $this->info("正在转换文件: {$input}");
            $result = $this->converter->convert($script, $language);

            // 确定输出路径
            if (!$output) {
                $output = $this->generateOutputPath($input, $format);
            }

            // 检查文件是否存在
            if (file_exists($output) && !$overwrite && !$dryRun) {
                if (!$this->confirm("文件 {$output} 已存在，是否覆盖？")) {
                    $this->info("跳过文件: {$input}");
                    return Command::SUCCESS;
                }
            }

            // 生成输出内容
            $outputContent = $this->generateOutputContent($result, $format);

            if ($dryRun) {
                $this->info("预览模式 - 将要写入到: {$output}");
                $this->line("转换结果:");
                $this->line($outputContent);
            } else {
                // 确保输出目录存在
                $outputDir = dirname($output);
                if (!is_dir($outputDir)) {
                    mkdir($outputDir, 0755, true);
                }

                // 写入文件
                File::put($output, $outputContent);
                $this->info("转换完成: {$output}");
            }

            // 显示转换信息
            $this->displayConversionInfo($result);

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("转换失败: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 转换目录
     */
    protected function convertDirectory(string $input, ?string $output, ?string $language, string $format, bool $recursive, bool $overwrite, bool $dryRun): int
    {
        // 查找脚本文件
        $files = $this->findScriptFiles($input, $recursive, $language);

        if (empty($files)) {
            $this->warn("在目录中未找到脚本文件: {$input}");
            return Command::SUCCESS;
        }

        $this->info("找到 " . count($files) . " 个文件需要转换");

        // 确定输出目录
        if (!$output) {
            $output = $input . '_converted';
        }

        $successCount = 0;
        $failureCount = 0;
        $skippedCount = 0;

        // 创建进度条
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        foreach ($files as $file) {
            try {
                // 计算相对路径
                $relativePath = str_replace($input . DIRECTORY_SEPARATOR, '', $file);
                $outputFile = $output . DIRECTORY_SEPARATOR . $this->generateOutputPath($relativePath, $format);

                // 自动检测语言
                $fileLanguage = $language ?: $this->detectLanguage($file);
                if (!$fileLanguage) {
                    $this->newLine();
                    $this->warn("跳过文件（无法检测语言）: {$file}");
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // 读取和转换文件
                $script = File::get($file);
                $result = $this->converter->convert($script, $fileLanguage);

                // 检查输出文件是否存在
                if (file_exists($outputFile) && !$overwrite && !$dryRun) {
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // 生成输出内容
                $outputContent = $this->generateOutputContent($result, $format);

                if (!$dryRun) {
                    // 确保输出目录存在
                    $outputDir = dirname($outputFile);
                    if (!is_dir($outputDir)) {
                        mkdir($outputDir, 0755, true);
                    }

                    // 写入文件
                    File::put($outputFile, $outputContent);
                }

                $successCount++;

            } catch (Exception $e) {
                $this->newLine();
                $this->error("转换失败 {$file}: " . $e->getMessage());
                $failureCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // 显示统计信息
        $this->info("转换完成！");
        $this->table(
            ['状态', '数量'],
            [
                ['成功', $successCount],
                ['失败', $failureCount],
                ['跳过', $skippedCount],
                ['总计', count($files)]
            ]
        );

        if ($dryRun) {
            $this->info("预览模式 - 文件将输出到: {$output}");
        } else {
            $this->info("输出目录: {$output}");
        }

        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * 检测文件语言
     */
    protected function detectLanguage(string $filePath): ?string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'js':
            case 'javascript':
                return 'javascript';
            case 'py':
            case 'python':
                return 'python';
            default:
                // 尝试通过文件内容检测
                $content = File::get($filePath);
                if (strpos($content, 'document.querySelector') !== false || strpos($content, 'window.') !== false) {
                    return 'javascript';
                }
                if (strpos($content, 'driver.find_element') !== false || strpos($content, 'from selenium') !== false) {
                    return 'python';
                }
                return null;
        }
    }

    /**
     * 查找脚本文件
     */
    protected function findScriptFiles(string $directory, bool $recursive, ?string $language): array
    {
        $extensions = [];

        if (!$language || $language === 'javascript') {
            $extensions = array_merge($extensions, ['js', 'javascript']);
        }

        if (!$language || $language === 'python') {
            $extensions = array_merge($extensions, ['py', 'python']);
        }

        $files = [];
        $pattern = $recursive ? '**/*' : '*';

        foreach ($extensions as $ext) {
            $globPattern = $directory . DIRECTORY_SEPARATOR . $pattern . '.' . $ext;
            $files = array_merge($files, glob($globPattern, GLOB_BRACE));
        }

        return array_unique($files);
    }

    /**
     * 生成输出路径
     */
    protected function generateOutputPath(string $inputPath, string $format): string
    {
        $pathInfo = pathinfo($inputPath);
        $baseName = $pathInfo['filename'];
        $directory = $pathInfo['dirname'] ?? '';

        $extension = $format === 'json' ? 'json' : 'php';

        $outputName = $baseName . '_converted.' . $extension;

        return $directory ? $directory . DIRECTORY_SEPARATOR . $outputName : $outputName;
    }

    /**
     * 生成输出内容
     */
    protected function generateOutputContent(array $result, string $format): string
    {
        if ($format === 'json') {
            return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        // PHP格式
        $content = "<?php\n\n";
        $content .= "// 自动转换的Dusk脚本\n";
        $content .= "// 原始语言: " . $result['original_language'] . "\n";
        $content .= "// 转换时间: " . now()->toDateTimeString() . "\n\n";

        if (!empty($result['conversion_notes'])) {
            $content .= "// 转换说明:\n";
            foreach ($result['conversion_notes'] as $note) {
                $content .= "// - {$note}\n";
            }
            $content .= "\n";
        }

        if (!empty($result['warnings'])) {
            $content .= "// 警告:\n";
            foreach ($result['warnings'] as $warning) {
                $content .= "// ⚠️ {$warning}\n";
            }
            $content .= "\n";
        }

        $content .= "use Laravel\\Dusk\\Browser;\n\n";
        $content .= "// 在Dusk测试方法中使用以下代码:\n";
        $content .= "public function testConvertedScript()\n";
        $content .= "{\n";
        $content .= "    \$this->browse(function (Browser \$browser) {\n";

        // 缩进转换后的脚本
        $lines = explode("\n", trim($result['converted_script']));
        foreach ($lines as $line) {
            if (trim($line)) {
                $content .= "        " . $line . "\n";
            }
        }

        $content .= "    });\n";
        $content .= "}\n";

        return $content;
    }

    /**
     * 显示转换信息
     */
    protected function displayConversionInfo(array $result): void
    {
        if (!empty($result['conversion_notes'])) {
            $this->info("转换说明:");
            foreach ($result['conversion_notes'] as $note) {
                $this->line("  • {$note}");
            }
        }

        if (!empty($result['warnings'])) {
            $this->warn("警告:");
            foreach ($result['warnings'] as $warning) {
                $this->line("  ⚠️ {$warning}");
            }
        }
    }
}
