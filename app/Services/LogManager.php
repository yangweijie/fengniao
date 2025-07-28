<?php

namespace App\Services;

use App\Models\TaskExecution;
use App\Models\TaskLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use App\Events\TaskLogCreated;
use App\Events\ScreenshotCaptured;
use Exception;

class LogManager
{
    protected string $screenshotDisk = 'public';
    protected string $screenshotPath = 'screenshots';
    protected int $maxLogRetentionDays = 30;

    /**
     * 记录任务日志
     */
    public function log(TaskExecution $execution, string $level, string $message, ?array $context = null, ?string $screenshotPath = null): TaskLog
    {
        // 创建日志记录
        $log = TaskLog::create([
            'execution_id' => $execution->id,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'screenshot_path' => $screenshotPath
        ]);

        // 记录到Laravel日志
        Log::channel('single')->log($level, "[Task {$execution->task_id}] {$message}", $context ?? []);

        // 触发实时日志事件
        Event::dispatch(new TaskLogCreated($log));

        return $log;
    }

    /**
     * 捕获截图
     */
    public function captureScreenshot(TaskExecution $execution, $driver, string $description = ''): ?string
    {
        try {
            $filename = $this->generateScreenshotFilename($execution, $description);
            $path = "{$this->screenshotPath}/{$filename}";

            // 确保目录存在
            Storage::disk($this->screenshotDisk)->makeDirectory($this->screenshotPath);

            // 获取完整的文件系统路径用于WebDriver
            $fullPath = Storage::disk($this->screenshotDisk)->path($path);

            // 截图
            $driver->takeScreenshot($fullPath);

            // 触发截图事件
            Event::dispatch(new ScreenshotCaptured($execution, $filename, $description));

            Log::info("截图已保存", [
                'execution_id' => $execution->id,
                'filename' => $filename,
                'description' => $description,
                'path' => $path,
                'url' => Storage::disk($this->screenshotDisk)->url($path)
            ]);

            return $filename;

        } catch (Exception $e) {
            Log::error("截图失败", [
                'execution_id' => $execution->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * 获取任务执行的所有日志
     */
    public function getExecutionLogs(int $executionId, ?string $level = null, int $limit = 100): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = TaskLog::where('execution_id', $executionId);

        if ($level) {
            $query->where('level', $level);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * 获取实时日志流
     */
    public function getRealtimeLogs(int $executionId): \Illuminate\Support\Collection
    {
        return TaskLog::where('execution_id', $executionId)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * 搜索日志
     */
    public function searchLogs(array $criteria): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = TaskLog::query();

        // 按执行ID搜索
        if (isset($criteria['execution_id'])) {
            $query->where('execution_id', $criteria['execution_id']);
        }

        // 按任务ID搜索
        if (isset($criteria['task_id'])) {
            $query->whereHas('execution', function ($q) use ($criteria) {
                $q->where('task_id', $criteria['task_id']);
            });
        }

        // 按日志级别搜索
        if (isset($criteria['level'])) {
            $query->where('level', $criteria['level']);
        }

        // 按关键词搜索
        if (isset($criteria['keyword'])) {
            $query->where('message', 'like', '%' . $criteria['keyword'] . '%');
        }

        // 按时间范围搜索
        if (isset($criteria['start_time'])) {
            $query->where('created_at', '>=', $criteria['start_time']);
        }

        if (isset($criteria['end_time'])) {
            $query->where('created_at', '<=', $criteria['end_time']);
        }

        // 只显示有截图的日志
        if (isset($criteria['has_screenshot']) && $criteria['has_screenshot']) {
            $query->whereNotNull('screenshot_path');
        }

        return $query->with('execution.task')
            ->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 50);
    }

    /**
     * 获取截图URL
     */
    public function getScreenshotUrl(string $filename): ?string
    {
        $path = "{$this->screenshotPath}/{$filename}";

        if (Storage::disk($this->screenshotDisk)->exists($path)) {
            return Storage::disk($this->screenshotDisk)->url($path);
        }

        return null;
    }

    /**
     * 获取截图内容
     */
    public function getScreenshotContent(string $filename): ?string
    {
        $path = "{$this->screenshotPath}/{$filename}";
        
        if (Storage::disk($this->screenshotDisk)->exists($path)) {
            return Storage::disk($this->screenshotDisk)->get($path);
        }

        return null;
    }

    /**
     * 删除截图
     */
    public function deleteScreenshot(string $filename): bool
    {
        $path = "{$this->screenshotPath}/{$filename}";
        
        if (Storage::disk($this->screenshotDisk)->exists($path)) {
            return Storage::disk($this->screenshotDisk)->delete($path);
        }

        return false;
    }

    /**
     * 清理旧日志
     */
    public function cleanOldLogs(int $days = null): int
    {
        $days = $days ?? $this->maxLogRetentionDays;
        $cutoffDate = now()->subDays($days);

        // 获取要删除的日志（包含截图路径）
        $logsToDelete = TaskLog::where('created_at', '<', $cutoffDate)
            ->whereNotNull('screenshot_path')
            ->pluck('screenshot_path');

        // 删除相关截图文件
        foreach ($logsToDelete as $screenshotPath) {
            $this->deleteScreenshot($screenshotPath);
        }

        // 删除日志记录
        $deletedCount = TaskLog::where('created_at', '<', $cutoffDate)->delete();

        Log::info("清理旧日志完成", [
            'days' => $days,
            'deleted_logs' => $deletedCount,
            'deleted_screenshots' => $logsToDelete->count()
        ]);

        return $deletedCount;
    }

    /**
     * 获取日志统计信息
     */
    public function getLogStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $stats = TaskLog::where('created_at', '>=', $startDate)
            ->selectRaw('level, count(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        $totalLogs = array_sum($stats);
        $screenshotCount = TaskLog::where('created_at', '>=', $startDate)
            ->whereNotNull('screenshot_path')
            ->count();

        return [
            'total_logs' => $totalLogs,
            'by_level' => $stats,
            'screenshot_count' => $screenshotCount,
            'period_days' => $days,
            'start_date' => $startDate->toDateString()
        ];
    }

    /**
     * 导出日志
     */
    public function exportLogs(array $criteria, string $format = 'json'): string
    {
        $logs = $this->searchLogs($criteria);

        $exportData = collect($logs->items())->map(function ($log) {
            return [
                'id' => $log->id,
                'execution_id' => $log->execution_id,
                'task_id' => $log->execution->task_id ?? null,
                'task_name' => $log->execution->task->name ?? null,
                'level' => $log->level,
                'message' => $log->message,
                'context' => $log->context,
                'screenshot_path' => $log->screenshot_path,
                'created_at' => $log->created_at->toISOString()
            ];
        });

        $filename = 'logs_export_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
        $path = storage_path("app/exports/{$filename}");

        // 确保导出目录存在
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        switch ($format) {
            case 'json':
                file_put_contents($path, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
            case 'csv':
                $this->exportToCsv($exportData, $path);
                break;
            default:
                throw new Exception("不支持的导出格式: {$format}");
        }

        Log::info("日志导出完成", [
            'filename' => $filename,
            'format' => $format,
            'record_count' => count($exportData)
        ]);

        return $path;
    }

    /**
     * 生成截图文件名
     */
    protected function generateScreenshotFilename(TaskExecution $execution, string $description = ''): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s-u');
        $suffix = $description ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $description) : '';
        
        return "task_{$execution->task_id}_exec_{$execution->id}_{$timestamp}{$suffix}.png";
    }

    /**
     * 导出为CSV格式
     */
    protected function exportToCsv($data, string $path): void
    {
        $file = fopen($path, 'w');
        
        // 写入BOM以支持中文
        fwrite($file, "\xEF\xBB\xBF");
        
        // 写入表头
        if (!empty($data)) {
            fputcsv($file, array_keys($data[0]));
            
            // 写入数据
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        }
        
        fclose($file);
    }
}
