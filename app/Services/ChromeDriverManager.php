<?php

namespace App\Services;

use Laravel\Dusk\Chrome\ChromeProcess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Exception;

class ChromeDriverManager
{
    protected ?ChromeProcess $chromeProcess = null;
    protected bool $isRunning = false;
    protected int $port = 9515;

    public function start(): bool
    {
        if ($this->isRunning) {
            Log::info("ChromeDriver已经在运行");
            return true;
        }

        try {
            // 检查ChromeDriver是否已经在运行
            if ($this->isPortInUse($this->port)) {
                Log::info("ChromeDriver端口已被占用，假设服务已启动");
                $this->isRunning = true;
                return true;
            }

            // 启动ChromeDriver
            $this->chromeProcess = new ChromeProcess();
            $this->chromeProcess->toProcess()->start();
            
            // 等待启动
            sleep(2);
            
            if ($this->isPortInUse($this->port)) {
                $this->isRunning = true;
                Log::info("ChromeDriver启动成功", ['port' => $this->port]);
                return true;
            } else {
                Log::error("ChromeDriver启动失败");
                return false;
            }
            
        } catch (Exception $e) {
            Log::error("ChromeDriver启动异常", ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function stop(): void
    {
        if ($this->chromeProcess) {
            try {
                $this->chromeProcess->stop();
                $this->isRunning = false;
                Log::info("ChromeDriver已停止");
            } catch (Exception $e) {
                Log::error("停止ChromeDriver失败", ['error' => $e->getMessage()]);
            }
        }
    }

    public function restart(): bool
    {
        $this->stop();
        sleep(1);
        return $this->start();
    }

    public function isRunning(): bool
    {
        // 首先检查端口是否被占用（无论内部状态如何）
        $portInUse = $this->isPortInUse($this->port);

        if ($portInUse) {
            // 如果端口被占用，更新内部状态
            $this->isRunning = true;
            return true;
        }

        // 如果端口没有被占用，更新内部状态
        $this->isRunning = false;
        return false;
    }

    public function getStatus(): array
    {
        return [
            'is_running' => $this->isRunning(),
            'port' => $this->port,
            'process_id' => $this->chromeProcess ? $this->chromeProcess->processId() : null
        ];
    }

    protected function isPortInUse(int $port): bool
    {
        try {
            $result = Process::run("lsof -i :$port");
            return $result->successful() && !empty(trim($result->output()));
        } catch (Exception $e) {
            // 如果lsof命令不可用，尝试其他方法
            try {
                $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
                if ($socket) {
                    fclose($socket);
                    return true;
                }
                return false;
            } catch (Exception $e) {
                return false;
            }
        }
    }

    public function ensureRunning(): bool
    {
        if ($this->isRunning()) {
            return true;
        }
        
        Log::info("ChromeDriver未运行，尝试启动");
        return $this->start();
    }

    public function __destruct()
    {
        // 析构时不自动停止，因为可能有其他进程在使用
    }
}
