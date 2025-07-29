# 🚀 Dusk 自动化宏完整指南

## 📋 **宏功能总览**

我为您的Dusk自动化平台创建了一套完整的宏系统，包含40+个实用宏，让自动化脚本编写变得更加简单高效。

## 🎯 **宏分类**

### **1. 智能等待类**
- `waitForAnyElement(array $selectors, int $seconds = 10)` - 等待任意元素出现
- `waitForPageLoad(int $timeout = 30)` - 等待页面完全加载
- `waitForAjax(int $timeout = 30)` - 等待Ajax请求完成
- `waitForLoadingToFinish(array $selectors, int $timeout = 30)` - 等待加载动画消失
- `waitUntilMissing(string $selector, int $seconds = 10)` - 等待元素消失
- `waitAndGetText(string $selector, int $timeout = 10)` - 等待并获取文本
- `waitForUrlContains(string $needle, int $seconds = 10)` - 等待URL包含特定字符串
- `waitForTitle(string $title, int $timeout = 10)` - 等待页面标题

### **2. 智能交互类**
- `smartClick(string $selector, int $timeout = 10)` - 智能点击（支持多种选择器）
- `smartType(string $selector, string $text, bool $clear = true)` - 智能输入
- `humanType(string $selector, string $text)` - 模拟人类输入
- `scrollAndClick(string $selector)` - 滚动到元素并点击
- `clickIfExists(string $selector, int $timeout = 5)` - 条件点击
- `clickAll(string $selector, int $delay = 500)` - 批量点击
- `smartSelect(string $selector, string $option)` - 智能下拉选择

### **3. 表单操作类**
- `fillForm(array $data)` - 智能表单填写
- `smartLogin(string $usernameSelector, string $passwordSelector, string $username, string $password, string $submitSelector)` - 智能登录
- `smartSearch(string $searchSelector, string $query, string $submitSelector = null)` - 智能搜索
- `fillTableRow(string $tableSelector, int $rowIndex, array $data)` - 填写表格行
- `smartUpload(string $selector, string $filePath)` - 智能文件上传

### **4. 页面检测类**
- `hasElement(string $selector)` - 检查元素是否存在
- `getAttribute(string $selector, string $attribute)` - 获取元素属性
- `setAttribute(string $selector, string $attribute, string $value)` - 设置元素属性
- `removeAttribute(string $selector, string $attribute)` - 移除元素属性
- `getAllText(string $selector)` - 获取所有匹配元素的文本

### **5. 标签页管理类**
- `switchToNewTab()` - 切换到新标签页
- `closeTabAndSwitchBack()` - 关闭当前标签页并切换回主标签页
- `handleAlert(bool $accept = true)` - 处理弹窗

### **6. 实用工具类**
- `screenshotWithTimestamp(string $name = 'screenshot')` - 带时间戳截图
- `randomPause(int $minMs = 500, int $maxMs = 2000)` - 随机等待
- `acceptCookies(array $selectors)` - 智能Cookie接受
- `closeAds(array $selectors)` - 智能广告关闭
- `measurePageLoad()` - 页面性能监控

## 🎨 **快速开始示例**

### **基础使用**
```php
// 访问网站并等待加载
$browser->visit('https://example.com')
        ->waitForPageLoad()
        ->acceptCookies()
        ->closeAds();

// 智能登录
$browser->smartLogin('#email', '#password', 'user@example.com', 'password123');

// 智能搜索
$browser->smartSearch('#search', '搜索内容');

// 智能表单填写
$browser->fillForm([
    '#name' => '张三',
    '#email' => 'zhangsan@example.com',
    '#age' => '25',
    '#newsletter' => true
]);
```

### **高级功能**
```php
// 等待任意元素出现
$browser->waitForAnyElement(['.login-form', '#loginModal', '[data-testid="login"]']);

// 模拟人类输入
$browser->humanType('#message', '这是一条消息');

// 批量操作
$browser->clickAll('.like-button', 1000); // 每秒点击一个

// 性能监控
$metrics = $browser->measurePageLoad();
echo "页面加载时间: {$metrics['load_time_ms']}ms";
```

## 📚 **预设模板**

