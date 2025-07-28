# 蜂鸟自动化平台 (Fengniao Automation Platform)

基于Laravel Dusk的企业级Web自动化测试和任务执行平台，提供可视化工作流编辑、多浏览器并发执行、智能监控告警等功能。

## 🚀 核心特性

### 🎯 任务管理
- **可视化工作流编辑器**: 拖拽式节点编辑，支持条件分支和循环
- **多种任务类型**: 浏览器自动化、API测试、数据采集等
- **灵活调度系统**: 支持Cron表达式、一次性任务、手动触发
- **任务依赖管理**: 支持任务间的依赖关系和数据传递

### 🌐 浏览器管理
- **智能浏览器池**: 自动管理Chrome实例，支持负载均衡
- **多标签页并发**: 单实例多标签页执行，提高资源利用率
- **Cookie管理**: 自动保存和同步Cookie，支持会话保持
- **截图和录制**: 自动截图、视频录制，便于调试和审计

### 📊 监控告警
- **实时性能监控**: CPU、内存、磁盘使用率监控
- **任务执行统计**: 成功率、执行时间、错误分析
- **多渠道通知**: 邮件、钉钉、企业微信、Slack等
- **智能告警**: 基于阈值和趋势的智能告警

### 🔐 权限管理
- **RBAC权限模型**: 基于角色的访问控制
- **细粒度权限**: 模块级、操作级权限控制
- **审计日志**: 完整的操作审计和追踪
- **多租户支持**: 支持多组织、多项目隔离

### 🛠 开发工具
- **脚本转换器**: JavaScript/Python脚本自动转换为Dusk代码
- **调试模式**: 可视化调试、单步执行、断点调试
- **API接口**: 完整的RESTful API，支持第三方集成
- **插件系统**: 支持自定义插件扩展功能

## 📋 系统要求

### 最低要求
- **操作系统**: Linux (Ubuntu 18.04+, CentOS 7+) / macOS 10.14+ / Windows 10+
- **PHP**: 8.2+
- **数据库**: MySQL 8.0+ / PostgreSQL 13+
- **缓存**: Redis 6.0+
- **内存**: 4GB+
- **磁盘**: 20GB+

### 推荐配置
- **CPU**: 4核心+
- **内存**: 8GB+
- **磁盘**: SSD 50GB+
- **网络**: 100Mbps+

### 依赖软件
- **Docker**: 20.10+
- **Docker Compose**: 2.0+
- **Google Chrome**: 最新稳定版
- **Node.js**: 18.0+ (开发环境)

## 🚀 快速开始

### 使用Docker部署（推荐）

1. **克隆项目**
```bash
git clone https://github.com/your-org/fengniao.git
cd fengniao
```

2. **配置环境**
```bash
# 复制环境配置文件
cp .env.example .env

# 编辑配置文件
vim .env
```

3. **一键部署**
```bash
# 赋予执行权限
chmod +x deploy.sh

# 部署到生产环境
./deploy.sh production --build --migrate --seed

# 或部署到开发环境
./deploy.sh development --build --migrate
```

4. **访问系统**
- Web界面: http://localhost
- API文档: http://localhost/api/documentation
- 监控面板: http://localhost:3000 (如果启用)

### 手动安装

1. **安装PHP依赖**
```bash
composer install --no-dev --optimize-autoloader
```

2. **安装Node.js依赖**
```bash
npm install
npm run build
```

3. **配置环境**
```bash
cp .env.example .env
php artisan key:generate
```

4. **数据库迁移**
```bash
php artisan migrate
php artisan permission:manage init --force
```

5. **启动服务**
```bash
# 启动Web服务
php artisan serve

# 启动队列工作进程
php artisan queue:work

# 启动任务调度器
php artisan schedule:work
```

## 📖 使用指南

### 创建第一个任务

1. **登录系统**
   - 默认管理员账号: admin@example.com
   - 默认密码: password

2. **创建任务**
   ```bash
   # 使用命令行创建
   php artisan task:create "我的第一个任务" \
     --type=browser \
     --url="https://example.com" \
     --schedule="0 9 * * *"
   ```

3. **配置任务**
   - 访问Web界面的任务管理页面
   - 使用可视化编辑器配置任务流程
   - 设置执行计划和通知规则

4. **执行任务**
   ```bash
   # 手动执行
   php artisan task:execute {task-id}
   
   # 查看执行结果
   php artisan task:status {task-id}
   ```

### 工作流编辑器使用

1. **节点类型**
   - **开始节点**: 工作流入口点
   - **动作节点**: 执行具体操作（点击、输入、截图等）
   - **条件节点**: 条件判断和分支控制
   - **结束节点**: 工作流结束点

2. **连接节点**
   - 拖拽节点到画布
   - 连接节点创建执行流程
   - 配置节点参数和条件

3. **调试工作流**
   ```bash
   # 启用调试模式
   php artisan workflow:execute workflow.json --debug
   
   # 单步执行
   php artisan workflow:execute workflow.json --step-by-step
   ```

### 浏览器池管理

