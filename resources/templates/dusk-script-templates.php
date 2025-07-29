<?php

/**
 * Dusk 自动化脚本模板集合
 * 用户可以复制这些模板作为起点
 */

// ================================
// 基础网站登录模板
// ================================
function basicLoginTemplate() {
    return '
// 基础登录脚本
$browser->visit("https://example.com/login")
        ->waitForPageLoad()
        ->acceptCookies()
        ->closeAds()
        ->smartLogin("#email", "#password", "user@example.com", "password123")
        ->waitForUrlContains("/dashboard")
        ->screenshotWithTimestamp("login_success");
';
}

// ================================
// 电商购物模板
// ================================
function ecommerceShoppingTemplate() {
    return '
// 电商自动化购物脚本
$browser->visit("https://shop.example.com")
        ->waitForPageLoad()
        ->acceptCookies()
        
        // 搜索商品
        ->smartSearch("#search", "iPhone 15")
        ->waitForPageLoad()
        
        // 选择商品
        ->scrollAndClick(".product-item:first-child")
        ->waitForPageLoad()
        
        // 添加到购物车
        ->smartClick("加入购物车")
        ->waitForAnyElement([".cart-success", ".added-notification"])
        ->screenshotWithTimestamp("added_to_cart")
        
        // 查看购物车
        ->smartClick(".cart-icon")
        ->waitForPageLoad()
        
        // 结账
        ->smartClick("去结算")
        ->waitForPageLoad()
        ->fillForm([
            "#shipping-name" => "张三",
            "#shipping-phone" => "13800138000",
            "#shipping-address" => "北京市朝阳区xxx街道"
        ])
        ->smartClick("提交订单")
        ->waitForUrlContains("/order-success")
        ->screenshotWithTimestamp("order_completed");
';
}

// ================================
// 社交媒体发布模板
// ================================
function socialMediaPostTemplate() {
    return '
// 社交媒体自动发布脚本
$browser->visit("https://social.example.com")
        ->waitForPageLoad()
        
        // 登录
        ->smartLogin("#username", "#password", "user@example.com", "password123")
        ->waitForUrlContains("/home")
        
        // 创建新帖子
        ->smartClick("发布动态")
        ->waitFor(".post-editor")
        
        // 输入内容
        ->humanType(".post-content", "今天天气真好！#美好生活 #分享日常")
        ->randomPause()
        
        // 上传图片（可选）
        ->clickIfExists(".upload-photo")
        ->pause(1000)
        
        // 发布
        ->smartClick("发布")
        ->waitForAnyElement([".post-success", ".published"])
        ->screenshotWithTimestamp("post_published");
';
}

// ================================
// 数据采集模板
// ================================
function dataScrapingTemplate() {
    return '
// 数据采集脚本
$data = [];
$page = 1;

do {
    $browser->visit("https://example.com/products?page=" . $page)
            ->waitForPageLoad()
            ->waitForLoadingToFinish();
    
    // 获取当前页数据
    $names = $browser->getAllText(".product-name");
    $prices = $browser->getAllText(".product-price");
    $links = $browser->getAllText(".product-link");
    
    // 组合数据
    for ($i = 0; $i < count($names); $i++) {
        $data[] = [
            "name" => $names[$i] ?? "",
            "price" => $prices[$i] ?? "",
            "link" => $links[$i] ?? "",
            "page" => $page
        ];
    }
    
    // 检查是否有下一页
    $hasNext = $browser->hasElement(".next-page:not(.disabled)");
    if ($hasNext) {
        $browser->click(".next-page")->waitForPageLoad();
        $page++;
    }
    
} while ($hasNext && $page <= 10); // 最多采集10页

// 保存数据
file_put_contents("scraped_data.json", json_encode($data, JSON_PRETTY_PRINT));
';
}

// ================================
// 表单自动填写模板
// ================================
function formFillingTemplate() {
    return '
// 表单自动填写脚本
$browser->visit("https://example.com/form")
        ->waitForPageLoad()
        
        // 填写基本信息
        ->fillForm([
            "#name" => "张三",
            "#email" => "zhangsan@example.com",
            "#phone" => "13800138000",
            "#gender" => ["value" => "male"],
            "#age" => "25",
            "#newsletter" => true,
            "#terms" => true
        ])
        
        // 填写地址信息
        ->smartSelect("#country", "中国")
        ->pause(1000) // 等待省份列表加载
        ->smartSelect("#province", "北京市")
        ->pause(1000) // 等待城市列表加载
        ->smartSelect("#city", "朝阳区")
        
        // 上传文件
        ->smartUpload("#avatar", "/path/to/avatar.jpg")
        ->smartUpload("#resume", "/path/to/resume.pdf")
        
        // 提交表单
        ->smartClick("提交")
        ->waitForAnyElement([".success-message", ".form-submitted"])
        ->screenshotWithTimestamp("form_submitted");
';
}

