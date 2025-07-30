<div class="space-y-6" @click.stop>
    <!-- 概述 -->
    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
        <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">
            🌍 环境变量使用指南
        </h3>
        <p class="text-green-800 dark:text-green-200">
            环境变量用于存储任务执行时的配置信息，支持动态配置、敏感信息保护和环境隔离。
        </p>
    </div>

    <!-- 标签页导航 -->
    <div x-data="{ activeTab: 'basic' }" class="w-full" @click.stop>
        <!-- 标签页头部 -->
        <div class="flex space-x-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg mb-4">
            <div @click.stop="activeTab = 'basic'" 
                 :class="activeTab === 'basic' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                 class="flex-1 py-2 px-4 rounded-md font-medium transition-colors cursor-pointer text-center">
                📋 基础用法
            </div>
            <div @click.stop="activeTab = 'examples'" 
                 :class="activeTab === 'examples' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                 class="flex-1 py-2 px-4 rounded-md font-medium transition-colors cursor-pointer text-center">
                🎯 实际案例
            </div>
            <div @click.stop="activeTab = 'advanced'" 
                 :class="activeTab === 'advanced' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                 class="flex-1 py-2 px-4 rounded-md font-medium transition-colors cursor-pointer text-center">
                🔧 高级技巧
            </div>
            <div @click.stop="activeTab = 'security'" 
                 :class="activeTab === 'security' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                 class="flex-1 py-2 px-4 rounded-md font-medium transition-colors cursor-pointer text-center">
                🛡️ 安全实践
            </div>
        </div>

        <!-- 基础用法 -->
        <div x-show="activeTab === 'basic'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">📋 基础用法</h3>
            
            <div class="grid grid-cols-1 gap-4">
                <!-- 什么是环境变量 -->
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3">🤔 什么是环境变量？</h4>
                    <div class="space-y-2 text-sm">
                        <p>环境变量是在任务执行时可以访问的键值对配置，用于：</p>
                        <ul class="list-disc list-inside space-y-1 ml-4">
                            <li>存储API密钥、数据库密码等敏感信息</li>
                            <li>配置不同环境的参数（开发/测试/生产）</li>
                            <li>动态控制任务行为</li>
                            <li>复用配置信息，避免硬编码</li>
                        </ul>
                    </div>
                </div>

                <!-- 如何配置 -->
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-3">⚙️ 如何配置环境变量</h4>
                    <div class="space-y-2 text-sm">
                        <p><strong>在环境变量标签页中添加键值对：</strong></p>
                        <div class="bg-gray-900 text-green-400 p-3 rounded text-xs">
                            <div>变量名: API_KEY</div>
                            <div>变量值: your_api_key_here</div>
                            <br>
                            <div>变量名: DATABASE_URL</div>
                            <div>变量值: mysql://user:pass@localhost/db</div>
                            <br>
                            <div>变量名: DEBUG_MODE</div>
                            <div>变量值: true</div>
                        </div>
                    </div>
                </div>

                <!-- 如何使用 -->
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                    <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-3">💻 如何在脚本中使用</h4>
                    <div class="space-y-2 text-sm">
                        <p><strong>在脚本中通过 $envVars 数组访问：</strong></p>
                        <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto"><code>// 获取环境变量
$apiKey = $envVars['API_KEY'] ?? 'default_key';
$databaseUrl = $envVars['DATABASE_URL'];
$debugMode = $envVars['DEBUG_MODE'] === 'true';

// 使用环境变量
if ($debugMode) {
    echo "调试模式已开启";
}

// API调用示例
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey
])->get('https://api.example.com/data');</code></pre>
                    </div>
                </div>

                <!-- 常用变量类型 -->
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-3">📝 常用变量类型</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <strong>认证信息：</strong>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>API_KEY - API密钥</li>
                                <li>ACCESS_TOKEN - 访问令牌</li>
                                <li>USERNAME - 用户名</li>
                                <li>PASSWORD - 密码</li>
                            </ul>
                        </div>
                        <div>
                            <strong>配置参数：</strong>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>BASE_URL - 基础URL</li>
                                <li>TIMEOUT - 超时时间</li>
                                <li>RETRY_COUNT - 重试次数</li>
                                <li>DEBUG_MODE - 调试模式</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 实际案例 -->
        <div x-show="activeTab === 'examples'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">🎯 实际案例</h3>
            
            <!-- API数据采集案例 -->
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3">🌐 API数据采集任务</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>环境变量配置：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">API_BASE_URL = https://api.example.com
API_KEY = sk-1234567890abcdef
RATE_LIMIT = 100
TIMEOUT = 30
RETRY_COUNT = 3</pre>
                    </div>
                    <div>
                        <strong>脚本代码：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">$baseUrl = $envVars['API_BASE_URL'];
