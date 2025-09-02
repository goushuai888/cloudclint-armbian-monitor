# Armbian设备监控平台现代化重构开发文档

## 项目概述

本文档基于现有Armbian设备监控平台的PHP版本，为全面重构为现代化Vue+Quasar前端应用提供详细的开发指南。重构目标是保持现有数据库结构不变，提供更好的用户体验和现代化的技术架构。

### 现有系统分析

**当前技术栈:**
- 后端：PHP 7.4+
- 数据库：MySQL 8.4.5 
- 前端：Bootstrap + jQuery + 传统PHP模板
- 架构：传统单体PHP应用

**核心功能:**
- 🖥️ Armbian设备实时监控（在线状态、CPU、内存、磁盘、温度）
- 📊 设备统计概览和可视化仪表板
- 🔄 自动心跳检测和状态更新
- 📱 响应式Web界面
- ⚙️ 设备管理（编号、备注、删除）
- 🔌 RESTful API接口
- 👥 用户认证和权限管理
- 📝 心跳日志记录和分析
- 🏷️ 设备分组管理

## 目标架构设计

### 前端技术栈 (推荐)

**核心框架:**
```json
{
  "framework": "Vue 3 + Composition API",
  "ui": "Quasar Framework v2+",
  "language": "TypeScript",
  "bundler": "Vite",
  "packageManager": "pnpm"
}
```

**状态管理:**
- **Pinia** - Vue官方推荐状态管理
- 设备状态实时更新
- 用户认证状态管理
- 界面主题和偏好设置

**路由和导航:**
- **Vue Router 4** - 现代路由管理
- 路由守卫和权限控制
- 懒加载和代码分割

**数据可视化:**
- **Chart.js** 或 **ECharts** - 设备资源监控图表
- **Quasar内置组件** - 进度条、统计卡片

**工具链:**
```json
{
  "linting": "ESLint Flat Config",
  "formatting": "Prettier",
  "typeCheck": "vue-tsc",
  "testing": "Vitest + Vue Test Utils"
}
```

### 后端技术栈 (推荐)

**方案A: Node.js (推荐)**
```json
{
  "runtime": "Node.js 18+",
  "framework": "Fastify",
  "orm": "Prisma",
  "validation": "Zod",
  "auth": "JWT + Redis",
  "api": "OpenAPI 3.0"
}
```

**优势:**
- TypeScript全栈一致性
- 高性能异步I/O
- 丰富的npm生态
- 与前端技术栈完美结合

**方案B: Go (高性能选择)**
```json
{
  "language": "Go 1.21+",
  "framework": "Gin",
  "orm": "GORM",
  "database": "MySQL Driver",
  "auth": "JWT-Go",
  "docs": "Swagger"
}
```

**优势:**
- 极高的并发性能
- 简洁的部署方式
- 优秀的系统监控能力

**方案C: Python FastAPI (快速开发)**
```json
{
  "language": "Python 3.11+",
  "framework": "FastAPI",
  "orm": "SQLAlchemy 2.0",
  "validation": "Pydantic",
  "auth": "python-jose",
  "docs": "自动生成OpenAPI"
}
```

## 数据库兼容性方案

### 现有数据库结构保持不变

基于SQL分析，现有数据库包含以下核心表：

```sql
-- 核心业务表
devices              -- 设备信息主表
device_groups        -- 设备分组
device_backup        -- 已删除设备备份
heartbeat_logs       -- 心跳日志
users               -- 用户管理
login_logs          -- 登录日志
request_log         -- 请求日志
system_config       -- 系统配置
```

### API适配层设计

**设备管理API:**
```typescript
// GET /api/devices - 获取设备列表
interface DeviceListResponse {
  devices: Device[]
  stats: {
    total: number
    online: number
    offline: number
    onlineRate: number
  }
  pagination: PaginationMeta
}

// GET /api/devices/:id - 获取设备详情
interface DeviceDetailResponse {
  device: Device
  recentHeartbeats: HeartbeatLog[]
  resourceHistory: ResourceMetric[]
}

// PUT /api/devices/:id - 更新设备信息
interface UpdateDeviceRequest {
  device_name?: string
  remarks?: string
  order_number?: number
  group_id?: number
}
```

