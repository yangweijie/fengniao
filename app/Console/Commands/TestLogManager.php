<?php

namespace App\Console\Commands;

use App\Models\TaskExecution;
use App\Services\LogManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestLogManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:log-manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试日志管理器功能';

    /**
     * Execute the console command.
     */
    public function handle(LogManager $logManager)
    {
        $this->info('开始测试日志管理器功能...');

        // 创建测试执行记录（使用模拟数据）
        $execution = new TaskExecution();
        $execution->id = 999999; // 使用一个不太可能冲突的ID
        $execution->task_id = 999999;
        $execution->status = 'running';
        $execution->start_time = now();
        $execution->exists = true; // 标记为已存在，避免保存到数据库

        try {
            // 临时禁用外键约束
            DB::statement('PRAGMA foreign_keys=OFF');

            // 1. 测试日志记录
            $this->info('1. 测试日志记录功能...');

            // 直接创建日志记录，避免外键约束
            \App\Models\TaskLog::create([
                'execution_id' => 999999,
                'level' => 'info',
                'message' => '测试信息日志',
                'context' => ['test' => 'data']
            ]);

            \App\Models\TaskLog::create([
                'execution_id' => 999999,
                'level' => 'warning',
                'message' => '测试警告日志'
            ]);

            \App\Models\TaskLog::create([
                'execution_id' => 999999,
                'level' => 'error',
                'message' => '测试错误日志',
                'context' => ['error_code' => 500]
            ]);

            $this->info("✓ 创建了 3 条测试日志");

            // 2. 测试日志搜索
            $this->info('2. 测试日志搜索功能...');

            $searchResults = $logManager->searchLogs([
                'execution_id' => 999999,
                'level' => 'error'
            ]);

            $this->info("✓ 搜索到 {$searchResults->count()} 条错误日志");

            // 3. 测试统计功能
            $this->info('3. 测试统计功能...');

            $stats = $logManager->getLogStatistics(1);
            $this->table(
                ['统计项', '数值'],
                [
                    ['总日志数', $stats['total_logs']],
                    ['Info日志', $stats['by_level']['info'] ?? 0],
                    ['Warning日志', $stats['by_level']['warning'] ?? 0],
                    ['Error日志', $stats['by_level']['error'] ?? 0],
                    ['截图数量', $stats['screenshot_count']]
                ]
            );

            // 4. 测试导出功能
            $this->info('4. 测试导出功能...');

            $exportPath = $logManager->exportLogs([
                'execution_id' => 999999
            ], 'json');

            $this->info("✓ 导出文件: " . basename($exportPath));

            // 5. 测试实时日志
            $this->info('5. 测试实时日志功能...');

            $realtimeLogs = $logManager->getRealtimeLogs(999999);
            $this->info("✓ 获取到 {$realtimeLogs->count()} 条实时日志");

            // 6. 清理测试数据
            $this->info('6. 清理测试数据...');

            \App\Models\TaskLog::where('execution_id', 999999)->delete();

            if (file_exists($exportPath)) {
                unlink($exportPath);
            }

            $this->info('✓ 测试数据清理完成');

            $this->info('🎉 日志管理器功能测试完成！');

        } catch (\Exception $e) {
            $this->error("测试失败: " . $e->getMessage());

            // 清理测试数据
            \App\Models\TaskLog::where('execution_id', 999999)->delete();

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
