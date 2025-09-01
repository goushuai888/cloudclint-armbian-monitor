# Armbian设备在线监控平台

一个基于PHP+MySQL的轻量级Armbian小盒子在线检测平台，用于实时监控设备状态、系统资源使用情况和设备健康状况。

## 功能特性

- 🖥️ **实时监控**: 监控设备在线状态、CPU、内存、磁盘使用率
- 🌡️ **温度监控**: 实时显示设备温度信息
- 📊 **可视化界面**: 现代化的Bootstrap界面，响应式设计
- 🔄 **自动刷新**: 页面自动刷新，实时更新设备状态
- 📱 **移动友好**: 支持手机、平板等移动设备访问
- ⚙️ **管理后台**: 完整的设备管理和系统配置功能
- 🔌 **RESTful API**: 标准的API接口，易于集成
- 📝 **日志记录**: 完整的心跳日志记录和管理
- 🛠️ **客户端脚本**: 提供完整的Armbian客户端监控脚本

## 系统要求

### 服务端
- PHP 7.4+
- MySQL 5.7+ 或 MariaDB 10.3+
- Web服务器 (Apache/Nginx/PHP内置服务器)
- PDO MySQL扩展

### 客户端 (Armbian设备)
- Bash shell
- curl
- 基本的Linux命令工具 (awk, grep, cat等)

## 安装部署

### 1. 下载项目

```bash
git clone <repository-url>
cd CloudClint
```

### 2. 数据库配置

#### 创建数据库
```sql
-- 导入数据库结构
mysql -u root -p < sql/init.sql
```

#### 配置数据库连接
编辑 `config/database.php` 文件，修改数据库连接信息：

```php
private $host = 'localhost';        // 数据库主机
private $db_name = 'armbian_monitor'; // 数据库名
private $username = 'root';         // 数据库用户名
private $password = '';             // 数据库密码
```

### 3. Web服务器配置

#### 使用PHP内置服务器（开发环境）
```bash
cd /path/to/CloudClint
php -S localhost:8080
```

