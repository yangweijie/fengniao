# 蜂鸟自动化平台安装指南

本文档详细介绍了蜂鸟自动化平台的安装和配置过程。

## 📋 安装前准备

### 系统要求

#### 最低配置
- **操作系统**: Linux (Ubuntu 18.04+, CentOS 7+) / macOS 10.14+ / Windows 10+
- **CPU**: 2核心
- **内存**: 4GB RAM
- **磁盘**: 20GB 可用空间
- **网络**: 稳定的互联网连接

#### 推荐配置
- **操作系统**: Ubuntu 20.04 LTS / CentOS 8
- **CPU**: 4核心或更多
- **内存**: 8GB RAM 或更多
- **磁盘**: 50GB SSD
- **网络**: 100Mbps 或更高带宽

### 软件依赖

#### 必需软件
- **PHP**: 8.2 或更高版本
- **Composer**: 2.0 或更高版本
- **Node.js**: 18.0 或更高版本
- **NPM**: 8.0 或更高版本
- **MySQL**: 8.0 或更高版本
- **Redis**: 6.0 或更高版本
- **Google Chrome**: 最新稳定版

#### 可选软件
- **Docker**: 20.10+ (推荐用于部署)
- **Docker Compose**: 2.0+
- **Nginx**: 1.18+ (生产环境推荐)
- **Supervisor**: 4.0+ (进程管理)

## 🐳 Docker 安装（推荐）

Docker 安装是最简单和推荐的安装方式，可以避免复杂的环境配置。

### 1. 安装 Docker 和 Docker Compose

#### Ubuntu/Debian
```bash
# 更新包索引
sudo apt update

# 安装必要的包
sudo apt install -y apt-transport-https ca-certificates curl gnupg lsb-release

# 添加 Docker 官方 GPG 密钥
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# 添加 Docker 仓库
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# 安装 Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# 启动 Docker 服务
sudo systemctl start docker
sudo systemctl enable docker

# 将当前用户添加到 docker 组
sudo usermod -aG docker $USER
```

#### CentOS/RHEL
```bash
# 安装必要的包
sudo yum install -y yum-utils

# 添加 Docker 仓库
sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo

# 安装 Docker
sudo yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# 启动 Docker 服务
sudo systemctl start docker
sudo systemctl enable docker

# 将当前用户添加到 docker 组
sudo usermod -aG docker $USER
```

#### macOS
```bash
# 使用 Homebrew 安装
brew install --cask docker

# 或者下载 Docker Desktop for Mac
# https://www.docker.com/products/docker-desktop
```

### 2. 克隆项目代码

```bash
# 克隆项目
git clone https://github.com/your-org/fengniao.git
cd fengniao

# 或者下载发布版本
wget https://github.com/your-org/fengniao/archive/v1.0.0.tar.gz
tar -xzf v1.0.0.tar.gz
cd fengniao-1.0.0
```

### 3. 配置环境变量

```bash
# 复制环境配置文件
cp .env.example .env

# 编辑配置文件
vim .env
```

#### 关键配置项说明

```env
# 应用基础配置
APP_NAME="蜂鸟自动化平台"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=fengniao
DB_USERNAME=fengniao
DB_PASSWORD=your_secure_password_here

# Redis 配置
REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password_here
REDIS_PORT=6379

# 邮件配置（用于通知）
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# 浏览器配置
BROWSER_POOL_SIZE=5
BROWSER_MAX_TABS=3
BROWSER_TIMEOUT=300
BROWSER_HEADLESS=true

# 通知配置
NOTIFICATION_EMAIL_ENABLED=true
NOTIFICATION_EMAIL_TO=admin@your-domain.com
```

### 4. 一键部署

```bash
# 赋予执行权限
chmod +x deploy.sh

# 部署到生产环境
./deploy.sh production --build --migrate --seed

# 部署过程说明：
# --build: 构建 Docker 镜像
# --migrate: 运行数据库迁移
# --seed: 初始化基础数据
```

### 5. 验证安装

```bash
# 检查服务状态
docker-compose ps

# 访问健康检查端点
curl http://localhost/health

# 查看日志
docker-compose logs -f app
```

## 🔧 手动安装

如果您不想使用 Docker，可以选择手动安装。

### 1. 安装系统依赖

