<div class="space-y-6" @click.stop>
    <!-- 概述 -->
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
            🔐 登录配置项使用指南
        </h3>
        <p class="text-blue-800 dark:text-blue-200">
            登录配置项用于存储网站的自动登录相关信息，实现配置与代码分离，提高安全性和复用性。
        </p>
    </div>

    <!-- 标签页导航 -->
    <div x-data="{ activeTab: 'basic' }" class="w-full" @click.stop>
        <!-- 标签页头部 -->
        <div class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg mb-4">
            <div @click.stop="activeTab = 'basic'" 
                 :class="activeTab === 'basic' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                 class="flex-1 py-2 px-4 rounded-md font-medium transition-colors cursor-pointer text-center">
                📋 基础配置
            </div>
            <div @click.stop="activeTab = 'examples'" 
                 :class="activeTab === 'examples' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                 class="flex-1 py-2 px-4 rounded-md font-medium transition-colors cursor-pointer text-center">
                🎯 实际案例
            </div>
            <div @click.stop="activeTab = 'advanced'" 
                 :class="activeTab === 'advanced' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                 class="flex-1 py-2 px-4 rounded-md font-medium transition-colors cursor-pointer text-center">
                🔧 高级用法
            </div>
            <div @click.stop="activeTab = 'best-practices'" 
                 :class="activeTab === 'best-practices' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                 class="flex-1 py-2 px-4 rounded-md font-medium transition-colors cursor-pointer text-center">
                💡 最佳实践
            </div>
        </div>

        <!-- 基础配置 -->
        <div x-show="activeTab === 'basic'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">📋 基础配置项</h3>
            
            <div class="grid grid-cols-1 gap-4">
                <!-- 必需配置项 -->
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                    <h4 class="font-semibold text-red-900 dark:text-red-100 mb-3">🔴 必需配置项</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-red-100 dark:bg-red-900 px-2 py-1 rounded">login_url</code> - 登录页面URL</div>
                        <div><code class="bg-red-100 dark:bg-red-900 px-2 py-1 rounded">username</code> - 登录用户名/邮箱/手机号</div>
                        <div><code class="bg-red-100 dark:bg-red-900 px-2 py-1 rounded">password</code> - 登录密码</div>
                    </div>
                </div>

                <!-- 页面元素选择器 -->
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3">🎯 页面元素选择器</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">username_selector</code> - 用户名输入框选择器</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">password_selector</code> - 密码输入框选择器</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">login_button_selector</code> - 登录按钮选择器</div>
                        <div><code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">captcha_selector</code> - 验证码输入框（可选）</div>
                    </div>
                </div>

                <!-- 登录验证方式 -->
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-3">✅ 登录验证方式（二选一）</h4>
                    <div class="space-y-2 text-sm">
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">success_indicator</code> - 登录成功元素选择器</div>
                        <div><code class="bg-green-100 dark:bg-green-900 px-2 py-1 rounded">success_url</code> - 登录成功后的URL（支持通配符）</div>
                    </div>
                    <div class="mt-2 p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded text-xs">
                        <strong>💡 推荐使用 success_url：</strong>很多网站登录后会直接跳转，使用URL验证更可靠
                    </div>
                </div>
            </div>

            <!-- 脚本中的使用方法 -->
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">📝 脚本中的使用方法</h4>
                <pre class="bg-gray-900 text-green-400 p-3 rounded text-sm overflow-x-auto"><code>// 访问配置项
$browser->visit($loginConfig['login_url']);
$browser->type($loginConfig['username_selector'], $loginConfig['username']);
$browser->type($loginConfig['password_selector'], $loginConfig['password']);
$browser->click($loginConfig['login_button_selector']);

// 验证登录成功（二选一）
// 方式1：等待元素出现
$browser->waitFor($loginConfig['success_indicator']);

