/**
 * 设备工具函数
 * 提供设备相关的实用功能和数据处理
 */

(function() {
    'use strict';

    // 设备工具对象
    const DeviceUtils = {
        
        // 格式化设备状态
        formatDeviceStatus: function(status, lastHeartbeat) {
            const now = new Date();
            const heartbeat = new Date(lastHeartbeat);
            const diffMinutes = Math.floor((now - heartbeat) / (1000 * 60));
            
            if (status === 'online' && diffMinutes <= 5) {
                return {
                    status: 'online',
                    text: '在线',
                    class: 'online'
                };
            } else {
                return {
                    status: 'offline',
                    text: '离线',
                    class: 'offline'
                };
            }
        },
        
        // 格式化时间显示
        formatTime: function(timestamp) {
            if (!timestamp) return '--';
            
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / (1000 * 60));
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            
            if (minutes < 1) {
                return '刚刚';
            } else if (minutes < 60) {
                return `${minutes}分钟前`;
            } else if (hours < 24) {
                return `${hours}小时前`;
            } else if (days < 7) {
                return `${days}天前`;
            } else {
                return date.toLocaleDateString('zh-CN', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        },
        
        // 格式化IP地址显示
        formatIPAddress: function(ip) {
            if (!ip) return '--';
            
            // 检查是否为内网IP
            const isPrivate = this.isPrivateIP(ip);
            
            return {
                ip: ip,
                isPrivate: isPrivate,
                displayText: ip
            };
        },
        
        // 判断是否为内网IP
        isPrivateIP: function(ip) {
            const privateRanges = [
                /^10\./,
                /^172\.(1[6-9]|2[0-9]|3[0-1])\./,
                /^192\.168\./,
                /^127\./,
                /^169\.254\./
            ];
            
            return privateRanges.some(range => range.test(ip));
        },
        
        // 格式化系统资源显示
        formatSystemResource: function(type, value, total) {
            if (value === null || value === undefined) {
                return {
                    value: '--',
                    percentage: 0,
                    status: 'unknown',
                    color: '#9ca3af'
                };
            }
            
            let percentage = 0;
            let status = 'normal';
            let color = '#10b981';
            let displayValue = value;
            
            switch (type) {
                case 'cpu':
                    percentage = parseFloat(value);
                    displayValue = `${percentage.toFixed(1)}%`;
                    if (percentage > 80) {
                        status = 'critical';
                        color = '#ef4444';
                    } else if (percentage > 60) {
                        status = 'warning';
                        color = '#f59e0b';
                    }
                    break;
                    
                case 'memory':
                    if (total && total > 0) {
                        percentage = (value / total) * 100;
                        displayValue = `${this.formatBytes(value)} / ${this.formatBytes(total)}`;
                    } else {
                        displayValue = this.formatBytes(value);
                    }
                    if (percentage > 85) {
                        status = 'critical';
                        color = '#ef4444';
                    } else if (percentage > 70) {
                        status = 'warning';
                        color = '#f59e0b';
                    }
                    break;
                    
                case 'storage':
                    if (total && total > 0) {
                        percentage = (value / total) * 100;
                        displayValue = `${this.formatBytes(value)} / ${this.formatBytes(total)}`;
                    } else {
                        displayValue = this.formatBytes(value);
                    }
                    if (percentage > 90) {
                        status = 'critical';
                        color = '#ef4444';
                    } else if (percentage > 75) {
                        status = 'warning';
                        color = '#f59e0b';
                    }
                    break;
                    
                case 'temperature':
                    percentage = Math.min(value / 100, 1) * 100;
                    displayValue = `${value}°C`;
                    if (value > 80) {
                        status = 'critical';
                        color = '#ef4444';
                    } else if (value > 70) {
                        status = 'warning';
                        color = '#f59e0b';
                    }
                    break;
            }
            
            return {
                value: displayValue,
                percentage: Math.round(percentage),
                status: status,
                color: color
            };
        },
        
        // 格式化字节大小
        formatBytes: function(bytes) {
            if (bytes === 0) return '0 B';
            if (!bytes || bytes < 0) return '--';
            
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },
        
        // 生成设备图标
        generateDeviceIcon: function(deviceType, status) {
            const icons = {
                'server': '🖥️',
                'desktop': '💻',
                'laptop': '💻',
                'mobile': '📱',
                'tablet': '📱',
                'router': '📡',
                'switch': '🔌',
                'camera': '📷',
                'printer': '🖨️',
                'default': '💻'
            };
            
            return icons[deviceType] || icons.default;
        },
        
        // 获取设备类型显示名称
        getDeviceTypeName: function(type) {
            const typeNames = {
                'server': '服务器',
                'desktop': '台式机',
                'laptop': '笔记本',
                'mobile': '手机',
                'tablet': '平板',
                'router': '路由器',
                'switch': '交换机',
                'camera': '摄像头',
                'printer': '打印机',
                'default': '设备'
            };
            
            return typeNames[type] || typeNames.default;
        },
        
        // 验证设备数据
        validateDeviceData: function(device) {
            const required = ['id', 'name'];
            const missing = required.filter(field => !device[field]);
            
            if (missing.length > 0) {
                console.warn('设备数据缺少必需字段:', missing);
                return false;
            }
            
            return true;
        },
        
        // 搜索设备
        searchDevices: function(devices, query) {
            if (!query || query.trim() === '') {
                return devices;
            }
            
            const searchTerm = query.toLowerCase().trim();
            
            return devices.filter(device => {
                return (
                    (device.name && device.name.toLowerCase().includes(searchTerm)) ||
                    (device.ip && device.ip.includes(searchTerm)) ||
                    (device.remark && device.remark.toLowerCase().includes(searchTerm)) ||
                    (device.device_number && device.device_number.toLowerCase().includes(searchTerm))
                );
            });
        },
        
        // 排序设备
        sortDevices: function(devices, sortBy, sortOrder = 'asc') {
            return devices.sort((a, b) => {
                let aValue = a[sortBy];
                let bValue = b[sortBy];
                
                // 处理特殊排序字段
                if (sortBy === 'status') {
                    aValue = a.status === 'online' ? 1 : 0;
                    bValue = b.status === 'online' ? 1 : 0;
                } else if (sortBy === 'last_heartbeat' || sortBy === 'register_time') {
                    aValue = new Date(aValue || 0);
                    bValue = new Date(bValue || 0);
                }
                
                // 处理null/undefined值
                if (aValue == null && bValue == null) return 0;
                if (aValue == null) return sortOrder === 'asc' ? 1 : -1;
                if (bValue == null) return sortOrder === 'asc' ? -1 : 1;
                
                // 比较值
                if (aValue < bValue) {
                    return sortOrder === 'asc' ? -1 : 1;
                }
                if (aValue > bValue) {
                    return sortOrder === 'asc' ? 1 : -1;
                }
                return 0;
            });
        }
    };
    
    // 导出到全局作用域
    window.DeviceUtils = DeviceUtils;
    
})();