# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目概览

这是一个正在从传统架构迁移到现代化前后端分离架构的 Armbian 设备在线监控平台，用于实时监控设备状态、系统资源使用情况和设备健康状况。

### 当前架构状态
项目采用 **渐进式迁移策略**，新旧系统并行开发：

#### 新架构 (主要开发方向)
- **后端**: Node.js + Fastify + TypeScript + MySQL
- **前端**: Vue 3 + Quasar UI + TypeScript + Pinia
- **目录**: `backend/` 和 `frontend/`

#### 旧系统 (保持兼容)
- **后端**: PHP 7.4+, MySQL 5.7+/MariaDB 10.3+
- **前端**: Bootstrap 5, JavaScript ES6+, CSS3
- **目录**: `legacy/`

### 共同组件
- **数据库**: MySQL/MariaDB 5.7+
- **客户端**: Bash shell 脚本 (兼容两套系统)

## 开发环境配置

### 新架构开发环境

#### 后端开发 (backend/)
```bash
# 安装依赖
cd backend/
npm install

# 配置环境变量
cp .env.example .env
# 编辑 .env 文件设置数据库连接

# 开发模式启动
npm run dev          # 开发服务器 (tsx watch)
npm run build        # TypeScript编译
npm run type-check   # TypeScript类型检查
npm run lint         # ESLint代码检查
npm run format       # Prettier格式化

# 数据库操作
npm run db:migrate   # 数据库迁移
npm run db:seed      # 种子数据
```

#### 前端开发 (frontend/)
```bash
# 安装依赖 (必须使用pnpm)
cd frontend/
pnpm install

# 开发模式启动
pnpm dev             # Quasar开发服务器
pnpm build           # 生产构建
pnpm lint            # ESLint代码检查
pnpm format          # Prettier格式化

# TypeScript类型检查 (修改代码后必须执行)
npx vue-tsc --noEmit
```

### 旧系统开发环境 (legacy/)

#### 数据库配置
```bash
# 1. 创建数据库
mysql -u root -p < sql/init.sql

# 2. 复制配置文件
cp config/db_config.example.php config/db_config.php

# 3. 编辑数据库连接信息
# 修改 config/db_config.php 中的连接参数
```

#### 开发服务器启动
```bash
# PHP 内置服务器（推荐用于开发）
php -S localhost:8080

# 或使用部署优化脚本
./deploy_optimization.sh deploy
```

## 项目结构

### 新架构目录结构

#### 后端 (backend/)
- `src/` - TypeScript 源码
  - `app.ts` - Fastify 应用入口
  - `routes/` - API 路由定义
  - `controllers/` - 控制器层
  - `services/` - 业务逻辑层
  - `models/` - 数据模型
  - `middleware/` - 中间件
  - `config/` - 配置文件
  - `utils/` - 工具函数
  - `types/` - TypeScript 类型定义
  - `scripts/` - 数据库迁移和种子脚本

#### 前端 (frontend/)
- `src/` - Vue 3 源码
  - `components/` - Vue 组件
  - `pages/` - 页面组件
  - `layouts/` - 布局组件
  - `stores/` - Pinia 状态管理
  - `services/` - API 服务
  - `router/` - 路由配置
  - `types/` - TypeScript 类型定义
  - `utils/` - 工具函数
  - `boot/` - Quasar 启动配置

### 旧系统目录结构 (legacy/)
- `api/` - RESTful API 接口
  - `heartbeat.php` - 设备心跳数据接收
  - `auth_check.php` - 用户认证检查
  - `update_device.php` - 设备信息更新
- `classes/` - PHP 类文件（PSR-4 规范）
  - `Auth.php` - 用户认证
  - `Device.php` - 设备管理
  - `ConnectionPool.php` - 数据库连接池
- `config/` - 配置文件目录
- `views/` - PHP 模板文件
- `assets/` - 静态资源

### 共享资源
- `sql/` - 数据库脚本和迁移文件
- `client/` - Armbian 设备监控脚本
- 文档文件：`DEVELOPMENT_PLAN.md`, `ARCHITECTURE_ANALYSIS.md`, `CODE_STANDARDS.md`

## 开发规范

### 新架构开发规范

