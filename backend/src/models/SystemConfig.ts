import { query, queryOne, insert, update, deleteRecord } from '@/config/database.js'
import type { SystemConfig, PaginationMeta } from '@/types/index.js'
import dayjs from 'dayjs'

export class SystemConfigModel {
  // 获取所有系统配置
  static async getAllConfigs(): Promise<SystemConfig[]> {
    const sql = 'SELECT * FROM system_configs ORDER BY config_key ASC'
    return await query<SystemConfig>(sql)
  }

  // 获取配置列表（带分页）
  static async getConfigs(options: {
    page?: number
    limit?: number
    search?: string
  } = {}): Promise<{
    configs: SystemConfig[]
    pagination: PaginationMeta
  }> {
    const { page = 1, limit = 20, search } = options

    // 构建WHERE条件
    const whereConditions: string[] = []
    const params: any[] = []

    if (search) {
      whereConditions.push('(config_key LIKE ? OR config_description LIKE ?)')
      const searchTerm = `%${search}%`
      params.push(searchTerm, searchTerm)
    }

    const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : ''

    // 获取总数
    const countSql = `SELECT COUNT(*) as total FROM system_configs ${whereClause}`
    const [{ total }] = await query<{ total: number }>(countSql, params)

    // 计算分页
    const totalPages = Math.ceil(total / limit)
    const offset = (page - 1) * limit

    // 获取配置列表
    const configsSql = `
      SELECT *
      FROM system_configs
      ${whereClause}
      ORDER BY config_key ASC
      LIMIT ${limit} OFFSET ${offset}
    `
    const configs = await query<SystemConfig>(configsSql, params)

    return {
      configs,
      pagination: {
        page,
        limit,
        total,
        totalPages
      }
    }
  }

  // 根据配置键获取配置
  static async getConfigByKey(configKey: string): Promise<SystemConfig | null> {
    const sql = 'SELECT * FROM system_configs WHERE config_key = ?'
    return await queryOne<SystemConfig>(sql, [configKey])
  }

  // 根据ID获取配置
  static async getConfigById(configId: number): Promise<SystemConfig | null> {
    const sql = 'SELECT * FROM system_configs WHERE id = ?'
    return await queryOne<SystemConfig>(sql, [configId])
  }

  // 获取配置值（仅返回值）
  static async getConfigValue(configKey: string): Promise<string | null> {
    const config = await this.getConfigByKey(configKey)
    return config ? config.config_value : null
  }

  // 创建系统配置
  static async createConfig(configData: {
    config_key: string
    config_value: string
    config_description?: string
  }): Promise<SystemConfig> {
    const {
      config_key,
      config_value,
      config_description = null
    } = configData

    // 检查配置键是否已存在
    const existingConfig = await this.getConfigByKey(config_key)
    if (existingConfig) {
      throw new Error('配置键已存在')
    }

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const insertData = {
      config_key,
      config_value,
      config_description,
      updated_at: now
    }

    const configId = await insert('system_configs', insertData)
    return await this.getConfigById(configId) as SystemConfig
  }

  // 更新系统配置
  static async updateConfig(
    configKey: string,
    updateData: {
      config_value?: string
      config_description?: string
    }
  ): Promise<SystemConfig | null> {
    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const finalUpdateData = {
      ...updateData,
      updated_at: now
    }

    const affectedRows = await update('system_configs', finalUpdateData, 'config_key = ?', [configKey])
    
    if (affectedRows === 0) {
      return null
    }

    return await this.getConfigByKey(configKey)
  }

  // 根据ID更新系统配置
  static async updateConfigById(
    configId: number,
    updateData: {
      config_key?: string
      config_value?: string
      config_description?: string
    }
  ): Promise<SystemConfig | null> {
    const { config_key } = updateData

    // 如果要更新配置键，检查是否已存在
    if (config_key) {
      const existingConfig = await this.getConfigByKey(config_key)
      if (existingConfig && existingConfig.id !== configId) {
        throw new Error('配置键已存在')
      }
    }

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const finalUpdateData = {
      ...updateData,
      updated_at: now
    }

    const affectedRows = await update('system_configs', finalUpdateData, 'id = ?', [configId])
    
    if (affectedRows === 0) {
      return null
    }

    return await this.getConfigById(configId)
  }

