import { FastifyInstance, FastifyRequest, FastifyReply } from 'fastify'
import bcrypt from 'bcrypt'
import { queryOne, insert } from '@/config/database.js'
import { config } from '@/config/index.js'
import type { User, LoginLog, ApiResponse, JWTPayload, TokenResponse } from '@/types/index.js'
import { RefreshTokenService } from '@/services/refreshTokenService.js'
import { SecurityLogService } from '@/services/securityLogService.js'
import dayjs from 'dayjs'

// 认证路由插件
export default async function authRoutes(app: FastifyInstance) {
  
  // 用户登录
  app.post('/login', {
    schema: {
      body: {
        type: 'object',
        required: ['username', 'password'],
        properties: {
          username: { 
            type: 'string', 
            minLength: 3, 
            maxLength: 50 
          },
          password: { 
            type: 'string', 
            minLength: 6 
          }
        }
      }
    }
  }, async (request: FastifyRequest<{
    Body: {
      username: string
      password: string
    }
  }>, reply: FastifyReply) => {
    const { username, password } = request.body
    const ip = request.ip
    const userAgent = request.headers['user-agent'] || null

    // 记录登录日志的辅助函数
    const logLogin = async (status: 'success' | 'failed', reason?: string, userId?: number) => {
      try {
        await insert('login_logs', {
          user_id: userId || null,
          username,
          ip_address: ip,
          user_agent: userAgent,
          status: status,
          failure_reason: reason || null,
          created_at: dayjs().format('YYYY-MM-DD HH:mm:ss')
        })
      } catch (logError) {
        app.log.error(logError, '记录登录日志失败')
      }
    }

    try {
      // 分析登录风险
      const riskAnalysis = await SecurityLogService.analyzeLoginAttempt(
        username,
        ip,
        userAgent || 'Unknown'
      )

      // 检查IP是否被封禁
      if (await SecurityLogService.isIPBlocked(ip)) {
        await SecurityLogService.logSecurityEvent({
          event_type: 'login_attempt',
          username,
          ip_address: ip,
          user_agent: userAgent || 'Unknown',
          details: JSON.stringify({ status: 'blocked', reason: 'IP被临时封禁' }),
          risk_level: 'critical'
        })
        
        return reply.code(429).send({
          success: false,
          message: '登录尝试过于频繁，请稍后再试',
          timestamp: new Date().toISOString()
        })
      }

      // 如果风险过高，阻止登录
      if (riskAnalysis.shouldBlock) {
        await SecurityLogService.logSecurityEvent({
          event_type: 'login_attempt',
          username,
          ip_address: ip,
          user_agent: userAgent || 'Unknown',
          details: JSON.stringify({ 
            status: 'blocked', 
            reason: '风险过高', 
            riskFactors: riskAnalysis.riskFactors 
          }),
          risk_level: riskAnalysis.riskLevel
        })
        
        return reply.code(429).send({
          success: false,
          message: '检测到异常登录行为，请稍后再试',
          timestamp: new Date().toISOString()
        })
      }

      // 查找用户
      const user = await queryOne<User>(
        'SELECT * FROM users WHERE username = ?',
        [username]
      )

      if (!user) {
        await logLogin('failed', '用户不存在')
        await SecurityLogService.logSecurityEvent({
          event_type: 'login_attempt',
          username,
          ip_address: ip,
          user_agent: userAgent || 'Unknown',
          details: JSON.stringify({ status: 'failed', reason: '用户不存在' }),
          risk_level: riskAnalysis.riskLevel
        })
        
        return reply.code(401).send({
          success: false,
          message: '用户名或密码错误',
          timestamp: new Date().toISOString()
        })
      }

      // 验证密码
      if (!user.password) {
        await logLogin('failed', '用户密码未设置')
        await SecurityLogService.logSecurityEvent({
          event_type: 'login_attempt',
          user_id: user.id,
          username,
          ip_address: ip,
          user_agent: userAgent || 'Unknown',
          details: JSON.stringify({ status: 'failed', reason: '用户密码未设置' }),
          risk_level: riskAnalysis.riskLevel
        })
        
        return reply.code(401).send({
          success: false,
          message: '用户名或密码错误',
          timestamp: new Date().toISOString()
        })
      }
      
      // 处理PHP bcrypt格式兼容性：将$2y$替换为$2b$
      const normalizedHash = user.password.replace(/^\$2y\$/, '$2b$')
      const passwordValid = await bcrypt.compare(password, normalizedHash)
      
      if (!passwordValid) {
        await logLogin('failed', '密码错误')
        await SecurityLogService.logSecurityEvent({
          event_type: 'login_attempt',
          user_id: user.id,
          username,
          ip_address: ip,
          user_agent: userAgent || 'Unknown',
          details: JSON.stringify({ status: 'failed', reason: '密码错误' }),
          risk_level: riskAnalysis.riskLevel
        })
        
        return reply.code(401).send({
          success: false,
          message: '用户名或密码错误',
          timestamp: new Date().toISOString()
        })
      }

      // 更新最后登录时间
      const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
      await queryOne(
        'UPDATE users SET last_login = ? WHERE id = ?',
        [now, user.id]
      )

      // 生成设备指纹
      const deviceFingerprint = RefreshTokenService.generateDeviceFingerprint(
        request.headers['user-agent'],
        request.ip
      )

      // 限制用户的refresh token数量
      await RefreshTokenService.limitUserTokens(user.id)

      // 生成refresh token
      const refreshToken = await RefreshTokenService.createRefreshToken(
        user.id,
        deviceFingerprint
      )

      // 生成JWT access token
      const payload: JWTPayload = {
        id: user.id,
        username: user.username,
        role: user.role
      }

      const accessToken = app.jwt.sign(payload, {
        expiresIn: config.jwt.expiresIn
      })

      // 记录成功登录
      await logLogin('success', undefined, user.id)
      await SecurityLogService.logSecurityEvent({
        event_type: 'login_attempt',
        user_id: user.id,
        username,
        ip_address: ip,
        user_agent: userAgent || 'Unknown',
        details: JSON.stringify({ status: 'success' }),
        risk_level: 'low'
      })

      // 设置httpOnly cookie存储refresh token
      reply.setCookie('refreshToken', refreshToken, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict',
        maxAge: 7 * 24 * 60 * 60 * 1000, // 7天
        path: '/'
      })

      const tokenResponse: TokenResponse = {
        accessToken,
        refreshToken, // 仍然在响应中返回，前端可以选择使用
        expiresIn: 15 * 60, // 15分钟，以秒为单位
        tokenType: 'Bearer'
      }

      const response: ApiResponse = {
        success: true,
        message: '登录成功',
        data: {
          user: {
            id: user.id,
            username: user.username,
            email: user.email,
            role: user.role,
            last_login: now
          },
          ...tokenResponse
        },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)

    } catch (error) {
      app.log.error(error, '登录过程中发生错误')
      
      try {
        await logLogin('failed', '服务器错误')
      } catch (logError) {
        app.log.error(logError, '记录登录日志失败')
      }

      return reply.code(500).send({
        success: false,
        message: '登录失败，请稍后重试',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 刷新令牌
  app.post('/refresh', async (request, reply) => {
    try {
      // 优先从cookie中读取refresh token，如果没有则从请求体中读取
      const cookieRefreshToken = request.cookies?.refreshToken
      const { refreshToken: bodyRefreshToken } = request.body as { refreshToken?: string }
      const refreshToken = cookieRefreshToken || bodyRefreshToken
      
      if (!refreshToken) {
        return reply.code(400).send({
          success: false,
          message: 'Refresh token is required',
          timestamp: new Date().toISOString()
        })
      }

      // 生成设备指纹
      const deviceFingerprint = RefreshTokenService.generateDeviceFingerprint(
        request.headers['user-agent'],
        request.ip
      )

      // 验证refresh token
      const tokenRecord = await RefreshTokenService.validateRefreshToken(
        refreshToken,
        deviceFingerprint
      )

      if (!tokenRecord) {
        // 记录无效的refresh token尝试
        await SecurityLogService.logSecurityEvent({
          event_type: 'token_refresh',
          ip_address: request.ip,
          user_agent: request.headers['user-agent'] || 'Unknown',
          details: JSON.stringify({ 
            status: 'failed', 
            reason: '无效的refresh token',
            deviceFingerprint 
          }),
          risk_level: 'medium'
        })
        
        return reply.code(401).send({
          success: false,
          message: 'Invalid or expired refresh token',
          timestamp: new Date().toISOString()
        })
      }

      // 获取用户信息
      const user = await queryOne<User>(
        'SELECT * FROM users WHERE id = ?',
        [tokenRecord.user_id]
      )

      if (!user) {
        return reply.code(401).send({
          success: false,
          message: 'User not found',
          timestamp: new Date().toISOString()
        })
      }

      // 生成新的access token
      const payload: JWTPayload = {
        id: user.id,
        username: user.username,
        role: user.role
      }

      const accessToken = app.jwt.sign(payload, {
        expiresIn: config.jwt.expiresIn
      })

      // 生成新的refresh token
      const newRefreshToken = await RefreshTokenService.createRefreshToken(
        user.id,
        deviceFingerprint
      )

      // 撤销旧的refresh token
      await RefreshTokenService.revokeRefreshToken(refreshToken)

      // 设置httpOnly cookie存储refresh token
      reply.setCookie('refreshToken', newRefreshToken, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict',
        maxAge: 7 * 24 * 60 * 60 * 1000, // 7天
        path: '/'
      })

      const tokenResponse: TokenResponse = {
        accessToken,
        refreshToken: newRefreshToken, // 仍然在响应中返回，前端可以选择使用
        expiresIn: 15 * 60, // 15分钟，以秒为单位
        tokenType: 'Bearer'
      }

      const response: ApiResponse = {
        success: true,
        message: '令牌刷新成功',
        data: tokenResponse,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)

    } catch (error) {
      app.log.error(error, '刷新令牌失败')
      return reply.code(500).send({
        success: false,
        message: '刷新令牌失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取当前用户信息
  app.get('/me', {
    preHandler: [app.authenticate]
  }, async (request, reply) => {
    try {
      const userId = (request.user as any)?.id
      
      const user = await queryOne<User>(
        'SELECT id, username, email, role, last_login, created_at FROM users WHERE id = ?',
        [userId]
      )

      if (!user) {
        return reply.code(404).send({
          success: false,
          message: '用户不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse = {
        success: true,
        message: '获取用户信息成功',
        data: user,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)

    } catch (error) {
      app.log.error(error, '获取用户信息失败')
      return reply.code(500).send({
        success: false,
        message: '获取用户信息失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 修改密码 (兼容旧接口)
  app.post('/change-password', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['current_password', 'new_password'],
        properties: {
          current_password: { type: 'string', minLength: 6 },
          new_password: { type: 'string', minLength: 6, maxLength: 100 }
        }
      }
    }
  }, async (request, reply) => {
    const { current_password, new_password } = request.body as { current_password: string; new_password: string }
    const userId = (request.user as any)?.id

    try {
      // 获取当前用户信息
      const user = await queryOne<User>(
        'SELECT password FROM users WHERE id = ?',
        [userId]
      )

      if (!user) {
        return reply.code(404).send({
          success: false,
          message: '用户不存在',
          timestamp: new Date().toISOString()
        })
      }

      // 验证当前密码
      const currentPasswordValid = await bcrypt.compare(current_password, user.password)
      
      if (!currentPasswordValid) {
        return reply.code(401).send({
          success: false,
          message: '当前密码错误',
          timestamp: new Date().toISOString()
        })
      }

      // 加密新密码
      const saltRounds = 12
      const newPasswordHash = await bcrypt.hash(new_password, saltRounds)

      // 更新密码
      await queryOne(
        'UPDATE users SET password = ?, updated_at = ? WHERE id = ?',
        [newPasswordHash, dayjs().format('YYYY-MM-DD HH:mm:ss'), userId]
      )

      const response: ApiResponse = {
        success: true,
        message: '密码修改成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)

    } catch (error) {
      app.log.error(error, '修改密码失败')
      return reply.code(500).send({
        success: false,
        message: '修改密码失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 修改密码 (新接口)
  app.put('/password', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['current_password', 'new_password'],
        properties: {
          current_password: { type: 'string', minLength: 6 },
          new_password: { type: 'string', minLength: 6, maxLength: 100 }
        }
      }
    }
  }, async (request, reply) => {
    const { current_password, new_password } = request.body as { current_password: string; new_password: string }
    const userId = (request.user as any)?.id

    try {
      // 获取当前用户信息
      const user = await queryOne<User>(
        'SELECT password FROM users WHERE id = ?',
        [userId]
      )

      if (!user) {
        return reply.code(404).send({
          success: false,
          message: '用户不存在',
          timestamp: new Date().toISOString()
        })
      }

      // 验证当前密码
      const currentPasswordValid = await bcrypt.compare(current_password, user.password)
      
      if (!currentPasswordValid) {
        return reply.code(401).send({
          success: false,
          message: '当前密码错误',
          timestamp: new Date().toISOString()
        })
      }

      // 加密新密码
      const saltRounds = 12
      const newPasswordHash = await bcrypt.hash(new_password, saltRounds)

      // 更新密码
      await queryOne(
        'UPDATE users SET password = ?, updated_at = ? WHERE id = ?',
        [newPasswordHash, dayjs().format('YYYY-MM-DD HH:mm:ss'), userId]
      )

      const response: ApiResponse = {
        success: true,
        message: '密码修改成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)

    } catch (error) {
      app.log.error(error, '修改密码失败')
      return reply.code(500).send({
        success: false,
        message: '修改密码失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 登出
  app.post('/logout', async (request, reply) => {
    try {
      // 优先从cookie中读取refresh token，如果没有则从请求体中读取
      const cookieRefreshToken = request.cookies?.refreshToken
      const { refreshToken: bodyRefreshToken } = request.body as { refreshToken?: string }
      const refreshToken = cookieRefreshToken || bodyRefreshToken
      
      if (refreshToken) {
        // 撤销refresh token
        await RefreshTokenService.revokeRefreshToken(refreshToken)
      }

      // 记录登出事件
      await SecurityLogService.logSecurityEvent({
        event_type: 'logout',
        ip_address: request.ip,
        user_agent: request.headers['user-agent'] || 'Unknown',
        details: JSON.stringify({ status: 'success' }),
        risk_level: 'low'
      })

      // 清除httpOnly cookie
      reply.clearCookie('refreshToken', {
        path: '/'
      })

      const response: ApiResponse = {
        success: true,
        message: '登出成功',
        data: null,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)

    } catch (error) {
      app.log.error(error, '登出失败')
      return reply.code(500).send({
        success: false,
        message: '登出失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取登录日志（管理员功能）
  app.get('/login-logs', {
    preHandler: [app.authenticate],
    schema: {
      querystring: {
        type: 'object',
        properties: {
          page: { type: 'number', minimum: 1, default: 1 },
          limit: { type: 'number', minimum: 1, maximum: 100, default: 20 },
          username: { type: 'string' },
          status: { type: 'string', enum: ['success', 'failed'] }
        }
      }
    }
  }, async (request, reply) => {
    try {
      // 检查权限（只有管理员可以查看登录日志）
      if ((request.user as any)?.role !== 'admin') {
        return reply.code(403).send({
          success: false,
          message: '权限不足',
          timestamp: new Date().toISOString()
        })
      }

      const { page = 1, limit = 20, username, status } = request.query as {
        page?: number
        limit?: number
        username?: string
        status?: 'success' | 'failed'
      }

      // 构建查询条件
      const whereConditions: string[] = []
      const params: any[] = []

      if (username) {
        whereConditions.push('username LIKE ?')
        params.push(`%${username}%`)
      }

      if (status) {
        whereConditions.push('status = ?')
        params.push(status)
      }

      const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : ''

      // 获取总数
      const totalResult = await queryOne<{ total: number }>(
        `SELECT COUNT(*) as total FROM login_logs ${whereClause}`,
        params
      )
      const total = totalResult?.total || 0

      // 计算分页
      const totalPages = Math.ceil(total / limit)
      const offset = (page - 1) * limit

      // 获取日志列表
      const logs = await queryOne<LoginLog[]>(
        `SELECT * FROM login_logs ${whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?`,
        [...params, limit, offset]
      ) || []

      const response: ApiResponse = {
        success: true,
        message: '获取登录日志成功',
        data: {
          logs,
          pagination: {
            page,
            limit,
            total,
            totalPages
          }
        },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)

    } catch (error) {
      app.log.error(error, '获取登录日志失败')
      return reply.code(500).send({
        success: false,
        message: '获取登录日志失败',
        timestamp: new Date().toISOString()
      })
    }
  })
}