**实时监控API:**
```typescript
// WebSocket连接 - 实时设备状态
interface DeviceStatusUpdate {
  device_id: string
  status: 'online' | 'offline' | 'warning'
  cpu_usage: number
  memory_usage: number
  disk_usage: number
  temperature: number
  last_heartbeat: string
}

// Server-Sent Events - 替代方案
// GET /api/devices/stream
```

## 项目结构设计

### 前端项目结构
```
frontend/
├── src/
│   ├── components/           # 通用组件
│   │   ├── DeviceCard.vue   # 设备卡片
│   │   ├── StatsOverview.vue # 统计概览
│   │   ├── ResourceChart.vue # 资源图表
│   │   └── DeviceFilters.vue # 设备筛选
│   ├── layouts/             # 布局组件
│   │   ├── MainLayout.vue   # 主布局
│   │   └── AuthLayout.vue   # 认证布局
│   ├── pages/               # 页面组件
│   │   ├── Dashboard.vue    # 设备监控仪表板
│   │   ├── DeviceManagement.vue # 设备管理
│   │   ├── DeviceDetail.vue # 设备详情
│   │   ├── Settings.vue     # 系统设置
│   │   └── Login.vue        # 登录页面
│   ├── stores/              # Pinia状态管理
│   │   ├── device.ts        # 设备状态
│   │   ├── auth.ts          # 认证状态
│   │   └── system.ts        # 系统设置
│   ├── services/            # API服务
│   │   ├── api.ts           # API基础配置
│   │   ├── device.ts        # 设备API
│   │   ├── auth.ts          # 认证API
│   │   └── websocket.ts     # WebSocket连接
│   ├── types/               # TypeScript类型定义
│   │   ├── device.ts        # 设备类型
│   │   ├── api.ts           # API类型
│   │   └── common.ts        # 通用类型
│   ├── utils/               # 工具函数
│   │   ├── format.ts        # 格式化工具
│   │   ├── validation.ts    # 验证工具
│   │   └── constants.ts     # 常量定义
│   └── router/              # 路由配置
│       ├── index.ts         # 路由主配置
│       └── guards.ts        # 路由守卫
├── public/                  # 静态资源
├── quasar.config.js        # Quasar配置
├── package.json            # 依赖管理
└── tsconfig.json           # TypeScript配置
```

### 后端项目结构 (Node.js示例)
```
backend/
├── src/
│   ├── controllers/         # 控制器层
│   │   ├── device.ts       # 设备控制器
│   │   ├── auth.ts         # 认证控制器
│   │   └── heartbeat.ts    # 心跳控制器
│   ├── services/           # 业务逻辑层
│   │   ├── deviceService.ts
│   │   ├── authService.ts
│   │   └── monitorService.ts
│   ├── models/             # 数据模型
│   │   ├── Device.ts       # 设备模型
│   │   ├── User.ts         # 用户模型
│   │   └── HeartbeatLog.ts # 心跳日志模型
│   ├── middleware/         # 中间件
│   │   ├── auth.ts         # 认证中间件
│   │   ├── validation.ts   # 数据验证
│   │   └── cors.ts         # 跨域处理
│   ├── routes/             # 路由定义
│   │   ├── api.ts          # API路由主入口
│   │   ├── device.ts       # 设备路由
│   │   └── auth.ts         # 认证路由
│   ├── utils/              # 工具函数
│   │   ├── database.ts     # 数据库连接
│   │   ├── jwt.ts          # JWT工具
│   │   └── logger.ts       # 日志工具
│   ├── types/              # 类型定义
│   └── app.ts              # 应用主入口
├── prisma/                 # Prisma ORM
│   ├── schema.prisma       # 数据库Schema
│   └── migrations/         # 数据库迁移
├── package.json
└── tsconfig.json
```

## 开发流程详解

### 1. 环境准备

**安装Node.js和pnpm:**
```bash
# 安装Node.js 18+
curl -fsSL https://fnm.vercel.app/install | bash
fnm install 18
fnm use 18

# 安装pnpm
npm install -g pnpm
```

