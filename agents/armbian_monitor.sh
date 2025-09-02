#!/bin/bash

# Armbian设备监控客户端脚本
# 用于向监控平台发送设备心跳信息
# 使用方法：将此脚本放置在Armbian设备上，并设置定时任务
# 设备ID：基于硬件信息生成稳定的唯一标识符（CPU序列号、machine-id或随机UUID）

# 配置部分 - 请根据实际情况修改
API_URL="http://localhost:3000/api/devices/heartbeat"  # 监控平台API地址
DEVICE_NAME="$(hostname)"                          # 设备名称，默认使用主机名
LOG_FILE="/var/log/armbian_monitor.log"           # 日志文件路径

# 日志函数
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# 生成设备唯一ID（使用持久化UUID）
generate_device_id() {
    local device_id_file="/etc/armbian-monitor-id"
    
    # 检查多个可能的设备ID文件位置
    for possible_file in "/etc/armbian-monitor-id" "/var/tmp/armbian-monitor-id" "/tmp/armbian-monitor-id"; do
        if [ -f "$possible_file" ]; then
            DEVICE_ID=$(cat "$possible_file" 2>/dev/null)
            if [ -n "$DEVICE_ID" ]; then
                device_id_file="$possible_file"
                return
            fi
        fi
    done
    
    # 生成新的设备ID
    # 不再使用KSA ID，直接生成基于硬件信息的稳定ID
    
    # 生成基于硬件信息的稳定ID
    # 尝试使用CPU序列号
    if [ -f /proc/cpuinfo ]; then
        CPU_SERIAL=$(grep "^Serial" /proc/cpuinfo | cut -d':' -f2 | tr -d ' ')
        if [ -n "$CPU_SERIAL" ] && [ "$CPU_SERIAL" != "0000000000000000" ]; then
            DEVICE_ID="armbian-${CPU_SERIAL:0:12}"
        fi
    fi
    
    # 如果没有CPU序列号，使用machine-id
    if [ -z "$DEVICE_ID" ]; then
        if [ -f /etc/machine-id ]; then
            MACHINE_ID=$(cat /etc/machine-id)
            DEVICE_ID="armbian-${MACHINE_ID:0:12}"
        elif [ -f /var/lib/dbus/machine-id ]; then
            MACHINE_ID=$(cat /var/lib/dbus/machine-id)
            DEVICE_ID="armbian-${MACHINE_ID:0:12}"
        fi
    fi
    
    # 最后备选方案：生成随机UUID
    if [ -z "$DEVICE_ID" ]; then
        if command -v uuidgen >/dev/null 2>&1; then
            UUID=$(uuidgen | tr -d '-')
            DEVICE_ID="armbian-${UUID:0:12}"
        else
            # 如果没有uuidgen，使用时间戳和随机数生成
            TIMESTAMP=$(date +%s)
            RANDOM_NUM=$((RANDOM * RANDOM))
            DEVICE_ID="armbian-$(echo "${TIMESTAMP}${RANDOM_NUM}" | sha256sum | cut -c1-12)"
        fi
    fi
    
    # 将设备ID保存到文件中
    if echo "$DEVICE_ID" > "$device_id_file" 2>/dev/null; then
        log_message "生成新的设备ID: $DEVICE_ID，保存到: $device_id_file"
    else
        # 如果无法写入/etc，尝试写入/var/tmp
        device_id_file="/var/tmp/armbian-monitor-id"
        if echo "$DEVICE_ID" > "$device_id_file" 2>/dev/null; then
            log_message "生成新的设备ID: $DEVICE_ID，保存到: $device_id_file"
        else
            # 最后尝试写入/tmp
            device_id_file="/tmp/armbian-monitor-id"
            echo "$DEVICE_ID" > "$device_id_file" 2>/dev/null
            log_message "生成新的设备ID: $DEVICE_ID，保存到: $device_id_file"
        fi
    fi
}

# 调用函数生成设备ID
generate_device_id