$apiKey = $envVars['API_KEY'];
$timeout = (int)$envVars['TIMEOUT'];

$response = Http::timeout($timeout)
    ->withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Accept' => 'application/json'
    ])
    ->get($baseUrl . '/users');

if ($response->successful()) {
    $data = $response->json();
    // 处理数据...
}</pre>
                    </div>
                </div>
            </div>

            <!-- 数据库操作案例 -->
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                <h4 class="font-semibold text-green-900 dark:text-green-100 mb-3">🗄️ 数据库操作任务</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>环境变量配置：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">DB_HOST = localhost
DB_PORT = 3306
DB_DATABASE = automation
DB_USERNAME = root
DB_PASSWORD = secret123
TABLE_PREFIX = task_</pre>
                    </div>
                    <div>
                        <strong>脚本代码：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">$host = $envVars['DB_HOST'];
$database = $envVars['DB_DATABASE'];
$username = $envVars['DB_USERNAME'];
$password = $envVars['DB_PASSWORD'];
$prefix = $envVars['TABLE_PREFIX'];

$pdo = new PDO(
    "mysql:host={$host};dbname={$database}",
    $username,
    $password
);

$stmt = $pdo->prepare(
    "INSERT INTO {$prefix}results (data, created_at) VALUES (?, NOW())"
);
$stmt->execute([$collectedData]);</pre>
                    </div>
                </div>
            </div>

            <!-- 文件操作案例 -->
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-3">📁 文件操作任务</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>环境变量配置：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">DOWNLOAD_PATH = /var/www/downloads
BACKUP_PATH = /var/backups
FILE_PREFIX = task_
MAX_FILE_SIZE = 10485760
ALLOWED_EXTENSIONS = jpg,png,pdf,xlsx</pre>
                    </div>
                    <div>
                        <strong>脚本代码：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">$downloadPath = $envVars['DOWNLOAD_PATH'];
$filePrefix = $envVars['FILE_PREFIX'];
$maxSize = (int)$envVars['MAX_FILE_SIZE'];
$allowedExts = explode(',', $envVars['ALLOWED_EXTENSIONS']);

// 下载文件
$filename = $filePrefix . date('Y-m-d_H-i-s') . '.xlsx';
$fullPath = $downloadPath . '/' . $filename;

// 检查文件大小
if (filesize($fullPath) > $maxSize) {
    throw new Exception('文件大小超过限制');
}

// 移动到备份目录
$backupPath = $envVars['BACKUP_PATH'] . '/' . $filename;
rename($fullPath, $backupPath);</pre>
                    </div>
                </div>
            </div>

            <!-- 邮件通知案例 -->
            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg border border-orange-200 dark:border-orange-800">
                <h4 class="font-semibold text-orange-900 dark:text-orange-100 mb-3">📧 邮件通知任务</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>环境变量配置：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">SMTP_HOST = smtp.gmail.com
SMTP_PORT = 587
SMTP_USERNAME = your@gmail.com
SMTP_PASSWORD = app_password
MAIL_FROM = noreply@company.com
MAIL_TO = admin@company.com
MAIL_SUBJECT_PREFIX = [自动化任务]</pre>
                    </div>
                    <div>
                        <strong>脚本代码：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $envVars['SMTP_HOST'];
$mail->Port = $envVars['SMTP_PORT'];
$mail->Username = $envVars['SMTP_USERNAME'];
$mail->Password = $envVars['SMTP_PASSWORD'];

$mail->setFrom($envVars['MAIL_FROM']);
$mail->addAddress($envVars['MAIL_TO']);

$subject = $envVars['MAIL_SUBJECT_PREFIX'] . ' 任务执行完成';
$mail->Subject = $subject;
$mail->Body = "任务已成功执行，共处理 {$count} 条数据。";

