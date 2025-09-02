# 代码规范文档

## 概述

本文档定义了Armbian设备监控平台项目的代码规范和最佳实践，旨在确保代码质量、可维护性和团队协作效率。所有开发人员都应严格遵循这些规范。

## 通用规范

### 文件和目录命名
- **文件名**: 使用kebab-case（短横线分隔）
  - ✅ `device-card.vue`
  - ✅ `user-management.ts`
  - ❌ `DeviceCard.vue`
  - ❌ `user_management.ts`

- **目录名**: 使用kebab-case
  - ✅ `components/device-management/`
  - ✅ `services/api-client/`
  - ❌ `components/DeviceManagement/`

- **常量文件**: 使用UPPER_SNAKE_CASE
  - ✅ `API_CONSTANTS.ts`
  - ✅ `ERROR_CODES.ts`

### 代码注释
- **文件头注释**: 每个文件都应包含文件说明
```typescript
/**
 * 设备管理相关的API服务
 * @author 开发团队
 * @since 2024-01-22
 */
```

- **函数注释**: 使用JSDoc格式
```typescript
/**
 * 获取设备列表
 * @param filters 筛选条件
 * @param pagination 分页参数
 * @returns 设备列表和分页信息
 */
async function getDevices(filters: DeviceFilters, pagination: PaginationParams) {
  // 实现代码
}
```

- **复杂逻辑注释**: 解释业务逻辑和算法
```typescript
// 计算设备在线率，考虑心跳超时时间
const onlineRate = (onlineCount / totalCount) * 100;
```

### Git提交规范
使用Conventional Commits规范：

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

**类型说明**:
- `feat`: 新功能
- `fix`: 修复bug
- `docs`: 文档更新
- `style`: 代码格式调整
- `refactor`: 代码重构
- `test`: 测试相关
- `chore`: 构建工具、依赖更新等

**示例**:
```
feat(auth): 添加JWT认证功能

- 实现用户登录API
- 添加token验证中间件
- 完善错误处理机制

Closes #123
```

## 前端规范

### Vue.js规范

#### 组件命名
- **组件文件**: 使用PascalCase
  - ✅ `DeviceCard.vue`
  - ✅ `UserManagement.vue`
  - ❌ `deviceCard.vue`

- **组件注册**: 使用PascalCase
```vue
<template>
  <DeviceCard :device="device" />
  <UserManagement />
</template>
```

#### 组件结构
```vue
<template>
  <!-- 模板内容 -->
</template>

<script setup lang="ts">
// 导入语句
import { ref, computed, onMounted } from 'vue'
import type { Device } from '@/types/device'

// Props定义
interface Props {
  device: Device
  showActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true
})

// Emits定义
interface Emits {
  update: [device: Device]
  delete: [id: string]
}

const emit = defineEmits<Emits>()

// 响应式数据
const loading = ref(false)
const error = ref<string | null>(null)

// 计算属性
const isOnline = computed(() => {
  return props.device.status === 'online'
})

// 方法
const handleUpdate = async () => {
  try {
    loading.value = true
    // 更新逻辑
    emit('update', props.device)
  } catch (err) {
    error.value = err instanceof Error ? err.message : '更新失败'
  } finally {
    loading.value = false
  }
}

// 生命周期
onMounted(() => {
  // 初始化逻辑
})
</script>

<style scoped>
/* 样式定义 */
.device-card {
  /* 使用Quasar类名，避免自定义样式 */
}
</style>
```

#### Props和Events
- **Props**: 使用camelCase，提供类型定义
```typescript
interface Props {
  deviceId: string
  showActions?: boolean
  maxItems?: number
}
```

- **Events**: 使用kebab-case
```vue
<template>
  <button @click="$emit('device-updated', device)">
    更新设备
  </button>
</template>
```

