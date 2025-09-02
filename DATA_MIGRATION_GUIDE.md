# 数据迁移执行指南

## 概述

本指南将帮助您将旧系统（ztao）的数据安全地迁移到新的Armbian监控系统。

## 迁移前准备

### 1. 数据备份

**重要：在执行任何迁移操作前，请务必备份所有数据！**

```bash
# 备份旧系统数据库
mysqldump -u root -p ztao > ztao_backup_$(date +%Y%m%d_%H%M%S).sql

# 备份新系统数据库（如果已有数据）
mysqldump -u root -p armbian_monitor > armbian_monitor_backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. 环境检查

- 确保MySQL服务正在运行
- 确保有足够的磁盘空间
- 确保数据库用户有足够的权限

## 迁移步骤

### 步骤1：准备新系统数据库

如果新系统数据库不存在，请先创建：

```sql
CREATE DATABASE IF NOT EXISTS armbian_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 步骤2：执行数据迁移

使用我们提供的迁移脚本：

```bash
# 执行完整的数据迁移脚本
mysql -u root -p < migrate_from_legacy.sql
```

**注意：** 这个脚本会：
- 创建新系统的所有表结构
- 将旧系统数据迁移到新表中
- 自动处理设备ID格式转换（ksa- → armbian-）
- 设置默认密码为 `123456`（需要用户首次登录后修改）

### 步骤3：验证迁移结果

执行以下查询验证数据迁移是否成功：

```sql
USE armbian_monitor;

-- 检查用户数据
SELECT COUNT(*) as user_count FROM users;
SELECT username, email, created_at FROM users LIMIT 5;

-- 检查设备数据
SELECT COUNT(*) as device_count FROM devices;
SELECT device_id, device_name, status FROM devices LIMIT 10;

-- 检查设备ID格式转换
SELECT 
    COUNT(CASE WHEN device_id LIKE 'ksa-%' THEN 1 END) as ksa_count,
    COUNT(CASE WHEN device_id LIKE 'armbian-%' THEN 1 END) as armbian_count
FROM devices;

-- 检查心跳日志
SELECT COUNT(*) as heartbeat_count FROM heartbeat_logs;

-- 检查系统配置
SELECT config_key, config_value FROM system_config;
```

### 步骤4：更新应用配置

确保新系统的数据库配置正确：

1. 检查 `backend/src/config/database.ts` 中的数据库连接配置
2. 确保数据库名称为 `armbian_monitor`
3. 重启后端服务

```bash
cd backend
npm run dev
```

## 设备ID兼容性说明

### 旧系统设备ID格式
- 格式：`ksa-xxxxxxxxxx`（10位数字）
- 示例：`ksa-0327020032`

### 新系统设备ID格式
- 格式：`armbian-xxxxxxxxxxxx`（12位字符）
- 生成规则：
  1. 优先使用CPU序列号
  2. 其次使用machine-id
  3. 最后使用随机UUID

### 迁移处理
- 迁移脚本会自动将 `ksa-` 前缀替换为 `armbian-`
- 保持原有的数字部分不变
- 例：`ksa-0327020032` → `armbian-0327020032`

## 客户端脚本更新

### 新设备
新部署的设备使用更新后的 `armbian_monitor.sh` 脚本，会自动生成 `armbian-` 前缀的设备ID。

### 已有设备
对于已经部署的设备，有两种处理方式：

#### 方式1：保持现有ID（推荐）
- 不需要更新客户端脚本
- 设备会继续使用原有的设备ID
- 数据库中的记录会被迁移脚本更新

#### 方式2：重新生成ID
- 更新客户端脚本到最新版本
- 删除设备上的ID文件：`rm -f /etc/armbian-monitor-id /var/tmp/armbian-monitor-id /tmp/armbian-monitor-id`
- 重新运行脚本，会生成新的设备ID
- **注意：** 这会在数据库中创建新的设备记录

## 故障排除

### 常见问题

1. **权限错误**
   ```
   ERROR 1045 (28000): Access denied for user
   ```
   解决：检查数据库用户权限，确保有CREATE、INSERT、UPDATE权限

2. **表已存在错误**
   ```
   ERROR 1050 (42S01): Table 'xxx' already exists
   ```
   解决：检查是否已经执行过迁移，或者先清空目标数据库

3. **字符集问题**
   ```
   ERROR 1366 (HY000): Incorrect string value
   ```
   解决：确保数据库和表使用utf8mb4字符集

### 回滚操作

如果迁移出现问题，可以回滚到原始状态：

```bash
# 删除新数据库
mysql -u root -p -e "DROP DATABASE IF EXISTS armbian_monitor;"

# 恢复旧数据库（如果被修改）
mysql -u root -p ztao < ztao_backup_YYYYMMDD_HHMMSS.sql
```

## 迁移后检查清单

- [ ] 用户可以正常登录
- [ ] 设备列表显示正确
- [ ] 设备状态更新正常
- [ ] 心跳数据记录正常
- [ ] 系统配置生效
- [ ] 客户端脚本正常工作
- [ ] 数据库性能正常

## 联系支持

如果在迁移过程中遇到问题，请：
1. 保存错误日志
2. 记录执行的具体步骤
3. 提供数据库版本和系统环境信息

---

**重要提醒：**
- 在生产环境执行迁移前，请先在测试环境验证
- 建议在业务低峰期执行迁移
- 迁移完成后，请及时通知用户修改默认密码