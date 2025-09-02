# 数据库表结构文档

本文档详细描述了Armbian设备监控平台的数据库表结构，基于现有代码分析整理。

## 数据库配置

- **数据库类型**: MySQL 5.7+
- **字符集**: utf8mb4
- **排序规则**: utf8mb4_unicode_ci
- **时区**: UTC (+00:00)

## 核心业务表

### 1. users - 用户表

用户认证和权限管理的核心表。

```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    password_hash VARCHAR(255) NOT NULL COMMENT '密码哈希',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱地址',
    role ENUM('admin', 'user') DEFAULT 'user' COMMENT '用户角色',
    is_locked TINYINT(1) DEFAULT 0 COMMENT '是否锁定 0=正常 1=锁定',
    last_login DATETIME DEFAULT NULL COMMENT '最后登录时间',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';
```

**字段说明:**
- `id`: 主键，自增ID
- `username`: 用户名，唯一索引
- `password_hash`: 使用bcrypt加密的密码哈希
- `email`: 邮箱地址，可为空
- `role`: 用户角色，admin(管理员) 或 user(普通用户)
- `is_locked`: 账户锁定状态
- `last_login`: 最后登录时间

### 2. devices - 设备表

存储Armbian设备的基本信息和状态。

```sql
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(64) NOT NULL UNIQUE COMMENT '设备唯一标识',
    device_name VARCHAR(100) NOT NULL COMMENT '设备名称',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP地址(支持IPv6)',
    mac_address VARCHAR(17) DEFAULT NULL COMMENT 'MAC地址',
    system_info JSON DEFAULT NULL COMMENT '系统信息(JSON格式)',
    cpu_usage DECIMAL(5,2) DEFAULT 0.00 COMMENT 'CPU使用率(%)',
    memory_usage DECIMAL(5,2) DEFAULT 0.00 COMMENT '内存使用率(%)',
    disk_usage DECIMAL(5,2) DEFAULT 0.00 COMMENT '磁盘使用率(%)',
    temperature DECIMAL(5,2) DEFAULT NULL COMMENT '温度(°C)',
    uptime BIGINT DEFAULT 0 COMMENT '运行时间(秒)',
    remarks TEXT DEFAULT NULL COMMENT '备注信息',
    last_heartbeat DATETIME DEFAULT NULL COMMENT '最后心跳时间',
    order_number INT DEFAULT 0 COMMENT '排序号',
    group_id INT DEFAULT NULL COMMENT '设备分组ID',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_device_id (device_id),
    INDEX idx_device_name (device_name),
    INDEX idx_ip_address (ip_address),
    INDEX idx_mac_address (mac_address),
    INDEX idx_last_heartbeat (last_heartbeat),
    INDEX idx_group_id (group_id),
    INDEX idx_order_number (order_number),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备表';
```

**字段说明:**
- `device_id`: 设备唯一标识符，通常为UUID或MAC地址
- `system_info`: JSON格式存储系统信息，包含os、kernel、arch、distro等
- `cpu_usage/memory_usage/disk_usage`: 资源使用率，范围0-100
- `temperature`: 设备温度，可为空
- `uptime`: 设备运行时间（秒）
- `order_number`: 用于前端显示排序
- `group_id`: 关联设备分组表

### 3. heartbeat_logs - 心跳日志表

记录设备的心跳数据，用于监控和历史分析。

```sql
CREATE TABLE IF NOT EXISTS heartbeat_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(64) NOT NULL COMMENT '设备ID',
    heartbeat_time DATETIME NOT NULL COMMENT '心跳时间',
    cpu_usage DECIMAL(5,2) DEFAULT 0.00 COMMENT 'CPU使用率(%)',
    memory_usage DECIMAL(5,2) DEFAULT 0.00 COMMENT '内存使用率(%)',
    disk_usage DECIMAL(5,2) DEFAULT 0.00 COMMENT '磁盘使用率(%)',
    temperature DECIMAL(5,2) DEFAULT NULL COMMENT '温度(°C)',
    uptime BIGINT DEFAULT 0 COMMENT '运行时间(秒)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '记录创建时间',
    
    INDEX idx_device_id (device_id),
    INDEX idx_heartbeat_time (heartbeat_time),
    INDEX idx_device_heartbeat (device_id, heartbeat_time),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='心跳日志表';
```

**数据保留策略:**
- 建议保留30-90天的心跳数据
- 可通过定时任务清理过期数据
- 对于重要设备可保留更长时间

### 4. device_groups - 设备分组表

设备分组管理，支持设备的逻辑分类。

```sql
CREATE TABLE IF NOT EXISTS device_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL COMMENT '分组名称',
    group_description TEXT DEFAULT NULL COMMENT '分组描述',
    group_color VARCHAR(7) DEFAULT '#1976d2' COMMENT '分组颜色(HEX)',
    group_icon VARCHAR(50) DEFAULT 'devices' COMMENT '分组图标',
    sort_order INT DEFAULT 0 COMMENT '排序顺序',
    is_default TINYINT(1) DEFAULT 0 COMMENT '是否默认分组',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_group_name (group_name),
    INDEX idx_sort_order (sort_order),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备分组表';
```

## 系统管理表

