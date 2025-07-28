<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\TaskLog;
use Illuminate\Console\Command;

class CreateDemoData extends Command
{
    protected $signature = 'demo:create-data';
    protected $description = '创建演示数据用于测试任务日志功能';

    public function handle()
    {
        $this->info('🎯 创建演示数据...');

        // 创建或获取任务
        $task = Task::first();
        if (!$task) {
            $task = Task::create([
                'name' => '演示任务',
                'type' => 'browser',
                'cron_expression' => '0 9 * * *',
                'enabled' => true,
                'script' => 'console.log("Hello World");',
                'description' => '这是一个演示任务，用于展示日志功能'
            ]);
            $this->info("✅ 演示任务已创建 (ID: {$task->id})");
        } else {
            $this->info("✅ 找到任务: {$task->name} (ID: {$task->id})");
        }

        // 创建执行记录
        $execution = TaskExecution::create([
            'task_id' => $task->id,
            'status' => 'success',
            'start_time' => now()->subMinutes(5),
            'end_time' => now(),
            'duration' => 300, // 5分钟 = 300秒
        ]);
        $this->info("✅ 执行记录已创建 (ID: {$execution->id})");

        // 创建日志记录
        $logs = [
            ['level' => 'info', 'message' => '任务开始执行', 'context' => ['step' => 1]],
            ['level' => 'info', 'message' => '正在初始化浏览器...', 'context' => ['step' => 2]],
            ['level' => 'info', 'message' => '浏览器初始化完成', 'context' => ['step' => 3, 'browser_id' => 'chrome-001']],
            ['level' => 'info', 'message' => '导航到目标页面', 'context' => ['step' => 4, 'url' => 'https://example.com']],
            ['level' => 'warning', 'message' => '页面加载较慢', 'context' => ['step' => 5, 'load_time' => 3.2]],
            ['level' => 'info', 'message' => '页面加载完成', 'context' => ['step' => 6]],
            ['level' => 'info', 'message' => '执行脚本操作', 'context' => ['step' => 7, 'action' => 'click']],
            ['level' => 'error', 'message' => '发现一个小错误', 'context' => ['step' => 8, 'error' => 'Element not found']],
            ['level' => 'info', 'message' => '错误已修复，继续执行', 'context' => ['step' => 9]],
            ['level' => 'info', 'message' => '任务执行完成', 'context' => ['step' => 10, 'result' => 'success']],
        ];

        foreach ($logs as $index => $logData) {
            TaskLog::create([
                'execution_id' => $execution->id,
                'level' => $logData['level'],
                'message' => $logData['message'],
                'context' => $logData['context'],
                'created_at' => now()->subMinutes(5)->addSeconds($index * 10),
            ]);
        }

        $this->info('✅ 已创建 ' . count($logs) . ' 条日志记录');
        
        $this->newLine();
        $this->info('🌐 访问链接:');
        $this->line("  - 任务列表: http://127.0.0.1:8005/admin/tasks");
        $this->line("  - 任务日志: http://127.0.0.1:8005/admin/tasks/{$task->id}/logs");
        $this->line("  - 登录页面: http://127.0.0.1:8005/admin/login");
        
        $this->newLine();
        $this->info('🔐 登录信息:');
        $this->line('  - 邮箱: admin@fengniao.local');
        $this->line('  - 密码: password');
        
        $this->newLine();
        $this->info('✨ 现在您可以在浏览器中访问管理界面，查看任务日志功能！');

        return 0;
    }
}
