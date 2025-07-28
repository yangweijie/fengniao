<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Jobs\ExecuteTaskJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Cron\CronExpression;
use Carbon\Carbon;

class TaskScheduler
{
    protected DuskExecutor $duskExecutor;
    protected LogManager $logManager;

    public function __construct(DuskExecutor $duskExecutor, LogManager $logManager)
    {
        $this->duskExecutor = $duskExecutor;
        $this->logManager = $logManager;
    }

    /**
     * 检查任务是否应该运行
     */
    public function shouldRunTask(Task $task): bool
    {
        if ($task->status !== 'enabled') {
            return false;
        }

        if (!$task->cron_expression) {
            return false;
        }

        try {
            $cron = new CronExpression($task->cron_expression);
            $lastRun = $task->executions()->latest()->first()?->created_at;
            
            if (!$lastRun) {
                return true;
            }

            $nextRun = $cron->getNextRunDate($lastRun);
            return $nextRun <= now();
        } catch (\Exception $e) {
            Log::error("Invalid cron expression for task {$task->id}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * 计算下次运行时间
     */
    public function calculateNextRunTime(Task $task): ?Carbon
    {
        if (!$task->cron_expression) {
            return null;
        }

        try {
            $cron = new CronExpression($task->cron_expression);
            return Carbon::instance($cron->getNextRunDate());
        } catch (\Exception $e) {
            Log::error("Invalid cron expression for task {$task->id}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * 执行任务
     */
    public function executeTask(Task $task): TaskExecution
    {
        $execution = TaskExecution::create([
            'task_id' => $task->id,
            'status' => 'running',
            'start_time' => now(),
            'config' => $task->toArray()
        ]);

        try {
            // 分发到队列执行
            ExecuteTaskJob::dispatch($task, $execution);
            
            Log::info("Task {$task->id} dispatched to queue", [
                'task_id' => $task->id,
                'execution_id' => $execution->id
            ]);

            return $execution;
        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'end_time' => now(),
                'error_message' => $e->getMessage()
            ]);

            Log::error("Failed to dispatch task {$task->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * 同步执行任务（用于测试）
     */
    public function executeTaskSync(Task $task): TaskExecution
    {
        $execution = TaskExecution::create([
            'task_id' => $task->id,
            'status' => 'running',
            'start_time' => now(),
            'config' => $task->toArray()
        ]);

        try {
            $result = $this->duskExecutor->execute($task, $execution);
            
            $execution->update([
                'status' => 'completed',
                'end_time' => now(),
                'result' => $result
            ]);

            return $execution;
        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'end_time' => now(),
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 获取待执行的任务
     */
    public function getPendingTasks(): \Illuminate\Database\Eloquent\Collection
    {
        return Task::where('status', 'enabled')
            ->whereNotNull('cron_expression')
            ->get()
            ->filter(function ($task) {
                return $this->shouldRunTask($task);
            });
    }

    /**
     * 运行调度器
     */
    public function run(): void
    {
        $pendingTasks = $this->getPendingTasks();

        Log::info("Found {$pendingTasks->count()} pending tasks");

        foreach ($pendingTasks as $task) {
            try {
                $this->executeTask($task);
                Log::info("Scheduled task {$task->id} for execution");
            } catch (\Exception $e) {
                Log::error("Failed to schedule task {$task->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * 获取任务统计信息
     */
    public function getTaskStats(): array
    {
        return [
            'total_tasks' => Task::count(),
            'enabled_tasks' => Task::where('status', 'enabled')->count(),
            'disabled_tasks' => Task::where('status', 'disabled')->count(),
            'running_executions' => TaskExecution::where('status', 'running')->count(),
            'completed_today' => TaskExecution::where('status', 'completed')
                ->whereDate('created_at', today())
                ->count(),
            'failed_today' => TaskExecution::where('status', 'failed')
                ->whereDate('created_at', today())
                ->count(),
        ];
    }

    /**
     * 停止运行中的任务
     */
    public function stopTask(Task $task): bool
    {
        $runningExecutions = $task->executions()
            ->where('status', 'running')
            ->get();

        foreach ($runningExecutions as $execution) {
            $execution->update([
                'status' => 'cancelled',
                'end_time' => now(),
                'error_message' => 'Task stopped by user'
            ]);
        }

        return true;
    }

    /**
     * 重试失败的任务
     */
    public function retryTask(TaskExecution $execution): TaskExecution
    {
        if ($execution->status !== 'failed') {
            throw new \InvalidArgumentException('Only failed executions can be retried');
        }

        return $this->executeTask($execution->task);
    }

    /**
     * 清理旧的执行记录
     */
    public function cleanupOldExecutions(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return TaskExecution::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * 验证Cron表达式
     */
    public function validateCronExpression(string $expression): bool
    {
        try {
            new CronExpression($expression);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取Cron表达式的下几次运行时间
     */
    public function getNextRunTimes(string $cronExpression, int $count = 5): array
    {
        try {
            $cron = new CronExpression($cronExpression);
            $times = [];
            $currentTime = now();
            
            for ($i = 0; $i < $count; $i++) {
                $nextTime = $cron->getNextRunDate($currentTime);
                $times[] = Carbon::instance($nextTime);
                $currentTime = $nextTime;
            }
            
            return $times;
        } catch (\Exception $e) {
            return [];
        }
    }
}
