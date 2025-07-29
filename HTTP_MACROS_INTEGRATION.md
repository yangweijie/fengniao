# 🌐 HTTP宏集成完成

## ✅ **新增功能概览**

我已经为您的自动化平台添加了强大的HTTP宏功能，包括：

### **14个HTTP宏方法**
- **智能重试** - `$http::smartRetry()`
- **日志记录** - `$http::withLogging()`
- **JSON API** - `$http::jsonApi()`
- **认证API** - `$http::apiWithAuth()`
- **表单数据** - `$http::formData()`
- **文件上传** - `$http::uploadFile()`
- **文件下载** - `$http::downloadFile()`
- **批量请求** - `$http::batchRequests()`
- **健康检查** - `$http::healthCheck()`
- **分页获取** - `$http::getAllPages()`
- **速率限制** - `$http::withRateLimit()`
- **响应缓存** - `$http::withCache()`
- **浏览器模拟** - `$http::asBrowser()`
- **代理请求** - `$http::withProxy()`

### **6个HTTP代码模板**
- **智能重试模板** - `http-smart-retry`
- **批量请求模板** - `http-batch-requests`
- **文件下载模板** - `http-download-file`
- **健康检查模板** - `http-health-check`
- **分页数据模板** - `http-paginated-data`
- **速率限制模板** - `http-rate-limited`

## 🎯 **HTTP宏详细说明**

### **1. 智能重试 - `$http::smartRetry()`**
```php
$response = $http::smartRetry('https://api.example.com', [], 3, 1000);
```
- **功能**: 自动重试失败的请求，支持指数退避
- **参数**: URL, 选项, 最大重试次数, 初始延迟(毫秒)

### **2. 日志记录 - `$http::withLogging()`**
```php
$response = $http::withLogging('API')->get('https://api.example.com');
```
- **功能**: 自动记录请求和响应日志
- **参数**: 日志前缀

### **3. JSON API - `$http::jsonApi()`**
```php
$response = $http::jsonApi('https://api.example.com')->get('/users');
```
- **功能**: 自动设置JSON请求头，适合API调用
- **参数**: 基础URL

### **4. 认证API - `$http::apiWithAuth()`**
```php
$response = $http::apiWithAuth('token123', 'Bearer', 'https://api.example.com')->get('/protected');
```
- **功能**: 带认证的API请求
- **参数**: Token, 认证类型, 基础URL

### **5. 表单数据 - `$http::formData()`**
```php
$response = $http::formData(['name' => 'John', 'email' => 'john@example.com'])->post('https://example.com/form');
```
- **功能**: 发送表单数据
- **参数**: 表单数据数组

### **6. 文件上传 - `$http::uploadFile()`**
```php
$response = $http::uploadFile('avatar', '/path/to/image.jpg', ['user_id' => 123])->post('https://example.com/upload');
```
- **功能**: 上传文件
- **参数**: 字段名, 文件路径, 额外数据

### **7. 文件下载 - `$http::downloadFile()`**
```php
$result = $http::downloadFile('https://example.com/file.pdf', '/local/path/file.pdf');
```
- **功能**: 下载文件到本地
- **返回**: 包含成功状态、路径和大小的数组

### **8. 批量请求 - `$http::batchRequests()`**
```php
$requests = [
    ['method' => 'GET', 'url' => 'https://api1.example.com'],
    ['method' => 'POST', 'url' => 'https://api2.example.com', 'data' => ['key' => 'value']]
];
$responses = $http::batchRequests($requests, 5);
```
- **功能**: 并发执行多个HTTP请求
- **参数**: 请求数组, 并发数

### **9. 健康检查 - `$http::healthCheck()`**
```php
$health = $http::healthCheck('https://api.example.com/health', 10);
```
- **功能**: 检查服务健康状态
- **返回**: 状态码、响应时间、成功状态等信息

### **10. 分页获取 - `$http::getAllPages()`**
```php
$allData = $http::getAllPages('https://api.example.com/data', ['filter' => 'active'], 'page', 'data');
```
- **功能**: 自动获取所有分页数据
- **参数**: 基础URL, 参数, 页码参数名, 数据键名

