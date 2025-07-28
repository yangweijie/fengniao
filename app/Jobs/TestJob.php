<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TestJob implements ShouldQueue
{
    use Queueable;

    public string $data;

    /**
     * Create a new job instance.
     */
    public function __construct(string $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 简单的测试任务，记录日志
        \Log::info('TestJob executed', ['data' => $this->data]);
    }
}
