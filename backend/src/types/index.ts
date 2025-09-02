// 设备相关类型
export interface Device {
  id: number
  device_id: string
  device_name: string
  ip_address: string | null
  mac_address: string | null
  system_info: SystemInfo | string | null
  cpu_usage: number
  memory_usage: number
  disk_usage: number
  temperature: number | null
  uptime: number
  remarks: string | null
  status: 'online' | 'offline' | 'warning'
  last_heartbeat: string | null
  created_at: string
  updated_at: string
  order_number: number
  group_id?: number
}

export interface SystemInfo {
  os: string
  kernel: string
  arch: string
  distro?: string
}

export interface DeviceStats {
  total: number
  online: number
  offline: number
  onlineRate: number
}

export interface HeartbeatLog {
  id: number
  device_id: string
  heartbeat_time: string
  cpu_usage: number
  memory_usage: number
  disk_usage: number
  temperature: number | null
  uptime: number
  created_at: string
}

export interface DeviceGroup {
  id: number
  group_name: string
  group_description: string | null
  group_color: string
  group_icon: string
  sort_order: number
  is_default: boolean
  created_at: string
  updated_at: string
}

// 用户相关类型
export interface User {
  id: number
  username: string
  email: string | null
  password: string
  role: string
  last_login: string | null
  created_at: string
  updated_at: string
}

export interface LoginLog {
  id: number
  username: string
  ip_address: string
  user_agent: string | null
  status: 'success' | 'failed'
  failure_reason: string | null
  created_at: string
}

// API相关类型
export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data?: T
  timestamp: string
}

export interface PaginationParams {
  page: number
  limit: number
}

export interface PaginationMeta {
  page: number
  limit: number
  total: number
  totalPages: number
}

export interface DeviceFilters {
  status?: 'online' | 'offline'
  search?: string
  groupId?: number
  year?: number
  month?: number
  page?: number
  limit?: number
}

// 心跳上报类型
export interface HeartbeatRequest {
  device_id: string
  device_name?: string
  mac_address?: string
  system_info?: SystemInfo
  cpu_usage: number
  memory_usage: number
  disk_usage: number
  temperature?: number
  uptime: number
  ip_address?: string
}

// WebSocket消息类型
export interface WebSocketMessage {
  type: 'device_update' | 'device_added' | 'device_removed' | 'stats_update' | 'heartbeat'
  data: any
  timestamp: string
}

// 系统配置类型
export interface SystemConfig {
  id: number
  config_key: string
  config_value: string
  config_description: string | null
  updated_at: string
}

// 请求日志类型
export interface RequestLog {
  id: number
  ip_address: string
  user_agent: string | null
  request_method: string
  request_uri: string
  response_code: number
  response_time: number
  created_at: string
}

// Fastify扩展类型
declare module 'fastify' {
  interface FastifyInstance {
    authenticate: (request: FastifyRequest, reply: FastifyReply) => Promise<void>
  }
}

declare module '@fastify/jwt' {
  interface FastifyJWT {
    user: {
      id: number
      username: string
      role: string
    }
  }
}

// JWT Payload类型
export interface JWTPayload {
  id: number
  username: string
  role: string
  iat?: number
  exp?: number
}

// Refresh Token类型
export interface RefreshToken {
  id: number
  user_id: number
  token: string
  device_fingerprint?: string
  expires_at: string
  created_at: string
  last_used_at?: string
}

// Token响应类型
export interface TokenResponse {
  accessToken: string
  refreshToken: string
  expiresIn: number
  tokenType: string
}

// 查询构建器类型
export interface QueryBuilder {
  select: string[]
  from: string
  joins: string[]
  where: string[]
  params: any[]
  orderBy: string[]
  groupBy: string[]
  limit?: number
  offset?: number
}