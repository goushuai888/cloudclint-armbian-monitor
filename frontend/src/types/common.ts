// 通用类型定义

// 基础状态类型
export type LoadingState = 'idle' | 'loading' | 'success' | 'error'

export type DeviceStatus = 'online' | 'offline' | 'warning'

export type SortDirection = 'asc' | 'desc'

// 通用响应包装器
export interface BaseResponse<T = unknown> {
  success: boolean
  message: string
  data?: T
  error?: string
  timestamp: string
}

// 分页参数
export interface PaginationParams {
  page: number
  limit: number
}

export interface SortParams {
  sortBy: string
  sortDirection: SortDirection
}

// 时间范围筛选
export interface DateRange {
  startDate: string
  endDate: string
}

// 资源使用率颜色映射
export interface ResourceColorConfig {
  normal: string    // 0-69%
  warning: string   // 70-89%
  critical: string  // 90-100%
}

// 主题配置
export interface ThemeConfig {
  primary: string
  secondary: string
  accent: string
  positive: string
  negative: string
  warning: string
  info: string
  dark: boolean
}

// 本地存储键名常量
export enum StorageKeys {
  AUTH_TOKEN = 'auth_token',
  REFRESH_TOKEN = 'refresh_token',
  USER_INFO = 'user_info',
  THEME_CONFIG = 'theme_config',
  DEVICE_FILTERS = 'device_filters',
  DASHBOARD_SETTINGS = 'dashboard_settings'
}

// API端点常量
export enum ApiEndpoints {
  // 认证相关
  LOGIN = '/api/auth/login',
  LOGOUT = '/api/auth/logout',
  REFRESH = '/api/auth/refresh',
  
  // 设备相关
  DEVICES = '/api/devices',
  DEVICE_DETAIL = '/api/devices/:id',
  DEVICE_HEARTBEATS = '/api/devices/:id/heartbeats',
  
  // 心跳上报
  HEARTBEAT = '/api/heartbeat',
  
  // 统计数据
  STATS = '/api/stats',
  DASHBOARD = '/api/dashboard',
  
  // 系统配置
  CONFIG = '/api/config',
  
  // 日志
  LOGS = '/api/logs',
  LOGIN_LOGS = '/api/logs/login',
  REQUEST_LOGS = '/api/logs/request'
}

// WebSocket事件类型
export enum WebSocketEvents {
  DEVICE_UPDATE = 'device_update',
  DEVICE_ADDED = 'device_added',
  DEVICE_REMOVED = 'device_removed',
  STATS_UPDATE = 'stats_update',
  HEARTBEAT = 'heartbeat',
  ERROR = 'error',
  CONNECT = 'connect',
  DISCONNECT = 'disconnect'
}

// 设备资源使用率阈值
export const RESOURCE_THRESHOLDS = {
  cpu: {
    warning: 70,
    critical: 90
  },
  memory: {
    warning: 80,
    critical: 95
  },
  disk: {
    warning: 80,
    critical: 90
  },
  temperature: {
    warning: 70,
    critical: 85
  }
} as const

// 时间格式常量
export const TIME_FORMATS = {
  FULL_DATETIME: 'YYYY-MM-DD HH:mm:ss',
  DATE_ONLY: 'YYYY-MM-DD',
  TIME_ONLY: 'HH:mm:ss',
  COMPACT_DATETIME: 'MM-DD HH:mm',
  RELATIVE_TIME: 'relative'
} as const

// 自动刷新间隔（毫秒）
export const REFRESH_INTERVALS = {
  DASHBOARD: 30000,     // 30秒
  DEVICE_LIST: 15000,   // 15秒
  DEVICE_DETAIL: 5000,  // 5秒
  STATS: 60000          // 1分钟
} as const

// 心跳超时时间（毫秒）
export const HEARTBEAT_TIMEOUT = 5 * 60 * 1000 // 5分钟

// 分页默认配置
export const PAGINATION_DEFAULTS = {
  PAGE: 1,
  LIMIT: 20,
  MAX_LIMIT: 100
} as const