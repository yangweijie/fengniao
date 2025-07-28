<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class UpdateTaskScript extends Command
{
    protected $signature = 'task:update-script {id} {--script=}';
    protected $description = '更新指定任务的脚本内容';

    public function handle()
    {
        $taskId = $this->argument('id');
        $script = $this->option('script');

        $task = Task::find($taskId);
        
        if (!$task) {
            $this->error("未找到ID为 {$taskId} 的任务");
            return 1;
        }

        $this->info("当前任务信息:");
        $this->line("  ID: {$task->id}");
        $this->line("  名称: {$task->name}");
        $this->line("  类型: {$task->type}");
        $this->line("  当前脚本内容:");
        $this->line($task->script ?: '(空)');

        if ($script) {
            $task->update(['script' => $script]);
            $this->info("✅ 任务脚本已更新");
            $this->line("新脚本内容:");
            $this->line($script);
        } else {
            $this->info("使用 --script 参数来更新脚本内容");
        }

        return 0;
    }
}
