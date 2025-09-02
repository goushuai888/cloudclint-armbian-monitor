import { FastifyInstance, FastifyRequest, FastifyReply } from 'fastify'
import { DeviceModel } from '@/models/Device.js'
import type { DeviceFilters, HeartbeatRequest, ApiResponse, Device } from '@/types/index.js'

// 设备路由插件
export default async function deviceRoutes(app: FastifyInstance) {
  
  // 获取设备列表（带分页和筛选）
  app.get('/', {
    schema: {
      querystring: {
        type: 'object',
        properties: {
          status: { type: 'string', enum: ['online', 'offline'] },
          search: { type: 'string' },
          groupId: { type: 'number' },
          year: { type: 'number' },
          month: { type: 'number' },
          page: { type: 'number', minimum: 1, default: 1 },
          limit: { type: 'number', minimum: 1, maximum: 500, default: 20 }
        }
      }
    }
  }, async (request: FastifyRequest<{
    Querystring: DeviceFilters
  }>, reply: FastifyReply) => {
    try {
      const filters = request.query
      const result = await DeviceModel.getDevices(filters)

      const response: ApiResponse = {
        success: true,
        message: '获取设备列表成功',
        data: result,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取设备列表失败')
      return reply.code(500).send({
        success: false,
        message: '获取设备列表失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取设备统计信息
  app.get('/stats', async (_request: FastifyRequest, reply: FastifyReply) => {
    try {
      const stats = await DeviceModel.getDeviceStats()

      const response: ApiResponse = {
        success: true,
        message: '获取统计信息成功',
        data: stats,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取统计信息失败')
      return reply.code(500).send({
        success: false,
        message: '获取统计信息失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取单个设备信息
  app.get('/:deviceId', {
    schema: {
      params: {
        type: 'object',
        properties: {
          deviceId: { type: 'string' }
        },
        required: ['deviceId']
      }
    }
  }, async (request: FastifyRequest<{
    Params: { deviceId: string }
  }>, reply: FastifyReply) => {
    try {
      const { deviceId } = request.params
      const device = await DeviceModel.getDeviceById(deviceId)

      if (!device) {
        return reply.code(404).send({
          success: false,
          message: '设备不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse<Device> = {
        success: true,
        message: '获取设备信息成功',
        data: device,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取设备信息失败')
      return reply.code(500).send({
        success: false,
        message: '获取设备信息失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 设备心跳上报（创建或更新设备）
  app.post('/heartbeat', {
    schema: {
      body: {
        type: 'object',
        required: ['device_id', 'cpu_usage', 'memory_usage', 'disk_usage', 'uptime'],
        properties: {
          device_id: { type: 'string' },
          device_name: { type: 'string' },
          ip_address: { type: 'string' },
          mac_address: { type: 'string' },
          system_info: { type: 'object' },
          cpu_usage: { type: 'number', minimum: 0, maximum: 100 },
          memory_usage: { type: 'number', minimum: 0, maximum: 100 },
          disk_usage: { type: 'number', minimum: 0, maximum: 100 },
          temperature: { type: 'number' },
          uptime: { type: 'number', minimum: 0 }
        }
      }
    }
  }, async (request: FastifyRequest<{
    Body: HeartbeatRequest
  }>, reply: FastifyReply) => {
    try {
      const heartbeatData = request.body
      
      // 获取客户端IP（如果未提供）
      if (!heartbeatData.ip_address) {
        heartbeatData.ip_address = request.ip
      }

      const device = await DeviceModel.upsertDevice(heartbeatData)

      // 广播设备更新给WebSocket客户端
      // TODO: 实现WebSocket广播

      const response: ApiResponse<Device> = {
        success: true,
        message: '设备心跳上报成功',
        data: device,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '设备心跳上报失败')
      return reply.code(500).send({
        success: false,
        message: '设备心跳上报失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 更新设备信息（需要认证）
  app.put<{
    Params: { deviceId: string }
    Body: {
      device_name?: string
      remarks?: string
      order_number?: number
      created_at?: string
    }
  }>('/:deviceId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          deviceId: { type: 'string' }
        },
        required: ['deviceId']
      },
      body: {
        type: 'object',
        properties: {
          device_name: { type: 'string' },
          remarks: { type: 'string' },
          order_number: { type: 'number' },
          created_at: { type: 'string' }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { deviceId } = request.params
      const updateData = request.body

      const device = await DeviceModel.updateDevice(deviceId, updateData)

      if (!device) {
        return reply.code(404).send({
          success: false,
          message: '设备不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse<Device> = {
        success: true,
        message: '设备信息更新成功',
        data: device,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '设备信息更新失败')
      return reply.code(500).send({
        success: false,
        message: '设备信息更新失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 删除设备（需要认证）
  app.delete<{
    Params: { deviceId: string }
  }>('/:deviceId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          deviceId: { type: 'string' }
        },
        required: ['deviceId']
      }
    }
  }, async (request, reply) => {
    try {
      const { deviceId } = request.params
      const success = await DeviceModel.deleteDevice(deviceId)

      if (!success) {
        return reply.code(404).send({
          success: false,
          message: '设备不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse = {
        success: true,
        message: '设备删除成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '设备删除失败')
      return reply.code(500).send({
        success: false,
        message: '设备删除失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 批量删除设备（需要认证）
  app.delete<{
    Body: { device_ids: string[] }
  }>('/', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['device_ids'],
        properties: {
          device_ids: {
            type: 'array',
            items: { type: 'string' },
            minItems: 1
          }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { device_ids } = request.body
      const deletedCount = await DeviceModel.batchDeleteDevices(device_ids)

      const response: ApiResponse = {
        success: true,
        message: `成功删除 ${deletedCount} 个设备`,
        data: { deletedCount },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '批量删除设备失败')
      return reply.code(500).send({
        success: false,
        message: '批量删除设备失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取设备心跳历史
  app.get('/:deviceId/heartbeats', {
    schema: {
      params: {
        type: 'object',
        properties: {
          deviceId: { type: 'string' }
        },
        required: ['deviceId']
      },
      querystring: {
        type: 'object',
        properties: {
          startDate: { type: 'string' },
          endDate: { type: 'string' },
          limit: { type: 'number', minimum: 1, maximum: 1000, default: 100 },
          page: { type: 'number', minimum: 1, default: 1 }
        }
      }
    }
  }, async (request: FastifyRequest<{
    Params: { deviceId: string }
    Querystring: {
      startDate?: string
      endDate?: string
      limit?: number
      page?: number
    }
  }>, reply: FastifyReply) => {
    try {
      const { deviceId } = request.params
      const options = request.query

      const result = await DeviceModel.getHeartbeatHistory(deviceId, options)

      const response: ApiResponse = {
        success: true,
        message: '获取心跳历史成功',
        data: result,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取心跳历史失败')
      return reply.code(500).send({
        success: false,
        message: '获取心跳历史失败',
        timestamp: new Date().toISOString()
      })
    }
  })
}