<?php

namespace App\Livewire;

use App\Models\TaskExecution;
use App\Models\TaskLog;
use App\Services\LogManager;
use Livewire\Component;
use Livewire\Attributes\On;

class RealtimeLogViewer extends Component
{
    public ?int $executionId = null;
    public ?int $taskId = null;
    public array $logs = [];
    public array $filters = [
        'level' => '',
        'search' => '',
        'auto_scroll' => true,
        'show_context' => false
    ];
    public int $maxLogs = 100;
    public bool $isConnected = false;

    public function mount(?int $executionId = null, ?int $taskId = null)
    {
        $this->executionId = $executionId;
        $this->taskId = $taskId;
        $this->loadInitialLogs();
    }

    public function loadInitialLogs()
    {
        $query = TaskLog::query();

        if ($this->executionId) {
            $query->where('execution_id', $this->executionId);
        } elseif ($this->taskId) {
            $query->whereHas('execution', function ($q) {
                $q->where('task_id', $this->taskId);
            });
        }

        if ($this->filters['level']) {
            $query->where('level', $this->filters['level']);
        }

        if ($this->filters['search']) {
            $query->where('message', 'like', '%' . $this->filters['search'] . '%');
        }

        $this->logs = $query->with('execution.task')
            ->orderBy('created_at', 'desc')
            ->limit($this->maxLogs)
            ->get()
            ->reverse()
            ->values()
            ->toArray();
    }

    #[On('echo-private:execution.{executionId}.logs,log.created')]
    public function onLogCreated($data)
    {
        if ($this->executionId && $data['execution_id'] != $this->executionId) {
            return;
        }

        // 应用过滤器
        if ($this->filters['level'] && $data['level'] !== $this->filters['level']) {
            return;
        }

        if ($this->filters['search'] && !str_contains(strtolower($data['message']), strtolower($this->filters['search']))) {
            return;
        }

        // 添加新日志到列表
        $this->logs[] = $data;

        // 保持最大日志数量限制
        if (count($this->logs) > $this->maxLogs) {
            array_shift($this->logs);
        }

        // 触发前端更新
        $this->dispatch('log-added', $data);
    }

    #[On('echo-private:task.{taskId}.logs,log.created')]
    public function onTaskLogCreated($data)
    {
        if ($this->taskId && !$this->executionId) {
            $this->onLogCreated($data);
        }
    }

    public function updateFilters()
    {
        $this->loadInitialLogs();
    }

    public function clearLogs()
    {
        $this->logs = [];
    }

    public function exportLogs()
    {
        $logManager = app(LogManager::class);

        $criteria = [];
        if ($this->executionId) {
            $criteria['execution_id'] = $this->executionId;
        } elseif ($this->taskId) {
            $criteria['task_id'] = $this->taskId;
        }

        if ($this->filters['level']) {
            $criteria['level'] = $this->filters['level'];
        }

        $path = $logManager->exportLogs($criteria, 'json');

        return response()->download($path);
    }

    public function getListeners()
    {
        $listeners = [];

        if ($this->executionId) {
            $listeners["echo-private:execution.{$this->executionId}.logs,log.created"] = 'onLogCreated';
        }

        if ($this->taskId) {
            $listeners["echo-private:task.{$this->taskId}.logs,log.created"] = 'onTaskLogCreated';
        }

        return $listeners;
    }

    public function render()
    {
        return view('livewire.realtime-log-viewer', [
            'execution' => $this->executionId ? TaskExecution::find($this->executionId) : null,
            'logLevels' => ['debug', 'info', 'warning', 'error', 'critical']
        ]);
    }
}
