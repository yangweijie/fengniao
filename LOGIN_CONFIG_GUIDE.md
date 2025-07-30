# 🔐 登录配置项使用指南

## 📋 **登录配置项的作用**

登录配置项用于存储网站的自动登录相关信息，包括：
- 登录凭据（用户名、密码）
- 页面元素选择器
- 登录流程配置
- 验证规则等

在脚本中可以通过 `$loginConfig` 变量访问这些配置。

## 🎯 **完整示例：电商网站自动登录**

### **1. 登录配置设置**

在"登录配置"标签页中，添加以下键值对：

| 配置项 | 值 | 说明 |
|--------|-----|------|
| `login_url` | `https://www.example-shop.com/login` | 登录页面URL |
| `username` | `your_username@email.com` | 登录用户名 |
| `password` | `your_password123` | 登录密码 |
| `username_selector` | `#email` | 用户名输入框选择器 |
| `password_selector` | `#password` | 密码输入框选择器 |
| `login_button_selector` | `button[type="submit"]` | 登录按钮选择器 |
| `success_indicator` | `.user-dashboard` | 登录成功标识 |
| `captcha_selector` | `#captcha` | 验证码输入框（可选） |
| `remember_me_selector` | `#remember` | 记住我选择框（可选） |

### **2. 脚本中使用配置**

```php
<?php
// 访问登录页面
$browser->visit($loginConfig['login_url']);

// 等待页面加载
$browser->waitForPageLoad();

// 填写用户名
$browser->type($loginConfig['username_selector'], $loginConfig['username']);

// 填写密码
$browser->type($loginConfig['password_selector'], $loginConfig['password']);

// 如果有记住我选项，勾选它
if (isset($loginConfig['remember_me_selector'])) {
    $browser->check($loginConfig['remember_me_selector']);
}

// 点击登录按钮
$browser->click($loginConfig['login_button_selector']);

// 等待登录成功
$browser->waitFor($loginConfig['success_indicator']);

// 验证登录是否成功
if ($browser->element($loginConfig['success_indicator'])) {
    echo "登录成功！";
} else {
    throw new Exception("登录失败");
}
?>
```

## 🏪 **实际案例：淘宝登录配置**

### **配置项设置**
```
login_url = https://login.taobao.com/member/login.jhtml
username = your_phone_number
password = your_password
username_selector = #fm-login-id
password_selector = #fm-login-password
login_button_selector = .fm-button
success_indicator = .site-nav-user
wait_after_login = 3
```

### **对应脚本**
```php
<?php
// 访问淘宝登录页
$browser->visit($loginConfig['login_url']);

// 等待页面加载
$browser->waitForPageLoad();

// 输入手机号
$browser->type($loginConfig['username_selector'], $loginConfig['username']);

// 输入密码
$browser->type($loginConfig['password_selector'], $loginConfig['password']);

// 点击登录
$browser->click($loginConfig['login_button_selector']);

// 等待登录成功（等待用户头像出现）
$browser->waitFor($loginConfig['success_indicator']);

// 额外等待时间（如果配置了）
if (isset($loginConfig['wait_after_login'])) {
    sleep($loginConfig['wait_after_login']);
}

echo "淘宝登录成功！";
?>
```

## 🔧 **高级配置示例**

### **多步骤登录流程**
```
# 第一步：输入用户名
step1_url = https://accounts.example.com/signin
username_selector = input[name="username"]
next_button_selector = #identifierNext

# 第二步：输入密码
password_selector = input[name="password"]
login_button_selector = #passwordNext

# 验证配置
success_indicator = .profile-menu
error_indicator = .error-message
max_wait_time = 10
```

