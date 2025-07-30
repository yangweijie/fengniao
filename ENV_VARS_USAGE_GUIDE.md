# 🌍 环境变量使用指南

## 🎯 **什么是环境变量？**

环境变量是在任务执行时可以访问的键值对配置，用于：
- 存储API密钥、数据库密码等敏感信息
- 配置不同环境的参数（开发/测试/生产）
- 动态控制任务行为
- 复用配置信息，避免硬编码

## ⚙️ **如何配置环境变量**

### **在任务编辑页面的"环境变量"标签页中添加：**

| 变量名 | 变量值 | 说明 |
|--------|--------|------|
| `API_KEY` | `sk-1234567890abcdef` | API密钥 |
| `BASE_URL` | `https://api.example.com` | 基础URL |
| `TIMEOUT` | `30` | 超时时间（秒） |
| `DEBUG_MODE` | `true` | 调试模式开关 |
| `RETRY_COUNT` | `3` | 重试次数 |

## 💻 **如何在脚本中使用**

### **基本用法**
```php
// 获取环境变量
$apiKey = $envVars['API_KEY'] ?? 'default_key';
$baseUrl = $envVars['BASE_URL'];
$timeout = (int)($envVars['TIMEOUT'] ?? 30);
$debugMode = ($envVars['DEBUG_MODE'] ?? 'false') === 'true';

// 使用环境变量
if ($debugMode) {
    echo "调试模式已开启";
}
```

### **API调用示例**
```php
$apiKey = $envVars['API_KEY'];
$baseUrl = $envVars['BASE_URL'];
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
}
```

## 🎯 **实际案例**

### **案例1：API数据采集**

#### **环境变量配置：**
```
API_BASE_URL = https://api.example.com
API_KEY = sk-1234567890abcdef
RATE_LIMIT = 100
TIMEOUT = 30
RETRY_COUNT = 3
```

#### **脚本代码：**
```php
$baseUrl = $envVars['API_BASE_URL'];
$apiKey = $envVars['API_KEY'];
$timeout = (int)$envVars['TIMEOUT'];
$retryCount = (int)$envVars['RETRY_COUNT'];

for ($i = 0; $i < $retryCount; $i++) {
    try {
        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey
            ])
            ->get($baseUrl . '/data');
            
        if ($response->successful()) {
            $data = $response->json();
            // 处理数据
            break;
        }
    } catch (Exception $e) {
        if ($i === $retryCount - 1) {
            throw $e; // 最后一次重试失败
        }
        sleep(2); // 等待2秒后重试
    }
}
```

### **案例2：数据库操作**

#### **环境变量配置：**
```
DB_HOST = localhost
DB_PORT = 3306
DB_DATABASE = automation
DB_USERNAME = root
DB_PASSWORD = secret123
TABLE_PREFIX = task_
```

#### **脚本代码：**
```php
$host = $envVars['DB_HOST'];
$port = $envVars['DB_PORT'];
$database = $envVars['DB_DATABASE'];
$username = $envVars['DB_USERNAME'];
$password = $envVars['DB_PASSWORD'];
$prefix = $envVars['TABLE_PREFIX'];

$dsn = "mysql:host={$host};port={$port};dbname={$database}";
$pdo = new PDO($dsn, $username, $password);

$stmt = $pdo->prepare(
    "INSERT INTO {$prefix}results (data, created_at) VALUES (?, NOW())"
);
$stmt->execute([json_encode($collectedData)]);
```

### **案例3：文件操作**

#### **环境变量配置：**
```
DOWNLOAD_PATH = /var/www/downloads
BACKUP_PATH = /var/backups
FILE_PREFIX = task_
MAX_FILE_SIZE = 10485760
ALLOWED_EXTENSIONS = jpg,png,pdf,xlsx
```

#### **脚本代码：**
```php
$downloadPath = $envVars['DOWNLOAD_PATH'];
$backupPath = $envVars['BACKUP_PATH'];
$filePrefix = $envVars['FILE_PREFIX'];
$maxSize = (int)$envVars['MAX_FILE_SIZE'];
$allowedExts = explode(',', $envVars['ALLOWED_EXTENSIONS']);

// 生成文件名
$filename = $filePrefix . date('Y-m-d_H-i-s') . '.xlsx';
$fullPath = $downloadPath . '/' . $filename;

// 下载文件后检查
if (file_exists($fullPath)) {
    // 检查文件大小
    if (filesize($fullPath) > $maxSize) {
        throw new Exception('文件大小超过限制');
    }
    
    // 检查文件扩展名
    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
    if (!in_array($ext, $allowedExts)) {
        throw new Exception('不允许的文件类型');
    }
    
    // 移动到备份目录
    $backupFile = $backupPath . '/' . $filename;
    rename($fullPath, $backupFile);
}
```

