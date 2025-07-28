#!/bin/bash

# 蜂鸟自动化平台部署脚本
# 使用方法: ./deploy.sh [环境] [选项]
# 环境: development, staging, production
# 选项: --build, --migrate, --seed, --backup

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 默认值
ENVIRONMENT="production"
BUILD_IMAGES=false
RUN_MIGRATIONS=false
RUN_SEEDERS=false
CREATE_BACKUP=false
FORCE_RECREATE=false

# 解析命令行参数
while [[ $# -gt 0 ]]; do
    case $1 in
        development|staging|production)
            ENVIRONMENT="$1"
            shift
            ;;
        --build)
            BUILD_IMAGES=true
            shift
            ;;
        --migrate)
            RUN_MIGRATIONS=true
            shift
            ;;
        --seed)
            RUN_SEEDERS=true
            shift
            ;;
        --backup)
            CREATE_BACKUP=true
            shift
            ;;
        --force-recreate)
            FORCE_RECREATE=true
            shift
            ;;
        --help)
            echo "使用方法: $0 [环境] [选项]"
            echo "环境:"
            echo "  development  开发环境"
            echo "  staging      测试环境"
            echo "  production   生产环境 (默认)"
            echo "选项:"
            echo "  --build          重新构建Docker镜像"
            echo "  --migrate        运行数据库迁移"
            echo "  --seed           运行数据库种子"
            echo "  --backup         创建数据库备份"
            echo "  --force-recreate 强制重新创建容器"
            echo "  --help           显示此帮助信息"
            exit 0
            ;;
        *)
            echo -e "${RED}未知参数: $1${NC}"
            exit 1
            ;;
    esac
done

# 函数定义
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_requirements() {
    log_info "检查系统要求..."
    
    # 检查Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker未安装，请先安装Docker"
        exit 1
    fi
    
    # 检查Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose未安装，请先安装Docker Compose"
        exit 1
    fi
    
    # 检查磁盘空间
    AVAILABLE_SPACE=$(df . | tail -1 | awk '{print $4}')
    if [ "$AVAILABLE_SPACE" -lt 2097152 ]; then # 2GB in KB
        log_warning "可用磁盘空间不足2GB，可能影响部署"
    fi
    
    log_success "系统要求检查通过"
}

setup_environment() {
    log_info "设置环境配置..."
    
    # 复制环境配置文件
    if [ ! -f ".env" ]; then
        if [ -f ".env.${ENVIRONMENT}" ]; then
            cp ".env.${ENVIRONMENT}" .env
            log_info "使用 .env.${ENVIRONMENT} 配置文件"
        else
            cp .env.example .env
            log_warning "使用默认配置文件，请检查配置"
        fi
    fi
    
    # 创建必要的目录
    mkdir -p storage/logs
    mkdir -p storage/app/screenshots
    mkdir -p storage/app/backups
    mkdir -p docker/nginx/ssl
    
    # 设置权限
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
    chmod +x docker/start.sh
    
    log_success "环境配置完成"
}

create_backup() {
    if [ "$CREATE_BACKUP" = true ]; then
        log_info "创建数据库备份..."
        
        BACKUP_FILE="storage/app/backups/backup_$(date +%Y%m%d_%H%M%S).sql"
        
        if docker-compose ps mysql | grep -q "Up"; then
            docker-compose exec mysql mysqldump -u fengniao -pfengniao_password fengniao > "$BACKUP_FILE"
            log_success "数据库备份已创建: $BACKUP_FILE"
        else
            log_warning "MySQL容器未运行，跳过备份"
        fi
    fi
}

build_images() {
    if [ "$BUILD_IMAGES" = true ]; then
        log_info "构建Docker镜像..."
        docker-compose build --no-cache
        log_success "Docker镜像构建完成"
    fi
}

deploy_services() {
    log_info "部署服务..."
    
    # 停止现有服务
    if [ "$FORCE_RECREATE" = true ]; then
        docker-compose down -v
        log_info "已停止并删除所有容器和卷"
    else
        docker-compose down
        log_info "已停止现有容器"
    fi
    
    # 启动服务
    if [ "$ENVIRONMENT" = "development" ]; then
        docker-compose up -d --remove-orphans
    else
        docker-compose -f docker-compose.yml up -d --remove-orphans
    fi
    
    log_success "服务部署完成"
}

