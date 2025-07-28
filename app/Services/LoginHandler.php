<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskExecution;
use App\Models\TaskLog;
use App\Services\CookieManager;
use App\Services\AccountRotationService;
use Laravel\Dusk\Browser;
use Illuminate\Support\Facades\Log;
use Exception;

class LoginHandler
{
    protected CookieManager $cookieManager;
    protected AccountRotationService $accountRotation;

    public function __construct(CookieManager $cookieManager, AccountRotationService $accountRotation)
    {
        $this->cookieManager = $cookieManager;
        $this->accountRotation = $accountRotation;
    }

    /**
     * 智能登录处理
     */
    public function handleLogin(Task $task, $tabSession, TaskExecution $execution): bool
    {
        if (!$this->needsLogin($task)) {
            return true;
        }

        $this->log($execution, 'info', "开始智能登录处理");

        try {
            // 获取可用账号
            $account = $this->accountRotation->getNextAccount($task);
            if (!$account) {
                throw new Exception("没有可用的账号");
            }

            $this->log($execution, 'info', "选择账号: " . $account['username']);

            // 1. 尝试使用已保存的Cookie
            if ($this->tryLoginWithCookies($task, $tabSession, $execution, $account)) {
                $this->log($execution, 'info', "Cookie登录成功");
                $this->accountRotation->recordAccountUsage($task, $account);
                return true;
            }

            // 2. Cookie无效，执行完整登录流程
            $this->log($execution, 'info', "Cookie无效，开始完整登录流程");
            $result = $this->performFullLogin($task, $tabSession, $execution, $account);

            if ($result) {
                $this->accountRotation->recordAccountUsage($task, $account);
            } else {
                // 登录失败，可能需要锁定账号
                $this->accountRotation->lockAccount($task, $account, 30, "登录失败");
            }

            return $result;

        } catch (Exception $e) {
            $this->log($execution, 'error', "登录处理失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 检查任务是否需要登录
     */
    protected function needsLogin(Task $task): bool
    {
        return !empty($task->login_config) && 
               isset($task->login_config['username_env']) && 
               isset($task->login_config['password_env']);
    }

    /**
     * 尝试使用Cookie登录
     */
    protected function tryLoginWithCookies(Task $task, $tabSession, TaskExecution $execution, array $account): bool
    {
        if (!$task->domain) {
            return false;
        }

        $accountId = $account['username'];

        // 检查Cookie是否有效
        if (!$this->cookieManager->isCookieValid($task->domain, $accountId)) {
            $this->log($execution, 'info', "Cookie无效或已过期");
            return false;
        }

        // 加载Cookie
        $cookies = $this->cookieManager->loadCookies($task->domain, $accountId);
        if (empty($cookies)) {
            return false;
        }

        try {
            // 切换到正确的标签页
            $tabSession->switchToTab();
            
            // 访问登录检查URL或主页
            $checkUrl = $task->login_config['login_check_url'] ?? "https://{$task->domain}";
            $tabSession->driver->navigate()->to($checkUrl);

            // 注入Cookie
            foreach ($cookies as $cookie) {
                try {
                    $tabSession->driver->manage()->addCookie($cookie);
                } catch (Exception $e) {
                    $this->log($execution, 'warning', "Cookie注入失败: " . $e->getMessage());
                }
            }

            // 刷新页面以应用Cookie
            $tabSession->driver->navigate()->refresh();

            // 检查是否登录成功
            if ($this->checkLoginStatus($task, $tabSession, $execution)) {
                // 更新Cookie使用时间
                $cookieRecord = \App\Models\Cookie::findForDomain($task->domain, $accountId);
                if ($cookieRecord) {
                    $cookieRecord->markAsUsed();
                }
                
                return true;
            }

            return false;

        } catch (Exception $e) {
            $this->log($execution, 'warning', "Cookie登录尝试失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 执行完整登录流程
     */
    protected function performFullLogin(Task $task, $tabSession, TaskExecution $execution, array $account): bool
    {
        try {
            // 切换到正确的标签页
            $tabSession->switchToTab();
            
            // 访问登录页面
            $loginUrl = $task->login_config['login_url'] ?? "https://{$task->domain}/login";
            $tabSession->driver->navigate()->to($loginUrl);
            
            $this->log($execution, 'info', "访问登录页面: {$loginUrl}");

            // 等待页面加载
            sleep(2);

            // 获取登录凭据
            $username = $account['username'];
            $password = $account['password'];

            if (!$username || !$password) {
                throw new Exception("登录凭据不完整");
            }

            // 执行登录操作
            $this->fillLoginForm($task, $tabSession, $execution, $username, $password);

            // 等待登录处理
            sleep(3);

            // 检查登录结果
            if ($this->checkLoginStatus($task, $tabSession, $execution)) {
                // 保存新的Cookie
                $this->saveLoginCookies($task, $tabSession, $execution, $account);
                return true;
            }

            // 检查是否遇到验证码
            if ($this->detectCaptcha($task, $tabSession, $execution)) {
                $this->handleCaptcha($task, $tabSession, $execution);
                return false; // 需要人工处理
            }

            throw new Exception("登录失败，未知原因");

        } catch (Exception $e) {
            $this->log($execution, 'error', "完整登录流程失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 填写登录表单
     */
    protected function fillLoginForm(Task $task, $tabSession, TaskExecution $execution, string $username, string $password): void
    {
        $config = $task->login_config;
        
        // 用户名字段
        $usernameSelector = $config['username_selector'] ?? 'input[name="username"], input[name="email"], #username, #email';
        $passwordSelector = $config['password_selector'] ?? 'input[name="password"], #password';
        $submitSelector = $config['submit_selector'] ?? 'button[type="submit"], input[type="submit"], .login-btn';

        try {
            // 填写用户名
            $usernameElement = $tabSession->driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($usernameSelector));
            $usernameElement->clear();
            $usernameElement->sendKeys($username);
            
            $this->log($execution, 'info', "已填写用户名");

            // 填写密码
            $passwordElement = $tabSession->driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($passwordSelector));
            $passwordElement->clear();
            $passwordElement->sendKeys($password);
            
            $this->log($execution, 'info', "已填写密码");

            // 点击登录按钮
            $submitElement = $tabSession->driver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($submitSelector));
            $submitElement->click();
            
            $this->log($execution, 'info', "已点击登录按钮");

        } catch (Exception $e) {
            throw new Exception("填写登录表单失败: " . $e->getMessage());
        }
    }

    /**
     * 检查登录状态
     */
    protected function checkLoginStatus(Task $task, $tabSession, TaskExecution $execution): bool
    {
        $config = $task->login_config;
        
        // 登录成功的标识选择器
        $loggedInSelector = $config['logged_in_selector'] ?? '.user-menu, .logout, .profile, [data-user]';
        
        try {
            // 等待页面稳定
            sleep(2);
            
            // 查找登录成功的标识元素
            $elements = $tabSession->driver->findElements(\Facebook\WebDriver\WebDriverBy::cssSelector($loggedInSelector));
            
            if (!empty($elements)) {
                $this->log($execution, 'info', "检测到登录成功标识");
                return true;
            }

            // 检查URL变化
            $currentUrl = $tabSession->driver->getCurrentURL();
            $loginUrl = $config['login_url'] ?? '';
            
            if ($loginUrl && !str_contains($currentUrl, parse_url($loginUrl, PHP_URL_PATH))) {
                $this->log($execution, 'info', "URL已跳转，可能登录成功");
                return true;
            }

            return false;

        } catch (Exception $e) {
            $this->log($execution, 'warning', "检查登录状态失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 保存登录后的Cookie
     */
    protected function saveLoginCookies(Task $task, $tabSession, TaskExecution $execution, array $account): void
    {
        try {
            $cookies = $tabSession->driver->manage()->getCookies();
            $accountId = $account['username'];

            $this->cookieManager->saveCookies($task->domain, $cookies, $accountId);
            $this->log($execution, 'info', "登录Cookie已保存");

        } catch (Exception $e) {
            $this->log($execution, 'warning', "保存登录Cookie失败: " . $e->getMessage());
        }
    }

    /**
     * 检测验证码
     */
    protected function detectCaptcha(Task $task, $tabSession, TaskExecution $execution): bool
    {
        $captchaSelectors = [
            'img[src*="captcha"]',
            '.captcha',
            '#captcha',
            'img[alt*="验证码"]',
            'img[alt*="captcha"]'
        ];

        try {
            foreach ($captchaSelectors as $selector) {
                $elements = $tabSession->driver->findElements(\Facebook\WebDriver\WebDriverBy::cssSelector($selector));
                if (!empty($elements)) {
                    $this->log($execution, 'warning', "检测到验证码");
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 处理验证码
     */
    protected function handleCaptcha(Task $task, $tabSession, TaskExecution $execution): void
    {
        try {
            // 截图保存验证码
            $filename = "captcha_task_{$execution->id}_" . time() . ".png";
            $path = storage_path("app/captcha/{$filename}");
            
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            
            $tabSession->driver->takeScreenshot($path);
            
            $this->log($execution, 'warning', "遇到验证码，已截图保存", null, $filename);
            
            // 标记任务需要人工处理
            $execution->update([
                'status' => 'failed',
                'error_message' => '遇到验证码，需要人工处理'
            ]);
            
        } catch (Exception $e) {
            $this->log($execution, 'error', "处理验证码失败: " . $e->getMessage());
        }
    }

    /**
     * 获取账号标识符
     */
    protected function getAccountIdentifier(Task $task): ?string
    {
        $usernameEnv = $task->login_config['username_env'] ?? null;
        return $usernameEnv ? $this->getEnvValue($usernameEnv) : null;
    }

    /**
     * 获取环境变量值
     */
    protected function getEnvValue(string $envKey): ?string
    {
        return env($envKey) ?: ($task->env_vars[$envKey] ?? null);
    }

    /**
     * 记录日志
     */
    protected function log(TaskExecution $execution, string $level, string $message, ?array $context = null, ?string $screenshotPath = null): void
    {
        TaskLog::create([
            'execution_id' => $execution->id,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'screenshot_path' => $screenshotPath
        ]);
        
        Log::channel('single')->log($level, "[Login {$execution->task_id}] {$message}", $context ?? []);
    }
}
