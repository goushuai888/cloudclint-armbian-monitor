import { queryOne, query, insert } from '@/config/database.js'
import { config } from '@/config/index.js'
import type { RefreshToken } from '@/types/index.js'
import crypto from 'crypto'
import dayjs from 'dayjs'

export class RefreshTokenService {
  /**
   * 生成refresh token
   */
  static generateRefreshToken(): string {
    return crypto.randomBytes(64).toString('hex')
  }

  /**
   * 生成设备指纹
   * 基于用户代理、IP地址等信息生成唯一设备标识
   */
  static generateDeviceFingerprint(userAgent?: string, ip?: string): string {
    // 提取用户代理中的关键信息
    const ua = userAgent || 'unknown'
    const normalizedUA = ua.toLowerCase()
    
    // 提取浏览器和操作系统信息
    const browserInfo = this.extractBrowserInfo(normalizedUA)
    const osInfo = this.extractOSInfo(normalizedUA)
    
    // 组合设备特征信息（不包含时间戳，确保同一设备生成相同指纹）
    const deviceData = `${browserInfo}-${osInfo}-${ip || 'unknown'}`
    
    return crypto.createHash('sha256').update(deviceData).digest('hex')
  }

  /**
   * 提取浏览器信息
   */
  private static extractBrowserInfo(userAgent: string): string {
    if (userAgent.includes('chrome')) return 'chrome'
    if (userAgent.includes('firefox')) return 'firefox'
    if (userAgent.includes('safari') && !userAgent.includes('chrome')) return 'safari'
    if (userAgent.includes('edge')) return 'edge'
    if (userAgent.includes('opera')) return 'opera'
    return 'unknown'
  }

  /**
   * 提取操作系统信息
   */
  private static extractOSInfo(userAgent: string): string {
    if (userAgent.includes('windows')) return 'windows'
    if (userAgent.includes('mac os')) return 'macos'
    if (userAgent.includes('linux')) return 'linux'
    if (userAgent.includes('android')) return 'android'
    if (userAgent.includes('ios')) return 'ios'
    return 'unknown'
  }

  /**
   * 创建refresh token记录
   */
  static async createRefreshToken(
    userId: number,
    deviceFingerprint?: string
  ): Promise<string> {
    const token = this.generateRefreshToken()
    const expiresAt = dayjs().add(7, 'day').format('YYYY-MM-DD HH:mm:ss')

    await insert(
      'refresh_tokens',
      {
        user_id: userId,
        token,
        device_fingerprint: deviceFingerprint,
        expires_at: expiresAt
      }
    )

    return token
  }

  /**
   * 验证refresh token
   */
  static async validateRefreshToken(
    token: string,
    deviceFingerprint?: string
  ): Promise<RefreshToken | null> {
    try {
      const refreshToken = await queryOne<RefreshToken>(
        `SELECT * FROM refresh_tokens 
         WHERE token = ? AND expires_at > NOW()`,
        [token]
      )

      if (!refreshToken) {
        return null
      }

      // 验证设备指纹（如果提供）
      if (deviceFingerprint && refreshToken.device_fingerprint) {
        if (refreshToken.device_fingerprint !== deviceFingerprint) {
          return null
        }
      }

      // 更新最后使用时间
      await queryOne(
        'UPDATE refresh_tokens SET last_used_at = NOW() WHERE id = ?',
        [refreshToken.id]
      )

      return refreshToken
    } catch (error) {
      console.error('验证refresh token失败:', error)
      return null
    }
  }

  /**
   * 撤销refresh token
   */
  static async revokeRefreshToken(token: string): Promise<boolean> {
    try {
      const result = await queryOne(
        'DELETE FROM refresh_tokens WHERE token = ?',
        [token]
      )
      return true
    } catch (error) {
      console.error('撤销refresh token失败:', error)
      return false
    }
  }

  /**
   * 撤销用户的所有refresh token
   */
  static async revokeAllUserTokens(userId: number): Promise<boolean> {
    try {
      await queryOne(
        'DELETE FROM refresh_tokens WHERE user_id = ?',
        [userId]
      )
      return true
    } catch (error) {
      console.error('撤销用户所有refresh token失败:', error)
      return false
    }
  }

  /**
   * 清理过期的refresh token
   */
  static async cleanupExpiredTokens(): Promise<number> {
    try {
      const result = await queryOne<{ affectedRows: number }>(
        'DELETE FROM refresh_tokens WHERE expires_at < NOW()'
      )
      return result?.affectedRows || 0
    } catch (error) {
      console.error('清理过期refresh token失败:', error)
      return 0
    }
  }

  /**
   * 获取用户的refresh token数量
   */
  static async getUserTokenCount(userId: number): Promise<number> {
    try {
      const result = await queryOne<{ count: number }>(
        'SELECT COUNT(*) as count FROM refresh_tokens WHERE user_id = ? AND expires_at > NOW()',
        [userId]
      )
      return result?.count || 0
    } catch (error) {
      console.error('获取用户token数量失败:', error)
      return 0
    }
  }

  /**
   * 限制用户的refresh token数量（最多5个）
   */
  static async limitUserTokens(userId: number, maxTokens: number = 5): Promise<void> {
    try {
      const count = await this.getUserTokenCount(userId)
      if (count >= maxTokens) {
        // 删除最旧的token
        await queryOne(
          `DELETE FROM refresh_tokens 
           WHERE user_id = ? 
           ORDER BY created_at ASC 
           LIMIT ?`,
          [userId, count - maxTokens + 1]
        )
      }
    } catch (error) {
      console.error('限制用户token数量失败:', error)
    }
  }
}