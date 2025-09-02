import axios, {
  type AxiosInstance,
  type AxiosRequestConfig,
  type AxiosResponse,
  type InternalAxiosRequestConfig,
} from 'axios';
import { useAuthStore } from '../stores/auth';
import type { ApiResponse } from '../types/api';

// API基础配置
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000';
const API_TIMEOUT = 30000;

// 创建axios实例
export const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: API_TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// 请求拦截器
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // 添加认证token
    const authStore = useAuthStore();
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`;
    }

    // 添加请求时间戳（用于防止缓存）
    if (config.method === 'get') {
      config.params = {
        ...config.params,
        _t: Date.now(),
      };
    }

    return config;
  },
  async (error) => {
     return Promise.reject(error instanceof Error ? error : new Error(String(error)));
   },
);

// 响应拦截器
api.interceptors.response.use(
  (response: AxiosResponse<ApiResponse>) => {
    const { data } = response;

    // 如果后端返回的success为false，视为业务错误
    if (data && typeof data === 'object' && 'success' in data && !data.success) {
      const error = new Error(data.message || '请求失败') as Error & {
        response?: AxiosResponse;
        isBusinessError?: boolean;
      };
      error.response = response;
      error.isBusinessError = true;
      throw error;
    }

    return response;
  },
  async (error) => {
    const { response, config } = error;

    // 处理HTTP状态码错误
    if (response) {
      const { status, data } = response;
      let message = '请求失败';

      switch (status) {
        case 401: {
          message = '认证失败，请重新登录';
          // 如果不是刷新token的请求，尝试自动刷新token
          if (!config?.url?.includes('/api/auth/refresh')) {
            const authStore = useAuthStore();
            if (authStore.refreshToken) {
              try {
                await authStore.refreshAccessToken();
                // 重新发送原始请求
                if (config) {
                  config.headers.Authorization = `Bearer ${authStore.token}`;
                  return await api.request(config);
                }
              } catch {
                // 刷新失败，清除认证信息
                void authStore.logout();
              }
            } else {
              // 没有refresh token，直接登出
              void authStore.logout();
            }
          }
          break;
        }

        case 403:
          message = '权限不足，无法访问';
          break;

        case 404:
          message = '请求的资源不存在';
          break;

        case 422:
          message = data?.message || '请求参数验证失败';
          break;

        case 429:
          message = '请求过于频繁，请稍后再试';
          break;

        case 500:
          message = '服务器内部错误';
          break;

        case 502:
        case 503:
        case 504:
          message = '服务暂时不可用，请稍后再试';
          break;

        default:
          message = data?.message || `请求失败 (${status})`;
      }

      // 显示错误通知（排除某些不需要通知的接口）
      const silentUrls = ['/api/auth/refresh', '/api/heartbeat'];
      const shouldNotify = !silentUrls.some((url) => config?.url?.includes(url));

      if (shouldNotify) {
        // Note: Notification will be handled by the calling component
      }

      error.message = message;
    } else if (error.code === 'ECONNABORTED') {
      error.message = '请求超时，请检查网络连接';
    } else if (error.code === 'ERR_NETWORK') {
      error.message = '网络连接失败，请检查网络设置';
    }

    return Promise.reject(error instanceof Error ? error : new Error(String(error)));
  },
);

// API请求封装函数
export const apiRequest = {
  // GET请求
  get<T = unknown>(
    url: string,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<ApiResponse<T>>> {
    return api.get(url, config);
  },

  // POST请求
  post<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<ApiResponse<T>>> {
    return api.post(url, data, config);
  },

  // PUT请求
  put<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<ApiResponse<T>>> {
    return api.put(url, data, config);
  },

  // PATCH请求
  patch<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<ApiResponse<T>>> {
    return api.patch(url, data, config);
  },

  // DELETE请求
  delete<T = unknown>(
    url: string,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<ApiResponse<T>>> {
    return api.delete(url, config);
  },

  // 上传文件
  upload<T = unknown>(
    url: string,
    file: File,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<ApiResponse<T>>> {
    const formData = new FormData();
    formData.append('file', file);

    return api.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      ...config,
    });
  },
};

// 请求重试配置
export const retryRequest = async <T>(
  requestFn: () => Promise<T>,
  maxRetries = 3,
  delay = 1000,
): Promise<T> => {
  let lastError: unknown;

  for (let i = 0; i < maxRetries; i++) {
    try {
      return await requestFn();
    } catch (error) {
      lastError = error;
      if (i < maxRetries - 1) {
        await new Promise((resolve) => setTimeout(resolve, delay * Math.pow(2, i)));
      }
    }
  }

  throw lastError;
};

// 并发请求控制
export const concurrentRequest = async <T>(
  requests: (() => Promise<T>)[],
  maxConcurrency = 5,
): Promise<T[]> => {
  const results: T[] = [];
  const executing: Promise<void>[] = [];

  for (const request of requests) {
    const promise = request().then((result) => {
      results.push(result);
    });

    executing.push(promise);

    if (executing.length >= maxConcurrency) {
      await Promise.race(executing);
      const index = executing.findIndex((p) => p === promise);
      if (index !== -1) {
        void executing.splice(index, 1);
      }
    }
  }

  await Promise.all(executing);
  return results;
};

export default api;
