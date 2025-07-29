# 🚀 Dusk 自动化宏使用指南

## 📋 **宏功能概览**

我为您的Dusk自动化平台创建了一套强大的宏，让编写自动化脚本变得更加简单和高效。

### ✨ **核心功能**

1. **智能等待和查找** - 自动处理页面加载和元素等待
2. **智能交互** - 更可靠的点击、输入和表单操作
3. **人性化操作** - 模拟真实用户行为
4. **错误处理** - 优雅处理各种异常情况
5. **便捷工具** - 截图、标签页管理等实用功能

## 🎯 **使用示例**

### **1. 智能等待和查找**

```php
// 等待任意一个元素出现（适用于动态加载页面）
$browser->waitForAnyElement([
    '.login-form',
    '#loginModal',
    '[data-testid="login"]'
], 15);

// 等待页面完全加载
$browser->waitForPageLoad();

// 等待Ajax请求完成
$browser->waitForAjax();

// 等待加载动画消失
$browser->waitForLoadingToFinish([
    '.loading-spinner',
    '.overlay',
    '[data-loading="true"]'
]);
```

### **2. 智能点击和输入**

```php
// 智能点击（支持多种选择器策略）
$browser->smartClick('登录'); // 按文本查找
$browser->smartClick('#login-btn'); // CSS选择器
$browser->smartClick('[title="登录"]'); // 属性选择器

// 智能输入（自动清空现有内容）
$browser->smartType('#username', 'admin@example.com');
$browser->smartType('#password', 'password123');

// 模拟人类输入（带随机延迟）
$browser->humanType('#search', '搜索关键词');

// 滚动到元素并点击
$browser->scrollAndClick('.footer-link');
```

### **3. 表单操作**

```php
// 智能表单填写
$browser->fillForm([
    '#username' => 'admin@example.com',
    '#password' => 'password123',
    '#remember' => true, // 复选框
    '#country' => ['value' => 'CN'], // 下拉选择
    '#age' => '25'
]);

// 智能登录
$browser->smartLogin(
    '#username', 
    '#password', 
    'admin@example.com', 
    'password123',
    '#login-btn'
);

// 智能搜索
$browser->smartSearch('#search-input', '搜索内容', '.search-btn');
```

### **4. 页面交互**

```php
// 检查元素是否存在（不抛出异常）
if ($browser->hasElement('.notification')) {
    $browser->click('.notification .close');
}

// 条件点击
$browser->clickIfExists('.cookie-banner .accept');

// 处理弹窗
$browser->handleAlert(true); // 接受弹窗
$browser->handleAlert(false); // 取消弹窗

// 批量点击
$browser->clickAll('.item .like-btn', 1000); // 每次点击间隔1秒
```

### **5. 标签页管理**

```php
// 点击链接打开新标签页
$browser->click('a[target="_blank"]')
        ->switchToNewTab()
        ->waitForPageLoad()
        ->assertSee('新页面内容')
        ->closeTabAndSwitchBack();
```

### **6. 数据获取**

```php
// 等待并获取文本
$title = $browser->waitAndGetText('h1.title');

// 获取所有匹配元素的文本
$items = $browser->getAllText('.product-name');
foreach ($items as $item) {
    echo "产品: $item\n";
}
```

### **7. 实用工具**

```php
// 带时间戳的截图
$browser->screenshotWithTimestamp('login_page');

// 随机等待（模拟人类行为）
$browser->randomPause(500, 2000);

// 等待URL变化
$browser->waitForUrlContains('/dashboard');

// 等待元素消失
$browser->waitUntilMissing('.loading-overlay');
```

## 🎨 **完整示例脚本**

### **电商网站自动化购物**