$mail->send();</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- 高级技巧 -->
        <div x-show="activeTab === 'advanced'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">🔧 高级技巧</h3>
            
            <!-- 条件执行 -->
            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <h4 class="font-semibold text-indigo-900 dark:text-indigo-100 mb-3">🔀 条件执行</h4>
                <div class="space-y-2 text-sm">
                    <p>根据环境变量控制任务执行流程：</p>
                    <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto"><code>// 环境变量: ENVIRONMENT = production, ENABLE_NOTIFICATIONS = true

$environment = $envVars['ENVIRONMENT'] ?? 'development';
$enableNotifications = ($envVars['ENABLE_NOTIFICATIONS'] ?? 'false') === 'true';

if ($environment === 'production') {
    // 生产环境特殊处理
    $browser->visit('https://prod.example.com');
    
    if ($enableNotifications) {
        // 发送通知
        sendNotification('任务开始执行');
    }
} else {
    // 开发环境
    $browser->visit('https://dev.example.com');
}</code></pre>
                </div>
            </div>

            <!-- 动态配置 -->
            <div class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-lg border border-teal-200 dark:border-teal-800">
                <h4 class="font-semibold text-teal-900 dark:text-teal-100 mb-3">⚡ 动态配置</h4>
                <div class="space-y-2 text-sm">
                    <p>使用环境变量动态调整任务行为：</p>
                    <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto"><code>// 环境变量: BATCH_SIZE = 50, DELAY_MS = 1000, MAX_RETRIES = 3

$batchSize = (int)($envVars['BATCH_SIZE'] ?? 10);
$delayMs = (int)($envVars['DELAY_MS'] ?? 500);
$maxRetries = (int)($envVars['MAX_RETRIES'] ?? 1);

$items = getItemsToProcess();
$batches = array_chunk($items, $batchSize);

foreach ($batches as $batch) {
    $retries = 0;
    
    while ($retries < $maxRetries) {
        try {
            processBatch($batch);
            break; // 成功，跳出重试循环
        } catch (Exception $e) {
            $retries++;
            if ($retries >= $maxRetries) {
                throw $e;
            }
            usleep($delayMs * 1000); // 延迟重试
        }
    }
    
    usleep($delayMs * 1000); // 批次间延迟
}</code></pre>
                </div>
            </div>

            <!-- 多环境配置 -->
            <div class="bg-pink-50 dark:bg-pink-900/20 p-4 rounded-lg border border-pink-200 dark:border-pink-800">
                <h4 class="font-semibold text-pink-900 dark:text-pink-100 mb-3">🌍 多环境配置</h4>
                <div class="space-y-2 text-sm">
                    <p>为不同环境设置不同的配置：</p>
                    <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto"><code>// 环境变量配置示例
// DEV_API_URL = https://dev-api.example.com
// PROD_API_URL = https://api.example.com
// ENVIRONMENT = production

$environment = $envVars['ENVIRONMENT'] ?? 'development';

$config = [
    'development' => [
        'api_url' => $envVars['DEV_API_URL'],
        'debug' => true,
        'timeout' => 60,
    ],
    'production' => [
        'api_url' => $envVars['PROD_API_URL'],
        'debug' => false,
        'timeout' => 30,
    ]
];

$currentConfig = $config[$environment];

$response = Http::timeout($currentConfig['timeout'])
    ->get($currentConfig['api_url'] . '/data');

if ($currentConfig['debug']) {
    echo "API响应: " . $response->body();
}</code></pre>
                </div>
            </div>

            <!-- 配置验证 -->
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                <h4 class="font-semibold text-red-900 dark:text-red-100 mb-3">✅ 配置验证</h4>
                <div class="space-y-2 text-sm">
                    <p>在任务开始前验证必需的环境变量：</p>
                    <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto"><code>// 验证必需的环境变量
$requiredVars = ['API_KEY', 'DATABASE_URL', 'SMTP_HOST'];
$missingVars = [];

foreach ($requiredVars as $var) {
    if (!isset($envVars[$var]) || empty($envVars[$var])) {
        $missingVars[] = $var;
    }
}

if (!empty($missingVars)) {
    throw new Exception('缺少必需的环境变量: ' . implode(', ', $missingVars));
}

// 验证配置格式
$apiKey = $envVars['API_KEY'];
if (!preg_match('/^sk-[a-zA-Z0-9]{32}$/', $apiKey)) {
    throw new Exception('API_KEY格式不正确');
}

