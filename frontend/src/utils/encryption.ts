import CryptoJS from 'crypto-js'

/**
 * 加密工具类
 * 用于加密和解密localStorage中的敏感信息
 */
class EncryptionService {
  private static readonly SECRET_KEY = 'ArmbianBox_2024_SecureKey'
  private static readonly STORAGE_PREFIX = 'encrypted_'

  /**
   * 加密数据
   * @param data 要加密的数据
   * @returns 加密后的字符串
   */
  static encrypt(data: string): string {
    try {
      const encrypted = CryptoJS.AES.encrypt(data, this.SECRET_KEY).toString()
      return encrypted
    } catch (error) {
      console.error('加密失败:', error)
      return data // 加密失败时返回原始数据
    }
  }

  /**
   * 解密数据
   * @param encryptedData 加密的数据
   * @returns 解密后的字符串
   */
  static decrypt(encryptedData: string): string {
    try {
      const decrypted = CryptoJS.AES.decrypt(encryptedData, this.SECRET_KEY)
      return decrypted.toString(CryptoJS.enc.Utf8)
    } catch (error) {
      console.error('解密失败:', error)
      return encryptedData // 解密失败时返回原始数据
    }
  }

  /**
   * 安全地设置localStorage项目
   * @param key 存储键
   * @param value 要存储的值
   * @param encrypt 是否加密存储
   */
  static setItem(key: string, value: string, encrypt: boolean = true): void {
    try {
      if (encrypt) {
        const encryptedValue = this.encrypt(value)
        localStorage.setItem(this.STORAGE_PREFIX + key, encryptedValue)
      } else {
        localStorage.setItem(key, value)
      }
    } catch (error) {
      console.error('存储数据失败:', error)
    }
  }

  /**
   * 安全地获取localStorage项目
   * @param key 存储键
   * @param encrypted 是否为加密存储
   * @returns 解密后的值或null
   */
  static getItem(key: string, encrypted: boolean = true): string | null {
    try {
      if (encrypted) {
        const encryptedValue = localStorage.getItem(this.STORAGE_PREFIX + key)
        if (encryptedValue) {
          return this.decrypt(encryptedValue)
        }
        return null
      } else {
        return localStorage.getItem(key)
      }
    } catch (error) {
      console.error('获取数据失败:', error)
      return null
    }
  }

  /**
   * 移除localStorage项目
   * @param key 存储键
   * @param encrypted 是否为加密存储
   */
  static removeItem(key: string, encrypted: boolean = true): void {
    try {
      if (encrypted) {
        localStorage.removeItem(this.STORAGE_PREFIX + key)
      } else {
        localStorage.removeItem(key)
      }
    } catch (error) {
      console.error('移除数据失败:', error)
    }
  }

  /**
   * 清除所有加密的localStorage项目
   */
  static clearEncryptedItems(): void {
    try {
      const keysToRemove: string[] = []
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i)
        if (key && key.startsWith(this.STORAGE_PREFIX)) {
          keysToRemove.push(key)
        }
      }
      keysToRemove.forEach(key => localStorage.removeItem(key))
    } catch (error) {
      console.error('清除加密数据失败:', error)
    }
  }

  /**
   * 迁移现有的未加密数据到加密存储
   * @param keys 要迁移的键列表
   */
  static migrateToEncrypted(keys: string[]): void {
    keys.forEach(key => {
      try {
        const value = localStorage.getItem(key)
        if (value) {
          // 保存加密版本
          this.setItem(key, value, true)
          // 移除未加密版本
          localStorage.removeItem(key)
          console.log(`已迁移 ${key} 到加密存储`)
        }
      } catch (error) {
        console.error(`迁移 ${key} 失败:`, error)
      }
    })
  }

  /**
   * 生成随机密钥（用于更高安全性的场景）
   * @param length 密钥长度
   * @returns 随机密钥
   */
  static generateRandomKey(length: number = 32): string {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
    let result = ''
    for (let i = 0; i < length; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return result
  }
}

export default EncryptionService