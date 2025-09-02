import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import duration from 'dayjs/plugin/duration';
import 'dayjs/locale/zh-cn';
import type { SystemInfo } from '../types/device';

// 配置dayjs
dayjs.extend(relativeTime);
dayjs.extend(duration);
dayjs.locale('zh-cn');

/**
 * 格式化设备状态文本
 */
export const formatDeviceStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    online: '在线',
    offline: '离线',
    warning: '警告',
  };
  return statusMap[status] || '未知';
};

/**
 * 格式化设备状态颜色
 */
export const getDeviceStatusColor = (status: string): string => {
  const colorMap: Record<string, string> = {
    online: 'positive',
    offline: 'negative',
    warning: 'warning',
  };
  return colorMap[status] || 'grey';
};

/**
 * 格式化最后在线时间
 */
export const formatLastSeen = (lastHeartbeat: string | null): string => {
  if (!lastHeartbeat) return '从未在线';

  const now = dayjs();
  const heartbeatTime = dayjs(lastHeartbeat);
  const diffInMinutes = now.diff(heartbeatTime, 'minute');

  if (diffInMinutes < 1) return '刚刚';
  if (diffInMinutes < 60) return `${diffInMinutes}分钟前`;

  const diffInHours = now.diff(heartbeatTime, 'hour');
  if (diffInHours < 24) return `${diffInHours}小时前`;

  const diffInDays = now.diff(heartbeatTime, 'day');
  if (diffInDays < 7) return `${diffInDays}天前`;

  return heartbeatTime.format('MM-DD HH:mm');
};

/**
 * 格式化最后在线时间（包含年份）
 */
export const formatLastSeenWithYear = (lastHeartbeat: string | null): string => {
  if (!lastHeartbeat) return '从未在线';

  const now = dayjs();
  const heartbeatTime = dayjs(lastHeartbeat);
  const diffInMinutes = now.diff(heartbeatTime, 'minute');

  if (diffInMinutes < 1) return '刚刚';
  if (diffInMinutes < 60) return `${diffInMinutes}分钟前`;

  const diffInHours = now.diff(heartbeatTime, 'hour');
  if (diffInHours < 24) return `${diffInHours}小时前`;

  const diffInDays = now.diff(heartbeatTime, 'day');
  if (diffInDays < 7) return `${diffInDays}天前`;

  return heartbeatTime.format('YYYY-MM-DD HH:mm');
};

/**
 * 格式化运行时间
 */
export const formatUptime = (seconds: number | null | undefined): string => {
  if (seconds === null || seconds === undefined || typeof seconds !== 'number' || isNaN(seconds)) {
    return '--';
  }
  if (seconds < 60) return `${seconds}秒`;

  const duration = dayjs.duration(seconds, 'seconds');
  const days = Math.floor(duration.asDays());
  const hours = duration.hours();
  const minutes = duration.minutes();

  if (days > 0) {
    return `${days}天${hours}小时`;
  }
  if (hours > 0) {
    return `${hours}小时${minutes}分钟`;
  }
  return `${minutes}分钟`;
};

/**
 * 格式化文件大小
 */
export const formatFileSize = (bytes: number | null | undefined): string => {
  if (bytes === null || bytes === undefined || typeof bytes !== 'number' || isNaN(bytes)) {
    return '--';
  }
  if (bytes === 0) return '0 B';

  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

/**
 * 格式化百分比
 */
export const formatPercentage = (value: number | null | undefined, decimals = 1): string => {
  if (value === null || value === undefined || typeof value !== 'number' || isNaN(value)) {
    return '--';
  }
  return `${value.toFixed(decimals)}%`;
};

/**
 * 格式化温度
 */
export const formatTemperature = (temp: number | null | undefined): string => {
  if (temp === null || temp === undefined || typeof temp !== 'number' || isNaN(temp)) {
    return '--';
  }
  return `${temp.toFixed(1)}°C`;
};

/**
 * 格式化设备名称（处理长名称）
 */
export const formatDeviceName = (name: string, maxLength = 20): string => {
  if (name.length <= maxLength) return name;
  return name.substring(0, maxLength) + '...';
};

/**
 * 格式化IP地址（处理IPv6）
 */
export const formatIpAddress = (ip: string | null): string => {
  if (!ip) return '未知';

  // IPv6地址缩短显示
  if (ip.includes(':')) {
    const parts = ip.split(':');
    if (parts.length > 4) {
      return `${parts.slice(0, 2).join(':')}::${parts.slice(-2).join(':')}`;
    }
  }

  return ip;
};

/**
 * 格式化MAC地址
 */
export const formatMacAddress = (mac: string | null): string => {
  if (!mac) return '未知';
  return mac.toUpperCase();
};

/**
 * 格式化数字（添加千分位分隔符）
 */
export const formatNumber = (num: number): string => {
  return num.toLocaleString('zh-CN');
};

/**
 * 格式化时间范围
 */
export const formatTimeRange = (start: string, end: string): string => {
  const startTime = dayjs(start);
  const endTime = dayjs(end);
  const now = dayjs();

  // 如果是今天
  if (startTime.isSame(now, 'day') && endTime.isSame(now, 'day')) {
    return `今天 ${startTime.format('HH:mm')} - ${endTime.format('HH:mm')}`;
  }

  // 如果是同一天
  if (startTime.isSame(endTime, 'day')) {
    return `${startTime.format('MM-DD')} ${startTime.format('HH:mm')} - ${endTime.format('HH:mm')}`;
  }

  // 跨天
  return `${startTime.format('MM-DD HH:mm')} - ${endTime.format('MM-DD HH:mm')}`;
};

/**
 * 获取资源使用率颜色
 */
export const getResourceColor = (usage: number): string => {
  if (usage >= 90) return 'negative';
  if (usage >= 70) return 'warning';
  return 'positive';
};

/**
 * 格式化设备系统信息
 */
export const formatSystemInfo = (systemInfo: SystemInfo | string | null): string => {
  if (!systemInfo || typeof systemInfo === 'string') {
    return systemInfo || '未知';
  }

  try {
    const info =
      typeof systemInfo === 'string' ? (JSON.parse(systemInfo) as SystemInfo) : systemInfo;
    const parts = [];

    if (info.distro) parts.push(info.distro);
    else if (info.os) parts.push(info.os);

    if (info.kernel) parts.push(info.kernel);
    if (info.arch) parts.push(info.arch);

    return parts.join(' / ') || '未知';
  } catch {
    return '格式错误';
  }
};

/**
 * 检查设备是否在线
 */
export const isDeviceOnline = (lastHeartbeat: string | null, timeoutMinutes = 5): boolean => {
  if (!lastHeartbeat) return false;

  const now = dayjs();
  const heartbeatTime = dayjs(lastHeartbeat);
  const diffInMinutes = now.diff(heartbeatTime, 'minute');

  return diffInMinutes <= timeoutMinutes;
};

/**
 * 格式化设备编号
 */
export const formatOrderNumber = (orderNumber: number): string => {
  return `#${orderNumber.toString().padStart(3, '0')}`;
};

/**
 * 格式化日期时间
 */
export const formatDateTime = (dateTime: string | null): string => {
  if (!dateTime) return '未知';
  return dayjs(dateTime).format('YYYY-MM-DD HH:mm:ss');
};
