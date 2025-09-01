#!/bin/bash

# CloudClint 设备监控平台 - 优化版本部署脚本
# 功能：安全部署优化版本，支持备份和回滚

set -e  # 遇到错误立即退出

# 配置变量
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ORIGINAL_FILE="$SCRIPT_DIR/index.php"
OPTIMIZED_FILE="$SCRIPT_DIR/index_optimized.php"
BACKUP_DIR="$SCRIPT_DIR/backups"
LOG_FILE="$SCRIPT_DIR/deployment.log"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 日志函数
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# 彩色输出函数
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
    log "SUCCESS: $1"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
    log "ERROR: $1"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
    log "WARNING: $1"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
    log "INFO: $1"
}

# 检查文件是否存在
check_file_exists() {
    if [[ ! -f "$1" ]]; then
        print_error "文件不存在: $1"
        exit 1
    fi
}

# 创建备份目录
create_backup_dir() {
    if [[ ! -d "$BACKUP_DIR" ]]; then
        mkdir -p "$BACKUP_DIR"
        print_success "创建备份目录: $BACKUP_DIR"
    fi
}

# 备份原文件
backup_original() {
    local timestamp=$(date +"%Y%m%d_%H%M%S")
    local backup_file="$BACKUP_DIR/index_backup_$timestamp.php"
    
    if [[ -f "$ORIGINAL_FILE" ]]; then
        cp "$ORIGINAL_FILE" "$backup_file"
        print_success "原文件已备份到: $backup_file"
        echo "$backup_file" > "$BACKUP_DIR/latest_backup.txt"
    else
        print_warning "原文件不存在，跳过备份"
    fi
}

# 部署优化版本
deploy_optimized() {
    print_info "开始部署优化版本..."
    
    # 复制优化文件
    cp "$OPTIMIZED_FILE" "$ORIGINAL_FILE"
    print_success "优化版本已部署"
    
    # 设置正确的文件权限
    chmod 644 "$ORIGINAL_FILE"
    print_success "文件权限已设置"
}

# 验证部署
validate_deployment() {
    print_info "验证部署结果..."
    
    # 检查 PHP 语法
    if php -l "$ORIGINAL_FILE" > /dev/null 2>&1; then
        print_success "PHP 语法检查通过"
    else
        print_error "PHP 语法检查失败"
        return 1
    fi
    
    # 检查文件权限
    if [[ -r "$ORIGINAL_FILE" ]]; then
        print_success "文件权限检查通过"
    else
        print_error "文件权限检查失败"
        return 1
    fi
    
    return 0
}

# 回滚到指定备份
rollback_to_backup() {
    local backup_file="$1"
    
    if [[ -z "$backup_file" ]]; then
        print_error "请指定备份文件路径"
        exit 1
    fi
    
    if [[ ! -f "$backup_file" ]]; then
        print_error "备份文件不存在: $backup_file"
        exit 1
    fi
    
    print_info "回滚到备份: $backup_file"
    cp "$backup_file" "$ORIGINAL_FILE"
    
    if validate_deployment; then
        print_success "回滚成功"
    else
        print_error "回滚后验证失败"
        exit 1
    fi
}

# 显示帮助信息
show_help() {
    echo "CloudClint 优化版本部署脚本"
    echo ""
    echo "用法:"
    echo "  $0 deploy              - 部署优化版本"
    echo "  $0 rollback [backup]   - 回滚到指定备份（不指定则回滚到最新备份）"
    echo "  $0 list                - 列出所有备份"
    echo "  $0 help                - 显示此帮助信息"
    echo ""
    echo "示例:"
    echo "  $0 deploy"
    echo "  $0 rollback"
    echo "  $0 rollback /path/to/backup.php"
    echo "  $0 list"
}

# 列出备份文件
list_backups() {
    print_info "备份文件列表:"
    
    if [[ ! -d "$BACKUP_DIR" ]] || [[ -z "$(ls -A "$BACKUP_DIR" 2>/dev/null)" ]]; then
        print_warning "没有找到备份文件"
        return
    fi
    
    echo ""
    echo "备份文件:"
    ls -la "$BACKUP_DIR"/*.php 2>/dev/null | while read -r line; do
        echo "  $line"
    done
    
    if [[ -f "$BACKUP_DIR/latest_backup.txt" ]]; then
        local latest_backup=$(cat "$BACKUP_DIR/latest_backup.txt")
        echo ""
        print_info "最新备份: $latest_backup"
    fi
}

# 回滚到最新备份
rollback_to_latest() {
    if [[ -f "$BACKUP_DIR/latest_backup.txt" ]]; then
        local latest_backup=$(cat "$BACKUP_DIR/latest_backup.txt")
        rollback_to_backup "$latest_backup"
    else
        print_error "没有找到最新备份记录"
        exit 1
    fi
}

# 主部署流程
main_deploy() {
    print_info "=== CloudClint 优化版本部署开始 ==="
    
    # 检查必要文件
    print_info "检查部署环境..."
    
    if [[ ! -d "$SCRIPT_DIR" ]]; then
        print_error "脚本目录不存在: $SCRIPT_DIR"
        exit 1
    fi
    
    check_file_exists "$OPTIMIZED_FILE"
    
    # 创建备份目录
    create_backup_dir
    
    # 备份原文件
    backup_original
    
    # 部署优化版本
    deploy_optimized
    
    # 验证部署
    if validate_deployment; then
        print_success "=== 部署成功完成 ==="
        echo ""
        print_info "部署后建议:"
        echo "  1. 测试所有功能是否正常"
        echo "  2. 检查页面加载速度"
        echo "  3. 观察错误日志: $LOG_FILE"
        echo "  4. 如有问题，可使用 '$0 rollback' 回滚"
        echo ""
        print_info "优化报告: optimization_report.md"
    else
        print_error "部署验证失败，正在自动回滚..."
        rollback_to_latest
        exit 1
    fi
}

# 主函数
main() {
    case "${1:-}" in
        "deploy")
            main_deploy
            ;;
        "rollback")
            if [[ -n "${2:-}" ]]; then
                rollback_to_backup "$2"
            else
                rollback_to_latest
            fi
            ;;
        "list")
            list_backups
            ;;
        "help"|"--help"|"-h")
            show_help
            ;;
        "")
            print_error "请指定操作命令"
            show_help
            exit 1
            ;;
        *)
            print_error "未知命令: $1"
            show_help
            exit 1
            ;;
    esac
}

# 执行主函数
main "$@"