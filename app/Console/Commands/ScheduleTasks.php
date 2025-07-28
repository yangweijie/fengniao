<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Jobs\ExecuteTaskJob;
use Illuminate\Console\Command;
use Cron\CronExpression;
use Carbon\Carbon;

class ScheduleTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查并调度需要执行的任务';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('开始检查待执行任务...');

        $enabledTasks = Task::where('status', 'enabled')->get();
        $scheduledCount = 0;

        foreach ($enabledTasks as $task) {
            if ($this->shouldExecuteTask($task)) {
                // 分发任务到队列
                ExecuteTaskJob::dispatch($task);
                $scheduledCount++;

                $this->info("任务 [{$task->name}] 已加入执行队列");
            }
        }

        $this->info("检查完成，共调度 {$scheduledCount} 个任务");

        return Command::SUCCESS;
    }

    /**
     * 检查任务是否应该执行
     */
    private function shouldExecuteTask(Task $task): bool
    {
        try {
            $cron = new CronExpression($task->cron_expression);
            $lastRun = $cron->getPreviousRunDate();
            $nextRun = $cron->getNextRunDate($lastRun);

            // 检查是否到了执行时间（允许1分钟误差）
            $now = Carbon::now();
            $executeTime = Carbon::instance($nextRun);

            return $now->diffInMinutes($executeTime) <= 1 && $now->gte($executeTime);

        } catch (\Exception $e) {
            $this->error("任务 [{$task->name}] Cron表达式错误: {$e->getMessage()}");
            return false;
        }
    }
}
