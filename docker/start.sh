#!/bin/bash

# 等待数据库启动
echo "等待数据库启动..."
while ! nc -z mysql 3306; do
  sleep 1
done
echo "数据库已启动"

# 等待Redis启动
echo "等待Redis启动..."
while ! nc -z redis 6379; do
  sleep 1
done
echo "Redis已启动"

# 运行Laravel初始化命令
echo "运行Laravel初始化..."

# 生成应用密钥
if [ ! -f .env ]; then
    cp .env.example .env
fi

php artisan key:generate --force

# 运行数据库迁移
php artisan migrate --force

# 初始化权限系统
php artisan permission:manage init --force

# 清理和优化缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 创建存储链接
php artisan storage:link

# 设置权限
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# 启动cron服务
service cron start

# 启动supervisor
echo "启动服务..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