**创建Quasar项目:**
```bash
# 创建新项目
pnpm create quasar-project frontend --branch next
cd frontend

# 选择配置
# ✅ TypeScript
# ✅ ESLint
# ✅ Prettier
# ✅ Vue DevTools
# ✅ Pinia
# ✅ Vue Router
```

**安装核心依赖:**
```bash
# 核心依赖
pnpm add @quasar/extras
pnpm add axios
pnpm add chart.js vue-chartjs
pnpm add dayjs
pnpm add lodash-es

# 开发依赖
pnpm add -D @types/lodash-es
pnpm add -D vite-plugin-checker
```

### 2. 核心功能实现

**设备状态管理 (Pinia Store):**
```typescript
// stores/device.ts
import { defineStore } from 'pinia'
import type { Device, DeviceStats } from '@/types/device'

export const useDeviceStore = defineStore('device', () => {
  const devices = ref<Device[]>([])
  const stats = ref<DeviceStats>({
    total: 0,
    online: 0,
    offline: 0,
    onlineRate: 0
  })
  
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  // 获取设备列表
  const fetchDevices = async () => {
    isLoading.value = true
    try {
      const response = await deviceApi.getDevices()
      devices.value = response.data.devices
      stats.value = response.data.stats
    } catch (err) {
      error.value = '获取设备列表失败'
      console.error(err)
    } finally {
      isLoading.value = false
    }
  }
  
  // 实时更新设备状态
  const updateDeviceStatus = (deviceId: string, update: Partial<Device>) => {
    const index = devices.value.findIndex(d => d.device_id === deviceId)
    if (index !== -1) {
      devices.value[index] = { ...devices.value[index], ...update }
    }
  }
  
  return {
    devices: readonly(devices),
    stats: readonly(stats),
    isLoading: readonly(isLoading),
    error: readonly(error),
    fetchDevices,
    updateDeviceStatus
  }
})
```

**WebSocket实时更新:**
```typescript
// services/websocket.ts
import { useDeviceStore } from '@/stores/device'

class WebSocketService {
  private ws: WebSocket | null = null
  private deviceStore = useDeviceStore()
  
  connect() {
    this.ws = new WebSocket(`ws://localhost:3000/ws`)
    
    this.ws.onmessage = (event) => {
      const data = JSON.parse(event.data)
      
      if (data.type === 'device_update') {
        this.deviceStore.updateDeviceStatus(data.device_id, data.update)
      }
    }
    
    this.ws.onclose = () => {
      // 重连逻辑
      setTimeout(() => this.connect(), 3000)
    }
  }
  
  disconnect() {
    this.ws?.close()
  }
}

export const wsService = new WebSocketService()
```

**设备卡片组件:**
```vue
<!-- components/DeviceCard.vue -->
<template>
  <q-card class="device-card" :class="statusClass">
    <q-card-section>
      <div class="row items-center justify-between">
        <div class="col">
          <div class="text-h6">{{ device.device_name }}</div>
          <div class="text-caption text-grey-6">{{ device.device_id }}</div>
        </div>
        <div class="col-auto">
          <q-badge 
            :color="statusColor" 
            :label="statusText"
            class="q-px-sm"
          />
        </div>
      </div>
    </q-card-section>
    
    <q-card-section v-if="isOnline">
      <div class="resource-grid">
        <div class="resource-item">
          <div class="resource-label">CPU</div>
          <q-linear-progress 
            :value="device.cpu_usage / 100"
            :color="getResourceColor(device.cpu_usage)"
            size="8px"
          />
          <div class="resource-value">{{ device.cpu_usage }}%</div>
        </div>
        
        <div class="resource-item">
          <div class="resource-label">内存</div>
          <q-linear-progress 
            :value="device.memory_usage / 100"
            :color="getResourceColor(device.memory_usage)"
            size="8px"
          />
          <div class="resource-value">{{ device.memory_usage }}%</div>
        </div>
        
        <div class="resource-item">
          <div class="resource-label">磁盘</div>
          <q-linear-progress 
            :value="device.disk_usage / 100"
            :color="getResourceColor(device.disk_usage)"
            size="8px"
          />
          <div class="resource-value">{{ device.disk_usage }}%</div>
        </div>
        
        <div class="resource-item" v-if="device.temperature">
          <div class="resource-label">温度</div>
          <div class="resource-value">{{ device.temperature }}°C</div>
        </div>
      </div>
    </q-card-section>
    
    <q-card-actions align="right">
      <q-btn 
        flat 
        dense 
        icon="edit" 
        @click="$emit('edit', device)"
        title="编辑设备"
      />
      <q-btn 
        flat 
        dense 
        icon="delete" 
        color="negative"
        @click="$emit('delete', device)"
        title="删除设备"
      />
    </q-card-actions>
  </q-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Device } from '@/types/device'