# 获取CPU使用率
get_cpu_usage() {
    # 方法1：使用/proc/stat（适用于Linux系统）
    if [ -f /proc/stat ]; then
        # 读取两次CPU数据，计算使用率
        cpu1=$(cat /proc/stat | grep '^cpu ' | awk '{print $2+$3+$4+$5+$6+$7+$8}')
        idle1=$(cat /proc/stat | grep '^cpu ' | awk '{print $5}')
        sleep 1
        cpu2=$(cat /proc/stat | grep '^cpu ' | awk '{print $2+$3+$4+$5+$6+$7+$8}')
        idle2=$(cat /proc/stat | grep '^cpu ' | awk '{print $5}')
        
        cpu_delta=$((cpu2 - cpu1))
        idle_delta=$((idle2 - idle1))
        
        if [ $cpu_delta -gt 0 ]; then
            cpu_usage=$(echo "scale=1; 100 * (1 - $idle_delta / $cpu_delta)" | bc 2>/dev/null)
            if [ -n "$cpu_usage" ]; then
                echo "$cpu_usage"
                return
            fi
        fi
    fi
    
    # 方法2：使用top命令（Linux版本）
    if command -v top >/dev/null 2>&1; then
        CPU=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')
        if [[ "$CPU" =~ ^[0-9]+\.?[0-9]*$ ]]; then
            echo "$CPU"
            return
        fi
    fi
    
    # 备选方案：使用vmstat
    if command -v vmstat >/dev/null 2>&1; then
        CPU=$(vmstat 1 2 | tail -1 | awk '{print 100-$15}')
        if [[ "$CPU" =~ ^[0-9]+\.?[0-9]*$ ]]; then
            echo "$CPU"
            return
        fi
    fi
    
    echo "0"
}

# 获取内存使用率
get_memory_usage() {
    if command -v free >/dev/null 2>&1; then
        MEMORY=$(free | grep Mem | awk '{printf "%.1f", $3/$2 * 100.0}')
        echo "$MEMORY"
    else
        echo "0"
    fi
}

# 获取磁盘使用率
get_disk_usage() {
    if command -v df >/dev/null 2>&1; then
        DISK=$(df / | tail -1 | awk '{print $5}' | cut -d'%' -f1)
        echo "$DISK"
    else
        echo "0"
    fi
}

# 获取温度
get_temperature() {
    # 尝试多个温度传感器路径
    TEMP_PATHS=(
        "/sys/class/thermal/thermal_zone0/temp"
        "/sys/class/thermal/thermal_zone1/temp"
        "/sys/devices/virtual/thermal/thermal_zone0/temp"
    )
    
    for path in "${TEMP_PATHS[@]}"; do
        if [ -f "$path" ]; then
            TEMP=$(cat "$path" 2>/dev/null)
            if [[ "$TEMP" =~ ^[0-9]+$ ]] && [ "$TEMP" -gt 0 ]; then
                # 温度通常以毫度为单位，需要除以1000
                echo "scale=1; $TEMP / 1000" | bc 2>/dev/null || echo "$(($TEMP / 1000))"
                return
            fi
        fi
    done
    
    # 如果找不到温度传感器，返回null
    echo "null"
}

# 获取系统运行时间（秒）
get_uptime() {
    if [ -f /proc/uptime ]; then
        UPTIME=$(cat /proc/uptime | awk '{print int($1)}')
        echo "$UPTIME"
    else
        echo "0"
    fi
}