#### 状态管理 (Pinia)
```typescript
// stores/device.ts
import { defineStore } from 'pinia'
import type { Device, DeviceFilters } from '@/types/device'
import { deviceApi } from '@/services/device'

export const useDeviceStore = defineStore('device', () => {
  // State
  const devices = ref<Device[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const onlineDevices = computed(() => 
    devices.value.filter(device => device.status === 'online')
  )

  const deviceCount = computed(() => devices.value.length)

  // Actions
  const fetchDevices = async (filters?: DeviceFilters) => {
    try {
      loading.value = true
      error.value = null
      const response = await deviceApi.getDevices(filters)
      devices.value = response.data.devices
    } catch (err) {
      error.value = err instanceof Error ? err.message : '获取设备列表失败'
      throw err
    } finally {
      loading.value = false
    }
  }

  const addDevice = (device: Device) => {
    devices.value.push(device)
  }

  const updateDevice = (id: string, updates: Partial<Device>) => {
    const index = devices.value.findIndex(device => device.id === id)
    if (index !== -1) {
      devices.value[index] = { ...devices.value[index], ...updates }
    }
  }

  const removeDevice = (id: string) => {
    const index = devices.value.findIndex(device => device.id === id)
    if (index !== -1) {
      devices.value.splice(index, 1)
    }
  }

  return {
    // State
    devices: readonly(devices),
    loading: readonly(loading),
    error: readonly(error),
    
    // Getters
    onlineDevices,
    deviceCount,
    
    // Actions
    fetchDevices,
    addDevice,
    updateDevice,
    removeDevice
  }
})
```

### TypeScript规范

#### 类型定义
```typescript
// types/device.ts

// 基础接口
export interface Device {
  id: string
  name: string
  ip: string
  status: DeviceStatus
  lastSeen: string
  systemInfo: SystemInfo
  createdAt: string
  updatedAt: string
}

// 枚举类型
export enum DeviceStatus {
  ONLINE = 'online',
  OFFLINE = 'offline',
  UNKNOWN = 'unknown'
}

// 联合类型
export type DeviceAction = 'update' | 'delete' | 'restart' | 'shutdown'

// 泛型接口
export interface ApiResponse<T> {
  success: boolean
  code: number
  message: string
  data: T
  timestamp: string
}

// 工具类型
export type DeviceUpdate = Partial<Pick<Device, 'name' | 'ip' | 'status'>>
export type DeviceCreate = Omit<Device, 'id' | 'createdAt' | 'updatedAt'>
```

#### 函数类型
```typescript
// 函数签名
type DeviceHandler = (device: Device) => void
type AsyncDeviceHandler = (device: Device) => Promise<void>

// 函数重载
function getDevice(id: string): Promise<Device>
function getDevice(filters: DeviceFilters): Promise<Device[]>
function getDevice(param: string | DeviceFilters): Promise<Device | Device[]> {
  // 实现
}
```

### 样式规范

#### CSS类命名
使用BEM命名法：
```scss
// Block
.device-card {
  // Element
  &__header {
    // Modifier
    &--highlighted {
      background-color: #f0f0f0;
    }
  }
  
  &__content {
    padding: 16px;
  }
  
  &__actions {
    display: flex;
    gap: 8px;
  }
}
```

#### Quasar组件使用
优先使用Quasar组件，避免自定义样式：
```vue
<template>
  <!-- 推荐 -->
  <q-card class="device-card">
    <q-card-section>
      <q-item>
        <q-item-section>
          <q-item-label>{{ device.name }}</q-item-label>
          <q-item-label caption>{{ device.ip }}</q-item-label>
        </q-item-section>
      </q-item>
    </q-card-section>
  </q-card>
  
  <!-- 避免 -->
  <div class="custom-card">
    <div class="custom-header">
      <!-- 自定义样式 -->
    </div>
  </div>
</template>
```

## 后端规范

### Node.js/TypeScript规范

#### 项目结构
```
backend/
├── src/
│   ├── config/          # 配置文件
│   ├── controllers/     # 控制器（如果使用）
│   ├── models/          # 数据模型
│   ├── routes/          # 路由定义
│   ├── services/        # 业务逻辑
│   ├── middleware/      # 中间件
│   ├── utils/           # 工具函数
│   ├── types/           # 类型定义
│   └── app.ts           # 应用入口
├── tests/               # 测试文件
├── docs/                # 文档
└── package.json
```