### **对应的多步骤脚本**
```php
<?php
// 第一步：输入用户名
$browser->visit($loginConfig['step1_url']);
$browser->type($loginConfig['username_selector'], $loginConfig['username']);
$browser->click($loginConfig['next_button_selector']);

// 等待密码页面
$browser->waitFor($loginConfig['password_selector']);

// 第二步：输入密码
$browser->type($loginConfig['password_selector'], $loginConfig['password']);
$browser->click($loginConfig['login_button_selector']);

// 等待登录结果
$maxWait = $loginConfig['max_wait_time'] ?? 10;
$browser->waitFor($loginConfig['success_indicator'], $maxWait);

// 检查是否有错误
if ($browser->element($loginConfig['error_indicator'])) {
    $errorText = $browser->text($loginConfig['error_indicator']);
    throw new Exception("登录失败: " . $errorText);
}

echo "多步骤登录成功！";
?>
```

## 🛡️ **安全配置示例**

### **带验证码的登录**
```
login_url = https://secure.example.com/login
username = your_username
password = your_password
username_selector = #username
password_selector = #password
captcha_selector = #captcha
captcha_image_selector = #captcha-image
login_button_selector = #login-btn
success_indicator = .dashboard
```

### **验证码处理脚本**
```php
<?php
$browser->visit($loginConfig['login_url']);

// 填写基本信息
$browser->type($loginConfig['username_selector'], $loginConfig['username']);
$browser->type($loginConfig['password_selector'], $loginConfig['password']);

// 处理验证码（如果存在）
if (isset($loginConfig['captcha_selector']) && $browser->element($loginConfig['captcha_selector'])) {
    // 截取验证码图片
    $browser->screenshot('captcha.png');
    
    // 这里可以集成OCR服务或人工输入
    echo "请查看captcha.png文件并手动输入验证码";
    
    // 暂停等待手动输入（实际使用中可以集成自动识别）
    $browser->pause(30000); // 30秒
}

// 提交登录
$browser->click($loginConfig['login_button_selector']);
$browser->waitFor($loginConfig['success_indicator']);

echo "安全登录完成！";
?>
```

## 📱 **移动端登录配置**

### **响应式登录页面**
```
login_url = https://m.example.com/login
username = your_mobile_number
password = your_password
username_selector = input[placeholder="手机号"]
password_selector = input[placeholder="密码"]
login_button_selector = .login-btn
success_indicator = .user-info
mobile_mode = true
```

### **移动端脚本**
```php
<?php
// 如果是移动端模式，设置用户代理
if ($loginConfig['mobile_mode'] ?? false) {
    $browser->userAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)');
}

$browser->visit($loginConfig['login_url']);
$browser->waitForPageLoad();

// 移动端可能需要点击输入框激活
$browser->click($loginConfig['username_selector']);
$browser->type($loginConfig['username_selector'], $loginConfig['username']);

$browser->click($loginConfig['password_selector']);
$browser->type($loginConfig['password_selector'], $loginConfig['password']);

$browser->click($loginConfig['login_button_selector']);
$browser->waitFor($loginConfig['success_indicator']);

echo "移动端登录成功！";
?>
```

## 💡 **最佳实践**

### **1. 配置项命名规范**
- 使用下划线分隔：`username_selector`
- 语义化命名：`success_indicator` 而不是 `div1`
- 分组命名：`step1_url`, `step2_url`

### **2. 安全考虑**
- 敏感信息加密存储
- 使用环境变量替代明文密码
- 定期更新登录凭据

### **3. 错误处理**
```php
// 检查必需配置项
$required = ['login_url', 'username', 'password'];
foreach ($required as $key) {
    if (!isset($loginConfig[$key])) {
        throw new Exception("缺少必需的登录配置: {$key}");
    }
}
```

### **4. 灵活性配置**
```
# 超时配置
page_load_timeout = 30
element_wait_timeout = 10
login_wait_timeout = 15

# 重试配置
max_retry_attempts = 3
retry_delay = 2

# 调试配置
debug_mode = true
screenshot_on_error = true
```

## 🎯 **总结**

登录配置项的核心优势：

✅ **配置与代码分离** - 修改登录信息无需改代码
✅ **复用性强** - 同一套脚本适用不同账号
✅ **安全性好** - 敏感信息集中管理
✅ **灵活性高** - 支持各种登录流程
✅ **维护简单** - 配置修改即时生效

通过合理使用登录配置项，可以让自动化脚本更加灵活和安全！