# 获取MAC地址
get_mac_address() {
    # 获取第一个非回环网络接口的MAC地址
    for interface in /sys/class/net/*; do
        if [ -f "$interface/address" ] && [ "$(basename $interface)" != "lo" ]; then
            MAC=$(cat "$interface/address" 2>/dev/null)
            if [ -n "$MAC" ] && [ "$MAC" != "00:00:00:00:00:00" ]; then
                echo "$MAC"
                return
            fi
        fi
    done
    echo "null"
}

# 获取内网IP地址
get_local_ip() {
    # 获取首选内网IP地址
    if command -v ip >/dev/null 2>&1; then
        # 使用ip命令
        LOCAL_IP=$(ip -4 addr show scope global | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -1)
    elif command -v ifconfig >/dev/null 2>&1; then
        # 使用ifconfig命令
        LOCAL_IP=$(ifconfig | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1' | head -1)
    else
        # 如果都不可用，尝试其他方法
        LOCAL_IP=$(hostname -I 2>/dev/null | awk '{print $1}')
    fi
    
    if [ -n "$LOCAL_IP" ]; then
        echo "$LOCAL_IP"
    else
        echo "null"
    fi
}

# 获取系统信息
get_system_info() {
    OS="$(uname -s)"
    KERNEL="$(uname -r)"
    ARCH="$(uname -m)"
    
    # 尝试获取发行版信息
    if [ -f /etc/os-release ]; then
        DISTRO=$(grep '^PRETTY_NAME=' /etc/os-release | cut -d'"' -f2)
    elif [ -f /etc/debian_version ]; then
        DISTRO="Debian $(cat /etc/debian_version)"
    else
        DISTRO="Unknown"
    fi
    
    cat <<EOF
{
  "os": "$OS",
  "kernel": "$KERNEL",
  "arch": "$ARCH",
  "distro": "$DISTRO"
}
EOF
}

# 主函数
main() {
    log_message "开始收集设备信息..."
    
    # 收集系统信息
    CPU_USAGE=$(get_cpu_usage)
    MEMORY_USAGE=$(get_memory_usage)
    DISK_USAGE=$(get_disk_usage)
    TEMPERATURE=$(get_temperature)
    UPTIME=$(get_uptime)
    MAC_ADDRESS=$(get_mac_address)
    LOCAL_IP=$(get_local_ip)
    SYSTEM_INFO=$(get_system_info)
    
    log_message "设备信息收集完成: CPU=${CPU_USAGE}%, MEM=${MEMORY_USAGE}%, DISK=${DISK_USAGE}%, TEMP=${TEMPERATURE}°C, IP=${LOCAL_IP}"
    
    # 构建JSON数据
    JSON_DATA=$(cat <<EOF
{
  "device_id": "$DEVICE_ID",
  "device_name": "$DEVICE_NAME",
  "mac_address": $([[ "$MAC_ADDRESS" == "null" ]] && echo "null" || echo "\"$MAC_ADDRESS\""),
  "ip_address": $([[ "$LOCAL_IP" == "null" ]] && echo "null" || echo "\"$LOCAL_IP\""),
  "system_info": $SYSTEM_INFO,
  "cpu_usage": $CPU_USAGE,
  "memory_usage": $MEMORY_USAGE,
  "disk_usage": $DISK_USAGE,
  "temperature": $TEMPERATURE,
  "uptime": $UPTIME
}
EOF
)
    
    # 发送心跳数据
    log_message "发送心跳数据到: $API_URL"
    
    if command -v curl >/dev/null 2>&1; then
        RESPONSE=$(curl -s -X POST "$API_URL" \
                       -H "Content-Type: application/json" \
                       -H "User-Agent: ArmbianMonitor/1.0" \
                       -d "$JSON_DATA" \
                       --connect-timeout 10 \
                       --max-time 30)
        
        CURL_EXIT_CODE=$?
        
        if [ $CURL_EXIT_CODE -eq 0 ]; then
            log_message "心跳发送成功: $RESPONSE"
            echo "心跳发送成功"
        else
            log_message "心跳发送失败，curl退出码: $CURL_EXIT_CODE"
            echo "心跳发送失败"
            exit 1
        fi
    else
        log_message "错误: 未找到curl命令，请安装curl"
        echo "错误: 未找到curl命令，请安装curl"
        exit 1
    fi
}

# 检查依赖
check_dependencies() {
    MISSING_DEPS=()
    
    # 检查必需的命令
    for cmd in curl awk grep cat; do
        if ! command -v "$cmd" >/dev/null 2>&1; then
            MISSING_DEPS+=("$cmd")
        fi
    done
    
    if [ ${#MISSING_DEPS[@]} -gt 0 ]; then
        echo "错误: 缺少必需的命令: ${MISSING_DEPS[*]}"
        echo "请安装缺少的软件包"
        exit 1
    fi
}

# 显示帮助信息
show_help() {
    cat <<EOF
Armbian设备监控客户端脚本

使用方法:
  $0 [选项]

选项:
  -h, --help     显示此帮助信息
  -t, --test     测试模式，显示收集的数据但不发送
  -v, --verbose  详细模式，显示更多信息
  --install      安装为系统服务（定时任务）
  --uninstall    卸载系统服务

配置:
  编辑脚本顶部的配置部分来修改API地址和其他设置
  
设备ID生成规则:
  1. 优先使用 CPU 序列号（如果可用且有效）
  2. 如果没有CPU序列号，则使用 machine-id
  3. 最后使用随机生成的UUID作为备选方案

定时任务:
  建议每1-5分钟运行一次，例如:
  */2 * * * * /path/to/armbian_monitor.sh >/dev/null 2>&1

EOF
}

