# 🎯 Dusk模板使用指南

## 📋 **可用的模板触发词**

我已经为您的任务编辑器添加了多个触发词，让用户更容易找到和使用模板：

### **基础模板**
- `dusk-template` - 基础Dusk脚本模板
- `dusk-basic` - 基础Dusk脚本模板
- `template` - 基础模板
- `模板` - 基础模板（中文）

### **登录模板**
- `dusk-login` - 登录脚本模板
- `login-template` - 登录脚本模板
- `login` - 登录模板

### **表单模板**
- `dusk-form` - 表单填写模板
- `form` - 表单模板

### **搜索模板**
- `dusk-search` - 搜索脚本模板
- `search` - 搜索模板

### **数据采集模板**
- `dusk-scraping` - 数据采集模板

### **错误处理模板**
- `dusk-error` - 错误处理模板

## 🎨 **使用方法**

### **步骤1：打开任务编辑器**
1. 访问 `/admin/tasks/create` 或编辑现有任务
2. 点击"脚本内容"标签页
3. 在Monaco编辑器中开始输入

### **步骤2：输入触发词**
在编辑器中输入任意一个触发词，例如：
- 输入 `dusk` 会显示所有dusk相关的模板
- 输入 `login` 会显示登录相关的模板
- 输入 `template` 会显示基础模板
- 输入 `模板` 会显示中文模板

### **步骤3：选择模板**
1. 等待代码提示出现（蓝色下拉框）
2. 使用方向键选择想要的模板
3. 按Tab键或Enter键插入模板

### **步骤4：编辑参数**
1. 模板插入后，光标会自动定位到第一个参数
2. 输入实际的值
3. 按Tab键跳转到下一个参数
4. 重复直到所有参数都填写完成

## 📝 **模板内容预览**

### **基础模板 (`dusk-template`)**
```php
// 基础Dusk脚本模板
$browser->visit('https://example.com')
        ->waitForPageLoad()
        ->acceptCookies()
        ->closeAds()
        ->screenshotWithTimestamp('step_name');
```

### **登录模板 (`dusk-login`)**
```php
// 登录脚本模板
$browser->visit('https://example.com/login')
        ->waitForPageLoad()
        ->smartLogin('#email', '#password', 'user@example.com', 'password123')
        ->waitForUrlContains('/dashboard')
        ->screenshotWithTimestamp('login_success');
```

### **表单模板 (`dusk-form`)**
```php
// 表单填写模板
$browser->visit('https://example.com/form')
        ->waitForPageLoad()
        ->fillForm([
            '#name' => '张三',
            '#email' => 'zhangsan@example.com',
            '#phone' => '13800138000',
            '#newsletter' => true
        ])
        ->smartClick('#submit')
        ->waitForAnyElement(['.success', '.submitted'])
        ->screenshotWithTimestamp('form_submitted');
```

### **搜索模板 (`dusk-search`)**
```php
// 搜索脚本模板
$browser->visit('https://example.com')
        ->waitForPageLoad()
        ->smartSearch('#search', '搜索关键词')
        ->waitForPageLoad()
        ->screenshotWithTimestamp('search_results');
```

### **数据采集模板 (`dusk-scraping`)**
```php
// 数据采集模板
$data = [];
$browser->visit('https://example.com/products')
        ->waitForPageLoad();

$names = $browser->getAllText('.product-name');
$prices = $browser->getAllText('.product-price');

for ($i = 0; $i < count($names); $i++) {
    $data[] = [
        'name' => $names[$i] ?? '',
        'price' => $prices[$i] ?? ''
    ];
}

file_put_contents('scraped_data.json', json_encode($data, JSON_PRETTY_PRINT));
```

### **错误处理模板 (`dusk-error`)**
```php
// 错误处理模板
try {
    $browser->visit('https://example.com')
            ->waitForPageLoad();
    
    // 主要操作
    if ($browser->hasElement('.login-required')) {
        $browser->smartLogin('#email', '#password', 'user@example.com', 'password123');
    }
    
    $browser->smartClick('.main-action');
    
} catch (\Exception $e) {
    $browser->screenshotWithTimestamp('error_occurred');
    
    // 记录错误
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
    
    // 尝试恢复
    $browser->refresh()->waitForPageLoad();
}
```

## 🔧 **故障排除**

### **如果看不到模板提示**
1. **刷新页面** - 确保最新的JavaScript文件已加载
2. **清除浏览器缓存** - 强制刷新（Ctrl+F5 或 Cmd+Shift+R）
3. **检查控制台** - 打开开发者工具查看是否有JavaScript错误
4. **手动触发** - 按 Ctrl+Space 手动触发代码提示

### **如果模板插入不完整**
1. **确保使用Tab键** - 而不是Enter键来插入模板
2. **等待完全加载** - 确保Monaco编辑器完全加载后再使用
3. **重新输入** - 删除部分内容重新输入触发词

### **如果参数跳转不工作**
1. **使用Tab键** - 而不是方向键来跳转参数
2. **确保在编辑模式** - 光标应该在编辑器内
3. **检查模板格式** - 确保模板包含 `${1:}` 格式的参数

## 🎯 **最佳实践**

### **选择合适的模板**
- **简单任务** - 使用基础模板 (`template`)
- **需要登录** - 使用登录模板 (`login`)
- **表单操作** - 使用表单模板 (`form`)
- **搜索功能** - 使用搜索模板 (`search`)
- **数据采集** - 使用采集模板 (`dusk-scraping`)
- **复杂逻辑** - 使用错误处理模板 (`dusk-error`)

### **自定义模板**
1. **复制现有模板** - 从预设模板开始
2. **修改参数** - 根据实际需求调整
3. **添加注释** - 为复杂逻辑添加说明
4. **测试验证** - 确保脚本能正常运行

### **提高效率**
1. **记住常用触发词** - 如 `login`, `form`, `search`
2. **使用Tab键快速跳转** - 在参数之间快速移动
3. **组合使用宏** - 结合其他Dusk宏方法
4. **保存常用配置** - 将常用的参数值保存为环境变量

## 🎉 **总结**

现在您的用户可以：

✅ **快速开始** - 输入简单触发词即可插入完整模板
✅ **多种选择** - 每种场景都有对应的模板
✅ **中文支持** - 支持中文触发词 `模板`
✅ **智能参数** - Tab键快速跳转和编辑参数
✅ **最佳实践** - 模板包含优化的代码结构

**用户现在可以在几秒钟内创建专业的Dusk自动化脚本！** 🚀

## 📞 **需要帮助？**

如果用户在使用模板时遇到问题：
1. 查看浏览器控制台是否有错误
2. 确保输入的触发词正确
3. 尝试刷新页面重新加载
4. 使用测试页面 `/test-monaco-templates.html` 验证功能
