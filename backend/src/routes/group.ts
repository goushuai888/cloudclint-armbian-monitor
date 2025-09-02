import { FastifyInstance, FastifyRequest, FastifyReply } from 'fastify'
import { DeviceGroupModel } from '@/models/DeviceGroup.js'
import type { DeviceGroup, ApiResponse } from '@/types/index.js'

// 设备分组路由插件
export default async function groupRoutes(app: FastifyInstance) {
  
  // 获取所有设备分组
  app.get('/all', async (request: FastifyRequest, reply: FastifyReply) => {
    try {
      const groups = await DeviceGroupModel.getAllGroups()

      const response: ApiResponse = {
        success: true,
        message: '获取设备分组成功',
        data: groups,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取设备分组失败')
      return reply.code(500).send({
        success: false,
        message: '获取设备分组失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取设备分组列表（带分页）
  app.get('/', {
    schema: {
      querystring: {
        type: 'object',
        properties: {
          page: { type: 'number', minimum: 1, default: 1 },
          limit: { type: 'number', minimum: 1, maximum: 100, default: 20 },
          search: { type: 'string' }
        }
      }
    }
  }, async (request: FastifyRequest<{
    Querystring: {
      page?: number
      limit?: number
      search?: string
    }
  }>, reply: FastifyReply) => {
    try {
      const options = request.query
      const result = await DeviceGroupModel.getGroups(options)

      const response: ApiResponse = {
        success: true,
        message: '获取设备分组列表成功',
        data: result,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取设备分组列表失败')
      return reply.code(500).send({
        success: false,
        message: '获取设备分组列表失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取单个设备分组
  app.get<{
    Params: { groupId: number }
  }>('/:groupId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          groupId: { type: 'number' }
        },
        required: ['groupId']
      }
    }
  }, async (request, reply) => {
    try {
      const { groupId } = request.params
      const group = await DeviceGroupModel.getGroupById(groupId)

      if (!group) {
        return reply.code(404).send({
          success: false,
          message: '设备分组不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse<DeviceGroup> = {
        success: true,
        message: '获取设备分组信息成功',
        data: group,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取设备分组信息失败')
      return reply.code(500).send({
        success: false,
        message: '获取设备分组信息失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 创建设备分组
  app.post<{
    Body: {
      group_name: string
      group_description?: string
      group_color?: string
      group_icon?: string
      sort_order?: number
    }
  }>('/', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['group_name'],
        properties: {
          group_name: { type: 'string', minLength: 1, maxLength: 100 },
          group_description: { type: 'string', maxLength: 500 },
          group_color: { type: 'string', pattern: '^#[0-9A-Fa-f]{6}$' },
          group_icon: { type: 'string', maxLength: 50 },
          sort_order: { type: 'number', minimum: 0 }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const groupData = request.body
      const group = await DeviceGroupModel.createGroup(groupData)

      const response: ApiResponse<DeviceGroup> = {
        success: true,
        message: '创建设备分组成功',
        data: group,
        timestamp: new Date().toISOString()
      }

      return reply.code(201).send(response)
    } catch (error) {
      app.log.error(error, '创建设备分组失败')
      
      if (error instanceof Error && error.message.includes('已存在')) {
        return reply.code(409).send({
          success: false,
          message: error.message,
          timestamp: new Date().toISOString()
        })
      }

      return reply.code(500).send({
        success: false,
        message: '创建设备分组失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 更新设备分组
  app.put<{
    Params: { groupId: number }
    Body: {
      group_name?: string
      group_description?: string
      group_color?: string
      group_icon?: string
      sort_order?: number
    }
  }>('/:groupId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          groupId: { type: 'number' }
        },
        required: ['groupId']
      },
      body: {
        type: 'object',
        properties: {
          group_name: { type: 'string', minLength: 1, maxLength: 100 },
          group_description: { type: 'string', maxLength: 500 },
          group_color: { type: 'string', pattern: '^#[0-9A-Fa-f]{6}$' },
          group_icon: { type: 'string', maxLength: 50 },
          sort_order: { type: 'number', minimum: 0 }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { groupId } = request.params
      const updateData = request.body

      const group = await DeviceGroupModel.updateGroup(groupId, updateData)

      if (!group) {
        return reply.code(404).send({
          success: false,
          message: '设备分组不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse<DeviceGroup> = {
        success: true,
        message: '更新设备分组成功',
        data: group,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '更新设备分组失败')
      return reply.code(500).send({
        success: false,
        message: '更新设备分组失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 删除设备分组
  app.delete<{
    Params: { groupId: number }
  }>('/:groupId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          groupId: { type: 'number' }
        },
        required: ['groupId']
      }
    }
  }, async (request, reply) => {
    try {
      const { groupId } = request.params
      const success = await DeviceGroupModel.deleteGroup(groupId)

      if (!success) {
        return reply.code(404).send({
          success: false,
          message: '设备分组不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse = {
        success: true,
        message: '删除设备分组成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '删除设备分组失败')
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          timestamp: new Date().toISOString()
        })
      }

      return reply.code(500).send({
        success: false,
        message: '删除设备分组失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 批量删除设备分组
  app.delete<{
    Body: { group_ids: number[] }
  }>('/', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['group_ids'],
        properties: {
          group_ids: {
            type: 'array',
            items: { type: 'number' },
            minItems: 1
          }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { group_ids } = request.body
      const deletedCount = await DeviceGroupModel.batchDeleteGroups(group_ids)

      const response: ApiResponse = {
        success: true,
        message: `成功删除 ${deletedCount} 个设备分组`,
        data: { deletedCount },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '批量删除设备分组失败')
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          timestamp: new Date().toISOString()
        })
      }

      return reply.code(500).send({
        success: false,
        message: '批量删除设备分组失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 移动设备到分组
  app.post<{
    Body: {
      device_id: string
      group_id: number | null
    }
  }>('/move-device', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['device_id'],
        properties: {
          device_id: { type: 'string' },
          group_id: { type: ['number', 'null'] }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { device_id, group_id } = request.body
      const success = await DeviceGroupModel.moveDeviceToGroup(device_id, group_id)

      if (!success) {
        return reply.code(404).send({
          success: false,
          message: '设备不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse = {
        success: true,
        message: '移动设备成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '移动设备失败')
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          timestamp: new Date().toISOString()
        })
      }

      return reply.code(500).send({
        success: false,
        message: '移动设备失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 批量移动设备到分组
  app.post<{
    Body: {
      device_ids: string[]
      group_id: number | null
    }
  }>('/batch-move-devices', {
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
          },
          group_id: { type: ['number', 'null'] }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { device_ids, group_id } = request.body
      const movedCount = await DeviceGroupModel.batchMoveDevicesToGroup(device_ids, group_id)

      const response: ApiResponse = {
        success: true,
        message: `成功移动 ${movedCount} 个设备`,
        data: { movedCount },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '批量移动设备失败')
      
      if (error instanceof Error) {
        return reply.code(400).send({
          success: false,
          message: error.message,
          timestamp: new Date().toISOString()
        })
      }

      return reply.code(500).send({
        success: false,
        message: '批量移动设备失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 更新分组排序
  app.put<{
    Body: {
      group_orders: { id: number; sort_order: number }[]
    }
  }>('/order', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['group_orders'],
        properties: {
          group_orders: {
            type: 'array',
            items: {
              type: 'object',
              required: ['id', 'sort_order'],
              properties: {
                id: { type: 'number' },
                sort_order: { type: 'number', minimum: 0 }
              }
            },
            minItems: 1
          }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { group_orders } = request.body
      const success = await DeviceGroupModel.updateGroupOrder(group_orders)

      const response: ApiResponse = {
        success: true,
        message: '更新分组排序成功',
        data: { success },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '更新分组排序失败')
      return reply.code(500).send({
        success: false,
        message: '更新分组排序失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取默认分组
  app.get('/default/info', async (request: FastifyRequest, reply: FastifyReply) => {
    try {
      const defaultGroup = await DeviceGroupModel.getDefaultGroup()

      const response: ApiResponse = {
        success: true,
        message: '获取默认分组成功',
        data: defaultGroup,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取默认分组失败')
      return reply.code(500).send({
        success: false,
        message: '获取默认分组失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 设置默认分组
  app.put<{
    Params: { groupId: number }
  }>('/default/:groupId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          groupId: { type: 'number' }
        },
        required: ['groupId']
      }
    }
  }, async (request, reply) => {
    try {
      const { groupId } = request.params
      const success = await DeviceGroupModel.setDefaultGroup(groupId)

      if (!success) {
        return reply.code(404).send({
          success: false,
          message: '设备分组不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse = {
        success: true,
        message: '设置默认分组成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '设置默认分组失败')
      return reply.code(500).send({
        success: false,
        message: '设置默认分组失败',
        timestamp: new Date().toISOString()
      })
    }
  })
}