# 安装定时任务
install_cron() {
    SCRIPT_PATH=$(realpath "$0")
    CRON_ENTRY="*/2 * * * * $SCRIPT_PATH >/dev/null 2>&1"
    
    # 检查是否已存在
    if crontab -l 2>/dev/null | grep -q "$SCRIPT_PATH"; then
        echo "定时任务已存在"
        return
    fi
    
    # 添加到crontab
    (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
    
    if [ $? -eq 0 ]; then
        echo "定时任务安装成功，每2分钟运行一次"
        echo "当前crontab:"
        crontab -l | grep "$SCRIPT_PATH"
    else
        echo "定时任务安装失败"
        exit 1
    fi
}

# 卸载定时任务
uninstall_cron() {
    SCRIPT_PATH=$(realpath "$0")
    
    # 从crontab中移除
    crontab -l 2>/dev/null | grep -v "$SCRIPT_PATH" | crontab -
    
    if [ $? -eq 0 ]; then
        echo "定时任务卸载成功"
    else
        echo "定时任务卸载失败"
        exit 1
    fi
}

# 测试模式
test_mode() {
    echo "=== Armbian监控客户端测试模式 ==="
    echo "设备ID: $DEVICE_ID"
    echo "设备名称: $DEVICE_NAME"
    echo "API地址: $API_URL"
    echo ""
    
    echo "收集系统信息..."
    CPU_USAGE=$(get_cpu_usage)
    MEMORY_USAGE=$(get_memory_usage)
    DISK_USAGE=$(get_disk_usage)
    TEMPERATURE=$(get_temperature)
    UPTIME=$(get_uptime)
    MAC_ADDRESS=$(get_mac_address)
    LOCAL_IP=$(get_local_ip)
    
    echo "CPU使用率: ${CPU_USAGE}%"
    echo "内存使用率: ${MEMORY_USAGE}%"
    echo "磁盘使用率: ${DISK_USAGE}%"
    echo "温度: ${TEMPERATURE}°C"
    echo "运行时间: ${UPTIME}秒"
    echo "MAC地址: $MAC_ADDRESS"
    echo "内网IP: $LOCAL_IP"
    echo ""
    
    echo "生成的JSON数据:"
    JSON_DATA=$(cat <<EOF
{
  "device_id": "$DEVICE_ID",
  "device_name": "$DEVICE_NAME",
  "mac_address": $([[ "$MAC_ADDRESS" == "null" ]] && echo "null" || echo "\"$MAC_ADDRESS\""),
  "ip_address": $([[ "$LOCAL_IP" == "null" ]] && echo "null" || echo "\"$LOCAL_IP\""),
  "system_info": $(get_system_info),
  "cpu_usage": $CPU_USAGE,
  "memory_usage": $MEMORY_USAGE,
  "disk_usage": $DISK_USAGE,
  "temperature": $TEMPERATURE,
  "uptime": $UPTIME
}
EOF
)
    
    echo "$JSON_DATA" | python3 -m json.tool 2>/dev/null || echo "$JSON_DATA"
}

# 解析命令行参数
case "$1" in
    -h|--help)
        show_help
        exit 0
        ;;
    -t|--test)
        check_dependencies
        test_mode
        exit 0
        ;;
    --install)
        install_cron
        exit 0
        ;;
    --uninstall)
        uninstall_cron
        exit 0
        ;;
    -v|--verbose)
        set -x
        ;;
esac

# 检查依赖并运行主程序
check_dependencies
main