### **11. 速率限制 - `$http::withRateLimit()`**
```php
$response = $http::withRateLimit(60)->get('https://api.example.com');
```
- **功能**: 限制请求频率，防止触发API限制
- **参数**: 每分钟请求数

### **12. 响应缓存 - `$http::withCache()`**
```php
$response = $http::withCache(300)->get('https://api.example.com');
```
- **功能**: 缓存HTTP响应，避免重复请求
- **参数**: 缓存时间(秒)

### **13. 浏览器模拟 - `$http::asBrowser()`**
```php
$response = $http::asBrowser('Mozilla/5.0...')->get('https://example.com');
```
- **功能**: 模拟浏览器请求，设置浏览器头
- **参数**: 用户代理字符串(可选)

### **14. 代理请求 - `$http::withProxy()`**
```php
$response = $http::withProxy('http://proxy:8080', 'user:pass')->get('https://example.com');
```
- **功能**: 通过代理发送请求
- **参数**: 代理地址, 认证信息(可选)

## 🎨 **Monaco编辑器中的使用**

### **方法提示**
当您在任务编辑器中输入 `$http::` 时，会显示所有可用的HTTP方法：

- 基础方法：`get`, `post`, `put`, `delete`, `withHeaders`, `withToken`
- 宏方法：`smartRetry`, `withLogging`, `jsonApi`, `apiWithAuth` 等

### **代码模板**
输入以下模板名称可以快速插入完整代码：

#### **智能重试模板 - `http-smart-retry`**
```php
// 智能重试请求模板
try {
    $response = $http::smartRetry('https://api.example.com/endpoint', [
        'param' => 'value'
    ], 3, 1000);
    
    if ($response->successful()) {
        $data = $response->json();
        $log('info', '请求成功', $data);
    }
} catch (\Exception $e) {
    $log('error', '重试失败: ' . $e->getMessage());
}
```

#### **批量请求模板 - `http-batch-requests`**
```php
// 批量请求模板
$requests = [
    ['method' => 'GET', 'url' => 'https://api1.example.com'],
    ['method' => 'POST', 'url' => 'https://api2.example.com', 'data' => ['key' => 'value']],
    ['method' => 'GET', 'url' => 'https://api3.example.com', 'headers' => ['Authorization' => 'Bearer token']]
];

$responses = $http::batchRequests($requests, 5);

foreach ($responses as $index => $response) {
    if ($response->successful()) {
        $log('info', "请求 #$index 成功", $response->json());
    } else {
        $log('error', "请求 #$index 失败: " . $response->status());
    }
}
```

#### **健康检查模板 - `http-health-check`**
```php
// 健康检查模板
$services = [
    'API服务' => 'https://api.example.com/health',
    '数据库' => 'https://db.example.com/ping',
    '缓存' => 'https://cache.example.com/status'
];

foreach ($services as $name => $url) {
    $health = $http::healthCheck($url, 10);
    
    if ($health['success']) {
        $log('info', "$name 健康检查通过", [
            '响应时间' => $health['response_time'] . 'ms',
            '状态码' => $health['status']
        ]);
    } else {
        $log('error', "$name 健康检查失败", [
            '错误' => $health['error'] ?? '未知错误'
        ]);
    }
}
```

## 🚀 **使用场景**

### **API数据采集**
- 使用 `smartRetry` 确保请求成功
- 使用 `withRateLimit` 避免被限制
- 使用 `getAllPages` 获取完整数据

### **服务监控**
- 使用 `healthCheck` 监控服务状态
- 使用 `withLogging` 记录监控日志
- 使用 `batchRequests` 并发检查多个服务

### **文件处理**
- 使用 `downloadFile` 下载文件
- 使用 `uploadFile` 上传文件
- 使用 `asBrowser` 模拟浏览器下载

### **数据同步**
- 使用 `apiWithAuth` 访问受保护的API
- 使用 `withCache` 避免重复请求
- 使用 `formData` 提交表单数据

## 🎯 **立即测试**

1. **打开任务编辑器** → `/admin/tasks/create`
2. **选择API任务类型**
3. **在脚本内容中输入 `$http::`** → 查看所有HTTP方法
4. **输入模板名称** → 如 `http-smart-retry`, `http-batch-requests`
5. **使用Tab键** → 在参数间跳转并编辑

**现在您拥有了强大的HTTP自动化能力！** 🌐
