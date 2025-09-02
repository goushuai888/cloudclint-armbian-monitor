import { insert, query } from '@/config/database.js'
import dayjs from 'dayjs'
import type { FastifyInstance } from 'fastify'

export interface SecurityLogEntry {
  id?: number
  event_type: 'login_attempt' | 'suspicious_activity' | 'token_refresh' | 'logout' | 'password_change' | 'account_lockout'
  user_id?: number
  username?: string
  ip_address: string
  user_agent?: string
  details: string
  risk_level: 'low' | 'medium' | 'high' | 'critical'
  created_at?: string
}

export interface LoginAttemptAnalysis {
  isRisky: boolean
  riskFactors: string[]
  riskLevel: 'low' | 'medium' | 'high' | 'critical'
  shouldBlock: boolean
}

export class SecurityLogService {
  private static app: FastifyInstance

  static initialize(app: FastifyInstance) {
    this.app = app
  }

  /**
   * 记录安全事件
   */
  static async logSecurityEvent(event: Omit<SecurityLogEntry, 'id' | 'created_at'>): Promise<void> {
    try {
      await insert('security_logs', {
        ...event,
        details: typeof event.details === 'object' ? JSON.stringify(event.details) : event.details,
        created_at: dayjs().format('YYYY-MM-DD HH:mm:ss')
      })
    } catch (error) {
      this.app?.log.error(error, '记录安全日志失败')
    }
  }

  /**
   * 分析登录尝试的风险
   */
  static async analyzeLoginAttempt(
    username: string,
    ip: string,
    userAgent: string,
    userId?: number
  ): Promise<LoginAttemptAnalysis> {
    const riskFactors: string[] = []
    let riskLevel: 'low' | 'medium' | 'high' | 'critical' = 'low'
    let shouldBlock = false

    try {
      // 检查最近的失败登录次数（过去15分钟）
      const recentFailures = await query<{ count: number }>(
        `SELECT COUNT(*) as count FROM security_logs 
         WHERE event_type = 'login_attempt' 
         AND (username = ? OR ip_address = ?) 
         AND details LIKE '%failed%' 
         AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)`,
        [username, ip]
      )

      const failureCount = (recentFailures[0] as any)?.count || 0
      if (failureCount >= 5) {
        riskFactors.push(`过去15分钟内失败登录${failureCount}次`)
        riskLevel = 'critical'
        shouldBlock = true
      } else if (failureCount >= 3) {
        riskFactors.push(`过去15分钟内失败登录${failureCount}次`)
        riskLevel = 'high'
      }

      // 检查是否来自新的IP地址（如果有用户ID）
      if (userId) {
        const knownIPs = await query<{ ip_address: string }>(
          `SELECT DISTINCT ip_address FROM security_logs 
           WHERE user_id = ? 
           AND event_type = 'login_attempt' 
           AND details LIKE '%success%' 
           AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)`,
          [userId]
        )

        const isNewIP = !knownIPs.some((log: { ip_address: string }) => log.ip_address === ip)
        if (isNewIP && knownIPs.length > 0) {
          riskFactors.push('来自新的IP地址')
          riskLevel = riskLevel === 'low' ? 'medium' : riskLevel
        }
      }

      // 检查用户代理异常
      if (!userAgent || userAgent.length < 10) {
        riskFactors.push('异常的用户代理')
        riskLevel = riskLevel === 'low' ? 'medium' : riskLevel
      }

      // 检查是否在异常时间登录（凌晨2-6点）
      const hour = dayjs().hour()
      if (hour >= 2 && hour <= 6) {
        riskFactors.push('异常时间登录')
        riskLevel = riskLevel === 'low' ? 'medium' : riskLevel
      }

      // 检查同一IP的并发登录尝试
      const concurrentAttempts = await query<{ count: number }>(
        `SELECT COUNT(*) as count FROM security_logs 
         WHERE ip_address = ? 
         AND event_type = 'login_attempt' 
         AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)`,
        [ip]
      )

      const concurrentCount = (concurrentAttempts[0] as any)?.count || 0
      if (concurrentCount >= 10) {
        riskFactors.push(`1分钟内${concurrentCount}次登录尝试`)
        riskLevel = 'critical'
        shouldBlock = true
      } else if (concurrentCount >= 5) {
        riskFactors.push(`1分钟内${concurrentCount}次登录尝试`)
        riskLevel = 'high'
      }

    } catch (error) {
      this.app?.log.error(error, '分析登录风险失败')
    }

    return {
      isRisky: riskFactors.length > 0,
      riskFactors,
      riskLevel,
      shouldBlock
    }
  }

  /**
   * 检查IP是否被临时封禁
   */
  static async isIPBlocked(ip: string): Promise<boolean> {
    try {
      const blocks = await query<{ count: number }>(
        `SELECT COUNT(*) as count FROM security_logs 
         WHERE ip_address = ? 
         AND event_type = 'login_attempt' 
         AND details LIKE '%blocked%' 
         AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)`,
        [ip]
      )

      return (blocks[0] as any)?.count > 0
    } catch (error) {
      this.app?.log.error(error, '检查IP封禁状态失败')
      return false
    }
  }

  /**
   * 获取安全统计信息
   */
  static async getSecurityStats(days: number = 7): Promise<{
    totalEvents: number
    loginAttempts: number
    failedLogins: number
    suspiciousActivities: number
    topRiskyIPs: Array<{ ip: string; count: number }>
  }> {
    try {
      const [totalEvents, loginAttempts, failedLogins, suspiciousActivities, topRiskyIPs] = await Promise.all([
        // 总事件数
        query<{ count: number }>(
          `SELECT COUNT(*) as count FROM security_logs 
           WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)`,
          [days]
        ),
        // 登录尝试数
        query<{ count: number }>(
          `SELECT COUNT(*) as count FROM security_logs 
           WHERE event_type = 'login_attempt' 
           AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)`,
          [days]
        ),
        // 失败登录数
        query<{ count: number }>(
          `SELECT COUNT(*) as count FROM security_logs 
           WHERE event_type = 'login_attempt' 
           AND details LIKE '%failed%' 
           AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)`,
          [days]
        ),
        // 可疑活动数
        query<{ count: number }>(
          `SELECT COUNT(*) as count FROM security_logs 
           WHERE risk_level IN ('high', 'critical') 
           AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)`,
          [days]
        ),
        // 风险IP排行
        query<{ ip: string; count: number }>(
          `SELECT ip_address as ip, COUNT(*) as count FROM security_logs 
           WHERE risk_level IN ('high', 'critical') 
           AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY) 
           GROUP BY ip_address 
           ORDER BY count DESC 
           LIMIT 10`,
          [days]
        )
      ])

      return {
        totalEvents: totalEvents[0]?.count || 0,
        loginAttempts: loginAttempts[0]?.count || 0,
        failedLogins: failedLogins[0]?.count || 0,
        suspiciousActivities: suspiciousActivities[0]?.count || 0,
        topRiskyIPs: topRiskyIPs || []
      }
    } catch (error) {
      this.app?.log.error(error, '获取安全统计失败')
      return {
        totalEvents: 0,
        loginAttempts: 0,
        failedLogins: 0,
        suspiciousActivities: 0,
        topRiskyIPs: []
      }
    }
  }

  /**
   * 清理过期的安全日志（保留30天）
   */
  static async cleanupOldLogs(): Promise<void> {
    try {
      await query(
        `DELETE FROM security_logs 
         WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)`
      )
      this.app?.log.info('清理过期安全日志完成')
    } catch (error) {
      this.app?.log.error(error, '清理过期安全日志失败')
    }
  }
}