## 🔧 **高级技巧**

### **条件执行**
```php
$environment = $envVars['ENVIRONMENT'] ?? 'development';
$enableNotifications = ($envVars['ENABLE_NOTIFICATIONS'] ?? 'false') === 'true';

if ($environment === 'production') {
    // 生产环境特殊处理
    $browser->visit('https://prod.example.com');
    
    if ($enableNotifications) {
        sendNotification('任务开始执行');
    }
} else {
    // 开发环境
    $browser->visit('https://dev.example.com');
}
```

### **配置验证**
```php
// 验证必需的环境变量
$requiredVars = ['API_KEY', 'BASE_URL', 'DB_PASSWORD'];
$missingVars = [];

foreach ($requiredVars as $var) {
    if (!isset($envVars[$var]) || empty($envVars[$var])) {
        $missingVars[] = $var;
    }
}

if (!empty($missingVars)) {
    throw new Exception('缺少必需的环境变量: ' . implode(', ', $missingVars));
}

// 验证格式
$apiKey = $envVars['API_KEY'];
if (!preg_match('/^sk-[a-zA-Z0-9]{32}$/', $apiKey)) {
    throw new Exception('API_KEY格式不正确');
}
```

### **动态配置**
```php
$batchSize = (int)($envVars['BATCH_SIZE'] ?? 10);
$delayMs = (int)($envVars['DELAY_MS'] ?? 500);

$items = getItemsToProcess();
$batches = array_chunk($items, $batchSize);

foreach ($batches as $batch) {
    processBatch($batch);
    usleep($delayMs * 1000); // 批次间延迟
}
```

## 🛡️ **安全最佳实践**

### **1. 敏感信息保护**
- ❌ 不要在脚本中硬编码密码和API密钥
- ✅ 使用环境变量存储所有敏感信息
- ✅ 定期轮换API密钥和密码

### **2. 数据验证**
```php
// 始终验证环境变量的值
$apiUrl = $envVars['API_URL'] ?? '';
if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
    throw new Exception('API_URL格式不正确');
}

$timeout = (int)($envVars['TIMEOUT'] ?? 0);
if ($timeout < 1 || $timeout > 300) {
    throw new Exception('超时时间必须在1-300秒之间');
}
```

### **3. 错误处理**
```php
try {
    $apiKey = $envVars['API_KEY'] ?? throw new Exception('API密钥未配置');
    // 使用API密钥...
} catch (Exception $e) {
    // 记录错误，但不暴露敏感信息
    $safeMessage = str_replace($apiKey, '***', $e->getMessage());
    error_log("任务执行失败: " . $safeMessage);
    throw new Exception('API调用失败，请检查配置');
}
```

## 📋 **常用环境变量模板**

### **API调用任务**
```
API_BASE_URL = https://api.example.com
API_KEY = your_api_key_here
API_VERSION = v1
TIMEOUT = 30
RETRY_COUNT = 3
RATE_LIMIT = 100
```

### **数据库任务**
```
DB_HOST = localhost
DB_PORT = 3306
DB_DATABASE = automation
DB_USERNAME = root
DB_PASSWORD = your_password
TABLE_PREFIX = task_
```

### **文件处理任务**
```
INPUT_PATH = /var/www/input
OUTPUT_PATH = /var/www/output
BACKUP_PATH = /var/backups
FILE_PREFIX = processed_
MAX_FILE_SIZE = 10485760
```

### **邮件通知任务**
```
SMTP_HOST = smtp.gmail.com
SMTP_PORT = 587
SMTP_USERNAME = your@gmail.com
SMTP_PASSWORD = app_password
MAIL_FROM = noreply@company.com
MAIL_TO = admin@company.com
```

## 🎉 **总结**

环境变量的核心优势：

✅ **安全性** - 敏感信息与代码分离  
✅ **灵活性** - 不同环境使用不同配置  
✅ **复用性** - 同一套脚本适用多种场景  
✅ **维护性** - 配置修改无需改代码  
✅ **可控性** - 动态调整任务行为  

通过合理使用环境变量，可以让自动化任务更加安全、灵活和易于维护！
