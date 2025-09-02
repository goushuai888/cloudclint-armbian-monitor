import { apiRequest } from './api'
import type {
  Device,
  DeviceListResponse,
  DeviceDetailResponse,
  DeviceFilters,
  UpdateDeviceRequest,
  HeartbeatLog
} from '../types/device'
import type { ApiResponse } from '../types/api'

/**
 * 设备相关API服务
 */
export const deviceApi = {
  /**
   * 获取设备列表
   */
  async getDevices(params?: DeviceFilters): Promise<ApiResponse<DeviceListResponse>> {
    const response = await apiRequest.get<DeviceListResponse>('/api/devices', { params })
    return response.data
  },

  /**
   * 获取设备详情
   */
  async getDevice(deviceId: string): Promise<ApiResponse<DeviceDetailResponse>> {
    const response = await apiRequest.get<DeviceDetailResponse>(`/api/devices/${deviceId}`)
    return response.data
  },

  /**
   * 更新设备信息
   */
  async updateDevice(deviceId: string, data: UpdateDeviceRequest): Promise<ApiResponse<Device>> {
    const response = await apiRequest.put<Device>(`/api/devices/${deviceId}`, data)
    return response.data
  },

  /**
   * 删除设备
   */
  async deleteDevice(deviceId: string): Promise<ApiResponse<void>> {
    const response = await apiRequest.delete<void>(`/api/devices/${deviceId}`)
    return response.data
  },

  /**
   * 获取设备心跳历史
   */
  async getHeartbeatHistory(deviceId: string, params?: {
    start_date?: string
    end_date?: string
    limit?: number
    page?: number
  }): Promise<ApiResponse<{
    heartbeats: HeartbeatLog[],
    pagination: { page: number, limit: number, total: number, totalPages: number }
  }>> {
    const response = await apiRequest.get<{
      heartbeats: HeartbeatLog[],
      pagination: { page: number, limit: number, total: number, totalPages: number }
    }>(`/api/devices/${deviceId}/heartbeats`, { params })
    return response.data
  },

  /**
   * 获取设备统计信息
   */
  async getDeviceStats(): Promise<ApiResponse<{
    total: number
    online: number
    offline: number
    onlineRate: number
    avgCpuUsage: number
    avgMemoryUsage: number
    avgDiskUsage: number
  }>> {
    const response = await apiRequest.get<{
      total: number
      online: number
      offline: number
      onlineRate: number
      avgCpuUsage: number
      avgMemoryUsage: number
      avgDiskUsage: number
    }>('/api/devices/stats')
    return response.data
  },

  /**
   * 搜索设备
   */
  async searchDevices(keyword: string, filters?: {
    status?: 'online' | 'offline'
    groupId?: number
    limit?: number
  }): Promise<ApiResponse<Device[]>> {
    const params = { search: keyword, ...filters }
    const response = await apiRequest.get<Device[]>('/api/devices/search', { params })
    return response.data
  },

  /**
   * 批量更新设备
   */
  async batchUpdateDevices(updates: {
    device_id: string
    data: UpdateDeviceRequest
  }[]): Promise<ApiResponse<Device[]>> {
    const response = await apiRequest.post<Device[]>('/api/devices/batch', { updates })
    return response.data
  },

  /**
   * 批量删除设备
   */
  async batchDeleteDevices(deviceIds: number[]): Promise<ApiResponse<void>> {
    const response = await apiRequest.delete<void>('/api/devices/batch', {
      data: { device_ids: deviceIds }
    })
    return response.data
  },

  /**
   * 导出设备列表
   */
  async exportDevices(format: 'csv' | 'excel' = 'csv', filters?: DeviceFilters): Promise<Blob> {
    const params = { format, ...filters }
    const response = await apiRequest.get<Blob>('/api/devices/export', {
      params,
      responseType: 'blob'
    })
    return response.data as unknown as Blob
  },

  /**
   * 获取设备资源使用历史图表数据
   */
  async getDeviceResources(deviceId: number, params?: {
    start_time?: string
    end_time?: string
    interval?: string
  }): Promise<ApiResponse<{
    timestamps: string[]
    cpu_usage: number[]
    memory_usage: number[]
    disk_usage: number[]
    temperature: (number | null)[]
  }>> {
    const response = await apiRequest.get<{
      timestamps: string[]
      cpu_usage: number[]
      memory_usage: number[]
      disk_usage: number[]
      temperature: (number | null)[]
    }>(`/api/devices/${deviceId}/resources`, { params })
    return response.data
  },

  /**
   * 测试设备连接
   */
  async testConnection(deviceId: number): Promise<ApiResponse<{
    reachable: boolean
    responseTime: number
    lastCheck: string
  }>> {
    const response = await apiRequest.post<{
      reachable: boolean
      responseTime: number
      lastCheck: string
    }>(`/api/devices/${deviceId}/test`)
    return response.data
  },

  /**
   * 重启设备（如果支持）
   */
  async rebootDevice(deviceId: number): Promise<ApiResponse<{
    success: boolean
    message: string
  }>> {
    const response = await apiRequest.post<{
      success: boolean
      message: string
    }>(`/api/devices/${deviceId}/reboot`)
    return response.data
  }
}

export default deviceApi
