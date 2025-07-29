<?php

namespace App\Services;

use App\Models\Task;
use App\Models\BrowserInstance;
use App\Services\ChromeDriverManager;
use Laravel\Dusk\Chrome\ChromeProcess;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Illuminate\Support\Facades\Log;
use Exception;

class BrowserPoolManager
{
    protected array $instances = [];
    protected int $maxInstances = 5;
    protected ChromeDriverManager $chromeDriverManager;

    public function __construct(ChromeDriverManager $chromeDriverManager)
    {
        $this->chromeDriverManager = $chromeDriverManager;
    }

    public function getBrowser(Task $task): BrowserInstanceWrapper
    {
        Log::info("请求浏览器实例", ['task' => $task->name, 'type' => $task->type]);

        // 确保ChromeDriver正在运行
        if (!$this->chromeDriverManager->ensureRunning()) {
            throw new Exception("ChromeDriver启动失败");
        }

        // 如果是独占任务，创建专用实例
        if ($task->is_exclusive) {
            return $this->createExclusiveInstance($task);
        }

        // 查找可用的非独占实例
        $instance = $this->findAvailableInstance($task);

        if (!$instance) {
            // 创建新实例
            $instance = $this->createNewInstance($task);
        }

        return $instance;
    }

    public function releaseBrowser(BrowserInstanceWrapper $instance): void
    {
        Log::info("释放浏览器实例", ['instance_id' => $instance->id]);
        
        if ($instance->isExclusive) {
            // 独占实例直接销毁
            $this->destroyInstance($instance);
        } else {
            // 非独占实例标记为空闲
            $instance->markAsIdle();
        }
    }

    protected function findAvailableInstance(Task $task): ?BrowserInstanceWrapper
    {
        // 优先查找相同域名的实例
        if ($task->domain) {
            foreach ($this->instances as $instance) {
                if ($instance->primaryDomain === $task->domain && $instance->canAcceptNewTab()) {
                    return $instance;
                }
            }
        }
        
        // 查找任何可用的实例
        foreach ($this->instances as $instance) {
            if ($instance->canAcceptNewTab()) {
                return $instance;
            }
        }
        
        return null;
    }

    protected function createExclusiveInstance(Task $task): BrowserInstanceWrapper
    {
        $instanceId = 'exclusive_' . uniqid();

        $instance = new BrowserInstanceWrapper([
            'id' => $instanceId,
            'isExclusive' => true,
            'primaryDomain' => $task->domain,
            'maxTabs' => 1,
            'debugMode' => $task->debug_mode ?? false
        ]);

        Log::info("创建独占浏览器实例", ['instance_id' => $instanceId, 'debug_mode' => $task->debug_mode]);

        return $instance;
    }

    protected function createNewInstance(Task $task): BrowserInstanceWrapper
    {
        if (count($this->instances) >= $this->maxInstances) {
            throw new Exception("浏览器实例池已满，无法创建新实例");
        }

        $instanceId = 'shared_' . uniqid();

        $instance = new BrowserInstanceWrapper([
            'id' => $instanceId,
            'isExclusive' => false,
            'primaryDomain' => $task->domain,
            'maxTabs' => 5,
            'debugMode' => $task->debug_mode ?? false
        ]);

        $this->instances[$instanceId] = $instance;

        Log::info("创建共享浏览器实例", ['instance_id' => $instanceId, 'debug_mode' => $task->debug_mode]);

        return $instance;
    }

