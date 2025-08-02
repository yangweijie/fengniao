<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\TaskLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ApiExecutor
{
    public function execute(Task $task, TaskExecution $execution): array
    {
        $this->log($execution, 'info', "开始执行API任务: {$task->name}");
        
        try {
            // 设置环境变量
            if ($task->env_vars) {
                foreach ($task->env_vars as $key => $value) {
                    putenv("{$key}={$value}");
                }
            }
            
            // 执行脚本
            $result = $this->executeScript($task, $execution);
            
            $this->log($execution, 'info', "API任务执行完成");
            
            return $result;
            
        } catch (Exception $e) {
            $this->log($execution, 'error', "API任务执行失败: " . $e->getMessage());
            throw $e;
        }
    }

    protected function executeScript(Task $task, TaskExecution $execution): array
    {
        $this->log($execution, 'info', "开始执行API脚本");
        
        if (empty($task->script_content)) {
            $this->log($execution, 'warning', "脚本内容为空");
            return ['success' => true, 'message' => '脚本内容为空'];
        }
        
        try {
            // 创建HTTP客户端实例供脚本使用
            $http = Http::class;
            
            // 创建一个安全的执行环境
            $scriptWrapper = "
                try {
                    // 提供HTTP客户端
                    \$http = \\Illuminate\\Support\\Facades\\Http::class;
                    
                    // 提供日志记录函数
                    \$log = function(\$level, \$message, \$context = []) use (\$execution) {
                        \$this->log(\$execution, \$level, \$message, \$context);
                    };
                    
                    // 执行用户脚本
                    {$task->script_content}
                    
                } catch (Exception \$e) {
                    throw new Exception('API脚本执行错误: ' . \$e->getMessage());
                }
            ";

            extract(['task_id' => $$execution->task_id]);
            
            // 执行脚本
            $result = eval($scriptWrapper);
            
            $this->log($execution, 'info', "API脚本执行完成");
            
            return [
                'success' => true,
                'result' => $result
            ];
            
        } catch (Exception $e) {
            $this->log($execution, 'error', "API脚本执行异常: " . $e->getMessage());
            throw $e;
        }
    }

    protected function log(TaskExecution $execution, string $level, string $message, ?array $context = null): void
    {
        TaskLog::create([
            'execution_id' => $execution->id,
            'level' => $level,
            'message' => $message,
            'context' => $context
        ]);
        
        Log::channel('single')->log($level, "[API Task {$execution->task_id}] {$message}", $context ?? []);
    }
}