### 5. system_config - 系统配置表

存储系统级别的配置参数。

```sql
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键名',
    config_value TEXT DEFAULT NULL COMMENT '配置值',
    config_description TEXT DEFAULT NULL COMMENT '配置描述',
    config_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string' COMMENT '配置类型',
    is_public TINYINT(1) DEFAULT 0 COMMENT '是否公开配置',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_config_key (config_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';
```

**常用配置项:**
- `site_title`: 站点标题
- `heartbeat_timeout`: 心跳超时时间(秒)
- `data_retention_days`: 数据保留天数
- `email_notifications`: 邮件通知开关
- `alert_thresholds`: 告警阈值配置

### 6. login_logs - 登录日志表

记录用户登录行为，用于安全审计。

```sql
CREATE TABLE IF NOT EXISTS login_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL COMMENT '用户名',
    ip_address VARCHAR(45) NOT NULL COMMENT '登录IP地址',
    user_agent TEXT DEFAULT NULL COMMENT '用户代理字符串',
    login_status ENUM('success', 'failed') NOT NULL COMMENT '登录状态',
    failure_reason VARCHAR(255) DEFAULT NULL COMMENT '失败原因',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
    
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_login_status (login_status),
    INDEX idx_created_at (created_at),
    INDEX idx_username_time (username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录日志表';
```

### 7. request_log - 请求日志表

记录API请求日志，用于监控和调试。

```sql
CREATE TABLE IF NOT EXISTS request_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    method VARCHAR(10) NOT NULL COMMENT 'HTTP方法',
    url VARCHAR(500) NOT NULL COMMENT '请求URL',
    ip_address VARCHAR(45) NOT NULL COMMENT '客户端IP',
    user_agent TEXT DEFAULT NULL COMMENT '用户代理',
    response_status INT DEFAULT NULL COMMENT '响应状态码',
    response_time INT DEFAULT NULL COMMENT '响应时间(毫秒)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '请求时间',
    
    INDEX idx_method (method),
    INDEX idx_ip_address (ip_address),
    INDEX idx_response_status (response_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='请求日志表';
```

## 外键约束

```sql
-- 设备表关联设备分组
ALTER TABLE devices 
ADD CONSTRAINT fk_devices_group_id 
FOREIGN KEY (group_id) REFERENCES device_groups(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- 心跳日志关联设备
ALTER TABLE heartbeat_logs 
ADD CONSTRAINT fk_heartbeat_device_id 
FOREIGN KEY (device_id) REFERENCES devices(device_id) 
ON DELETE CASCADE ON UPDATE CASCADE;
```

## 初始数据

### 默认管理员用户

```sql
INSERT INTO users (username, password_hash, email, role) VALUES 
('admin', '$2b$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin');
-- 默认密码: password (请在生产环境中修改)
```

### 默认设备分组

```sql
INSERT INTO device_groups (group_name, group_description, group_color, group_icon, is_default) VALUES 
('默认分组', '系统默认设备分组', '#1976d2', 'devices', 1),
('服务器', '服务器设备', '#4caf50', 'dns', 0),
('开发板', '开发板设备', '#ff9800', 'memory', 0);
```

### 系统配置

```sql
INSERT INTO system_config (config_key, config_value, config_description, config_type) VALUES 
('site_title', 'Armbian设备监控平台', '站点标题', 'string'),
('heartbeat_timeout', '300', '心跳超时时间(秒)', 'number'),
('data_retention_days', '30', '数据保留天数', 'number'),
('email_notifications', 'false', '邮件通知开关', 'boolean');
```

## 索引优化建议

1. **复合索引**:
   - `devices(last_heartbeat, group_id)`: 用于按分组查询在线设备
   - `heartbeat_logs(device_id, heartbeat_time)`: 用于查询设备历史数据
   - `login_logs(username, created_at)`: 用于查询用户登录历史

2. **分区表**:
   - 对于`heartbeat_logs`表，可考虑按时间分区以提高查询性能
   - 对于`request_log`表，也可按月分区

3. **数据清理**:
   - 定期清理过期的心跳日志
   - 定期清理过期的请求日志
   - 定期清理过期的登录日志

## 数据兼容性说明

### 新旧系统字段映射

| 旧系统字段 | 新系统字段 | 说明 |
|-----------|-----------|------|
| `password` | `password_hash` | 密码存储方式改为bcrypt |
| `last_seen` | `last_heartbeat` | 字段名更规范 |
| `cpu_percent` | `cpu_usage` | 统一使用usage命名 |
| `memory_percent` | `memory_usage` | 统一使用usage命名 |
| `disk_percent` | `disk_usage` | 统一使用usage命名 |

### 数据迁移注意事项

1. **密码迁移**: 旧系统的密码需要重新加密
2. **JSON字段**: 旧系统的序列化数据需要转换为JSON格式
3. **时间字段**: 确保时区一致性
4. **外键约束**: 迁移时需要先迁移主表，再迁移关联表

## 性能监控

建议监控以下指标：
- 表大小增长趋势
- 慢查询日志
- 索引使用情况
- 连接池状态
- 磁盘空间使用

---

**文档版本**: 1.0  
**最后更新**: 2024-01-22  
**维护者**: 开发团队