wait_for_services() {
    log_info "等待服务启动..."
    
    # 等待MySQL
    log_info "等待MySQL启动..."
    timeout=60
    while ! docker-compose exec mysql mysqladmin ping -h localhost --silent && [ $timeout -gt 0 ]; do
        sleep 2
        timeout=$((timeout-2))
    done
    
    if [ $timeout -le 0 ]; then
        log_error "MySQL启动超时"
        exit 1
    fi
    
    # 等待Redis
    log_info "等待Redis启动..."
    timeout=30
    while ! docker-compose exec redis redis-cli ping | grep -q PONG && [ $timeout -gt 0 ]; do
        sleep 1
        timeout=$((timeout-1))
    done
    
    if [ $timeout -le 0 ]; then
        log_error "Redis启动超时"
        exit 1
    fi
    
    # 等待应用服务
    log_info "等待应用服务启动..."
    sleep 10
    
    log_success "所有服务已启动"
}

run_migrations() {
    if [ "$RUN_MIGRATIONS" = true ]; then
        log_info "运行数据库迁移..."
        docker-compose exec app php artisan migrate --force
        log_success "数据库迁移完成"
    fi
}

run_seeders() {
    if [ "$RUN_SEEDERS" = true ]; then
        log_info "运行数据库种子..."
        docker-compose exec app php artisan db:seed --force
        log_success "数据库种子完成"
    fi
}

initialize_system() {
    log_info "初始化系统..."
    
    # 生成应用密钥
    docker-compose exec app php artisan key:generate --force
    
    # 初始化权限系统
    docker-compose exec app php artisan permission:manage init --force
    
    # 优化缓存
    docker-compose exec app php artisan config:cache
    docker-compose exec app php artisan route:cache
    docker-compose exec app php artisan view:cache
    
    # 创建存储链接
    docker-compose exec app php artisan storage:link
    
    log_success "系统初始化完成"
}

health_check() {
    log_info "执行健康检查..."
    
    # 检查Web服务
    if curl -f http://localhost/health > /dev/null 2>&1; then
        log_success "Web服务正常"
    else
        log_error "Web服务异常"
        return 1
    fi
    
    # 检查数据库连接
    if docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';" | grep -q "Database OK"; then
        log_success "数据库连接正常"
    else
        log_error "数据库连接异常"
        return 1
    fi
    
    # 检查Redis连接
    if docker-compose exec app php artisan tinker --execute="Cache::put('test', 'ok'); echo Cache::get('test');" | grep -q "ok"; then
        log_success "Redis连接正常"
    else
        log_error "Redis连接异常"
        return 1
    fi
    
    log_success "健康检查通过"
}

show_status() {
    log_info "服务状态:"
    docker-compose ps
    
    echo ""
    log_info "访问信息:"
    echo "  Web界面: http://localhost"
    echo "  API文档: http://localhost/api/documentation"
    echo "  健康检查: http://localhost/health"
    
    if docker-compose ps | grep -q grafana; then
        echo "  监控面板: http://localhost:3000 (admin/admin123)"
    fi
    
    echo ""
    log_info "日志查看:"
    echo "  应用日志: docker-compose logs -f app"
    echo "  队列日志: docker-compose logs -f queue"
    echo "  Nginx日志: docker-compose logs -f nginx"
    echo "  所有日志: docker-compose logs -f"
}

# 主执行流程
main() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}    蜂鸟自动化平台部署脚本${NC}"
    echo -e "${BLUE}    环境: ${ENVIRONMENT}${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    
    check_requirements
    setup_environment
    create_backup
    build_images
    deploy_services
    wait_for_services
    run_migrations
    run_seeders
    initialize_system
    
    if health_check; then
        echo ""
        log_success "部署成功完成！"
        show_status
    else
        echo ""
        log_error "部署完成但健康检查失败，请检查日志"
        docker-compose logs --tail=50
        exit 1
    fi
}

# 执行主函数
main
