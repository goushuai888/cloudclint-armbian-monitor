import { query, queryOne, insert, update, deleteRecord } from '@/config/database.js'
import type { DeviceGroup, ApiResponse, PaginationMeta } from '@/types/index.js'
import dayjs from 'dayjs'

export class DeviceGroupModel {
  // 获取所有设备分组
  static async getAllGroups(): Promise<DeviceGroup[]> {
    const sql = `
      SELECT dg.*, 
             COUNT(d.id) as device_count
      FROM device_groups dg
      LEFT JOIN devices d ON d.group_id = dg.id
      GROUP BY dg.id
      ORDER BY dg.sort_order ASC, dg.created_at ASC
    `
    
    const groups = await query<DeviceGroup & { device_count: number }>(sql)
    return groups
  }

  // 获取分组列表（带分页）
  static async getGroups(options: {
    page?: number
    limit?: number
    search?: string
  } = {}): Promise<{
    groups: DeviceGroup[]
    pagination: PaginationMeta
  }> {
    const { page = 1, limit = 20, search } = options

    // 构建WHERE条件
    const whereConditions: string[] = []
    const params: any[] = []

    if (search) {
      whereConditions.push('(group_name LIKE ? OR group_description LIKE ?)')
      const searchTerm = `%${search}%`
      params.push(searchTerm, searchTerm)
    }

    const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : ''

    // 获取总数
    const countSql = `SELECT COUNT(*) as total FROM device_groups ${whereClause}`
    const [{ total }] = await query<{ total: number }>(countSql, params)

    // 计算分页
    const totalPages = Math.ceil(total / limit)
    const offset = (page - 1) * limit

    // 获取分组列表
    const groupsSql = `
      SELECT dg.*, 
             COUNT(d.id) as device_count
      FROM device_groups dg
      LEFT JOIN devices d ON d.group_id = dg.id
      ${whereClause}
      GROUP BY dg.id
      ORDER BY dg.sort_order ASC, dg.created_at ASC
      LIMIT ${limit} OFFSET ${offset}
    `
    const groups = await query<DeviceGroup & { device_count: number }>(groupsSql, params)

    return {
      groups,
      pagination: {
        page,
        limit,
        total,
        totalPages
      }
    }
  }

  // 根据ID获取单个分组
  static async getGroupById(groupId: number): Promise<DeviceGroup | null> {
    const sql = `
      SELECT dg.*, 
             COUNT(d.id) as device_count
      FROM device_groups dg
      LEFT JOIN devices d ON d.group_id = dg.id
      WHERE dg.id = ?
      GROUP BY dg.id
    `
    
    return await queryOne<DeviceGroup & { device_count: number }>(sql, [groupId])
  }

  // 创建设备分组
  static async createGroup(groupData: {
    group_name: string
    group_description?: string
    group_color?: string
    group_icon?: string
    sort_order?: number
  }): Promise<DeviceGroup> {
    const {
      group_name,
      group_description = null,
      group_color = '#1976d2',
      group_icon = 'devices',
      sort_order
    } = groupData

    // 如果没有指定排序，获取最大排序值+1
    let finalSortOrder = sort_order
    if (finalSortOrder === undefined) {
      const maxOrderResult = await queryOne<{ max_order: number }>(
        'SELECT COALESCE(MAX(sort_order), 0) as max_order FROM device_groups'
      )
      finalSortOrder = (maxOrderResult?.max_order || 0) + 1
    }

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const insertData = {
      group_name,
      group_description,
      group_color,
      group_icon,
      sort_order: finalSortOrder,
      is_default: false,
      created_at: now,
      updated_at: now
    }

    const groupId = await insert('device_groups', insertData)
    return await this.getGroupById(groupId) as DeviceGroup
  }

  // 更新设备分组
  static async updateGroup(
    groupId: number,
    updateData: {
      group_name?: string
      group_description?: string
      group_color?: string
      group_icon?: string
      sort_order?: number
    }
  ): Promise<DeviceGroup | null> {
    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const finalUpdateData = {
      ...updateData,
      updated_at: now
    }

    const affectedRows = await update('device_groups', finalUpdateData, 'id = ?', [groupId])
    
    if (affectedRows === 0) {
      return null
    }

    return await this.getGroupById(groupId)
  }

