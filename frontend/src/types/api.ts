// API相关类型定义
export interface ApiResponse<T = unknown> {
  success: boolean
  message: string
  data: T
  timestamp: string
}

export interface ApiError {
  success: false
  message: string
  error: string
  timestamp: string
  details?: Record<string, unknown>
}

export interface LoginRequest {
  username: string
  password: string
}

export interface TokenResponse {
  accessToken: string
  refreshToken: string
  expiresIn: number // 以秒为单位
  tokenType: string
}

export interface LoginResponse {
  success: boolean
  message: string
  data: {
    user: User
    accessToken: string
    refreshToken: string
    expiresIn: number
    tokenType: string
  }
  timestamp: string
}

export interface RefreshTokenRequest {
  refreshToken: string
}

export interface RefreshTokenResponse {
  success: boolean
  message: string
  data: TokenResponse
  timestamp: string
}

export interface User {
  id: number
  username: string
  email: string | null
  role: string
  last_login: string | null
  created_at: string
  updated_at: string
}

// 心跳上报接口
export interface HeartbeatRequest {
  device_id: string
  device_name?: string
  mac_address?: string
  system_info?: {
    os: string
    kernel: string
    arch: string
    distro?: string
  }
  cpu_usage: number
  memory_usage: number
  disk_usage: number
  temperature?: number
  uptime: number
  ip_address?: string
}

export interface HeartbeatResponse {
  success: boolean
  message: string
  timestamp: string
  device_id: string
}

// 系统配置
export interface SystemConfig {
  id: number
  config_key: string
  config_value: string
  config_description: string | null
  updated_at: string
}

export interface SystemStats {
  totalDevices: number
  onlineDevices: number
  offlineDevices: number
  totalHeartbeats: number
  avgCpuUsage: number
  avgMemoryUsage: number
  avgDiskUsage: number
  systemUptime: number
}

// 请求日志
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

// 登录日志
export interface LoginLog {
  id: number
  username: string
  ip_address: string
  user_agent: string | null
  login_status: 'success' | 'failed'
  failure_reason: string | null
  created_at: string
}