$timeout = $envVars['TIMEOUT'] ?? '30';
if (!is_numeric($timeout) || $timeout < 1) {
    throw new Exception('TIMEOUT必须是正整数');
}</code></pre>
                </div>
            </div>
        </div>

        <!-- 安全实践 -->
        <div x-show="activeTab === 'security'" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">🛡️ 安全实践</h3>
            
            <!-- 敏感信息保护 -->
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                <h4 class="font-semibold text-red-900 dark:text-red-100 mb-3">🔒 敏感信息保护</h4>
                <div class="space-y-3 text-sm">
                    <div>
                        <strong>❌ 不要这样做：</strong>
                        <pre class="bg-gray-900 text-red-400 p-2 rounded mt-1 text-xs">// 直接在脚本中硬编码敏感信息
$apiKey = 'sk-1234567890abcdef'; // 危险！
$password = 'mypassword123'; // 危险！</pre>
                    </div>
                    <div>
                        <strong>✅ 正确做法：</strong>
                        <pre class="bg-gray-900 text-green-400 p-2 rounded mt-1 text-xs">// 使用环境变量存储敏感信息
$apiKey = $envVars['API_KEY'] ?? throw new Exception('API_KEY未配置');
$password = $envVars['DB_PASSWORD'] ?? throw new Exception('数据库密码未配置');</pre>
                    </div>
                </div>
            </div>

            <!-- 访问控制 -->
            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg border border-orange-200 dark:border-orange-800">
                <h4 class="font-semibold text-orange-900 dark:text-orange-100 mb-3">👥 访问控制</h4>
                <ul class="space-y-2 text-sm">
                    <li>• <strong>最小权限原则：</strong>只配置任务必需的环境变量</li>
                    <li>• <strong>定期轮换：</strong>定期更新API密钥和密码</li>
                    <li>• <strong>权限分离：</strong>不同环境使用不同的凭据</li>
                    <li>• <strong>审计日志：</strong>记录环境变量的使用情况</li>
                </ul>
            </div>

            <!-- 数据验证 -->
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3">🔍 数据验证</h4>
                <div class="space-y-2 text-sm">
                    <p>始终验证环境变量的值：</p>
                    <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto"><code>// URL验证
$apiUrl = $envVars['API_URL'] ?? '';
if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
    throw new Exception('API_URL格式不正确');
}

// 邮箱验证
$email = $envVars['NOTIFICATION_EMAIL'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('邮箱格式不正确');
}

// 数值范围验证
$timeout = (int)($envVars['TIMEOUT'] ?? 0);
if ($timeout < 1 || $timeout > 300) {
    throw new Exception('超时时间必须在1-300秒之间');
}</code></pre>
                </div>
            </div>

            <!-- 错误处理 -->
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-3">⚠️ 错误处理</h4>
                <div class="space-y-2 text-sm">
                    <p>安全的错误处理和日志记录：</p>
                    <pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto"><code>try {
    $apiKey = $envVars['API_KEY'] ?? throw new Exception('API密钥未配置');
    
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey
    ])->get($apiUrl);
    
} catch (Exception $e) {
    // 记录错误，但不暴露敏感信息
    $safeMessage = str_replace($apiKey, '***', $e->getMessage());
    error_log("任务执行失败: " . $safeMessage);
    
    // 抛出安全的错误信息
    throw new Exception('API调用失败，请检查配置');
}</code></pre>
                </div>
            </div>

            <!-- 最佳实践清单 -->
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                <h4 class="font-semibold text-green-900 dark:text-green-100 mb-3">📋 安全检查清单</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>配置安全：</strong>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>✅ 使用强密码和复杂API密钥</li>
                            <li>✅ 定期轮换敏感凭据</li>
                            <li>✅ 不在日志中记录敏感信息</li>
                            <li>✅ 使用HTTPS进行API调用</li>
                        </ul>
                    </div>
                    <div>
                        <strong>代码安全：</strong>
                        <ul class="list-disc list-inside mt-1 space-y-1">
                            <li>✅ 验证所有环境变量</li>
                            <li>✅ 使用异常处理机制</li>
                            <li>✅ 实现超时和重试机制</li>
                            <li>✅ 记录操作审计日志</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