// 方式2：等待URL变化（推荐）
$browser->waitForLocation($loginConfig['success_url']);</code></pre>
            </div>
        </div>

        <!-- 实际案例 -->
        <div x-show="activeTab === 'examples'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">🎯 实际案例</h3>
            
            <!-- 淘宝登录案例 -->
            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg border border-orange-200 dark:border-orange-800">
                <h4 class="font-semibold text-orange-900 dark:text-orange-100 mb-3">🛒 淘宝登录配置</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>配置项：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">login_url = https://login.taobao.com/member/login.jhtml
username = your_phone_number
password = your_password
username_selector = #fm-login-id
password_selector = #fm-login-password
login_button_selector = .fm-button
success_url = https://www.taobao.com/*</pre>
                    </div>
                    <div>
                        <strong>脚本代码：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">$browser->visit($loginConfig['login_url']);
$browser->type($loginConfig['username_selector'], 
    $loginConfig['username']);
$browser->type($loginConfig['password_selector'], 
    $loginConfig['password']);
$browser->click($loginConfig['login_button_selector']);
$browser->waitForLocation($loginConfig['success_url']);</pre>
                    </div>
                </div>
            </div>

            <!-- 京东登录案例 -->
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                <h4 class="font-semibold text-red-900 dark:text-red-100 mb-3">🛍️ 京东登录配置</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>配置项：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">login_url = https://passport.jd.com/new/login.aspx
username = your_phone_number
password = your_password
username_selector = #loginname
password_selector = #nloginpwd
login_button_selector = #loginsubmit
success_url = https://www.jd.com/*
wait_after_login = 2</pre>
                    </div>
                    <div>
                        <strong>脚本代码：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">$browser->visit($loginConfig['login_url']);
$browser->type($loginConfig['username_selector'], 
    $loginConfig['username']);
$browser->type($loginConfig['password_selector'], 
    $loginConfig['password']);
$browser->click($loginConfig['login_button_selector']);
$browser->waitForLocation($loginConfig['success_url']);

// 额外等待时间
if (isset($loginConfig['wait_after_login'])) {
    sleep($loginConfig['wait_after_login']);
}</pre>
                    </div>
                </div>
            </div>

            <!-- GitHub登录案例 -->
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">🐙 GitHub登录配置</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>配置项：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">login_url = https://github.com/login
username = your_username
password = your_password
username_selector = #login_field
password_selector = #password
login_button_selector = input[type="submit"]
success_url = https://github.com/*
success_indicator = .Header-link--profile</pre>
                    </div>
                    <div>
                        <strong>脚本代码：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">$browser->visit($loginConfig['login_url']);
$browser->type($loginConfig['username_selector'], 
    $loginConfig['username']);
$browser->type($loginConfig['password_selector'], 
    $loginConfig['password']);
$browser->click($loginConfig['login_button_selector']);

// 双重验证：URL和元素
$browser->waitForLocation($loginConfig['success_url']);
$browser->waitFor($loginConfig['success_indicator']);</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- 高级用法 -->
        <div x-show="activeTab === 'advanced'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">🔧 高级用法</h3>
            
            <!-- waitForLocation详解 -->
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-3">🌐 waitForLocation 详解</h4>
                <div class="space-y-3 text-sm">
                    <div>
                        <strong>基本用法：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1">// 等待精确URL
$browser->waitForLocation('https://www.example.com/dashboard');

// 等待URL包含特定路径
$browser->waitForLocation('*/dashboard');

// 等待URL匹配模式
$browser->waitForLocation('https://www.example.com/*');</pre>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900/30 p-2 rounded">
                        <strong>💡 为什么推荐使用 waitForLocation？</strong>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>很多现代网站登录后直接跳转，没有"登录成功"元素</li>
                            <li>URL变化比DOM元素更可靠</li>
                            <li>支持通配符匹配，更灵活</li>
                            <li>避免因页面加载慢导致的误判</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 多步骤登录 -->
            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <h4 class="font-semibold text-indigo-900 dark:text-indigo-100 mb-3">🔄 多步骤登录</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>Google登录配置：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">step1_url = https://accounts.google.com/signin
username_selector = input[type="email"]
next_button_selector = #identifierNext
password_selector = input[type="password"]
login_button_selector = #passwordNext
success_url = https://myaccount.google.com/*</pre>
                    </div>
                    <div>
                        <strong>脚本代码：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">// 第一步：输入邮箱
