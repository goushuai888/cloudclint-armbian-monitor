import { config as dotenvConfig } from 'dotenv'

// 加载环境变量
dotenvConfig()

// 配置类型定义
interface Config {
  server: {
    port: number
    host: string
    nodeEnv: string
  }
  database: {
    host: string
    port: number
    user: string
    password: string
    name: string
  }
  jwt: {
    secret: string
    expiresIn: string
    refreshExpiresIn: string
  }
  cors: {
    origin: string
  }
  heartbeat: {
    timeout: number
  }
  websocket: {
    heartbeatInterval: number
  }
  logging: {
    retentionDays: number
  }
  rateLimit: {
    max: number
    window: number
  }
}

// 获取环境变量，支持默认值
function getEnv(key: string, defaultValue?: string): string {
  const value = process.env[key]
  if (value === undefined) {
    if (defaultValue !== undefined) {
      return defaultValue
    }
    throw new Error(`环境变量 ${key} 未设置`)
  }
  return value
}

// 获取数字类型的环境变量
function getEnvNumber(key: string, defaultValue?: number): number {
  const value = process.env[key]
  if (value === undefined) {
    if (defaultValue !== undefined) {
      return defaultValue
    }
    throw new Error(`环境变量 ${key} 未设置`)
  }
  const num = parseInt(value, 10)
  if (isNaN(num)) {
    throw new Error(`环境变量 ${key} 必须是数字`)
  }
  return num
}

// 导出配置对象
export const config: Config = {
  server: {
    port: getEnvNumber('PORT', 3000),
    host: getEnv('HOST', '0.0.0.0'),
    nodeEnv: getEnv('NODE_ENV', 'development')
  },
  database: {
    host: getEnv('DB_HOST', '127.0.0.1'),
    port: getEnvNumber('DB_PORT', 3306),
    user: getEnv('DB_USER', 'root'),
    password: getEnv('DB_PASSWORD', ''),
    name: getEnv('DB_NAME', 'armbox')
  },
  jwt: {
    secret: getEnv('JWT_SECRET'),
    expiresIn: getEnv('JWT_EXPIRES_IN', '15m'),
    refreshExpiresIn: getEnv('JWT_REFRESH_EXPIRES_IN', '7d')
  },
  cors: {
    origin: getEnv('CORS_ORIGIN', 'http://localhost:9000')
  },
  heartbeat: {
    timeout: getEnvNumber('HEARTBEAT_TIMEOUT', 300)
  },
  websocket: {
    heartbeatInterval: getEnvNumber('WS_HEARTBEAT_INTERVAL', 30000)
  },
  logging: {
    retentionDays: getEnvNumber('LOG_RETENTION_DAYS', 30)
  },
  rateLimit: {
    max: getEnvNumber('RATE_LIMIT_MAX', 100),
    window: getEnvNumber('RATE_LIMIT_WINDOW', 60000)
  }
}

// 验证必要的配置
export function validateConfig(): void {
  const requiredKeys = [
    'JWT_SECRET'
  ]
  
  const missingKeys = requiredKeys.filter(key => !process.env[key])
  
  if (missingKeys.length > 0) {
    throw new Error(`缺少必要的环境变量: ${missingKeys.join(', ')}`)
  }
  
  console.log('✅ 配置验证通过')
}

// 打印配置信息（隐藏敏感信息）
export function printConfig(): void {
  console.log('📋 服务器配置:')
  console.log(`   端口: ${config.server.port}`)
  console.log(`   主机: ${config.server.host}`)
  console.log(`   环境: ${config.server.nodeEnv}`)
  console.log(`   数据库: ${config.database.host}:${config.database.port}/${config.database.name}`)
  console.log(`   CORS源: ${config.cors.origin}`)
  console.log(`   心跳超时: ${config.heartbeat.timeout}秒`)
  console.log(`   JWT过期时间: ${config.jwt.expiresIn}`)
}