#### 代码风格
- **TypeScript**: 严格模式，所有代码必须有类型定义
- **前端**: Vue 3 Composition API + `<script setup>` 语法
- **后端**: Fastify + 依赖注入 + 分层架构
- **包管理**: 前端强制使用 `pnpm`，后端使用 `npm`

#### 提交前检查
```bash
# 后端检查流程
cd backend/
npm run lint && npm run type-check && npm run build

# 前端检查流程
cd frontend/
pnpm lint && npx vue-tsc --noEmit && pnpm build
```

#### API 设计原则
- RESTful 设计，统一的响应格式
- 使用 Zod 或 Joi 进行数据验证
- JWT 认证 + RBAC 权限控制
- WebSocket 支持实时数据推送

### 迁移策略
- **双轨制开发**: 新功能优先在新架构实现
- **API 兼容**: 保持与旧系统客户端的兼容性
- **数据同步**: 新旧系统共享数据库
- **渐进切换**: 按模块逐步迁移用户界面

## 客户端配置

### 设备 ID 识别规则
Armbian 客户端脚本会按以下优先级生成唯一设备 ID：
1. **KSA ID**（推荐）: 从 `/root/ksa_info.txt` 读取，格式：`ksa-8023297693`
2. **Machine ID**: 使用系统 machine-id，格式：`armbian-12345678`
3. **MAC 地址**: 使用网卡 MAC 地址，格式：`armbian-abcdef12`

### 客户端部署
```bash
# 下载脚本到 Armbian 设备
wget http://your-domain.com/client/armbian_monitor.sh
chmod +x armbian_monitor.sh

# 配置 API 地址
vim armbian_monitor.sh
# 修改: API_URL="http://your-domain.com/api/heartbeat.php"

# 测试运行
./armbian_monitor.sh --test

# 安装定时任务（每2分钟）
./armbian_monitor.sh --install
```

## API 接口

### 心跳接口
**POST** `/api/heartbeat.php`

请求体示例：
```json
{
  "device_id": "ksa-8023297693",
  "device_name": "armbian-box-01",
  "mac_address": "aa:bb:cc:dd:ee:ff",
  "system_info": {
    "os": "Armbian 23.02",
    "kernel": "5.15.74",
    "arch": "aarch64"
  },
  "cpu_usage": 15.5,
  "memory_usage": 45.2,
  "disk_usage": 60.8,
  "temperature": 42.5,
  "uptime": 86400
}
```

## 数据库设计

### 核心表结构
- `devices` - 设备基本信息
- `device_logs` - 设备心跳日志
- `users` - 用户认证信息
- `device_groups` - 设备分组（可选）

## 安全特性

### 数据库安全
- PDO 预处理语句防止 SQL 注入
- 环境变量或配置文件存储敏感信息
- 持久连接和连接池优化

### 文件保护
- `.gitignore` 排除敏感配置文件
- `config/db_config.php` 不提交到版本控制

### API 安全
- 输入验证和过滤
- 错误信息脱敏
- 请求日志记录

## 性能优化

### 前端优化
- CSS/JS 文件整合（减少 HTTP 请求 56%）
- 浏览器缓存配置
- 资源压缩和 Gzip

### 数据库优化
- 连接池管理
- 查询优化和索引
- 定期清理历史日志

### 服务器配置
- PHP OPcache 启用
- 适当的文件权限设置
- HTTPS 配置（生产环境）

## 故障排除

### 常见问题
1. **数据库连接失败**：检查 `config/db_config.php` 配置
2. **设备无法上报**：验证 API 地址和网络连接
3. **权限错误**：确认文件权限设置为 755/644

### 调试工具
```bash
# 客户端详细调试
./armbian_monitor.sh --verbose

# 查看客户端日志
tail -f /var/log/armbian_monitor.log

# 检查部署状态
./deploy_optimization.sh validate
```

## 扩展开发

### 添加新监控指标
1. 修改客户端脚本收集新数据
2. 更新 API 接口接收字段
3. 调整数据库表结构
4. 更新前端显示组件

### 自定义告警
- 可集成邮件/短信服务
- 实现阈值监控逻辑
- 添加告警历史记录

## 部署要求

### 系统要求
- **服务端**: PHP 7.4+, MySQL 5.7+, Web 服务器
- **客户端**: Bash, curl, 基本 Linux 命令

### 生产环境建议
- 启用 HTTPS
- 配置定期备份
- 设置监控告警
- 优化数据库性能
- 配置日志轮转