$browser->visit($loginConfig['step1_url']);
$browser->type($loginConfig['username_selector'], 
    $loginConfig['username']);
$browser->click($loginConfig['next_button_selector']);

// 等待密码页面
$browser->waitFor($loginConfig['password_selector']);

// 第二步：输入密码
$browser->type($loginConfig['password_selector'], 
    $loginConfig['password']);
$browser->click($loginConfig['login_button_selector']);

// 等待登录成功
$browser->waitForLocation($loginConfig['success_url']);</pre>
                    </div>
                </div>
            </div>

            <!-- 验证码处理 -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-3">🔐 验证码处理</h4>
                <div class="space-y-2 text-sm">
                    <div>
                        <strong>配置项：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1">captcha_selector = #captcha
captcha_image_selector = #captcha-image
manual_captcha = true</pre>
                    </div>
                    <div>
                        <strong>脚本处理：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1">// 检查是否有验证码
if (isset($loginConfig['captcha_selector']) && 
    $browser->element($loginConfig['captcha_selector'])) {
    
    // 截取验证码图片
    $browser->screenshot('captcha.png');
    
    // 暂停等待手动输入
    echo "请查看captcha.png并输入验证码";
    $browser->pause(30000); // 30秒
}</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- 最佳实践 -->
        <div x-show="activeTab === 'best-practices'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">💡 最佳实践</h3>
            
            <!-- 安全建议 -->
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                <h4 class="font-semibold text-red-900 dark:text-red-100 mb-3">🛡️ 安全建议</h4>
                <ul class="space-y-2 text-sm">
                    <li>• <strong>避免明文密码：</strong>考虑使用环境变量或加密存储</li>
                    <li>• <strong>定期更新：</strong>定期更换登录凭据</li>
                    <li>• <strong>权限最小化：</strong>使用专门的测试账号，避免使用管理员账号</li>
                    <li>• <strong>监控异常：</strong>设置登录失败告警</li>
                </ul>
            </div>

            <!-- 配置规范 -->
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3">📝 配置规范</h4>
                <ul class="space-y-2 text-sm">
                    <li>• <strong>命名规范：</strong>使用下划线分隔，如 <code>username_selector</code></li>
                    <li>• <strong>语义化：</strong>使用有意义的名称，如 <code>success_indicator</code></li>
                    <li>• <strong>分组命名：</strong>多步骤用 <code>step1_url</code>, <code>step2_url</code></li>
                    <li>• <strong>注释说明：</strong>复杂配置添加说明注释</li>
                </ul>
            </div>

            <!-- 错误处理 -->
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                <h4 class="font-semibold text-green-900 dark:text-green-100 mb-3">⚠️ 错误处理</h4>
                <div class="space-y-2 text-sm">
                    <div>
                        <strong>配置检查：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1">// 检查必需配置项
$required = ['login_url', 'username', 'password'];
foreach ($required as $key) {
    if (!isset($loginConfig[$key])) {
        throw new Exception("缺少必需的登录配置: {$key}");
    }
}</pre>
                    </div>
                    <div>
                        <strong>登录失败处理：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1">// 设置超时和重试
try {
    $browser->waitForLocation($loginConfig['success_url'], 10);
} catch (Exception $e) {
    // 检查是否有错误信息
    if ($browser->element('.error-message')) {
        $error = $browser->text('.error-message');
        throw new Exception("登录失败: " . $error);
    }
    throw $e;
}</pre>
                    </div>
                </div>
            </div>

            <!-- 性能优化 -->
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-3">⚡ 性能优化</h4>
                <ul class="space-y-2 text-sm">
                    <li>• <strong>合理超时：</strong>设置适当的等待时间，避免过长等待</li>
                    <li>• <strong>缓存登录：</strong>使用Cookie保持登录状态</li>
                    <li>• <strong>并发控制：</strong>避免同时多个任务使用同一账号</li>
                    <li>• <strong>资源清理：</strong>任务结束后清理浏览器资源</li>
                </ul>
            </div>
        </div>
    </div>
</div>
