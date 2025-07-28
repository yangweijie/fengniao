<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Services\BrowserPoolManager;
use App\Services\CookieManager;
use App\Services\LoginHandler;
use App\Services\LogManager;
use Laravel\Dusk\Browser;
use Exception;

class DuskExecutor
{
    protected BrowserPoolManager $browserPool;
    protected CookieManager $cookieManager;
    protected LoginHandler $loginHandler;
    protected LogManager $logManager;

    public function __construct(
        BrowserPoolManager $browserPool,
        CookieManager $cookieManager,
        LoginHandler $loginHandler,
        LogManager $logManager
    ) {
        $this->browserPool = $browserPool;
        $this->cookieManager = $cookieManager;
        $this->loginHandler = $loginHandler;
        $this->logManager = $logManager;
    }

    public function execute(Task $task, TaskExecution $execution): array
    {
        $this->log($execution, 'info', "开始执行浏览器任务: {$task->name}");
        
        // 获取浏览器实例
        $browserInstance = $this->browserPool->getBrowser($task);
        
        try {
            // 更新执行记录的浏览器信息
            $execution->update([
                'browser_instance_id' => $browserInstance->id
            ]);
            
            // 创建新标签页或使用独占浏览器
            $tabSession = $browserInstance->newTab($task);
            
            // 更新执行记录的标签页信息
            $execution->update([
                'tab_id' => $tabSession->id
            ]);
            
            $this->log($execution, 'info', "分配浏览器实例: {$browserInstance->id}, 标签页: {$tabSession->id}");

            // 智能登录处理
            if (!$this->loginHandler->handleLogin($task, $tabSession, $execution)) {
                throw new Exception("登录失败");
            }
            
            // 执行脚本
            $result = $this->executeScript($task, $tabSession, $execution);
            
            $this->log($execution, 'info', "任务执行完成");
            
            return $result;
            
        } catch (Exception $e) {
            $this->log($execution, 'error', "任务执行失败: " . $e->getMessage());
            throw $e;
            
        } finally {
            // 释放浏览器资源
            if (isset($tabSession)) {
                $browserInstance->closeTab($tabSession);
            }
            $this->browserPool->releaseBrowser($browserInstance);
        }
    }



    protected function executeScript(Task $task, $tabSession, TaskExecution $execution): array
    {
        $this->log($execution, 'info', "开始执行脚本");
        
        // 设置环境变量
        if ($task->env_vars) {
            foreach ($task->env_vars as $key => $value) {
                putenv("{$key}={$value}");
            }
        }
        
        // 确保切换到正确的标签页
        $tabSession->switchToTab();

        // 创建Browser实例
        $browser = new Browser($tabSession->driver);

        // 截图计数器
        $screenshotCount = 0;
        $screenshots = [];

        try {
            // 自动截图功能
            $duskExecutor = $this; // 保存外部this引用
            $browser->macro('autoScreenshot', function ($description = '') use ($execution, &$screenshotCount, &$screenshots, $tabSession, $duskExecutor) {
                // 确保在正确的标签页
                $tabSession->switchToTab();

                $screenshotCount++;

                // 使用公共方法捕获截图
                $filename = $duskExecutor->captureScreenshot($execution, $tabSession->driver, $description);

                if ($filename) {
                    $screenshots[] = $filename;
                    $duskExecutor->logScreenshot($execution, $description, $filename);
                }

                return $this;
            });
            
            // 执行用户脚本
            $this->executeUserScript($browser, $task->script_content, $execution);
            
            // 保存Cookie（如果任务有登录配置）
            if ($task->login_config && $task->domain) {
                $this->saveCookies($task, $tabSession, $execution);
            }
            
            // 更新执行记录的截图信息
            $execution->update(['screenshots' => $screenshots]);
            
            return [
                'success' => true,
                'screenshots' => $screenshots,
                'screenshot_count' => $screenshotCount
            ];
            
        } catch (Exception $e) {
            // 失败时也截图
            $browser->autoScreenshot("执行失败时的状态");
            $execution->update(['screenshots' => $screenshots]);
            
            throw $e;
        }
    }

    protected function executeUserScript($browser, string $scriptContent, TaskExecution $execution): void
    {
        if (empty($scriptContent)) {
            $this->log($execution, 'warning', "脚本内容为空");
            return;
        }
        
        // 在脚本执行前截图
        $browser->autoScreenshot("脚本执行前");
        
        try {
            // 创建一个安全的执行环境
            $scriptWrapper = "
                try {
                    {$scriptContent}
                } catch (Exception \$e) {
                    throw new Exception('脚本执行错误: ' . \$e->getMessage());
                }
            ";
            
            // 执行脚本
            eval($scriptWrapper);
            
            // 脚本执行后截图
            $browser->autoScreenshot("脚本执行完成");
            
        } catch (Exception $e) {
            $this->log($execution, 'error', "脚本执行异常: " . $e->getMessage());
            throw $e;
        }
    }

    protected function saveCookies(Task $task, $tabSession, TaskExecution $execution): void
    {
        try {
            $cookies = $tabSession->driver->manage()->getCookies();
            $account = $task->login_config['username_env'] ?? null;
            
            $this->cookieManager->saveCookies($task->domain, $cookies, $account);
            $this->log($execution, 'info', "Cookie已保存");
            
        } catch (Exception $e) {
            $this->log($execution, 'warning', "Cookie保存失败: " . $e->getMessage());
        }
    }

    protected function log(TaskExecution $execution, string $level, string $message, ?array $context = null, ?string $screenshotPath = null): void
    {
        $this->logManager->log($execution, $level, $message, $context, $screenshotPath);
    }

    public function captureScreenshot(TaskExecution $execution, $driver, string $description = ''): ?string
    {
        return $this->logManager->captureScreenshot($execution, $driver, $description);
    }

    public function logScreenshot(TaskExecution $execution, string $description, string $filename): void
    {
        $this->log($execution, 'info', "截图: {$description}", null, $filename);
    }
}
