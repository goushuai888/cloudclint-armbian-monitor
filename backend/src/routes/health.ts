import { FastifyInstance, FastifyRequest, FastifyReply } from 'fastify'
import { healthCheck } from '@/config/database.js'
import { config } from '@/config/index.js'
import type { ApiResponse } from '@/types/index.js'
import dayjs from 'dayjs'

// 健康检查路由插件
export default async function healthRoutes(app: FastifyInstance) {
  
  // 基础健康检查
  app.get('/', async (_request: FastifyRequest, reply: FastifyReply) => {
    const response: ApiResponse = {
      success: true,
      message: '服务运行正常',
      data: {
        status: 'healthy',
        timestamp: new Date().toISOString(),
        uptime: process.uptime(),
        version: '1.0.0',
        environment: config.server.nodeEnv
      },
      timestamp: new Date().toISOString()
    }

    return reply.code(200).send(response)
  })

  // 详细健康检查（包含数据库状态）
  app.get('/detailed', async (_request: FastifyRequest, reply: FastifyReply) => {
    try {
      const dbHealth = await healthCheck()
      const isHealthy = dbHealth.status === 'healthy'

      const response: ApiResponse = {
        success: isHealthy,
        message: isHealthy ? '所有服务运行正常' : '部分服务异常',
        data: {
          status: isHealthy ? 'healthy' : 'unhealthy',
          timestamp: new Date().toISOString(),
          uptime: process.uptime(),
          version: '1.0.0',
          environment: config.server.nodeEnv,
          services: {
            database: dbHealth,
            memory: {
              used: process.memoryUsage().heapUsed,
              total: process.memoryUsage().heapTotal,
              external: process.memoryUsage().external,
              rss: process.memoryUsage().rss
            },
            cpu: {
              usage: process.cpuUsage()
            }
          }
        },
        timestamp: new Date().toISOString()
      }

      const statusCode = isHealthy ? 200 : 503
      return reply.code(statusCode).send(response)

    } catch (error) {
      app.log.error(error, '健康检查失败')
      
      const response: ApiResponse = {
        success: false,
        message: '健康检查失败',
        data: {
          status: 'unhealthy',
          timestamp: new Date().toISOString(),
          error: error instanceof Error ? error.message : String(error)
        },
        timestamp: new Date().toISOString()
      }

      return reply.code(503).send(response)
    }
  })

  // Kubernetes就绪探针
  app.get('/ready', async (_request: FastifyRequest, reply: FastifyReply) => {
    try {
      const dbHealth = await healthCheck()
      
      if (dbHealth.status === 'healthy') {
        return reply.code(200).send({ status: 'ready' })
      } else {
        return reply.code(503).send({ status: 'not ready', reason: 'database unhealthy' })
      }
    } catch (error) {
      return reply.code(503).send({ status: 'not ready', reason: 'health check failed' })
    }
  })

  // Kubernetes存活探针
  app.get('/live', async (_request: FastifyRequest, reply: FastifyReply) => {
    return reply.code(200).send({ status: 'alive' })
  })

  // 系统信息
  app.get('/info', async (_request: FastifyRequest, reply: FastifyReply) => {
    const response: ApiResponse = {
      success: true,
      message: '获取系统信息成功',
      data: {
        server: {
          name: 'Armbian Box Monitor API',
          version: '1.0.0',
          environment: config.server.nodeEnv,
          host: config.server.host,
          port: config.server.port,
          startTime: dayjs().subtract(process.uptime(), 'seconds').toISOString(),
          uptime: process.uptime()
        },
        runtime: {
          node: process.version,
          platform: process.platform,
          arch: process.arch,
          pid: process.pid
        },
        memory: {
          used: Math.round(process.memoryUsage().heapUsed / 1024 / 1024),
          total: Math.round(process.memoryUsage().heapTotal / 1024 / 1024),
          external: Math.round(process.memoryUsage().external / 1024 / 1024),
          rss: Math.round(process.memoryUsage().rss / 1024 / 1024)
        },
        database: {
          host: config.database.host,
          port: config.database.port,
          name: config.database.name
        },
        features: {
          jwt: true,
          websocket: true,
          cors: true,
          rateLimit: true,
          helmet: true
        }
      },
      timestamp: new Date().toISOString()
    }

    return reply.code(200).send(response)
  })
}