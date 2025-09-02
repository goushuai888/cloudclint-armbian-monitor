import { defineStore } from 'pinia';
import { ref, computed, readonly } from 'vue';
import type { Device, DeviceStats, DeviceFilters } from '@/types/device';
import { deviceApi } from '@/services/device';
import { isDeviceOnline } from '@/utils/format';
import EncryptedStorage from '@/utils/encryption';

interface ApiError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

// 获取系统设置中的pageSize
const getPageSize = (): number => {
  try {
    const savedSettings = EncryptedStorage.getItem('systemSettings');
    if (savedSettings) {
      const settings = JSON.parse(savedSettings);
      return settings.pageSize || 20;
    }
  } catch {
    // 如果读取失败，使用默认值
  }
  return 20;
};

export const useDeviceStore = defineStore('device', () => {
  // 状态定义
  const devices = ref<Device[]>([]);
  const stats = ref<DeviceStats>({
    total: 0,
    online: 0,
    offline: 0,
    onlineRate: 0,
  });

  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const lastUpdated = ref<Date | null>(null);

  const currentFilters = ref<DeviceFilters>({
    page: 1,
    limit: getPageSize(),
  });

  const totalPages = ref(0);
  const currentPage = ref(1);

  // 计算属性
  const onlineDevices = computed(() =>
    devices.value.filter((device: Device) => isDeviceOnline(device.last_heartbeat)),
  );

  const offlineDevices = computed(() =>
    devices.value.filter((device: Device) => !isDeviceOnline(device.last_heartbeat)),
  );

  const sortedDevices = computed(() => {
    return [...devices.value].sort((a, b) => {
      // 首先按order_number排序（降序，数字越大越靠前）
      if (a.order_number !== b.order_number) {
        return b.order_number - a.order_number;
      }
      // 然后按在线状态排序（在线设备优先）
      const aOnline = isDeviceOnline(a.last_heartbeat);
      const bOnline = isDeviceOnline(b.last_heartbeat);
      if (aOnline !== bOnline) {
        return bOnline ? 1 : -1;
      }
      // 最后按创建时间排序（新设备优先）
      return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    });
  });

  // 获取设备列表
  const fetchDevices = async (filters?: DeviceFilters) => {
    isLoading.value = true;
    error.value = null;

    try {
      const mergedFilters = { ...currentFilters.value, ...filters };
      const response = await deviceApi.getDevices(mergedFilters);

      devices.value = response.data.devices;
      stats.value = response.data.stats;
      currentFilters.value = mergedFilters;
      totalPages.value = response.data.pagination.totalPages;
      currentPage.value = response.data.pagination.page;
      lastUpdated.value = new Date();
    } catch (err: unknown) {
      error.value = (err as ApiError).response?.data?.message || '获取设备列表失败';
      // Failed to fetch devices
    } finally {
      isLoading.value = false;
    }
  };

  // 刷新设备列表
  const refreshDevices = async () => {
    await fetchDevices(currentFilters.value);
  };

  // 更新设备状态（WebSocket实时更新）
  const updateDeviceStatus = (deviceId: string, update: Partial<Device>) => {
    const index = devices.value.findIndex((d: Device) => d.device_id === deviceId);
    if (index !== -1 && devices.value[index]) {
      Object.assign(devices.value[index], update, {
        updated_at: new Date().toISOString(),
      });
      // 注意：不重新计算统计数据，因为统计数据应该反映全局状态，而不是当前页面的设备
    }
  };

  // 添加新设备
  const addDevice = (device: Device) => {
    const exists = devices.value.find((d: Device) => d.device_id === device.device_id);
    if (!exists) {
      devices.value.unshift(device);
      // 注意：不重新计算统计数据，因为统计数据应该反映全局状态，而不是当前页面的设备
    }
  };

  // 删除设备
  const removeDevice = async (deviceId: string) => {
    try {
      await deviceApi.deleteDevice(deviceId);
      const index = devices.value.findIndex((d: Device) => d.device_id === deviceId);
      if (index !== -1) {
        devices.value.splice(index, 1);
        await fetchStats(); // 获取最新的全局统计数据
      }
    } catch (err: unknown) {
      error.value = (err as ApiError).response?.data?.message || '删除设备失败';
      throw err;
    }
  };

  // 更新设备信息
  const updateDevice = async (
    deviceId: string,
    data: {
      device_name?: string;
      remarks?: string;
      order_number?: number;
      created_at?: string;
    },
  ) => {
    try {
      const response = await deviceApi.updateDevice(deviceId, data);
      const index = devices.value.findIndex((d: Device) => d.device_id === deviceId);
      if (index !== -1) {
        devices.value[index] = response.data;
      }
    } catch (err: unknown) {
      error.value = (err as ApiError).response?.data?.message || '更新设备失败';
      throw err;
    }
  };

  // 设置筛选条件
  const setFilters = (filters: Partial<DeviceFilters>) => {
    currentFilters.value = { ...currentFilters.value, ...filters, page: 1 };
  };

  // 清除筛选条件
  const clearFilters = () => {
    currentFilters.value = { page: 1, limit: getPageSize() };
  };

  // 翻页
  const goToPage = async (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
      await fetchDevices({ ...currentFilters.value, page });
    }
  };

  // 获取单个设备详情
  const fetchDeviceDetail = async (deviceId: string) => {
    try {
      const response = await deviceApi.getDevice(deviceId);
      return response.data;
    } catch (err: unknown) {
      error.value = (err as ApiError).response?.data?.message || '获取设备详情失败';
      throw err;
    }
  };

  // 获取全局统计数据（从后端API获取）
  const fetchStats = async () => {
    try {
      const response = await deviceApi.getDeviceStats();
      stats.value = response.data;
    } catch (err: unknown) {
      console.error('获取统计数据失败:', err);
    }
  };



  // 清除错误
  const clearError = () => {
    error.value = null;
  };

  // 更新页面大小设置
  const updatePageSize = async () => {
    const newPageSize = getPageSize();
    if (currentFilters.value.limit !== newPageSize) {
      currentFilters.value.limit = newPageSize;
      // 重新获取数据以应用新的页面大小
      await fetchDevices(currentFilters.value);
    }
  };

  // 重置状态
  const resetState = () => {
    devices.value = [];
    stats.value = { total: 0, online: 0, offline: 0, onlineRate: 0 };
    isLoading.value = false;
    error.value = null;
    lastUpdated.value = null;
    currentFilters.value = { page: 1, limit: getPageSize() };
    totalPages.value = 0;
    currentPage.value = 1;
  };

  return {
    // 状态（只读）
    devices: readonly(devices),
    stats: readonly(stats),
    isLoading: readonly(isLoading),
    error: readonly(error),
    lastUpdated: readonly(lastUpdated),
    currentFilters: readonly(currentFilters),
    totalPages: readonly(totalPages),
    currentPage: readonly(currentPage),

    // 计算属性
    onlineDevices,
    offlineDevices,
    sortedDevices,

    // 方法
    fetchDevices,
    refreshDevices,
    updateDeviceStatus,
    addDevice,
    removeDevice,
    updateDevice,
    setFilters,
    clearFilters,
    goToPage,
    fetchDeviceDetail,
    fetchStats,
    clearError,
    resetState,
    updatePageSize,
  };
});