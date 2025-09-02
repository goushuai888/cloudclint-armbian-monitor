import { FastifyInstance } from 'fastify'
import { SystemConfigModel } from '@/models/SystemConfig.js'
import type { SystemConfig, ApiResponse } from '@/types/index.js'

// 系统配置路由插件
export default async function configRoutes(app: FastifyInstance) {
  
  // 获取所有配置
  app.get('/all', {
    preHandler: [app.authenticate]
  }, async (request, reply) => {
    try {
      const configs = await SystemConfigModel.getAllConfigs()

      const response: ApiResponse = {
        success: true,
        message: '获取系统配置成功',
        data: configs,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取系统配置失败')
      return reply.code(500).send({
        success: false,
        message: '获取系统配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取配置列表（带分页）
  app.get<{
    Querystring: {
      page?: number
      limit?: number
      search?: string
      category?: string
    }
  }>('/', {
    preHandler: [app.authenticate],
    schema: {
      querystring: {
        type: 'object',
        properties: {
          page: { type: 'number', minimum: 1, default: 1 },
          limit: { type: 'number', minimum: 1, maximum: 100, default: 20 },
          search: { type: 'string' },
          category: { type: 'string' }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const options = request.query
      const result = await SystemConfigModel.getConfigs(options)

      const response: ApiResponse = {
        success: true,
        message: '获取配置列表成功',
        data: result,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取配置列表失败')
      return reply.code(500).send({
        success: false,
        message: '获取配置列表失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 根据键获取配置
  app.get<{
    Params: { key: string }
  }>('/key/:key', {
    schema: {
      params: {
        type: 'object',
        properties: {
          key: { type: 'string' }
        },
        required: ['key']
      }
    }
  }, async (request, reply) => {
    try {
      const { key } = request.params
      const config = await SystemConfigModel.getConfigByKey(key)

      if (!config) {
        return reply.code(404).send({
          success: false,
          message: '配置项不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse<SystemConfig> = {
        success: true,
        message: '获取配置成功',
        data: config,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取配置失败')
      return reply.code(500).send({
        success: false,
        message: '获取配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 根据ID获取配置
  app.get<{
    Params: { configId: number }
  }>('/:configId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          configId: { type: 'number' }
        },
        required: ['configId']
      }
    }
  }, async (request, reply) => {
    try {
      const { configId } = request.params
      const config = await SystemConfigModel.getConfigById(configId)

      if (!config) {
        return reply.code(404).send({
          success: false,
          message: '配置项不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse<SystemConfig> = {
        success: true,
        message: '获取配置成功',
        data: config,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取配置失败')
      return reply.code(500).send({
        success: false,
        message: '获取配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 创建配置
  app.post<{
    Body: {
      config_key: string
      config_value: string
      config_description?: string
      config_type?: 'string' | 'number' | 'boolean' | 'json'
      is_public?: boolean
      category?: string
    }
  }>('/', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['config_key', 'config_value'],
        properties: {
          config_key: { type: 'string', minLength: 1, maxLength: 100 },
          config_value: { type: 'string' },
          config_description: { type: 'string', maxLength: 500 },
          config_type: { type: 'string', enum: ['string', 'number', 'boolean', 'json'], default: 'string' },
          is_public: { type: 'boolean', default: false },
          category: { type: 'string', maxLength: 50 }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const configData = request.body
      const config = await SystemConfigModel.createConfig(configData)

      const response: ApiResponse<SystemConfig> = {
        success: true,
        message: '创建配置成功',
        data: config,
        timestamp: new Date().toISOString()
      }

      return reply.code(201).send(response)
    } catch (error) {
      app.log.error(error, '创建配置失败')
      
      if (error instanceof Error && error.message.includes('已存在')) {
        return reply.code(409).send({
          success: false,
          message: error.message,
          timestamp: new Date().toISOString()
        })
      }

      return reply.code(500).send({
        success: false,
        message: '创建配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 更新配置
  app.put<{
    Params: { configId: number }
    Body: {
      config_value?: string
      config_description?: string
      config_type?: 'string' | 'number' | 'boolean' | 'json'
      is_public?: boolean
      category?: string
    }
  }>('/:configId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          configId: { type: 'number' }
        },
        required: ['configId']
      },
      body: {
        type: 'object',
        properties: {
          config_value: { type: 'string' },
          config_description: { type: 'string', maxLength: 500 },
          config_type: { type: 'string', enum: ['string', 'number', 'boolean', 'json'] },
          is_public: { type: 'boolean' },
          category: { type: 'string', maxLength: 50 }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { configId } = request.params
      const updateData = request.body

      const config = await SystemConfigModel.updateConfigById(configId, updateData)

      if (!config) {
        return reply.code(404).send({
          success: false,
          message: '配置项不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse<SystemConfig> = {
        success: true,
        message: '更新配置成功',
        data: config,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '更新配置失败')
      return reply.code(500).send({
        success: false,
        message: '更新配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 设置配置值（根据键）
  app.put<{
    Params: { key: string }
    Body: {
      value: string
      description?: string
    }
  }>('/key/:key', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          key: { type: 'string' }
        },
        required: ['key']
      },
      body: {
        type: 'object',
        required: ['value'],
        properties: {
          value: { type: 'string' },
          description: { type: 'string', maxLength: 500 }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { key } = request.params
      const { value, description } = request.body

      const config = await SystemConfigModel.setConfigValue(key, value, description)

      const response: ApiResponse<SystemConfig> = {
        success: true,
        message: '设置配置成功',
        data: config,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '设置配置失败')
      return reply.code(500).send({
        success: false,
        message: '设置配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 删除配置
  app.delete<{
    Params: { configId: number }
  }>('/:configId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          configId: { type: 'number' }
        },
        required: ['configId']
      }
    }
  }, async (request, reply) => {
    try {
      const { configId } = request.params
      const success = await SystemConfigModel.deleteConfigById(configId)

      if (!success) {
        return reply.code(404).send({
          success: false,
          message: '配置项不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse = {
        success: true,
        message: '删除配置成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '删除配置失败')
      return reply.code(500).send({
        success: false,
        message: '删除配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 批量删除配置
  app.delete<{
    Body: { config_ids: number[] }
  }>('/', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['config_ids'],
        properties: {
          config_ids: {
            type: 'array',
            items: { type: 'number' },
            minItems: 1
          }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { config_ids } = request.body
      const deletedCount = await SystemConfigModel.batchDeleteConfigs(config_ids)

      const response: ApiResponse = {
        success: true,
        message: `成功删除 ${deletedCount} 个配置项`,
        data: { deletedCount },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '批量删除配置失败')
      return reply.code(500).send({
        success: false,
        message: '批量删除配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 批量更新配置
  app.put<{
    Body: {
      configs: {
        id?: number
        key?: string
        value: string
        description?: string
      }[]
    }
  }>('/batch', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['configs'],
        properties: {
          configs: {
            type: 'array',
            items: {
              type: 'object',
              required: ['value'],
              properties: {
                id: { type: 'number' },
                key: { type: 'string' },
                value: { type: 'string' },
                description: { type: 'string', maxLength: 500 }
              }
            },
            minItems: 1
          }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { configs } = request.body
      // 转换配置格式
      const formattedConfigs = configs.map(config => {
        const formatted: {
          config_key: string
          config_value: string
          config_description?: string
        } = {
          config_key: config.key || '',
          config_value: config.value
        }
        if (config.description) {
          formatted.config_description = config.description
        }
        return formatted
      })
      const updatedConfigs = await SystemConfigModel.batchUpdateConfigs(formattedConfigs)
      const updatedCount = updatedConfigs.length

      const response: ApiResponse = {
        success: true,
        message: `成功更新 ${updatedCount} 个配置项`,
        data: { updatedCount },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '批量更新配置失败')
      return reply.code(500).send({
        success: false,
        message: '批量更新配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 根据前缀获取配置
  app.get<{
    Params: { prefix: string }
  }>('/prefix/:prefix', {
    schema: {
      params: {
        type: 'object',
        properties: {
          prefix: { type: 'string' }
        },
        required: ['prefix']
      }
    }
  }, async (request, reply) => {
    try {
      const { prefix } = request.params
      const configs = await SystemConfigModel.getConfigsByPrefix(prefix)

      const response: ApiResponse = {
        success: true,
        message: '获取配置成功',
        data: configs,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取配置失败')
      return reply.code(500).send({
        success: false,
        message: '获取配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取默认配置
  app.get('/defaults/all', {
    preHandler: [app.authenticate]
  }, async (request, reply) => {
    try {
      const defaults = await SystemConfigModel.getDefaultConfigs()

      const response: ApiResponse = {
        success: true,
        message: '获取默认配置成功',
        data: defaults,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取默认配置失败')
      return reply.code(500).send({
        success: false,
        message: '获取默认配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 初始化默认配置
  app.post('/defaults/init', {
    preHandler: [app.authenticate]
  }, async (request, reply) => {
    try {
      await SystemConfigModel.initializeDefaultConfigs()

      const response: ApiResponse = {
        success: true,
        message: '初始化默认配置成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '初始化默认配置失败')
      return reply.code(500).send({
        success: false,
        message: '初始化默认配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 导出配置
  app.get('/export', {
    preHandler: [app.authenticate]
  }, async (request, reply) => {
    try {
      const exportData = await SystemConfigModel.exportConfigs()

      const response: ApiResponse = {
        success: true,
        message: '导出配置成功',
        data: exportData,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '导出配置失败')
      return reply.code(500).send({
        success: false,
        message: '导出配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 导入配置
  app.post<{
    Body: {
      configs: SystemConfig[]
      overwrite?: boolean
    }
  }>('/import', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['configs'],
        properties: {
          configs: {
            type: 'array',
            items: {
              type: 'object',
              required: ['config_key', 'config_value'],
              properties: {
                config_key: { type: 'string' },
                config_value: { type: 'string' },
                config_description: { type: 'string' },
                config_type: { type: 'string', enum: ['string', 'number', 'boolean', 'json'] },
                is_public: { type: 'boolean' },
                category: { type: 'string' }
              }
            }
          },
          overwrite: { type: 'boolean', default: false }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { configs, overwrite = false } = request.body
      // 转换配置格式为键值对
      const configData: Record<string, string> = {}
      configs.forEach(config => {
        configData[config.config_key] = config.config_value
      })
      const importedConfigs = await SystemConfigModel.importConfigs(configData)
      const importedCount = importedConfigs.length

      const response: ApiResponse = {
        success: true,
        message: `成功导入 ${importedCount} 个配置项`,
        data: { importedCount },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '导入配置失败')
      return reply.code(500).send({
        success: false,
        message: '导入配置失败',
        timestamp: new Date().toISOString()
      })
    }
  })
}