    protected function destroyInstance(BrowserInstanceWrapper $instance): void
    {
        try {
            $instance->destroy();
            
            if (isset($this->instances[$instance->id])) {
                unset($this->instances[$instance->id]);
            }
            
            Log::info("销毁浏览器实例", ['instance_id' => $instance->id]);
            
        } catch (Exception $e) {
            Log::error("销毁浏览器实例失败", [
                'instance_id' => $instance->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getPoolStatus(): array
    {
        return [
            'total_instances' => count($this->instances),
            'max_instances' => $this->maxInstances,
            'instances' => array_map(function ($instance) {
                return [
                    'id' => $instance->id,
                    'is_exclusive' => $instance->isExclusive,
                    'primary_domain' => $instance->primaryDomain,
                    'active_tabs' => $instance->getActiveTabCount(),
                    'max_tabs' => $instance->maxTabs,
                    'status' => $instance->getStatus()
                ];
            }, $this->instances)
        ];
    }
}

/**
 * 浏览器实例包装器
 */
class BrowserInstanceWrapper
{
    public string $id;
    public bool $isExclusive;
    public ?string $primaryDomain;
    public int $maxTabs;
    public bool $debugMode;
    protected array $activeTabs = [];
    protected string $status = 'idle';
    protected $mainDriver = null; // 主WebDriver实例

    public function __construct(array $config)
    {
        $this->id = $config['id'];
        $this->isExclusive = $config['isExclusive'] ?? false;
        $this->primaryDomain = $config['primaryDomain'] ?? null;
        $this->maxTabs = $config['maxTabs'] ?? 5;
        $this->debugMode = $config['debugMode'] ?? false;
    }

    public function newTab(Task $task): TabSessionWrapper
    {
        if (!$this->canAcceptNewTab()) {
            throw new Exception("浏览器实例无法接受新标签页");
        }

        $tabId = uniqid();

        // 如果是第一个标签页，创建主WebDriver实例
        if (empty($this->activeTabs)) {
            $this->createMainDriver();
        }

        $tab = new TabSessionWrapper($tabId, $this, $task);

        $this->activeTabs[$tabId] = $tab;
        $this->status = 'busy';

        // 更新数据库记录
        $this->updateDatabaseRecord();

        Log::info("创建新标签页", [
            'instance_id' => $this->id,
            'tab_id' => $tabId,
            'task' => $task->name,
            'total_tabs' => count($this->activeTabs)
        ]);

        return $tab;
    }

    public function closeTab(TabSessionWrapper $tab): void
    {
        if (isset($this->activeTabs[$tab->id])) {
            $tab->close();
            unset($this->activeTabs[$tab->id]);
            
            Log::info("关闭标签页", [
                'instance_id' => $this->id,
                'tab_id' => $tab->id
            ]);
        }
        
        if (empty($this->activeTabs)) {
            $this->status = 'idle';
        }
    }

    public function canAcceptNewTab(): bool
    {
        return !$this->isExclusive && 
               count($this->activeTabs) < $this->maxTabs && 
               $this->status !== 'error';
    }

    public function getActiveTabCount(): int
    {
        return count($this->activeTabs);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function markAsIdle(): void
    {
        if (empty($this->activeTabs)) {
            $this->status = 'idle';
        }
    }

    public function destroy(): void
    {
        foreach ($this->activeTabs as $tab) {
            $tab->close();
        }
        $this->activeTabs = [];
        $this->status = 'destroyed';

        // 关闭主WebDriver实例
        if ($this->mainDriver) {
            try {
                $this->mainDriver->quit();
            } catch (Exception $e) {
                Log::warning("关闭主WebDriver失败", ['error' => $e->getMessage()]);
            }
            $this->mainDriver = null;
        }

        // 删除数据库记录
        BrowserInstance::where('id', $this->id)->delete();
    }

    public function getMainDriver()
    {
        return $this->mainDriver;
    }

    protected function createMainDriver(): void
    {
        try {
            // 配置Chrome选项
            $options = new ChromeOptions();

            // 基础参数
            $arguments = [
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--window-size=1920,1080',
                '--disable-web-security',
                '--disable-features=VizDisplayCompositor',
                '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ];

            // 根据任务的调试模式决定是否启用无头模式
            if (!$this->debugMode) {
                $arguments[] = '--headless';
                Log::info("启用无头模式", ['instance_id' => $this->id]);
            } else {
                Log::info("启用有头模式（调试模式）", ['instance_id' => $this->id]);
            }

            $options->addArguments($arguments);

            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

            // 创建主WebDriver实例
            $this->mainDriver = RemoteWebDriver::create(
                'http://localhost:9515',
                $capabilities,
                5000,
                5000
            );

            Log::info("主WebDriver创建成功", ['instance_id' => $this->id]);

        } catch (Exception $e) {
            Log::error("主WebDriver创建失败", [
                'instance_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function updateDatabaseRecord(): void
    {
        try {
            BrowserInstance::updateOrCreate(
                ['id' => $this->id],
                [
                    'status' => $this->status,
                    'primary_domain' => $this->primaryDomain,
                    'is_exclusive' => $this->isExclusive,
                    'active_tabs' => array_keys($this->activeTabs),
                    'resource_usage' => [
                        'tab_count' => count($this->activeTabs),
                        'memory_usage' => 0, // 后续实现
                        'cpu_usage' => 0     // 后续实现
                    ],
                    'last_activity_at' => now()
                ]
            );
        } catch (Exception $e) {
            Log::warning("更新浏览器实例数据库记录失败", [
                'instance_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

/**
 * 标签页会话包装器
 */
class TabSessionWrapper
{
    public string $id;
    public $driver; // WebDriver实例
    protected BrowserInstanceWrapper $instance;
    protected Task $task;
    protected ?string $windowHandle = null; // 窗口句柄

    public function __construct(string $id, BrowserInstanceWrapper $instance, Task $task)
    {
        $this->id = $id;
        $this->instance = $instance;
        $this->task = $task;

        // 创建实际的WebDriver实例
        $this->driver = $this->createWebDriver();
    }

    protected function createWebDriver()
    {
        try {
            // 使用实例的主WebDriver创建新标签页
            $mainDriver = $this->instance->getMainDriver();
            if ($mainDriver) {
                // 打开新标签页
                $mainDriver->executeScript('window.open("about:blank", "_blank");');

                // 获取所有窗口句柄
                $windowHandles = $mainDriver->getWindowHandles();

                // 切换到新标签页（最后一个窗口句柄）
                $newWindowHandle = end($windowHandles);
                $mainDriver->switchTo()->window($newWindowHandle);

                Log::info("新标签页创建成功", [
                    'tab_id' => $this->id,
                    'window_handle' => $newWindowHandle,
                    'total_windows' => count($windowHandles)
                ]);

                // 返回主WebDriver实例，但记录当前窗口句柄
                $this->windowHandle = $newWindowHandle;
                return $mainDriver;
            }

            Log::warning("主WebDriver不存在，使用模拟对象", ['tab_id' => $this->id]);
            return new MockWebDriver();

        } catch (Exception $e) {
            Log::warning("标签页创建失败，使用模拟对象", [
                'tab_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            return new MockWebDriver();
        }
    }

    public function close(): void
    {
        try {
            if ($this->windowHandle && $this->driver) {
                // 切换到要关闭的标签页
                $this->driver->switchTo()->window($this->windowHandle);

                // 关闭当前标签页
                $this->driver->close();

                // 获取剩余的窗口句柄
                $remainingHandles = $this->driver->getWindowHandles();

                // 如果还有其他标签页，切换到第一个
                if (!empty($remainingHandles)) {
                    $this->driver->switchTo()->window($remainingHandles[0]);
                }

                Log::info("标签页关闭成功", [
                    'tab_id' => $this->id,
                    'window_handle' => $this->windowHandle,
                    'remaining_tabs' => count($remainingHandles)
                ]);
            }
        } catch (Exception $e) {
            Log::warning("关闭标签页失败", [
                'tab_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function switchToTab(): void
    {
        if ($this->windowHandle && $this->driver) {
            try {
                $this->driver->switchTo()->window($this->windowHandle);
                Log::debug("切换到标签页", [
                    'tab_id' => $this->id,
                    'window_handle' => $this->windowHandle
                ]);
            } catch (Exception $e) {
                Log::warning("切换标签页失败", [
                    'tab_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function getWindowHandle(): ?string
    {
        return $this->windowHandle;
    }
}

/**
 * 模拟WebDriver（用于开发阶段）
 */
class MockWebDriver
{
    public function navigate()
    {
        return new class {
            public function to($url) {
                Log::info("模拟导航到: {$url}");
                return $this;
            }
        };
    }

    public function manage()
    {
        return new class {
            public function getCookies() {
                return [];
            }
            
            public function addCookie($cookie) {
                Log::info("模拟添加Cookie", $cookie);
                return $this;
            }
        };
    }

    public function quit()
    {
        Log::info("模拟关闭WebDriver");
    }
}
