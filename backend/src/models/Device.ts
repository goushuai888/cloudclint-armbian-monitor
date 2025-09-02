import { query, queryOne, insert, update, deleteRecord } from '@/config/database.js'
import type { Device, DeviceFilters, PaginationMeta, DeviceStats } from '@/types/index.js'
import dayjs from 'dayjs'

export class DeviceModel {
  // 获取设备列表（带分页和筛选）
  static async getDevices(filters: DeviceFilters = {}): Promise<{
    devices: Device[]
    pagination: PaginationMeta
    stats: DeviceStats
  }> {
    const { 
      status, 
      search, 
      groupId: _groupId, 
      year, 
      month, 
      page = 1, 
      limit = 20 
    } = filters

    // 构建WHERE条件
    const whereConditions: string[] = []
    const params: any[] = []

    // 状态筛选
    if (status) {
      if (status === 'online') {
        whereConditions.push('last_heartbeat IS NOT NULL AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE)')
      } else if (status === 'offline') {
        whereConditions.push('last_heartbeat IS NULL OR last_heartbeat <= DATE_SUB(NOW(), INTERVAL 5 MINUTE)')
      }
    }

    // 搜索筛选
    if (search) {
      whereConditions.push('(device_name LIKE ? OR device_id LIKE ? OR ip_address LIKE ? OR remarks LIKE ? OR order_number LIKE ?)')
      const searchTerm = `%${search}%`
      params.push(searchTerm, searchTerm, searchTerm, searchTerm, searchTerm)
    }

    // 分组筛选（暂时禁用，数据库中devices表没有group_id字段）
    // if (groupId) {
    //   whereConditions.push('group_id = ?')
    //   params.push(groupId)
    // }

    // 时间筛选
    if (year) {
      if (month) {
        whereConditions.push('YEAR(created_at) = ? AND MONTH(created_at) = ?')
        params.push(year, month)
      } else {
        whereConditions.push('YEAR(created_at) = ?')
        params.push(year)
      }
    }

    const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : ''

    // 获取总数
    const countSql = `
      SELECT COUNT(*) as total 
      FROM devices 
      ${whereClause}
    `
    const [{ total }] = await query<{ total: number }>(countSql, params)

    // 计算分页
    const totalPages = Math.ceil(total / limit)
    const offset = (page - 1) * limit

    // 获取设备列表
    const devicesSql = `
      SELECT d.*, 
             CASE 
               WHEN d.last_heartbeat IS NOT NULL AND d.last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
               THEN 'online' 
               ELSE 'offline' 
             END as calculated_status
      FROM devices d
      ${whereClause}
      ORDER BY d.order_number DESC, d.created_at DESC
      LIMIT ${limit} OFFSET ${offset}
    `
    const devices = await query<Device>(devicesSql, params)

    // 处理系统信息JSON解析
    const processedDevices = devices.map(device => ({
      ...device,
      system_info: typeof device.system_info === 'string' 
        ? JSON.parse(device.system_info || '{}') 
        : device.system_info,
      status: device.status as 'online' | 'offline' | 'warning'
    }))

    // 获取统计信息
    const stats = await this.getDeviceStats()

    return {
      devices: processedDevices,
      pagination: {
        page,
        limit,
        total,
        totalPages
      },
      stats
    }
  }

  // 获取设备统计信息
  static async getDeviceStats(): Promise<DeviceStats> {
    const sql = `
      SELECT 
        COUNT(*) as total,
        SUM(CASE 
          WHEN last_heartbeat IS NOT NULL AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
          THEN 1 
          ELSE 0 
        END) as online,
        SUM(CASE 
          WHEN last_heartbeat IS NULL OR last_heartbeat <= DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
          THEN 1 
          ELSE 0 
        END) as offline
      FROM devices
    `
    
    const [result] = await query<{
      total: string | number
      online: string | number
      offline: string | number
    }>(sql)

    // 确保转换为数字类型（MySQL bigNumberStrings配置可能返回字符串）
    const total = Number(result.total)
    const online = Number(result.online)
    const offline = Number(result.offline)
    const onlineRate = total > 0 ? Math.round((online / total) * 100) : 0

    return {
      total,
      online,
      offline,
      onlineRate
    }
  }

  // 根据设备ID获取单个设备
  static async getDeviceById(deviceId: string): Promise<Device | null> {
    const sql = `
      SELECT d.*, 
             CASE 
               WHEN d.last_heartbeat IS NOT NULL AND d.last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
               THEN 'online' 
               ELSE 'offline' 
             END as status
      FROM devices d
      WHERE d.device_id = ?
    `
    
    const device = await queryOne<Device>(sql, [deviceId])
    
    if (!device) return null

    // 处理系统信息JSON解析
    return {
      ...device,
      system_info: typeof device.system_info === 'string' 
        ? JSON.parse(device.system_info || '{}') 
        : device.system_info
    }
  }

