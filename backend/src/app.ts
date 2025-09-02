import Fastify from 'fastify'
import { config, validateConfig, printConfig } from '@/config/index.js'
import { testConnection, closePool } from '@/config/database.js'
import cors from '@fastify/cors'
import rateLimit from '@fastify/rate-limit'
import helmet from '@fastify/helmet'
import jwt from '@fastify/jwt'
import websocket from '@fastify/websocket'
import cookie from '@fastify/cookie'

// 导入路由
import deviceRoutes from '@/routes/device.js'
import authRoutes from '@/routes/auth.js'
import healthRoutes from '@/routes/health.js'
import groupRoutes from '@/routes/group.js'
import userRoutes from '@/routes/user.js'
import configRoutes from '@/routes/config.js'

// 导入服务
import { SecurityLogService } from '@/services/securityLogService.js'

export async function buildApp() {
  // 创建Fastify应用实例
  const loggerConfig = config.server.nodeEnv === 'development' 
    ? {
        level: 'info' as const,
        transport: { target: 'pino-pretty', options: { colorize: true } }
      }
    : {
        level: 'warn' as const
      }
  
  const app = Fastify({
    logger: loggerConfig
  })

  // 注册CORS插件
  await app.register(cors, {
    origin: config.cors.origin,
    credentials: true,
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
  })

  // 注册安全插件
  await app.register(helmet, {
    contentSecurityPolicy: false
  })

  // 注册限流插件
  await app.register(rateLimit, {
    max: config.rateLimit.max,
    timeWindow: config.rateLimit.window
  })

  // 注册JWT插件
  await app.register(jwt, {
    secret: config.jwt.secret
  })

  // 注册WebSocket插件
  await app.register(websocket)

  // 注册Cookie插件
  await app.register(cookie, {
    secret: config.jwt.secret, // 用于签名cookie
    parseOptions: {}
  })

  // 注册JWT认证钩子
  app.decorate('authenticate', async function (request, reply) {
    try {
      await request.jwtVerify()
    } catch (err) {
      reply.code(401).send({
        success: false,
        message: '认证失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 全局前置钩子 - 请求日志
  app.addHook('preHandler', async (request, _reply) => {
    if (config.server.nodeEnv === 'development') {
      app.log.info({
        method: request.method,
        url: request.url,
        ip: request.ip,
        userAgent: request.headers['user-agent']
      }, '请求处理')
    }
  })

  // 全局错误处理
  app.setErrorHandler((error, _request, reply) => {
    app.log.error(error)
    
    const statusCode = error.statusCode || 500
    const message = statusCode === 500 ? '服务器内部错误' : error.message
    
    reply.code(statusCode).send({
      success: false,
      message,
      timestamp: new Date().toISOString()
    })
  })

  // 404处理
  app.setNotFoundHandler((_request, reply) => {
    reply.code(404).send({
      success: false,
      message: '请求的资源不存在',
      timestamp: new Date().toISOString()
    })
  })

  // 注册路由
  await app.register(healthRoutes, { prefix: '/api/health' })
  await app.register(authRoutes, { prefix: '/api/auth' })
  await app.register(deviceRoutes, { prefix: '/api/devices' })
  await app.register(groupRoutes, { prefix: '/api/groups' })
  await app.register(userRoutes, { prefix: '/api/users' })
  await app.register(configRoutes, { prefix: '/api/config' })

  // WebSocket路由 - 实时设备状态更新
  app.register(async function (app) {
    app.get('/ws', { websocket: true }, (connection, _req) => {
      app.log.info('WebSocket连接建立')
      
      connection.on('message', (message) => {
        try {
          const data = JSON.parse(message.toString())
          app.log.info({ data }, 'WebSocket消息接收')
          
          // 回应心跳
          if (data.type === 'ping') {
            connection.send(JSON.stringify({
              type: 'pong',
              timestamp: new Date().toISOString()
            }))
          }
        } catch (error) {
          app.log.error(error, 'WebSocket消息解析失败')
        }
      })

      connection.on('close', () => {
        app.log.info('WebSocket连接关闭')
      })
    })
  })

  // 初始化安全日志服务
  SecurityLogService.initialize(app)

  return app
}

async function start() {
  try {
    // 验证配置
    validateConfig()
    
    // 测试数据库连接
    const dbConnected = await testConnection()
    if (!dbConnected) {
      throw new Error('数据库连接失败')
    }

    // 创建应用
    const app = await buildApp()

    // 启动服务器
    await app.listen({
      host: config.server.host,
      port: config.server.port
    })

    // 打印配置信息
    printConfig()
    console.log(`🚀 服务器已启动: http://${config.server.host}:${config.server.port}`)
    
  } catch (error) {
    console.error('❌ 服务器启动失败:', error)
    process.exit(1)
  }
}

// 优雅关闭处理
process.on('SIGINT', async () => {
  console.log('🛑 正在关闭服务器...')
  
  try {
    await closePool()
    process.exit(0)
  } catch (error) {
    console.error('❌ 关闭过程中发生错误:', error)
    process.exit(1)
  }
})

// 如果直接运行此文件，启动服务器
if (import.meta.url === `file://${process.argv[1]}`) {
  start()
}