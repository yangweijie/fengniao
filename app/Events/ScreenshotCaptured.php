<?php

namespace App\Events;

use App\Models\TaskExecution;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScreenshotCaptured implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TaskExecution $execution;
    public string $filename;
    public string $description;

    /**
     * Create a new event instance.
     */
    public function __construct(TaskExecution $execution, string $filename, string $description = '')
    {
        $this->execution = $execution;
        $this->filename = $filename;
        $this->description = $description;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("task.{$this->execution->task_id}.screenshots"),
            new PrivateChannel("execution.{$this->execution->id}.screenshots"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'execution_id' => $this->execution->id,
            'task_id' => $this->execution->task_id,
            'filename' => $this->filename,
            'description' => $this->description,
            'captured_at' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'screenshot.captured';
    }
}
