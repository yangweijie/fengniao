<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\TaskLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    /**
     * 获取任务的日志
     */
    public function getTaskLogs(Request $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        
        $query = TaskLog::query()
            ->whereHas('execution', function ($query) use ($taskId) {
                $query->where('task_id', $taskId);
            })
            ->with(['execution'])
            ->orderBy('created_at', 'desc');
            
        // 如果指定了after参数，只返回指定ID之后的日志
        if ($request->has('after')) {
            $query->where('id', '>', $request->get('after'));
            $query->orderBy('created_at', 'asc'); // 新日志按时间正序
        }
        
        // 限制返回数量
        $limit = min($request->get('limit', 100), 500);
        $logs = $query->limit($limit)->get();
        
        // 获取任务执行状态
        $latestExecution = $task->executions()
            ->latest('start_time')
            ->first();
            
        $hasRunningExecution = $task->executions()
            ->where('status', 'running')
            ->exists();
        
        return response()->json([
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'execution_id' => $log->execution_id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'context' => $log->context,
                    'created_at' => $log->created_at->toISOString(),
                    'execution_status' => $log->execution->status ?? null,
                ];
            }),
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
                'status' => $task->status,
            ],
            'latest_execution' => $latestExecution ? [
                'id' => $latestExecution->id,
                'status' => $latestExecution->status,
                'start_time' => $latestExecution->start_time->toISOString(),
                'end_time' => $latestExecution->end_time?->toISOString(),
            ] : null,
            'has_running_execution' => $hasRunningExecution,
            'total_count' => $logs->count(),
        ]);
    }
    
    /**
     * 获取执行的日志
     */
    public function getExecutionLogs(Request $request, int $executionId): JsonResponse
    {
        $execution = TaskExecution::findOrFail($executionId);
        
        $query = TaskLog::query()
            ->where('execution_id', $executionId)
            ->orderBy('created_at', 'desc');
            
        // 如果指定了after参数，只返回指定ID之后的日志
        if ($request->has('after')) {
            $query->where('id', '>', $request->get('after'));
            $query->orderBy('created_at', 'asc'); // 新日志按时间正序
        }
        
        // 限制返回数量
        $limit = min($request->get('limit', 100), 500);
        $logs = $query->limit($limit)->get();
        
        return response()->json([
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'execution_id' => $log->execution_id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'context' => $log->context,
                    'created_at' => $log->created_at->toISOString(),
                ];
            }),
            'execution' => [
                'id' => $execution->id,
                'task_id' => $execution->task_id,
                'status' => $execution->status,
                'start_time' => $execution->start_time->toISOString(),
                'end_time' => $execution->end_time?->toISOString(),
            ],
            'task' => [
                'id' => $execution->task->id,
                'name' => $execution->task->name,
            ],
            'total_count' => $logs->count(),
        ]);
    }
    
    /**
     * 获取连贯的日志文本（用于显示完整的执行过程）
     */
    public function getLogText(Request $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        
        // 获取最新的执行记录
        $execution = $task->executions()
            ->latest('start_time')
            ->first();
            
        if (!$execution) {
            return response()->json([
                'text' => '暂无执行记录',
                'execution' => null,
            ]);
        }
        
        // 获取该执行的所有日志
        $logs = TaskLog::where('execution_id', $execution->id)
            ->orderBy('created_at', 'asc')
            ->get();
            
        // 生成连贯的日志文本
        $logText = [];
        $logText[] = "=== 任务执行日志 ===";
        $logText[] = "任务: {$task->name}";
        $logText[] = "执行ID: {$execution->id}";
        $logText[] = "开始时间: " . $execution->start_time->format('Y-m-d H:i:s');
        $logText[] = "状态: " . $this->getStatusText($execution->status);
        
        if ($execution->end_time) {
            $logText[] = "结束时间: " . $execution->end_time->format('Y-m-d H:i:s');
            $duration = $execution->start_time->diffInSeconds($execution->end_time);
            $logText[] = "执行时长: {$duration}秒";
        }
        
        $logText[] = "";
        $logText[] = "=== 执行过程 ===";
        
        foreach ($logs as $log) {
            $timestamp = $log->created_at->format('H:i:s');
            $level = strtoupper($log->level);
            $logText[] = "[{$timestamp}] [{$level}] {$log->message}";
            
            // 如果有上下文信息，也添加进去
            if ($log->context) {
                $contextText = is_array($log->context) ? json_encode($log->context, JSON_UNESCAPED_UNICODE) : $log->context;
                $logText[] = "    上下文: {$contextText}";
            }
        }
        
        if ($execution->error_message) {
            $logText[] = "";
            $logText[] = "=== 错误信息 ===";
            $logText[] = $execution->error_message;
        }
        
        return response()->json([
            'text' => implode("\n", $logText),
            'execution' => [
                'id' => $execution->id,
                'status' => $execution->status,
                'start_time' => $execution->start_time->toISOString(),
                'end_time' => $execution->end_time?->toISOString(),
                'is_running' => $execution->status === 'running',
            ],
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
            ],
            'log_count' => $logs->count(),
        ]);
    }
    
    private function getStatusText(string $status): string
    {
        return match ($status) {
            'running' => '运行中',
            'success' => '成功',
            'failed' => '失败',
            default => $status,
        };
    }
}