// ================================
// 多标签页操作模板
// ================================
function multiTabTemplate() {
    return '
// 多标签页操作脚本
$browser->visit("https://example.com")
        ->waitForPageLoad()
        
        // 在新标签页中打开链接
        ->keys("a[href=\"/products\"]", ["{ctrl}", "{click}"])
        ->switchToNewTab()
        ->waitForPageLoad()
        ->assertSee("产品列表")
        
        // 在新标签页中操作
        ->smartClick(".product:first-child")
        ->waitForPageLoad()
        ->screenshotWithTimestamp("product_detail")
        
        // 关闭当前标签页，回到主标签页
        ->closeTabAndSwitchBack()
        ->assertSee("首页");
';
}

// ================================
// 文件下载模板
// ================================
function fileDownloadTemplate() {
    return '
// 文件下载脚本
$browser->visit("https://example.com/downloads")
        ->waitForPageLoad()
        
        // 登录（如果需要）
        ->smartLogin("#email", "#password", "user@example.com", "password123")
        ->waitForUrlContains("/downloads")
        
        // 下载文件
        ->smartClick(".download-btn:first-child")
        ->pause(2000) // 等待下载开始
        
        // 处理下载确认弹窗
        ->handleAlert(true)
        
        // 等待下载完成（可以通过检查下载文件夹或页面提示）
        ->waitForAnyElement([".download-complete", ".download-success"], 30)
        ->screenshotWithTimestamp("download_completed");
';
}

// ================================
// API测试模板
// ================================
function apiTestingTemplate() {
    return '
// API接口测试脚本（通过浏览器）
$browser->visit("https://api-docs.example.com")
        ->waitForPageLoad()
        
        // 获取API Token
        ->smartClick("#get-token")
        ->waitFor("#api-token")
        ->getAttribute("#api-token", "value")
        
        // 测试API端点
        ->smartType("#api-endpoint", "/api/users")
        ->smartSelect("#http-method", "GET")
        ->smartClick("#send-request")
        ->waitFor(".response-body")
        
        // 验证响应
        ->assertSee("200")
        ->assertSee("users")
        ->screenshotWithTimestamp("api_test_success");
';
}

// ================================
// 性能监控模板
// ================================
function performanceMonitoringTemplate() {
    return '
// 网站性能监控脚本
$performanceData = [];

$urls = [
    "https://example.com",
    "https://example.com/products",
    "https://example.com/about",
    "https://example.com/contact"
];

foreach ($urls as $url) {
    $browser->visit($url);
    
    // 测量页面加载时间
    $metrics = $browser->measurePageLoad();
    
    $performanceData[] = [
        "url" => $url,
        "load_time_ms" => $metrics["load_time_ms"],
        "timestamp" => $metrics["timestamp"],
        "title" => $browser->driver->getTitle()
    ];
    
    // 截图记录
    $browser->screenshotWithTimestamp("performance_" . basename($url));
    
    $browser->randomPause(1000, 3000);
}

// 保存性能数据
file_put_contents("performance_report.json", json_encode($performanceData, JSON_PRETTY_PRINT));
';
}

// ================================
// 错误处理模板
// ================================
function errorHandlingTemplate() {
    return '
// 带错误处理的脚本模板
try {
    $browser->visit("https://example.com")
            ->waitForPageLoad();
    
    // 尝试主要操作
    if ($browser->hasElement(".login-required")) {
        $browser->smartLogin("#email", "#password", "user@example.com", "password123");
    }
    
    // 主要业务逻辑
    $browser->smartClick(".main-action");
    
} catch (\Exception $e) {
    // 错误处理
    $browser->screenshotWithTimestamp("error_occurred");
    
    // 记录错误
    file_put_contents("error_log.txt", date("Y-m-d H:i:s") . " - " . $e->getMessage() . "\n", FILE_APPEND);
    
    // 尝试恢复操作
    $browser->refresh()
            ->waitForPageLoad()
            ->smartClick(".retry-action");
}
';
}