#### 路由定义
```typescript
// routes/device.ts
import { FastifyInstance, FastifyRequest, FastifyReply } from 'fastify'
import { DeviceModel } from '../models/Device'
import { deviceSchemas } from '../schemas/device'

export async function deviceRoutes(fastify: FastifyInstance) {
  const deviceModel = new DeviceModel()

  // 获取设备列表
  fastify.get('/devices', {
    schema: deviceSchemas.getDevices,
    preHandler: [fastify.authenticate]
  }, async (request: FastifyRequest<{
    Querystring: {
      page?: number
      limit?: number
      status?: string
      group?: string
    }
  }>, reply: FastifyReply) => {
    try {
      const { page = 1, limit = 20, status, group } = request.query
      
      const filters = {
        status,
        group,
        offset: (page - 1) * limit,
        limit
      }
      
      const result = await deviceModel.getDevices(filters)
      
      return reply.code(200).send({
        success: true,
        code: 200,
        message: '获取设备列表成功',
        data: result,
        timestamp: new Date().toISOString()
      })
    } catch (error) {
      request.log.error(error, '获取设备列表失败')
      return reply.code(500).send({
        success: false,
        code: 500,
        message: '服务器内部错误',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 创建设备
  fastify.post('/devices', {
    schema: deviceSchemas.createDevice,
    preHandler: [fastify.authenticate]
  }, async (request: FastifyRequest<{
    Body: {
      name: string
      ip: string
      group?: string
    }
  }>, reply: FastifyReply) => {
    try {
      const device = await deviceModel.createDevice(request.body)
      
      return reply.code(201).send({
        success: true,
        code: 201,
        message: '设备创建成功',
        data: device,
        timestamp: new Date().toISOString()
      })
    } catch (error) {
      if (error instanceof ValidationError) {
        return reply.code(400).send({
          success: false,
          code: 400,
          message: error.message,
          timestamp: new Date().toISOString()
        })
      }
      
      request.log.error(error, '创建设备失败')
      return reply.code(500).send({
        success: false,
        code: 500,
        message: '服务器内部错误',
        timestamp: new Date().toISOString()
      })
    }
  })
}
```

#### 数据模型
```typescript
// models/Device.ts
import { RowDataPacket, ResultSetHeader } from 'mysql2'
import { query, queryOne } from '../config/database'
import type { Device, DeviceFilters, CreateDeviceData } from '../types/device'

export class DeviceModel {
  /**
   * 获取设备列表
   */
  async getDevices(filters: DeviceFilters = {}) {
    const {
      status,
      group,
      search,
      offset = 0,
      limit = 20
    } = filters

    let whereClause = 'WHERE 1=1'
    const params: any[] = []

    if (status) {
      whereClause += ' AND status = ?'
      params.push(status)
    }

    if (group) {
      whereClause += ' AND group_id = ?'
      params.push(group)
    }

    if (search) {
      whereClause += ' AND (name LIKE ? OR ip LIKE ?)'
      params.push(`%${search}%`, `%${search}%`)
    }

    // 获取总数
    const countSql = `
      SELECT COUNT(*) as total
      FROM devices
      ${whereClause}
    `
    const countResult = await queryOne<{ total: number }>(countSql, params)
    const total = countResult?.total || 0

    // 获取设备列表
    const devicesSql = `
      SELECT 
        d.*,
        g.name as group_name,
        CASE 
          WHEN d.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'online'
          ELSE 'offline'
        END as status
      FROM devices d
      LEFT JOIN device_groups g ON d.group_id = g.id
      ${whereClause}
      ORDER BY d.created_at DESC
      LIMIT ? OFFSET ?
    `
    
    const devices = await query<Device[]>(
      devicesSql, 
      [...params, limit, offset]
    )

    return {
      devices,
      pagination: {
        total,
        page: Math.floor(offset / limit) + 1,
        limit,
        pages: Math.ceil(total / limit)
      }
    }
  }

  /**
   * 创建设备
   */
  async createDevice(data: CreateDeviceData): Promise<Device> {
    const sql = `
      INSERT INTO devices (name, ip, group_id, created_at, updated_at)
      VALUES (?, ?, ?, NOW(), NOW())
    `
    
    const result = await query<ResultSetHeader>(
      sql,
      [data.name, data.ip, data.group || null]
    )

    if (!result.insertId) {
      throw new Error('创建设备失败')
    }

    return this.getDeviceById(result.insertId.toString())
  }

  /**
   * 根据ID获取设备
   */
  async getDeviceById(id: string): Promise<Device> {
    const sql = `
      SELECT 
        d.*,
        g.name as group_name,
        CASE 
          WHEN d.last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'online'
          ELSE 'offline'
        END as status
      FROM devices d
      LEFT JOIN device_groups g ON d.group_id = g.id
      WHERE d.id = ?
    `
    
    const device = await queryOne<Device>(sql, [id])
    
    if (!device) {
      throw new Error('设备不存在')
    }
    
    return device
  }
}
```

