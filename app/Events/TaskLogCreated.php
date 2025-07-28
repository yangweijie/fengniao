<?php

namespace App\Events;

use App\Models\TaskLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskLogCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TaskLog $log;

    /**
     * Create a new event instance.
     */
    public function __construct(TaskLog $log)
    {
        $this->log = $log;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("task.{$this->log->execution->task_id}.logs"),
            new PrivateChannel("execution.{$this->log->execution_id}.logs"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->log->id,
            'execution_id' => $this->log->execution_id,
            'level' => $this->log->level,
            'message' => $this->log->message,
            'context' => $this->log->context,
            'screenshot_path' => $this->log->screenshot_path,
            'created_at' => $this->log->created_at->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'log.created';
    }
}