#### 使用Apache
```apache
<VirtualHost *:80>
    ServerName ztao.langne.com
    DocumentRoot /path/to/CloudClint
    
    <Directory /path/to/CloudClint>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### 使用Nginx
```nginx
server {
    listen 80;
    server_name ztao.langne.com;
    root /path/to/CloudClint;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. 权限设置

确保Web服务器对项目目录有适当的读写权限：

```bash
# 设置目录权限
chmod -R 755 /path/to/CloudClint
chown -R www-data:www-data /path/to/CloudClint
```

## 客户端配置

### 1. 下载客户端脚本

将 `client/armbian_monitor.sh` 脚本复制到Armbian设备：

```bash
# 在Armbian设备上执行
wget http://ztao.langne.com/client/armbian_monitor.sh
chmod +x armbian_monitor.sh
```

### 2. 配置脚本

编辑脚本，修改API地址：

```bash
vim armbian_monitor.sh

# 修改以下配置
API_URL="http://ztao.langne.com/api/heartbeat.php"  # 你的监控平台地址
DEVICE_NAME="$(hostname)"                          # 设备名称
```

### 设备ID识别

脚本会按以下优先级生成设备唯一ID：

1. **KSA ID（推荐）**：如果设备上存在 `/root/ksa_info.txt` 文件，将使用其中的 KSA ID
   ```
   KSA ID:8023297693
   KSA PSK:835601
   KSA SERVER:nat.kanxue.com
   KSA LINK:UDP
   ```
   生成的设备ID格式：`ksa-8023297693`

2. **Machine ID**：使用系统的 machine-id
   生成的设备ID格式：`armbian-12345678`

3. **MAC地址**：使用网卡MAC地址作为备选
   生成的设备ID格式：`armbian-abcdef12`

### 3. 测试脚本

```bash
# 测试模式，查看收集的数据
./armbian_monitor.sh --test

# 手动运行一次
./armbian_monitor.sh
```

### 4. 设置定时任务

```bash
# 自动安装定时任务（每2分钟运行一次）
./armbian_monitor.sh --install

# 或手动添加到crontab
crontab -e
# 添加以下行（每2分钟运行一次）
*/2 * * * * /path/to/armbian_monitor.sh >/dev/null 2>&1
```

## 使用说明

### 主界面

访问 `http://ztao.langne.com` 查看设备监控仪表板：

- **统计概览**: 显示总设备数、在线设备、离线设备等统计信息
- **设备列表**: 实时显示所有设备的状态和资源使用情况
- **自动刷新**: 页面每30秒自动刷新一次

### 管理后台

访问 `http://ztao.langne.com/admin.php` 进入管理后台：

- **仪表板**: 系统概览和维护功能
- **设备管理**: 查看和删除设备
- **系统设置**: 配置心跳超时、日志保留等参数
- **API文档**: 查看API接口文档和客户端脚本示例

### API接口

#### 心跳接口

**接口地址**: `POST /api/heartbeat.php`

**请求参数**:
```json
{
  "device_id": "设备唯一标识（必需）",
  "device_name": "设备名称（可选）",
  "mac_address": "MAC地址（可选）",
  "system_info": {
    "os": "操作系统信息",
    "kernel": "内核版本",
    "arch": "架构"
  },
  "cpu_usage": 15.5,
  "memory_usage": 45.2,
  "disk_usage": 60.8,
  "temperature": 42.5,
  "uptime": 86400
}
```

**响应示例**:
```json
{
  "success": true,
  "message": "心跳接收成功",
  "timestamp": "2024-01-01 12:00:00",
  "device_id": "armbian-001"
}
```

## 配置选项

### 系统配置

在管理后台的"系统设置"中可以配置以下参数：

- **网站标题**: 自定义网站标题
- **管理员邮箱**: 管理员联系邮箱
- **心跳超时时间**: 设备离线判断时间（默认30秒）
- **日志保留天数**: 心跳日志保留时间（默认30天）

### 客户端配置

客户端脚本支持以下配置选项：

```bash
# API地址
API_URL="http://ztao.langne.com/api/heartbeat.php"

# 设备名称
DEVICE_NAME="$(hostname)"

# 日志文件
LOG_FILE="/var/log/armbian_monitor.log"
```

## 故障排除

### 常见问题

1. **数据库连接失败**
   - 检查数据库配置信息
   - 确认数据库服务正在运行
   - 验证用户权限

2. **设备无法上报数据**
   - 检查API地址是否正确
   - 确认网络连接正常
   - 查看客户端日志文件

3. **权限错误**
   - 检查文件和目录权限
   - 确认Web服务器用户权限

### 日志查看

**服务端日志**:
- Web服务器错误日志
- PHP错误日志

**客户端日志**:
```bash
# 查看客户端日志
tail -f /var/log/armbian_monitor.log
```

### 调试模式

**客户端调试**:
```bash
# 详细模式运行
./armbian_monitor.sh --verbose

# 测试模式
./armbian_monitor.sh --test
```

## 安全建议

1. **数据库安全**
   - 使用强密码
   - 限制数据库访问权限
   - 定期备份数据

2. **Web安全**
   - 使用HTTPS（生产环境）
   - 设置适当的文件权限
   - 定期更新系统和软件

3. **API安全**
   - 考虑添加API密钥验证
   - 限制请求频率
   - 监控异常访问

## 性能优化

1. **数据库优化**
   - 定期清理旧日志
   - 添加适当的索引
   - 优化查询语句

2. **缓存优化**
   - 使用Redis或Memcached
   - 启用浏览器缓存
   - 压缩静态资源

## 扩展功能

### 计划中的功能

- [ ] 邮件/短信告警
- [ ] 数据图表展示
- [ ] 多用户权限管理
- [ ] 设备分组管理
- [ ] 历史数据分析
- [ ] 移动端APP

### 自定义开发

项目采用模块化设计，可以轻松扩展：

- 添加新的监控指标
- 自定义告警规则
- 集成第三方服务
- 开发插件系统

## SQL文件说明

项目包含以下SQL文件，用于手动部署和数据库维护：

- `sql/init.sql` - 初始化数据库结构
- `sql/device_backup.sql` - 创建设备备份表
- `sql/request_log.sql` - 创建请求日志表
- `sql/add_order_number.sql` - 添加设备排序编号字段
- `sql/update_remarks.sql` - 更新设备备注
- `sql/user_auth.sql` - 用户认证相关表

## 技术支持

- **项目地址**: [GitHub Repository]
- **问题反馈**: [Issues]
- **文档更新**: [Wiki]

## 许可证

本项目采用 MIT 许可证，详见 LICENSE 文件。

## 更新日志

### v1.0.0 (2024-01-01)
- 初始版本发布
- 基础监控功能
- Web管理界面
- RESTful API
- 客户端脚本

---

**开发团队**: CloudClint Team  
**联系邮箱**: admin@ztao.langne.com  
**官方网站**: http://ztao.langne.com