import { formatLastSeen } from '@/utils/format'

interface Props {
  device: Device
}

const props = defineProps<Props>()
const emit = defineEmits<{
  edit: [device: Device]
  delete: [device: Device]
}>()

const isOnline = computed(() => {
  if (!props.device.last_heartbeat) return false
  const now = new Date()
  const lastHeartbeat = new Date(props.device.last_heartbeat)
  return (now.getTime() - lastHeartbeat.getTime()) <= 5 * 60 * 1000 // 5分钟
})

const statusClass = computed(() => ({
  'online': isOnline.value,
  'offline': !isOnline.value
}))

const statusColor = computed(() => isOnline.value ? 'positive' : 'negative')
const statusText = computed(() => isOnline.value ? '在线' : '离线')

const getResourceColor = (usage: number) => {
  if (usage >= 90) return 'negative'
  if (usage >= 70) return 'warning'
  return 'positive'
}
</script>

<style lang="scss" scoped>
.device-card {
  transition: all 0.3s ease;
  
  &.online {
    border-left: 4px solid $positive;
  }
  
  &.offline {
    border-left: 4px solid $negative;
    opacity: 0.7;
  }
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  }
}

.resource-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  
  @media (max-width: 600px) {
    grid-template-columns: 1fr;
  }
}

.resource-item {
  .resource-label {
    font-size: 12px;
    color: var(--q-color-grey-6);
    margin-bottom: 4px;
  }
  
  .resource-value {
    font-size: 14px;
    font-weight: 500;
    margin-top: 4px;
  }
}
</style>
```

### 3. 响应式设计优化

基于记忆中的移动端优化经验，实现以下响应式断点：

```scss
// styles/responsive.scss
$breakpoints: (
  xs: 0px,
  sm: 600px,
  md: 1024px,
  lg: 1440px,
  xl: 1920px
);

// 移动端优化
@media (max-width: 599px) {
  .device-grid {
    grid-template-columns: 1fr;
    gap: 0.5rem;
    padding: 0.5rem;
  }
  
  .stats-overview {
    flex-direction: column;
    
    .stat-card {
      margin-bottom: 0.5rem;
    }
  }
}

// 平板优化  
@media (min-width: 600px) and (max-width: 1023px) {
  .device-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
}

// 桌面端优化
@media (min-width: 1024px) {
  .device-grid {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
  }
}

// 超宽屏优化
@media (min-width: 1920px) {
  .device-grid {
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 2rem;
  }
}
```

### 4. API集成

**设备API服务:**
```typescript
// services/device.ts
import { api } from './api'
import type { Device, DeviceListResponse, DeviceDetailResponse } from '@/types/device'