  // 删除设备分组
  static async deleteGroup(groupId: number): Promise<boolean> {
    // 检查是否为默认分组
    const group = await this.getGroupById(groupId)
    if (!group) return false
    
    if (group.is_default) {
      throw new Error('不能删除默认分组')
    }

    // 检查分组下是否有设备
    const deviceCount = await queryOne<{ count: number }>(
      'SELECT COUNT(*) as count FROM devices WHERE group_id = ?',
      [groupId]
    )

    if (deviceCount && deviceCount.count > 0) {
      throw new Error('分组下还有设备，无法删除')
    }

    const affectedRows = await deleteRecord('device_groups', 'id = ?', [groupId])
    return affectedRows > 0
  }

  // 批量删除设备分组
  static async batchDeleteGroups(groupIds: number[]): Promise<number> {
    if (groupIds.length === 0) return 0

    // 检查是否包含默认分组
    const defaultGroups = await query<DeviceGroup>(
      `SELECT id FROM device_groups WHERE id IN (${groupIds.map(() => '?').join(',')}) AND is_default = true`,
      groupIds
    )

    if (defaultGroups.length > 0) {
      throw new Error('不能删除默认分组')
    }

    // 检查分组下是否有设备
    const deviceCounts = await query<{ group_id: number; count: number }>(
      `SELECT group_id, COUNT(*) as count FROM devices WHERE group_id IN (${groupIds.map(() => '?').join(',')}) GROUP BY group_id`,
      groupIds
    )

    if (deviceCounts.length > 0) {
      throw new Error('部分分组下还有设备，无法删除')
    }

    const placeholders = groupIds.map(() => '?').join(',')
    const affectedRows = await deleteRecord('device_groups', `id IN (${placeholders})`, groupIds)
    return affectedRows
  }

  // 移动设备到分组
  static async moveDeviceToGroup(deviceId: string, groupId: number | null): Promise<boolean> {
    // 如果groupId不为null，检查分组是否存在
    if (groupId !== null) {
      const group = await this.getGroupById(groupId)
      if (!group) {
        throw new Error('目标分组不存在')
      }
    }

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const affectedRows = await update(
      'devices',
      { group_id: groupId, updated_at: now },
      'device_id = ?',
      [deviceId]
    )

    return affectedRows > 0
  }

  // 批量移动设备到分组
  static async batchMoveDevicesToGroup(deviceIds: string[], groupId: number | null): Promise<number> {
    if (deviceIds.length === 0) return 0

    // 如果groupId不为null，检查分组是否存在
    if (groupId !== null) {
      const group = await this.getGroupById(groupId)
      if (!group) {
        throw new Error('目标分组不存在')
      }
    }

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const placeholders = deviceIds.map(() => '?').join(',')
    const affectedRows = await update(
      'devices',
      { group_id: groupId, updated_at: now },
      `device_id IN (${placeholders})`,
      deviceIds
    )

    return affectedRows
  }

  // 更新分组排序
  static async updateGroupOrder(groupOrders: { id: number; sort_order: number }[]): Promise<boolean> {
    if (groupOrders.length === 0) return true

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    
    // 使用事务更新所有分组的排序
    for (const { id, sort_order } of groupOrders) {
      await update(
        'device_groups',
        { sort_order, updated_at: now },
        'id = ?',
        [id]
      )
    }

    return true
  }

  // 获取默认分组
  static async getDefaultGroup(): Promise<DeviceGroup | null> {
    const sql = 'SELECT * FROM device_groups WHERE is_default = true LIMIT 1'
    return await queryOne<DeviceGroup>(sql)
  }

  // 设置默认分组
  static async setDefaultGroup(groupId: number): Promise<boolean> {
    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    
    // 先取消所有分组的默认状态
    await update('device_groups', { is_default: false, updated_at: now }, '1=1')
    
    // 设置指定分组为默认
    const affectedRows = await update(
      'device_groups',
      { is_default: true, updated_at: now },
      'id = ?',
      [groupId]
    )

    return affectedRows > 0
  }
}