我还为您创建了多个预设模板，位于 `resources/templates/dusk-script-templates.php`：

1. **基础登录模板** - `basicLoginTemplate()`
2. **电商购物模板** - `ecommerceShoppingTemplate()`
3. **社交媒体发布模板** - `socialMediaPostTemplate()`
4. **数据采集模板** - `dataScrapingTemplate()`
5. **表单填写模板** - `formFillingTemplate()`
6. **多标签页操作模板** - `multiTabTemplate()`
7. **文件下载模板** - `fileDownloadTemplate()`
8. **API测试模板** - `apiTestingTemplate()`
9. **性能监控模板** - `performanceMonitoringTemplate()`
10. **错误处理模板** - `errorHandlingTemplate()`

## 🔧 **安装和配置**

### **1. 自动注册**
宏已经通过 `DuskMacroServiceProvider` 自动注册，无需手动配置。

### **2. 文件结构**
```
app/
├── Macros/
│   └── DuskMacros.php          # 宏定义文件
├── Providers/
│   └── DuskMacroServiceProvider.php  # 服务提供者
resources/
└── templates/
    └── dusk-script-templates.php     # 预设模板
tests/
└── DuskMacroTest.php           # 宏测试文件
```

### **3. 使用方法**
在任何Dusk脚本中直接调用宏方法：

```php
// 在任务脚本中使用
$browser->smartClick('登录按钮')
        ->fillForm($formData)
        ->screenshotWithTimestamp('操作完成');
```

## 🎯 **最佳实践**

### **1. 错误处理**
```php
// 使用条件操作避免异常
if ($browser->hasElement('.popup')) {
    $browser->click('.popup .close');
}

// 使用条件点击
$browser->clickIfExists('.cookie-banner .accept');
```

### **2. 性能优化**
```php
// 使用智能等待替代固定延迟
$browser->waitForAnyElement(['.content', '.loading-complete'])
        ->waitForLoadingToFinish();

// 模拟人类行为
$browser->humanType('#input', 'text')
        ->randomPause(500, 1500);
```

### **3. 调试支持**
```php
// 关键步骤截图
$browser->screenshotWithTimestamp('step_1_login')
        ->smartLogin('#email', '#password', $email, $password)
        ->screenshotWithTimestamp('step_2_logged_in');

// 性能监控
$metrics = $browser->measurePageLoad();
file_put_contents('performance.log', json_encode($metrics) . "\n", FILE_APPEND);
```

## 🚀 **高级用法示例**

### **复杂表单处理**
```php
$browser->fillForm([
    '#personal-info' => [
        '#name' => '张三',
        '#email' => 'zhangsan@example.com',
        '#phone' => '13800138000'
    ],
    '#preferences' => [
        '#newsletter' => true,
        '#notifications' => false,
        '#language' => ['value' => 'zh-CN']
    ]
]);
```

### **数据采集循环**
```php
$data = [];
$page = 1;

do {
    $browser->visit("https://example.com/products?page=$page")
            ->waitForLoadingToFinish();
    
    $items = $browser->getAllText('.product-name');
    $data = array_merge($data, $items);
    
    $hasNext = $browser->clickIfExists('.next-page');
    $page++;
} while ($hasNext && $page <= 10);
```

### **多站点监控**
```php
$sites = ['site1.com', 'site2.com', 'site3.com'];
$results = [];

foreach ($sites as $site) {
    $browser->visit("https://$site");
    $metrics = $browser->measurePageLoad();
    $results[$site] = $metrics;
    $browser->screenshotWithTimestamp("monitor_$site");
}

file_put_contents('monitoring_report.json', json_encode($results, JSON_PRETTY_PRINT));
```

## 🎉 **总结**

这套宏系统为您提供了：

✅ **40+个实用宏** - 覆盖所有常见自动化场景
✅ **10个预设模板** - 快速开始各种类型的自动化任务
✅ **智能错误处理** - 优雅处理各种异常情况
✅ **人性化操作** - 模拟真实用户行为
✅ **性能监控** - 内置页面性能测量
✅ **完整测试** - 包含测试用例确保稳定性

现在您可以用更少的代码编写更强大、更可靠的自动化脚本了！🚀