#### 错误处理
```typescript
// utils/errors.ts
export class AppError extends Error {
  public readonly statusCode: number
  public readonly code: string
  public readonly isOperational: boolean

  constructor(
    message: string,
    statusCode: number = 500,
    code: string = 'INTERNAL_ERROR',
    isOperational: boolean = true
  ) {
    super(message)
    this.statusCode = statusCode
    this.code = code
    this.isOperational = isOperational

    Error.captureStackTrace(this, this.constructor)
  }
}

export class ValidationError extends AppError {
  constructor(message: string) {
    super(message, 400, 'VALIDATION_ERROR')
  }
}

export class NotFoundError extends AppError {
  constructor(resource: string) {
    super(`${resource}不存在`, 404, 'NOT_FOUND')
  }
}

export class UnauthorizedError extends AppError {
  constructor(message: string = '未授权访问') {
    super(message, 401, 'UNAUTHORIZED')
  }
}
```

#### 数据验证
```typescript
// schemas/device.ts
import Joi from 'joi'

export const deviceSchemas = {
  getDevices: {
    querystring: Joi.object({
      page: Joi.number().integer().min(1).default(1),
      limit: Joi.number().integer().min(1).max(100).default(20),
      status: Joi.string().valid('online', 'offline'),
      group: Joi.string().uuid(),
      search: Joi.string().max(100)
    })
  },

  createDevice: {
    body: Joi.object({
      name: Joi.string().required().min(1).max(100),
      ip: Joi.string().ip().required(),
      group: Joi.string().uuid().optional()
    })
  },

  updateDevice: {
    params: Joi.object({
      id: Joi.string().uuid().required()
    }),
    body: Joi.object({
      name: Joi.string().min(1).max(100),
      ip: Joi.string().ip(),
      group: Joi.string().uuid().allow(null)
    }).min(1)
  }
}
```

### 数据库规范

#### SQL查询
```sql
-- 使用参数化查询，防止SQL注入
SELECT * FROM devices WHERE id = ? AND status = ?

-- 使用适当的索引
CREATE INDEX idx_devices_status ON devices(status);
CREATE INDEX idx_devices_last_seen ON devices(last_seen);

-- 使用事务处理关键操作
START TRANSACTION;
UPDATE devices SET status = 'offline' WHERE id = ?;
INSERT INTO device_logs (device_id, action, timestamp) VALUES (?, 'status_change', NOW());
COMMIT;
```

#### 数据库连接
```typescript
// config/database.ts
import mysql from 'mysql2/promise'
import { config } from './index'

// 连接池配置
const pool = mysql.createPool({
  host: config.database.host,
  port: config.database.port,
  user: config.database.user,
  password: config.database.password,
  database: config.database.name,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  acquireTimeout: 60000,
  timeout: 60000,
  reconnect: true
})

// 查询函数
export async function query<T = any>(
  sql: string, 
  params: any[] = []
): Promise<T> {
  try {
    const [rows] = await pool.execute(sql, params)
    return rows as T
  } catch (error) {
    console.error('Database query error:', error)
    throw error
  }
}

export async function queryOne<T = any>(
  sql: string, 
  params: any[] = []
): Promise<T | null> {
  const rows = await query<T[]>(sql, params)
  return rows.length > 0 ? rows[0] : null
}
```

## 测试规范

### 单元测试
```typescript
// tests/models/Device.test.ts
import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { DeviceModel } from '../../src/models/Device'
import { setupTestDatabase, cleanupTestDatabase } from '../helpers/database'

describe('DeviceModel', () => {
  let deviceModel: DeviceModel

  beforeEach(async () => {
    await setupTestDatabase()
    deviceModel = new DeviceModel()
  })

  afterEach(async () => {
    await cleanupTestDatabase()
  })

  describe('getDevices', () => {
    it('应该返回设备列表和分页信息', async () => {
      // 准备测试数据
      const testDevice = {
        name: '测试设备',
        ip: '192.168.1.100'
      }
      await deviceModel.createDevice(testDevice)

      // 执行测试
      const result = await deviceModel.getDevices()

      // 验证结果
      expect(result).toHaveProperty('devices')
      expect(result).toHaveProperty('pagination')
      expect(result.devices).toHaveLength(1)
      expect(result.devices[0]).toMatchObject(testDevice)
      expect(result.pagination.total).toBe(1)
    })

    it('应该支持状态筛选', async () => {
      // 测试实现
    })
  })

  describe('createDevice', () => {
    it('应该成功创建设备', async () => {
      const deviceData = {
        name: '新设备',
        ip: '192.168.1.101'
      }

      const device = await deviceModel.createDevice(deviceData)

      expect(device).toMatchObject(deviceData)
      expect(device.id).toBeDefined()
      expect(device.createdAt).toBeDefined()
    })

    it('应该验证IP地址格式', async () => {
      const invalidDevice = {
        name: '无效设备',
        ip: 'invalid-ip'
      }

      await expect(deviceModel.createDevice(invalidDevice))
        .rejects.toThrow('IP地址格式无效')
    })
  })
})
```