  // 设置配置值（如果不存在则创建）
  static async setConfigValue(
    configKey: string,
    configValue: string,
    configDescription?: string
  ): Promise<SystemConfig> {
    const existingConfig = await this.getConfigByKey(configKey)
    
    if (existingConfig) {
      // 更新现有配置
      const updateData: { config_value: string; config_description?: string } = {
         config_value: configValue
       }
       const description = configDescription || existingConfig.config_description
       if (description) {
         updateData.config_description = description
       }
      return await this.updateConfig(configKey, updateData) as SystemConfig
    } else {
      // 创建新配置
      const createData: { config_key: string; config_value: string; config_description?: string } = {
        config_key: configKey,
        config_value: configValue
      }
      if (configDescription) {
        createData.config_description = configDescription
      }
      return await this.createConfig(createData)
    }
  }

  // 删除系统配置
  static async deleteConfig(configKey: string): Promise<boolean> {
    const affectedRows = await deleteRecord('system_configs', 'config_key = ?', [configKey])
    return affectedRows > 0
  }

  // 根据ID删除系统配置
  static async deleteConfigById(configId: number): Promise<boolean> {
    const affectedRows = await deleteRecord('system_configs', 'id = ?', [configId])
    return affectedRows > 0
  }

  // 批量删除系统配置
  static async batchDeleteConfigs(configIds: number[]): Promise<number> {
    if (configIds.length === 0) return 0

    const placeholders = configIds.map(() => '?').join(',')
    const affectedRows = await deleteRecord('system_configs', `id IN (${placeholders})`, configIds)
    return affectedRows
  }

  // 批量更新配置
  static async batchUpdateConfigs(configs: {
    config_key: string
    config_value: string
    config_description?: string
  }[]): Promise<SystemConfig[]> {
    const results: SystemConfig[] = []
    
    for (const config of configs) {
      const result = await this.setConfigValue(
        config.config_key,
        config.config_value,
        config.config_description
      )
      results.push(result)
    }

    return results
  }

  // 获取配置分组（根据配置键前缀）
  static async getConfigsByPrefix(prefix: string): Promise<SystemConfig[]> {
    const sql = 'SELECT * FROM system_configs WHERE config_key LIKE ? ORDER BY config_key ASC'
    return await query<SystemConfig>(sql, [`${prefix}%`])
  }

  // 获取系统默认配置
  static async getDefaultConfigs(): Promise<Record<string, string>> {
    const defaultConfigs = {
      'system.name': 'Armbian Box Monitor',
      'system.version': '1.0.0',
      'system.description': 'Armbian设备监控系统',
      'heartbeat.timeout': '300',
      'heartbeat.cleanup_days': '30',
      'websocket.heartbeat_interval': '30',
      'logging.retention_days': '90',
      'ui.theme': 'light',
      'ui.language': 'zh-CN',
      'notification.email_enabled': 'false',
      'notification.webhook_enabled': 'false',
      'security.session_timeout': '3600',
      'security.max_login_attempts': '5'
    }

    return defaultConfigs
  }

  // 初始化默认配置
  static async initializeDefaultConfigs(): Promise<void> {
    const defaultConfigs = await this.getDefaultConfigs()
    
    for (const [key, value] of Object.entries(defaultConfigs)) {
      const existingConfig = await this.getConfigByKey(key)
      if (!existingConfig) {
        await this.createConfig({
          config_key: key,
          config_value: value,
          config_description: `系统默认配置: ${key}`
        })
      }
    }
  }

  // 导出配置
  static async exportConfigs(): Promise<Record<string, string>> {
    const configs = await this.getAllConfigs()
    const result: Record<string, string> = {}
    
    for (const config of configs) {
      result[config.config_key] = config.config_value
    }

    return result
  }

  // 导入配置
  static async importConfigs(configData: Record<string, string>): Promise<SystemConfig[]> {
    const results: SystemConfig[] = []
    
    for (const [key, value] of Object.entries(configData)) {
      const result = await this.setConfigValue(key, value)
      results.push(result)
    }

    return results
  }
}