#### Ubuntu/Debian
```bash
# 更新包索引
sudo apt update

# 安装 PHP 8.2 和扩展
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-redis \
    php8.2-gd php8.2-zip php8.2-mbstring php8.2-xml php8.2-curl \
    php8.2-bcmath php8.2-intl php8.2-soap php8.2-xsl

# 安装 Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装 Node.js 和 NPM
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# 安装 MySQL
sudo apt install -y mysql-server

# 安装 Redis
sudo apt install -y redis-server

# 安装 Nginx
sudo apt install -y nginx

# 安装 Supervisor
sudo apt install -y supervisor

# 安装 Google Chrome
wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" | sudo tee /etc/apt/sources.list.d/google.list
sudo apt update
sudo apt install -y google-chrome-stable
```

#### CentOS/RHEL
```bash
# 安装 EPEL 仓库
sudo yum install -y epel-release

# 安装 Remi 仓库（用于 PHP 8.2）
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# 启用 PHP 8.2 模块
sudo dnf module reset php
sudo dnf module enable php:remi-8.2

# 安装 PHP 和扩展
sudo yum install -y php php-fpm php-mysql php-redis php-gd php-zip \
    php-mbstring php-xml php-curl php-bcmath php-intl php-soap php-xsl

# 安装 Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装 Node.js
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs

# 安装 MySQL
sudo yum install -y mysql-server

# 安装 Redis
sudo yum install -y redis

# 安装 Nginx
sudo yum install -y nginx

# 安装 Supervisor
sudo yum install -y supervisor
```

### 2. 配置数据库

```bash
# 启动 MySQL 服务
sudo systemctl start mysql
sudo systemctl enable mysql

# 安全配置 MySQL
sudo mysql_secure_installation

# 创建数据库和用户
sudo mysql -u root -p
```

```sql
-- 在 MySQL 命令行中执行
CREATE DATABASE fengniao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fengniao'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON fengniao.* TO 'fengniao'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. 配置 Redis

```bash
# 启动 Redis 服务
sudo systemctl start redis
sudo systemctl enable redis

# 配置 Redis（可选）
sudo vim /etc/redis/redis.conf
```

### 4. 部署应用代码

```bash
# 创建应用目录
sudo mkdir -p /var/www/fengniao
cd /var/www/fengniao

# 克隆代码
sudo git clone https://github.com/your-org/fengniao.git .

# 设置权限
sudo chown -R www-data:www-data /var/www/fengniao
sudo chmod -R 755 /var/www/fengniao

# 安装 PHP 依赖
sudo -u www-data composer install --no-dev --optimize-autoloader

# 安装 Node.js 依赖
sudo -u www-data npm install
sudo -u www-data npm run build

# 配置环境
sudo -u www-data cp .env.example .env
sudo -u www-data vim .env

# 生成应用密钥
sudo -u www-data php artisan key:generate

# 运行数据库迁移
sudo -u www-data php artisan migrate --force

# 初始化权限系统
sudo -u www-data php artisan permission:manage init --force

# 优化缓存
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 创建存储链接
sudo -u www-data php artisan storage:link
```

### 5. 配置 Nginx

```bash
# 创建 Nginx 配置文件
sudo vim /etc/nginx/sites-available/fengniao
```

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/fengniao/public;
    index index.php index.html index.htm;

    # 日志配置
    access_log /var/log/nginx/fengniao.access.log;
    error_log /var/log/nginx/fengniao.error.log;

    # 主要位置块
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP 处理
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_read_timeout 300;
    }

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # 安全配置
    location ~ /\. {
        deny all;
    }

    location ~ /(storage|bootstrap|config|database|resources|routes|tests|vendor)/ {
        deny all;
    }
}
```

```bash
# 启用站点
sudo ln -s /etc/nginx/sites-available/fengniao /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl enable nginx
```

### 6. 配置 Supervisor

```bash
# 创建 Supervisor 配置文件
sudo vim /etc/supervisor/conf.d/fengniao.conf
```

```ini
[program:fengniao-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/fengniao/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
directory=/var/www/fengniao
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/fengniao/storage/logs/worker.log
stopwaitsecs=3600

[program:fengniao-scheduler]
command=php /var/www/fengniao/artisan schedule:work
directory=/var/www/fengniao
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/fengniao/storage/logs/scheduler.log
```

