<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Services\DuskExecutor;
use App\Services\ApiExecutor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ExecuteTaskJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 3600; // 1小时超时
    public $tries = 3; // 最多重试3次

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Task $task
    ) {
        // 设置队列名称
        $this->onQueue($task->type === 'browser' ? 'browser' : 'api');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("开始执行任务: {$this->task->name}");

        // 查找现有的执行记录（由TaskService创建）
        $execution = TaskExecution::where('task_id', $this->task->id)
            ->where('status', 'running')
            ->whereNull('end_time')
            ->latest()
            ->first();

        if (!$execution) {
            // 如果没有找到排队中的执行记录，创建一个新的
            $execution = TaskExecution::create([
                'task_id' => $this->task->id,
                'status' => 'running',
                'start_time' => now()
            ]);
        } else {
            // 更新状态为运行中
            $execution->update([
                'status' => 'running',
                'start_time' => now()
            ]);
        }

        try {
            // 根据任务类型选择执行器
            if ($this->task->isBrowserTask()) {
                $executor = app(DuskExecutor::class);
            } else {
                $executor = app(ApiExecutor::class);
            }

            // 执行任务
            $result = $executor->execute($this->task, $execution);

            // 更新执行记录
            $execution->update([
                'status' => 'success',
                'end_time' => now(),
                'duration' => now()->diffInSeconds($execution->start_time)
            ]);

            Log::info("任务执行成功: {$this->task->name}");

        } catch (Exception $e) {
            // 记录错误
            $execution->update([
                'status' => 'failed',
                'end_time' => now(),
                'duration' => now()->diffInSeconds($execution->start_time),
                'error_message' => $e->getMessage()
            ]);

            Log::error("任务执行失败: {$this->task->name}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 重新抛出异常以触发重试机制
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("任务最终执行失败: {$this->task->name}", [
            'error' => $exception->getMessage()
        ]);

        // 这里可以发送失败通知
    }
}
