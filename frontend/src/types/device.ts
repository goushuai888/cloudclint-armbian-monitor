// 设备相关类型定义
export interface Device {
  id: number;
  device_id: string;
  device_name: string;
  ip_address: string | null;
  mac_address: string | null;
  system_info: SystemInfo | null;
  cpu_usage: number;
  memory_usage: number;
  disk_usage: number;
  temperature: number | null;
  uptime: number;
  remarks: string | null;
  status: 'online' | 'offline' | 'warning';
  last_heartbeat: string | null;
  created_at: string;
  updated_at: string;
  order_number: number;
  group_id?: number;
}

export interface SystemInfo {
  os: string;
  kernel: string;
  arch: string;
  distro?: string;
}

export interface DeviceStats {
  total: number;
  online: number;
  offline: number;
  onlineRate: number;
}

export interface DeviceListResponse {
  devices: Device[];
  stats: DeviceStats;
  pagination: PaginationMeta;
}

export interface DeviceDetailResponse {
  device: Device;
  recentHeartbeats: HeartbeatLog[];
  resourceHistory: ResourceMetric[];
}

export interface PaginationMeta {
  page: number;
  limit: number;
  total: number;
  totalPages: number;
}

export interface HeartbeatLog {
  id: number;
  device_id: string;
  heartbeat_time: string;
  cpu_usage: number;
  memory_usage: number;
  disk_usage: number;
  temperature: number | null;
  uptime: number;
  created_at: string;
}

export interface ResourceMetric {
  timestamp: string;
  cpu_usage: number;
  memory_usage: number;
  disk_usage: number;
  temperature: number | null;
}

export interface DeviceGroup {
  id: number;
  group_name: string;
  group_description: string | null;
  group_color: string;
  group_icon: string;
  sort_order: number;
  is_default: boolean;
  created_at: string;
  updated_at: string;
}

export interface DeviceFilters {
  status?: 'online' | 'offline';
  search?: string;
  groupId?: number;
  year?: number;
  month?: number;
  page?: number;
  limit?: number;
}

export interface UpdateDeviceRequest {
  device_name?: string;
  remarks?: string;
  order_number?: number;
  group_id?: number;
  created_at?: string;
}

// WebSocket相关类型
export interface DeviceStatusUpdate {
  device_id: string;
  status: 'online' | 'offline' | 'warning';
  cpu_usage: number;
  memory_usage: number;
  disk_usage: number;
  temperature: number;
  last_heartbeat: string;
}

export interface WebSocketMessage {
  type: 'device_update' | 'device_added' | 'device_removed' | 'stats_update';
  data: Device | DeviceStats | DeviceStatusUpdate | Record<string, unknown>;
}
