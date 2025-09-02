import EncryptedStorage from './encryption';
import { StorageKeys } from '../types/common';

/**
 * 数据迁移助手类
 * 用于将现有的localStorage数据迁移到加密存储
 */
export class MigrationHelper {
  /**
   * 执行完整的数据迁移
   */
  static migrateAllData(): void {
    console.log('开始数据迁移...');
    
    try {
      // 迁移认证相关数据
      this.migrateAuthData();
      
      // 迁移设置数据
      this.migrateSettingsData();
      
      // 迁移其他应用数据
      this.migrateAppData();
      
      console.log('数据迁移完成');
    } catch (error) {
      console.error('数据迁移失败:', error);
      throw error;
    }
  }
  
  /**
   * 迁移认证相关数据
   */
  private static migrateAuthData(): void {
    const authKeys = [
      StorageKeys.AUTH_TOKEN,
      StorageKeys.REFRESH_TOKEN,
      StorageKeys.USER_INFO
    ];
    
    for (const key of authKeys) {
      const value = localStorage.getItem(key);
      if (value) {
        // 将数据迁移到加密存储
        EncryptedStorage.setItem(key, value);
        // 从原localStorage中删除
        localStorage.removeItem(key);
        console.log(`已迁移认证数据: ${key}`);
      }
    }
  }
  
  /**
   * 迁移设置数据
   */
  private static migrateSettingsData(): void {
    const settingsKeys = [
      'systemSettings',
      'notificationSettings',
      'securitySettings',
      'remembered_username'
    ];
    
    for (const key of settingsKeys) {
      const value = localStorage.getItem(key);
      if (value) {
        // 将数据迁移到加密存储
        EncryptedStorage.setItem(key, value);
        // 从原localStorage中删除
        localStorage.removeItem(key);
        console.log(`已迁移设置数据: ${key}`);
      }
    }
  }
  
  /**
   * 迁移其他应用数据
   */
  private static migrateAppData(): void {
    // 获取所有localStorage键
    const allKeys: string[] = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key) {
        allKeys.push(key);
      }
    }
    
    // 需要保留的非敏感数据键（不需要加密）
    const keepUnencrypted = [
      'theme_preference', // 主题偏好可以不加密
      'language_preference', // 语言偏好可以不加密
      'last_visit_time', // 最后访问时间可以不加密
    ];
    
    // 迁移其他敏感数据
    for (const key of allKeys) {
      if (!keepUnencrypted.includes(key)) {
        const value = localStorage.getItem(key);
        if (value) {
          // 将数据迁移到加密存储
          EncryptedStorage.setItem(key, value);
          // 从原localStorage中删除
          localStorage.removeItem(key);
          console.log(`已迁移应用数据: ${key}`);
        }
      }
    }
  }
  
  /**
   * 检查是否需要迁移
   */
  static needsMigration(): boolean {
    // 检查是否存在未加密的敏感数据
    const sensitiveKeys = [
      StorageKeys.AUTH_TOKEN,
      StorageKeys.REFRESH_TOKEN,
      StorageKeys.USER_INFO,
      'systemSettings',
      'notificationSettings',
      'securitySettings'
    ];
    
    return sensitiveKeys.some(key => localStorage.getItem(key) !== null);
  }
  
  /**
   * 获取迁移状态
   */
  static getMigrationStatus(): {
    needsMigration: boolean;
    unencryptedKeys: string[];
    encryptedKeys: string[];
  } {
    const unencryptedKeys: string[] = [];
    const encryptedKeys: string[] = [];
    
    // 检查localStorage中的键
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key) {
        unencryptedKeys.push(key);
      }
    }
    
    // 检查加密存储中的键
    const sensitiveKeys = [
      StorageKeys.AUTH_TOKEN,
      StorageKeys.REFRESH_TOKEN,
      StorageKeys.USER_INFO,
      'systemSettings',
      'notificationSettings',
      'securitySettings'
    ];
    
    for (const key of sensitiveKeys) {
      if (EncryptedStorage.getItem(key)) {
        encryptedKeys.push(key);
      }
    }
    
    return {
      needsMigration: this.needsMigration(),
      unencryptedKeys,
      encryptedKeys
    };
  }
}

/**
 * 自动执行数据迁移（如果需要）
 */
export function autoMigrate(): void {
  if (MigrationHelper.needsMigration()) {
    console.log('检测到需要数据迁移，开始自动迁移...');
    try {
      MigrationHelper.migrateAllData();
    } catch (error) {
      console.error('自动迁移失败:', error);
    }
  }
}