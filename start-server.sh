#!/bin/bash

# 蜂鸟自动化平台服务器启动脚本
# 用于避免 "Broken pipe" 错误

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 默认端口
DEFAULT_PORT=8000

# 解析命令行参数
PORT=${1:-$DEFAULT_PORT}

echo -e "${BLUE}🚀 启动蜂鸟自动化平台服务器${NC}"
echo -e "${BLUE}================================${NC}"

# 检查端口是否被占用
check_port() {
    local port=$1
    if lsof -ti:$port > /dev/null 2>&1; then
        echo -e "${YELLOW}⚠️  端口 $port 已被占用${NC}" >&2
        return 1
    else
        echo -e "${GREEN}✅ 端口 $port 可用${NC}" >&2
        return 0
    fi
}

# 查找可用端口
find_available_port() {
    local start_port=$1
    local port=$start_port
    
    while [ $port -le $((start_port + 100)) ]; do
        if check_port $port; then
            echo $port
            return 0
        fi
        port=$((port + 1))
    done
    
    echo -e "${RED}❌ 无法找到可用端口${NC}"
    exit 1
}

# 清理缓存
echo -e "${BLUE}🧹 清理应用缓存...${NC}"
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1

# 检查应用状态
echo -e "${BLUE}🔍 检查应用状态...${NC}"
if ! php artisan --version > /dev/null 2>&1; then
    echo -e "${RED}❌ Laravel 应用异常${NC}"
    exit 1
fi

# 检查数据库连接
echo -e "${BLUE}🗄️  检查数据库连接...${NC}"
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | grep -q "OK"; then
    echo -e "${GREEN}✅ 数据库连接正常${NC}"
else
    echo -e "${YELLOW}⚠️  数据库连接异常，但服务器仍可启动${NC}"
fi

# 查找可用端口
echo -e "${BLUE}🔍 查找可用端口...${NC}"
AVAILABLE_PORT=$(find_available_port $PORT)

# 设置环境变量
export APP_LOCALE=zh_CN

# 启动服务器
echo -e "${GREEN}🚀 启动服务器在端口 $AVAILABLE_PORT...${NC}"
echo -e "${GREEN}📱 访问地址: http://127.0.0.1:$AVAILABLE_PORT${NC}"
echo -e "${GREEN}🏥 健康检查: http://127.0.0.1:$AVAILABLE_PORT/health${NC}"
echo -e "${GREEN}🌐 语言测试: http://127.0.0.1:$AVAILABLE_PORT/lang-test${NC}"
echo -e "${BLUE}================================${NC}"
echo -e "${YELLOW}按 Ctrl+C 停止服务器${NC}"
echo ""

# 启动PHP内置服务器，使用更稳定的配置
if [ -f "php-server.ini" ]; then
    echo -e "${BLUE}📝 使用自定义PHP配置文件${NC}"
    php -S 127.0.0.1:$AVAILABLE_PORT -t public/ -c php-server.ini
else
    echo -e "${BLUE}📝 使用默认PHP配置${NC}"
    php -S 127.0.0.1:$AVAILABLE_PORT -t public/ \
        -d memory_limit=512M \
        -d max_execution_time=300 \
        -d upload_max_filesize=100M \
        -d post_max_size=100M \
        -d error_reporting=E_ALL \
        -d display_errors=0 \
        -d log_errors=1 \
        -d error_log=storage/logs/php_errors.log
fi