1. **查看浏览器状态**
   ```bash
   php artisan browser:status
   ```

2. **管理浏览器实例**
   ```bash
   # 启动浏览器池
   php artisan browser:start --instances=5
   
   # 停止所有实例
   php artisan browser:stop
   
   # 重启异常实例
   php artisan browser:restart --unhealthy-only
   ```

3. **健康检查**
   ```bash
   php artisan browser:health-check
   ```

### 监控和告警

1. **查看系统状态**
   ```bash
   php artisan monitor:performance --type=system
   ```

2. **配置通知渠道**
   ```bash
   # 测试邮件通知
   php artisan notification:test email
   
   # 测试钉钉通知
   php artisan notification:test dingtalk
   ```

3. **系统优化**
   ```bash
   # 分析系统性能
   php artisan system:optimize --analyze
   
   # 自动调优
   php artisan system:optimize --auto-tune
   ```

## 🔧 配置说明

### 环境变量配置

```env
# 应用基础配置
APP_NAME="蜂鸟自动化平台"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=fengniao
DB_USERNAME=fengniao
DB_PASSWORD=fengniao_password

# Redis配置
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# 队列配置
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database

# 缓存配置
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# 浏览器配置
BROWSER_POOL_SIZE=5
BROWSER_MAX_TABS=3
BROWSER_TIMEOUT=300
BROWSER_HEADLESS=true

# 通知配置
NOTIFICATION_EMAIL_ENABLED=true
NOTIFICATION_EMAIL_TO=admin@example.com
NOTIFICATION_DINGTALK_ENABLED=false
NOTIFICATION_DINGTALK_WEBHOOK=

# 监控配置
MONITORING_ENABLED=true
MONITORING_INTERVAL=60
PERFORMANCE_ALERT_CPU_THRESHOLD=80
PERFORMANCE_ALERT_MEMORY_THRESHOLD=85
```

## 🔌 API文档

### 认证

所有API请求需要包含认证令牌：

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     http://localhost/api/v1/tasks
```

### 主要接口

#### 任务管理

```bash
# 获取任务列表
GET /api/v1/tasks

# 创建任务
POST /api/v1/tasks
{
    "name": "测试任务",
    "type": "browser",
    "config": {
        "url": "https://example.com",
        "actions": [...]
    },
    "schedule": "0 9 * * *"
}

# 执行任务
POST /api/v1/tasks/{id}/execute

# 获取执行结果
GET /api/v1/tasks/{id}/executions
```

#### 浏览器管理

```bash
# 获取浏览器状态
GET /api/v1/browsers/status

# 重启浏览器实例
POST /api/v1/browsers/{id}/restart
```

#### 监控数据

```bash
# 获取系统监控数据
GET /api/v1/monitoring/system

# 获取任务统计
GET /api/v1/monitoring/tasks
```

## 🧪 测试

### 运行测试

```bash
# 运行所有测试
php artisan test

# 运行特定测试套件
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
php artisan test --testsuite=Integration

# 运行特定测试
php artisan test tests/Feature/TaskExecutionTest.php

# 生成测试覆盖率报告
php artisan test --coverage-html coverage
```

### 集成测试

```bash
# 运行集成测试
php artisan test tests/Integration/

# 测试浏览器功能
php artisan test tests/Integration/BrowserTest.php

# 测试任务执行流程
php artisan test tests/Integration/TaskExecutionFlowTest.php
```

### 性能测试

```bash
# 运行性能测试
php artisan test tests/Performance/

# 并发执行测试
php artisan test tests/Integration/ConcurrentExecutionTest.php
```

## 📞 支持与反馈

### 获取帮助

- **文档**: [https://docs.fengniao.com](https://docs.fengniao.com)
- **GitHub Issues**: [https://github.com/your-org/fengniao/issues](https://github.com/your-org/fengniao/issues)
- **讨论区**: [https://github.com/your-org/fengniao/discussions](https://github.com/your-org/fengniao/discussions)
- **邮件支持**: support@fengniao.com

### 社区

- **QQ群**: 123456789
- **微信群**: 扫描二维码加入
- **Slack**: [fengniao.slack.com](https://fengniao.slack.com)

### 商业支持

如需商业支持、定制开发或企业培训，请联系：
- **邮箱**: business@fengniao.com
- **电话**: +86-400-xxx-xxxx

## 📄 许可证

本项目采用 [MIT License](LICENSE) 开源协议。

## 🙏 致谢

感谢以下开源项目和贡献者：

- [Laravel](https://laravel.com) - 优秀的PHP框架
- [Laravel Dusk](https://laravel.com/docs/dusk) - 浏览器自动化测试工具
- [Vue.js](https://vuejs.org) - 渐进式JavaScript框架
- [Chrome DevTools Protocol](https://chromedevtools.github.io/devtools-protocol/) - 浏览器调试协议

特别感谢所有为项目贡献代码、文档和建议的开发者们！

---

<p align="center">
  <strong>蜂鸟自动化平台 - 让自动化测试更简单、更高效</strong>
</p>
