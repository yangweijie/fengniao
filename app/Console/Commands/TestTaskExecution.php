<?php

namespace App\Console\Commands;

use App\Services\TaskService;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\TaskLog;
use Illuminate\Console\Command;

class TestTaskExecution extends Command
{
    protected $signature = 'task:test-execution {id=1}';
    protected $description = '测试任务执行系统';

    public function handle()
    {
        $taskId = $this->argument('id');
        
        $this->info("🚀 测试任务执行系统");
        $this->line("==================");
        
        try {
            // 获取TaskService
            $taskService = app(TaskService::class);
            
            // 执行任务
            $this->info("📋 执行任务ID: {$taskId}");
            $execution = $taskService->executeTask($taskId);
            
            $this->info("✅ 任务已提交到队列");
            $this->line("   执行记录ID: {$execution->id}");
            $this->line("   初始状态: {$execution->status}");
            $this->line("   开始时间: {$execution->start_time}");
            
            // 等待几秒钟让队列处理
            $this->info("⏳ 等待队列处理任务...");
            sleep(8);
            
            // 刷新执行记录
            $execution->refresh();
            
            $this->info("📊 执行结果:");
            $this->line("   状态: {$execution->status}");
            $this->line("   开始时间: {$execution->start_time}");
            $this->line("   结束时间: " . ($execution->end_time ?: '未完成'));
            $this->line("   持续时间: " . ($execution->duration ?: '未知') . " 秒");
            
            if ($execution->error_message) {
                $this->error("   错误信息: {$execution->error_message}");
            }
            
            // 查看任务日志
            $this->info("📝 任务日志:");
            $logs = TaskLog::where('execution_id', $execution->id)
                ->orderBy('created_at')
                ->get();
            
            if ($logs->count() > 0) {
                foreach ($logs as $log) {
                    $time = $log->created_at->format('H:i:s');
                    $this->line("   [{$time}] [{$log->level}] {$log->message}");
                    if ($log->context && !empty($log->context)) {
                        $this->line("       上下文: " . json_encode($log->context, JSON_UNESCAPED_UNICODE));
                    }
                }
            } else {
                $this->warn("   暂无日志记录");
            }
            
            // 检查队列状态
            $this->info("🔍 队列状态检查:");
            $queueJobs = \DB::table('jobs')->count();
            $this->line("   待处理队列任务: {$queueJobs}");
            
            $failedJobs = \DB::table('failed_jobs')->count();
            $this->line("   失败队列任务: {$failedJobs}");
            
            if ($failedJobs > 0) {
                $this->error("❌ 失败的队列任务:");
                $failed = \DB::table('failed_jobs')->latest()->first();
                if ($failed) {
                    $this->line("   异常: " . substr($failed->exception, 0, 200) . "...");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ 执行失败: " . $e->getMessage());
            $this->line("   堆栈跟踪: " . $e->getTraceAsString());
        }
        
        $this->info("🎯 建议:");
        $this->line("1. 确保队列worker正在运行: php artisan queue:work");
        $this->line("2. 检查Laravel日志: tail -f storage/logs/laravel.log");
        $this->line("3. 在管理界面查看任务日志: http://127.0.0.1:8005/admin/tasks/{$taskId}/logs");
        
        $this->info("✨ 测试完成!");
        
        return 0;
    }
}