```bash
# 重新加载 Supervisor 配置
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
sudo systemctl enable supervisor
```

### 7. 配置定时任务

```bash
# 编辑 crontab
sudo crontab -e -u www-data
```

```cron
# Laravel 任务调度
* * * * * cd /var/www/fengniao && php artisan schedule:run >> /dev/null 2>&1
```

## 🔒 SSL 证书配置

### 使用 Let's Encrypt

```bash
# 安装 Certbot
sudo apt install -y certbot python3-certbot-nginx

# 获取 SSL 证书
sudo certbot --nginx -d your-domain.com

# 自动续期
sudo crontab -e
```

```cron
# SSL 证书自动续期
0 12 * * * /usr/bin/certbot renew --quiet
```

## 🧪 验证安装

### 1. 检查服务状态

```bash
# 检查 PHP-FPM
sudo systemctl status php8.2-fpm

# 检查 Nginx
sudo systemctl status nginx

# 检查 MySQL
sudo systemctl status mysql

# 检查 Redis
sudo systemctl status redis

# 检查 Supervisor
sudo systemctl status supervisor
```

### 2. 测试应用功能

```bash
# 健康检查
curl http://your-domain.com/health

# 测试 API
curl -H "Content-Type: application/json" http://your-domain.com/api/v1/status

# 检查队列工作进程
php artisan queue:monitor

# 检查浏览器池
php artisan browser:status
```

### 3. 访问 Web 界面

1. 打开浏览器访问: `http://your-domain.com`
2. 使用默认管理员账号登录:
   - 邮箱: `admin@example.com`
   - 密码: `password`
3. 首次登录后请立即修改密码

## 🔧 常见问题解决

### 权限问题

```bash
# 修复文件权限
sudo chown -R www-data:www-data /var/www/fengniao
sudo chmod -R 755 /var/www/fengniao/storage
sudo chmod -R 755 /var/www/fengniao/bootstrap/cache
```

### 内存不足

```bash
# 增加 PHP 内存限制
sudo vim /etc/php/8.2/fpm/php.ini
# memory_limit = 512M

# 重启 PHP-FPM
sudo systemctl restart php8.2-fpm
```

### 数据库连接失败

```bash
# 检查数据库服务
sudo systemctl status mysql

# 测试数据库连接
mysql -u fengniao -p -h localhost fengniao

# 检查防火墙
sudo ufw status
```

### Chrome 浏览器问题

```bash
# 检查 Chrome 安装
google-chrome --version

# 测试 Chrome 启动
google-chrome --headless --disable-gpu --dump-dom https://www.google.com
```

## 📈 性能优化建议

### 1. 系统级优化

```bash
# 增加文件描述符限制
echo "* soft nofile 65536" | sudo tee -a /etc/security/limits.conf
echo "* hard nofile 65536" | sudo tee -a /etc/security/limits.conf

# 优化内核参数
echo "net.core.somaxconn = 65535" | sudo tee -a /etc/sysctl.conf
echo "net.ipv4.tcp_max_syn_backlog = 65535" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

### 2. 应用级优化

```bash
# 启用 OPcache
sudo vim /etc/php/8.2/fpm/php.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 3. 数据库优化

```bash
# 优化 MySQL 配置
sudo vim /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 64M
max_connections = 200
```

## 🔄 升级指南

### Docker 环境升级

```bash
# 备份数据
./deploy.sh production --backup

# 拉取最新代码
git pull origin main

# 重新部署
./deploy.sh production --build --migrate
```

### 手动环境升级

```bash
# 备份数据库
php artisan backup:database

# 进入维护模式
php artisan down

# 更新代码
git pull origin main

# 更新依赖
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 运行迁移
php artisan migrate --force

# 清理缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 退出维护模式
php artisan up
```

## 📞 获取支持

如果在安装过程中遇到问题，请通过以下方式获取支持：

- **文档**: [https://docs.fengniao.com](https://docs.fengniao.com)
- **GitHub Issues**: [https://github.com/your-org/fengniao/issues](https://github.com/your-org/fengniao/issues)
- **邮件支持**: support@fengniao.com
- **QQ群**: 123456789

---

安装完成后，请参考 [用户手册](USER_GUIDE.md) 了解如何使用蜂鸟自动化平台。
