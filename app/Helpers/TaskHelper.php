<?php
namespace App\Helpers;

use App\Models\Task;

if (!function_exists('saveEnvVar')) {
    /**
     * 更新任务的环境变量（env_vars 字段）
     * @param int $task_id
     * @param string $key
     * @param mixed $value
     */
    function saveEnvVar($task_id, $key, $value) {
        $task = Task::find($task_id);
        if ($task) {
            $env = $task->env_vars ?: [];
            $env[$key] = $value;
            $task->env_vars = $env;
            $task->save();
        }
    }
}