export const deviceApi = {
  // 获取设备列表
  async getDevices(params?: {
    status?: 'online' | 'offline'
    search?: string
    page?: number
    limit?: number
    year?: number
    month?: number
  }): Promise<{ data: DeviceListResponse }> {
    const response = await api.get('/devices', { params })
    return response
  },

  // 获取设备详情
  async getDevice(deviceId: string): Promise<{ data: DeviceDetailResponse }> {
    const response = await api.get(`/devices/${deviceId}`)
    return response
  },

  // 更新设备信息
  async updateDevice(deviceId: string, data: {
    device_name?: string
    remarks?: string
    order_number?: number
  }): Promise<{ data: Device }> {
    const response = await api.put(`/devices/${deviceId}`, data)
    return response
  },

  // 删除设备
  async deleteDevice(deviceId: string): Promise<void> {
    await api.delete(`/devices/${deviceId}`)
  },

  // 获取设备心跳历史
  async getHeartbeatHistory(deviceId: string, params?: {
    start_date?: string
    end_date?: string
    limit?: number
  }) {
    const response = await api.get(`/devices/${deviceId}/heartbeats`, { params })
    return response
  }
}
```

### 5. 开发命令脚本

**package.json开发脚本:**
```json
{
  "scripts": {
    "dev": "quasar dev",
    "build": "quasar build",
    "lint": "eslint . --ext .vue,.js,.jsx,.cjs,.mjs,.ts,.tsx,.cts,.mts --fix",
    "format": "prettier --write .",
    "type-check": "vue-tsc --noEmit",
    "test": "vitest",
    "test:coverage": "vitest --coverage",
    "preview": "quasar build && quasar serve dist/spa"
  }
}
```

**标准开发流程:**
```bash
# 1. 安装依赖
pnpm install

# 2. 启动开发服务器
pnpm dev

# 3. 代码质量检查（每次提交前必须运行）
pnpm lint          # ESLint检查
pnpm format        # Prettier格式化  
pnpm type-check    # TypeScript类型检查

# 4. 运行测试
pnpm test

# 5. 构建生产版本
pnpm build
```

## 部署和运维

### Docker化部署

**frontend Dockerfile:**
```dockerfile
FROM node:18-alpine AS build

WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production

COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/dist/spa /usr/share/nginx/html
COPY nginx.conf /etc/nginx/nginx.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

**docker-compose.yml:**
```yaml
version: '3.8'
services:
  frontend:
    build: ./frontend
    ports:
      - "80:80"
    depends_on:
      - backend
      
  backend:
    build: ./backend  
    ports:
      - "3000:3000"
    environment:
      - DATABASE_URL=mysql://user:password@db:3306/ztao
      - JWT_SECRET=your-secret-key
    depends_on:
      - db
      
  db:
    image: mysql:8.4
    environment:
      - MYSQL_ROOT_PASSWORD=rootpassword
      - MYSQL_DATABASE=ztao
    ports:
      - "3306:3306"
    volumes:
      - ./ztao_20250831204757hs0x5.sql:/docker-entrypoint-initdb.d/init.sql
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

### 数据迁移策略

**保持数据库兼容:**
1. 使用现有SQL文件初始化数据库
2. 创建API适配层，无需修改数据库结构
3. 逐步迁移功能，支持新旧系统并行运行

**迁移步骤:**
```bash
# 1. 导入现有数据库
mysql -u root -p ztao < ztao_20250831204757hs0x5.sql

# 2. 启动新后端API服务
cd backend && pnpm start

# 3. 部署前端应用
cd frontend && pnpm build

# 4. 配置反向代理
# Nginx配置同时支持新旧系统访问
```

### 监控和日志

**应用监控:**
- 使用PM2或Docker健康检查
- 设置应用性能监控(APM)
- 配置错误日志收集

**数据库监控:**  
- MySQL慢查询日志
- 连接池监控
- 磁盘空间监控

## 总结

这个重构方案提供了：

1. **现代化技术栈** - Vue 3 + Quasar + TypeScript前端，Node.js/Go/Python后端可选
2. **数据库兼容性** - 保持现有MySQL数据库结构不变
3. **渐进式迁移** - 支持新旧系统并行，降低迁移风险  
4. **响应式设计** - 基于Quasar的移动优先设计
5. **实时监控** - WebSocket实现设备状态实时更新
6. **开发效率** - 完整的开发工具链和规范

通过这个方案，可以将现有PHP单体应用升级为现代化的前后端分离架构，同时保持所有现有功能和数据的完整性。

推荐优先使用**Node.js + Fastify**后端方案，可以实现TypeScript全栈一致性，提高开发效率和代码质量。