  // 创建或更新设备（心跳上报）
  static async upsertDevice(deviceData: {
    device_id: string
    device_name?: string
    ip_address?: string
    mac_address?: string
    system_info?: any
    cpu_usage: number
    memory_usage: number
    disk_usage: number
    temperature?: number
    uptime: number
  }): Promise<Device> {
    const {
      device_id,
      device_name,
      ip_address,
      mac_address,
      system_info,
      cpu_usage,
      memory_usage,
      disk_usage,
      temperature,
      uptime
    } = deviceData

    // 检查设备是否存在
    const existingDevice = await this.getDeviceById(device_id)
    
    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const systemInfoJson = system_info ? JSON.stringify(system_info) : null

    if (existingDevice) {
      // 更新现有设备
      const updateData: any = {
        cpu_usage,
        memory_usage,
        disk_usage,
        uptime,
        last_heartbeat: now,
        updated_at: now
      }

      if (ip_address) updateData.ip_address = ip_address
      if (mac_address) updateData.mac_address = mac_address
      if (systemInfoJson) updateData.system_info = systemInfoJson
      if (temperature !== undefined) updateData.temperature = temperature
      if (device_name && device_name !== existingDevice.device_name) {
        updateData.device_name = device_name
      }

      await update('devices', updateData, 'device_id = ?', [device_id])
      
      // 记录心跳日志
      await this.createHeartbeatLog({
        device_id,
        cpu_usage,
        memory_usage,
        disk_usage,
        temperature: temperature ?? null,
        uptime,
        heartbeat_time: now
      })

      return await this.getDeviceById(device_id) as Device
    } else {
      // 创建新设备
      const insertData = {
        device_id,
        device_name: device_name || device_id,
        ip_address: ip_address || null,
        mac_address: mac_address || null,
        system_info: systemInfoJson,
        cpu_usage,
        memory_usage,
        disk_usage,
        temperature: temperature || null,
        uptime,
        last_heartbeat: now,
        created_at: now,
        updated_at: now,
        order_number: 0
      }

      await insert('devices', insertData)
      
      // 记录心跳日志
      await this.createHeartbeatLog({
        device_id,
        cpu_usage,
        memory_usage,
        disk_usage,
        temperature: temperature ?? null,
        uptime,
        heartbeat_time: now
      })

      return await this.getDeviceById(device_id) as Device
    }
  }

  // 更新设备信息
  static async updateDevice(
    deviceId: string, 
    updateData: {
      device_name?: string
      remarks?: string
      order_number?: number
      created_at?: string
    }
  ): Promise<Device | null> {
    const data = {
      ...updateData,
      updated_at: dayjs().format('YYYY-MM-DD HH:mm:ss')
    }

    const affectedRows = await update('devices', data, 'device_id = ?', [deviceId])
    
    if (affectedRows === 0) {
      return null
    }

    return await this.getDeviceById(deviceId)
  }

  // 删除设备
  static async deleteDevice(deviceId: string): Promise<boolean> {
    // 先获取设备信息用于备份
    const device = await this.getDeviceById(deviceId)
    if (!device) return false

    // 检查备份表中是否已存在该设备记录
    const existingBackup = await queryOne(
      'SELECT device_id FROM device_backup WHERE device_id = ?',
      [deviceId]
    )

    // 如果备份记录不存在，则创建备份
    if (!existingBackup) {
      const backupData = {
        device_id: device.device_id,
        device_name: device.device_name,
        remarks: device.remarks || null,
        order_number: device.order_number,
        deleted_at: dayjs().format('YYYY-MM-DD HH:mm:ss')
      }

      await insert('device_backup', backupData)
    }

    // 删除设备
    const affectedRows = await deleteRecord('devices', 'device_id = ?', [deviceId])
    
    return affectedRows > 0
  }

  // 批量删除设备
  static async batchDeleteDevices(deviceIds: string[]): Promise<number> {
    if (deviceIds.length === 0) return 0

    let deletedCount = 0
    
    for (const deviceId of deviceIds) {
      const success = await this.deleteDevice(deviceId)
      if (success) deletedCount++
    }

    return deletedCount
  }

  // 创建心跳日志
  static async createHeartbeatLog(logData: {
    device_id: string
    cpu_usage: number
    memory_usage: number
    disk_usage: number
    temperature?: number | null
    uptime: number
    heartbeat_time: string
  }): Promise<void> {
    // heartbeat_logs表使用created_at字段，而不是heartbeat_time
    const insertData = {
      device_id: logData.device_id,
      cpu_usage: logData.cpu_usage,
      memory_usage: logData.memory_usage,
      disk_usage: logData.disk_usage,
      temperature: logData.temperature,
      uptime: logData.uptime,
      created_at: logData.heartbeat_time
    }

    await insert('heartbeat_logs', insertData)
  }

  // 获取设备心跳历史
  static async getHeartbeatHistory(
    deviceId: string,
    options: {
      startDate?: string
      endDate?: string
      limit?: number
      page?: number
    } = {}
  ): Promise<{
    heartbeats: any[]
    pagination: PaginationMeta
  }> {
    const { startDate, endDate, limit = 100, page = 1 } = options

    const whereConditions = ['device_id = ?']
    const params: any[] = [deviceId]

    if (startDate) {
      whereConditions.push('created_at >= ?')
      params.push(startDate)
    }

    if (endDate) {
      whereConditions.push('created_at <= ?')
      params.push(endDate)
    }

    const whereClause = whereConditions.join(' AND ')

    // 获取总数
    const countSql = `SELECT COUNT(*) as total FROM heartbeat_logs WHERE ${whereClause}`
    const [{ total }] = await query<{ total: number }>(countSql, params)

    // 计算分页
    const totalPages = Math.ceil(total / limit)
    const offset = (page - 1) * limit

    // 获取心跳记录
    const sql = `
      SELECT * FROM heartbeat_logs 
      WHERE ${whereClause}
      ORDER BY created_at DESC
      LIMIT ? OFFSET ?
    `

    const heartbeats = await query(sql, [...params, limit, offset])

    return {
      heartbeats,
      pagination: {
        page,
        limit,
        total,
        totalPages
      }
    }
  }

  // 清理过期的心跳日志
  static async cleanupOldHeartbeats(retentionDays: number = 30): Promise<number> {
    const cutoffDate = dayjs().subtract(retentionDays, 'day').format('YYYY-MM-DD HH:mm:ss')
    
    const affectedRows = await deleteRecord(
      'heartbeat_logs',
      'created_at < ?',
      [cutoffDate]
    )

    return affectedRows
  }
}