```php
<?php

// 访问电商网站
$browser->visit('https://example-shop.com')
        ->waitForPageLoad()
        ->waitForLoadingToFinish();

// 搜索商品
$browser->smartSearch('#search', 'iPhone 15', '.search-btn')
        ->waitForPageLoad();

// 选择第一个商品
$browser->scrollAndClick('.product-item:first-child .product-link')
        ->waitForPageLoad();

// 添加到购物车
$browser->smartClick('加入购物车')
        ->waitForAnyElement(['.cart-success', '.added-to-cart'])
        ->screenshotWithTimestamp('added_to_cart');

// 进入购物车
$browser->smartClick('.cart-icon')
        ->waitForPageLoad();

// 填写收货信息
$browser->fillForm([
    '#shipping-name' => '张三',
    '#shipping-phone' => '13800138000',
    '#shipping-address' => '北京市朝阳区xxx街道',
    '#payment-method' => ['value' => 'alipay']
]);

// 提交订单
$browser->smartClick('提交订单')
        ->waitForUrlContains('/order-success')
        ->assertSee('订单提交成功');
```

### **社交媒体自动化发布**

```php
<?php

// 登录社交媒体
$browser->visit('https://social-media.com/login')
        ->smartLogin('#email', '#password', 'user@example.com', 'password123')
        ->waitForUrlContains('/dashboard');

// 创建新帖子
$browser->smartClick('发布新动态')
        ->waitFor('.post-editor');

// 输入内容
$browser->humanType('.post-content', '今天天气真好！#美好生活')
        ->randomPause();

// 上传图片（如果有上传按钮）
if ($browser->hasElement('.upload-btn')) {
    $browser->click('.upload-btn')
            ->waitFor('input[type="file"]')
            ->attach('input[type="file"]', '/path/to/image.jpg');
}

// 发布
$browser->smartClick('发布')
        ->waitForAnyElement(['.post-success', '.published'])
        ->screenshotWithTimestamp('post_published');
```

### **数据采集示例**

```php
<?php

$products = [];

// 访问产品列表页
$browser->visit('https://example.com/products')
        ->waitForPageLoad();

// 获取所有产品信息
$productNames = $browser->getAllText('.product-name');
$productPrices = $browser->getAllText('.product-price');

for ($i = 0; $i < count($productNames); $i++) {
    $products[] = [
        'name' => $productNames[$i] ?? '',
        'price' => $productPrices[$i] ?? ''
    ];
}

// 保存数据
file_put_contents('products.json', json_encode($products, JSON_PRETTY_PRINT));
```

## 🛠️ **高级技巧**

### **1. 错误处理**

```php
try {
    $browser->smartClick('可能不存在的按钮');
} catch (\Exception $e) {
    // 备用方案
    $browser->smartClick('备用按钮');
}
```

### **2. 条件操作**

```php
// 根据页面状态执行不同操作
if ($browser->hasElement('.login-required')) {
    $browser->smartLogin('#email', '#password', $email, $password);
} else {
    $browser->smartClick('继续浏览');
}
```

### **3. 循环操作**

```php
// 处理分页数据
do {
    // 处理当前页数据
    $items = $browser->getAllText('.item-title');
    foreach ($items as $item) {
        // 处理每个项目
        echo "处理: $item\n";
    }
    
    // 尝试点击下一页
    $hasNext = $browser->clickIfExists('.next-page');
    if ($hasNext) {
        $browser->waitForPageLoad();
    }
} while ($hasNext);
```

## 📝 **最佳实践**

1. **总是使用智能等待** - 避免硬编码延迟
2. **优雅处理异常** - 使用`hasElement`和`clickIfExists`
3. **模拟人类行为** - 使用`humanType`和`randomPause`
4. **及时截图** - 在关键步骤使用`screenshotWithTimestamp`
5. **清晰的选择器** - 优先使用语义化的选择器

## 🎉 **开始使用**

这些宏已经自动注册到您的Dusk Browser实例中，您可以在任何Dusk脚本中直接使用它们。

现在您可以编写更简洁、更可靠的自动化脚本了！