### API测试
```typescript
// tests/routes/device.test.ts
import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { build } from '../helpers/app'
import { FastifyInstance } from 'fastify'

describe('Device Routes', () => {
  let app: FastifyInstance
  let authToken: string

  beforeEach(async () => {
    app = await build()
    
    // 获取认证token
    const loginResponse = await app.inject({
      method: 'POST',
      url: '/api/auth/login',
      payload: {
        username: 'test@example.com',
        password: 'password123'
      }
    })
    
    const loginData = JSON.parse(loginResponse.body)
    authToken = loginData.data.token
  })

  afterEach(async () => {
    await app.close()
  })

  describe('GET /api/devices', () => {
    it('应该返回设备列表', async () => {
      const response = await app.inject({
        method: 'GET',
        url: '/api/devices',
        headers: {
          authorization: `Bearer ${authToken}`
        }
      })

      expect(response.statusCode).toBe(200)
      
      const data = JSON.parse(response.body)
      expect(data.success).toBe(true)
      expect(data.data).toHaveProperty('devices')
      expect(data.data).toHaveProperty('pagination')
    })

    it('应该支持分页参数', async () => {
      const response = await app.inject({
        method: 'GET',
        url: '/api/devices?page=2&limit=10',
        headers: {
          authorization: `Bearer ${authToken}`
        }
      })

      expect(response.statusCode).toBe(200)
      
      const data = JSON.parse(response.body)
      expect(data.data.pagination.page).toBe(2)
      expect(data.data.pagination.limit).toBe(10)
    })

    it('未认证用户应该返回401', async () => {
      const response = await app.inject({
        method: 'GET',
        url: '/api/devices'
      })

      expect(response.statusCode).toBe(401)
    })
  })
})
```

## 性能规范

### 前端性能
- **组件懒加载**: 使用动态导入
```typescript
const DeviceDetail = defineAsyncComponent(() => import('@/pages/DeviceDetail.vue'))
```

- **图片懒加载**: 使用Intersection Observer
- **虚拟滚动**: 长列表使用虚拟滚动
- **缓存策略**: 合理使用浏览器缓存

### 后端性能
- **数据库查询优化**: 使用索引，避免N+1查询
- **缓存机制**: Redis缓存热点数据
- **连接池**: 数据库连接池管理
- **异步处理**: 使用异步I/O

## 安全规范

### 输入验证
- 所有用户输入都必须验证
- 使用白名单而非黑名单
- 防止SQL注入、XSS攻击

### 认证授权
- 使用JWT进行身份认证
- 实现基于角色的访问控制
- 敏感操作需要二次验证

### 数据保护
- 敏感数据加密存储
- 使用HTTPS传输
- 定期备份数据

## 部署规范

### 环境配置
```bash
# 开发环境
NODE_ENV=development
PORT=3000
DB_HOST=localhost

# 生产环境
NODE_ENV=production
PORT=8080
DB_HOST=prod-db-server
```

### Docker配置
```dockerfile
# Dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .

EXPOSE 8080

USER node

CMD ["npm", "start"]
```

## 工具配置

### ESLint配置
```json
{
  "extends": [
    "@typescript-eslint/recommended",
    "@vue/typescript/recommended",
    "prettier"
  ],
  "rules": {
    "@typescript-eslint/no-unused-vars": "error",
    "@typescript-eslint/explicit-function-return-type": "warn",
    "vue/component-name-in-template-casing": ["error", "PascalCase"]
  }
}
```

### Prettier配置
```json
{
  "semi": false,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "es5",
  "printWidth": 80,
  "endOfLine": "lf"
}
```

### TypeScript配置
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "node",
    "strict": true,
    "noImplicitAny": true,
    "noImplicitReturns": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true
  }
}
```

---

**文档版本**: 1.0  
**更新日期**: 2024-01-22  
**维护团队**: 开发团队