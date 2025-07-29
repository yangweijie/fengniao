<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExecution;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Support\Collection;

class TaskService
{
    public function createTask(array $data): Task
    {
        // 验证Cron表达式
        if (!CronExpression::isValidExpression($data['cron_expression'])) {
            throw new \InvalidArgumentException('Invalid cron expression');
        }

        return Task::create($data);
    }

    public function updateTask(int $id, array $data): Task
    {
        $task = Task::findOrFail($id);
        
        // 验证Cron表达式
        if (isset($data['cron_expression']) && !CronExpression::isValidExpression($data['cron_expression'])) {
            throw new \InvalidArgumentException('Invalid cron expression');
        }

        $task->update($data);
        return $task->fresh();
    }

    public function deleteTask(int $id): bool
    {
        $task = Task::findOrFail($id);
        return $task->delete();
    }

    public function toggleTaskStatus(int $id): bool
    {
        $task = Task::findOrFail($id);
        $newStatus = $task->status === 'enabled' ? 'disabled' : 'enabled';
        
        return $task->update(['status' => $newStatus]);
    }

    public function getNextRunTime(Task $task): ?Carbon
    {
        if (!$task->isEnabled()) {
            return null;
        }

        try {
            $cron = new CronExpression($task->cron_expression);
            return Carbon::instance($cron->getNextRunDate());
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getTasksWithNextRunTime(): Collection
    {
        return Task::where('status', 'enabled')
            ->get()
            ->map(function ($task) {
                $task->next_run_time = $this->getNextRunTime($task);
                return $task;
            });
    }

    public function executeTask(int $id): TaskExecution
    {
        $task = Task::findOrFail($id);

        // 检查是否已有运行中的执行记录
        $runningExecution = TaskExecution::where('task_id', $task->id)
            ->where('status', 'running')
            ->whereNull('end_time')
            ->first();

        if ($runningExecution) {
            throw new \Exception("任务正在执行中，请等待当前执行完成。执行ID: {$runningExecution->id}");
        }

        // 创建执行记录
        $execution = TaskExecution::create([
            'task_id' => $task->id,
            'status' => 'running',
            'start_time' => now()
        ]);

        // 分发队列任务
        \App\Jobs\ExecuteTaskJob::dispatch($task);

        return $execution;
    }

    public function stopTask(int $id): bool
    {
        $task = Task::findOrFail($id);

        // 查找运行中的执行记录
        $runningExecutions = TaskExecution::where('task_id', $task->id)
            ->where('status', 'running')
            ->whereNull('end_time')
            ->get();

        if ($runningExecutions->isEmpty()) {
            throw new \Exception("没有找到正在运行的任务执行记录");
        }

        // 停止所有运行中的执行
        foreach ($runningExecutions as $execution) {
            $execution->update([
                'status' => 'cancelled',
                'end_time' => now(),
                'error_message' => '任务被用户手动停止'
            ]);
        }

        return true;
    }

    public function getTaskExecutionHistory(int $taskId, int $limit = 10): Collection
    {
        return TaskExecution::where('task_id', $taskId)
            ->with('logs')
            ->orderBy('start_time', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getTaskStatistics(): array
    {
        $totalTasks = Task::count();
        $enabledTasks = Task::where('status', 'enabled')->count();
        $browserTasks = Task::where('type', 'browser')->count();
        $apiTasks = Task::where('type', 'api')->count();
        
        $recentExecutions = TaskExecution::where('start_time', '>=', now()->subDays(7))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total_tasks' => $totalTasks,
            'enabled_tasks' => $enabledTasks,
            'disabled_tasks' => $totalTasks - $enabledTasks,
            'browser_tasks' => $browserTasks,
            'api_tasks' => $apiTasks,
            'recent_executions' => $recentExecutions
        ];
    }

    public function duplicateTask(int $id): Task
    {
        $originalTask = Task::findOrFail($id);
        
        $newTaskData = $originalTask->toArray();
        unset($newTaskData['id'], $newTaskData['created_at'], $newTaskData['updated_at']);
        
        $newTaskData['name'] = $originalTask->name . ' (Copy)';
        $newTaskData['status'] = 'disabled'; // 复制的任务默认禁用
        
        return Task::create($newTaskData);
    }

    public function getTasksByDomain(string $domain): Collection
    {
        return Task::where('domain', $domain)
            ->where('status', 'enabled')
            ->get();
    }

    public function getExclusiveTasks(): Collection
    {
        return Task::where('is_exclusive', true)
            ->where('status', 'enabled')
            ->get();
    }

    public function getNonExclusiveTasks(): Collection
    {
        return Task::where('is_exclusive', false)
            ->where('status', 'enabled')
            ->get();
    }
}
