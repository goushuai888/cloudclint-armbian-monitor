import { defineStore } from 'pinia';
import { ref, computed, readonly } from 'vue';
import { useQuasar } from 'quasar';
import { apiRequest } from '../services/api';
import type { User, LoginRequest, LoginResponse, RefreshTokenResponse } from '../types/api';
import { StorageKeys } from '../types/common';
import EncryptedStorage from '../utils/encryption';

export const useAuthStore = defineStore('auth', () => {
  const $q = useQuasar();

  // 状态定义
  const token = ref<string | null>(null);
  const refreshToken = ref<string | null>(null);
  const user = ref<User | null>(null);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const isInitialized = ref(false);
  const tokenExpiresAt = ref<number | null>(null);
  
  // 自动登出相关状态
  const lastActivityTime = ref<number>(Date.now());
  const autoLogoutTimer = ref<NodeJS.Timeout | null>(null);
  const INACTIVITY_TIMEOUT = 30 * 60 * 1000; // 30分钟

  // 计算属性
  const isAuthenticated = computed(() => !!token.value && !!user.value);
  const userRole = computed(() => user.value?.role || null);
  const userName = computed(() => user.value?.username || null);

  // 登出
  const logout = async (showMessage = true): Promise<{ shouldRedirect: boolean }> => {
    try {
      // 调用后端登出API撤销token并清除httpOnly cookie
      await apiRequest.post('/api/auth/logout', {});
    } catch (error) {
      console.warn('登出API调用失败，但继续清除本地状态:', error);
    }
    
    // 清除内存中的认证信息
    token.value = null;
    refreshToken.value = null;
    user.value = null;
    error.value = null;
    tokenExpiresAt.value = null;
    
    // 清除持久化存储
    EncryptedStorage.removeItem(StorageKeys.AUTH_TOKEN);
    EncryptedStorage.removeItem(StorageKeys.REFRESH_TOKEN);
    EncryptedStorage.removeItem(StorageKeys.USER_INFO);
    
    // 停止活跃状态监听
    stopActivityMonitoring();
    
    if (showMessage) {
      $q.notify({
        type: 'info',
        message: '已退出登录',
        position: 'top',
        timeout: 2000,
      });
    }
    
    // 返回标志让调用方处理路由跳转
    return { shouldRedirect: true };
  };

  // 获取用户信息
  const fetchUserInfo = async (): Promise<void> => {
    try {
      const response = await apiRequest.get<User>('/api/auth/me');
      user.value = response.data.data;
      
      // 更新本地存储中的用户信息
      EncryptedStorage.setItem(StorageKeys.USER_INFO, JSON.stringify(response.data.data));
    } catch (error: unknown) {
      // 只有在明确收到401认证错误时才清除认证信息
      const axiosError = error as { response?: { status?: number }; message?: string };
      if (axiosError?.response?.status === 401) {
        // token确实无效，清除认证信息
            void logout(false);
      } else {
        // 其他错误（网络问题等）记录警告但不强制登出
        console.warn('获取用户信息失败，但保持登录状态:', axiosError?.message || error);
        throw error;
      }
    }
  };

  // 初始化认证状态
  const initAuth = async (): Promise<void> => {
    if (isInitialized.value) {
      return;
    }

    try {
      const storedToken = EncryptedStorage.getItem(StorageKeys.AUTH_TOKEN);
      const storedRefreshToken = EncryptedStorage.getItem(StorageKeys.REFRESH_TOKEN);
      const storedUser = EncryptedStorage.getItem(StorageKeys.USER_INFO);

      if (storedToken && storedRefreshToken && storedUser) {
        try {
          // 设置token和用户信息
          token.value = storedToken;
          refreshToken.value = storedRefreshToken;
          user.value = JSON.parse(storedUser);
          console.log('认证状态已从本地存储恢复');

          // 验证token是否仍然有效（静默验证，不强制登出）
          try {
            await fetchUserInfo();
            // 启动活跃状态监听
            startActivityMonitoring();
          } catch (error: unknown) {
            // 只有在明确收到401认证错误时才尝试刷新token
            const axiosError = error as { response?: { status?: number } };
            if (axiosError?.response?.status === 401) {
              // 尝试使用refresh token刷新access token
              try {
                await refreshAccessToken();
                // 启动活跃状态监听
                startActivityMonitoring();
              } catch (refreshError) {
                 // refresh token也无效，清除认证信息
                 console.error('刷新token失败:', refreshError);
                 void logout(false);
               }
            } else {
              // 其他错误（网络问题等）保持登录状态，让用户可以继续使用应用
              // 启动活跃状态监听
              startActivityMonitoring();
            }
          }
        } catch {
          // Failed to parse stored user info
          EncryptedStorage.removeItem(StorageKeys.AUTH_TOKEN);
          EncryptedStorage.removeItem(StorageKeys.REFRESH_TOKEN);
          EncryptedStorage.removeItem(StorageKeys.USER_INFO);
        }
      }
    } catch (error) {
      console.error('初始化认证状态失败:', error);
    } finally {
      isInitialized.value = true;
    }
  };

  // 登录
  const login = async (credentials: LoginRequest & { rememberMe?: boolean }) => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await apiRequest.post<LoginResponse['data']>('/api/auth/login', credentials);
      const { user: userInfo, accessToken, refreshToken: newRefreshTokenValue, expiresIn } = response.data.data;

      // 计算token过期时间
      const expiresAtTime = Date.now() + (expiresIn * 1000);

      // 保存认证信息到内存
      token.value = accessToken;
      refreshToken.value = newRefreshTokenValue;
      user.value = userInfo;
      tokenExpiresAt.value = expiresAtTime;

      // 持久化存储
      EncryptedStorage.setItem(StorageKeys.AUTH_TOKEN, accessToken);
      EncryptedStorage.setItem(StorageKeys.REFRESH_TOKEN, newRefreshTokenValue);
      EncryptedStorage.setItem(StorageKeys.USER_INFO, JSON.stringify(userInfo));
      
      // 保存记住我状态
      if (credentials.rememberMe) {
        localStorage.setItem('remembered_username', credentials.username);
      } else {
        localStorage.removeItem('remembered_username');
      }

      // 启动活跃状态监听
      startActivityMonitoring();

      $q.notify({
        type: 'positive',
        message: `欢迎回来，${userInfo.username}！`,
        position: 'top',
        timeout: 2000,
      });

      return { success: true };
    } catch (err: unknown) {
      const axiosError = err as { response?: { data?: { message?: string } } };
      error.value = axiosError?.response?.data?.message || '登录失败';
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  // 刷新access token
  const refreshAccessToken = async (): Promise<void> => {
    try {
      // 不需要在请求体中传递refresh token，后端会从httpOnly cookie中读取
      const response = await apiRequest.post<RefreshTokenResponse['data']>('/api/auth/refresh', {});

       const { accessToken, refreshToken: newRefreshTokenValue, expiresIn } = response.data.data;
      const expiresAtTime = Date.now() + (expiresIn * 1000);

      // 更新内存中的token
      token.value = accessToken;
      refreshToken.value = newRefreshTokenValue;
      tokenExpiresAt.value = expiresAtTime;

      // 更新本地存储
      EncryptedStorage.setItem(StorageKeys.AUTH_TOKEN, accessToken);
      EncryptedStorage.setItem(StorageKeys.REFRESH_TOKEN, newRefreshTokenValue);

      console.log('Access token已刷新');
    } catch (error) {
      console.error('刷新token失败:', error);
      throw error;
    }
  };

  // 更新用户资料
  const updateProfile = (profileData: Partial<User>): void => {
    console.log('TODO: 实现更新用户资料逻辑', profileData);
  };

  // 修改密码
  const changePassword = (oldPassword: string, newPassword: string): void => {
    console.log('TODO: 实现修改密码逻辑', oldPassword, newPassword);
  };

  // 验证token
  const validateToken = async (): Promise<boolean> => {
    try {
      await fetchUserInfo();
      return true;
    } catch {
      return false;
    }
  };

  // 检查权限
  const hasPermission = (permission: string): boolean => {
    console.log('TODO: 实现权限检查逻辑', permission);
    return true;
  };

  // 清除错误信息
  const clearError = (): void => {
    error.value = null;
  };

  // 更新用户活跃时间
  const updateActivity = (): void => {
    lastActivityTime.value = Date.now();
    resetAutoLogoutTimer();
  };

  // 重置自动登出定时器
  const resetAutoLogoutTimer = (): void => {
    if (autoLogoutTimer.value) {
      clearTimeout(autoLogoutTimer.value);
    }
    
    if (isAuthenticated.value) {
      autoLogoutTimer.value = setTimeout(() => {
        console.log('用户长时间不活跃，自动登出');
        void logout(true);
      }, INACTIVITY_TIMEOUT);
    }
  };

  // 清除自动登出定时器
  const clearAutoLogoutTimer = (): void => {
    if (autoLogoutTimer.value) {
      clearTimeout(autoLogoutTimer.value);
      autoLogoutTimer.value = null;
    }
  };

  // 开始监听用户活跃状态
  const startActivityMonitoring = (): void => {
    if (typeof window !== 'undefined') {
      const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
      
      events.forEach(event => {
        document.addEventListener(event, updateActivity, { passive: true });
      });
      
      resetAutoLogoutTimer();
    }
  };

  // 停止监听用户活跃状态
  const stopActivityMonitoring = (): void => {
    if (typeof window !== 'undefined') {
      const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
      
      events.forEach(event => {
        document.removeEventListener(event, updateActivity);
      });
      
      clearAutoLogoutTimer();
    }
  };

  return {
    // 状态
    token: readonly(token),
    refreshToken: readonly(refreshToken),
    tokenExpiresAt: readonly(tokenExpiresAt),
    user: readonly(user),
    isLoading: readonly(isLoading),
    error: readonly(error),
    isInitialized: readonly(isInitialized),

    // 计算属性
    isAuthenticated,
    userRole,
    userName,

    // 方法
    initAuth,
    login,
    logout,
    refreshAccessToken,
    fetchUserInfo,
    updateProfile,
    changePassword,
    validateToken,
    hasPermission,
    clearError,
    startActivityMonitoring,
    stopActivityMonitoring,
    updateActivity,
  };
});
