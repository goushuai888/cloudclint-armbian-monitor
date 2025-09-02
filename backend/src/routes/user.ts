import { FastifyInstance } from 'fastify'
import { UserModel } from '@/models/User.js'
import type { User, ApiResponse } from '@/types/index.js'

// 用户管理路由插件
export default async function userRoutes(app: FastifyInstance) {
  
  // 获取用户列表（带分页）
  app.get<{
    Querystring: {
      page?: number
      limit?: number
      search?: string
      role?: string
      status?: string
    }
  }>('/', {
    preHandler: [app.authenticate],
    schema: {
      querystring: {
        type: 'object',
        properties: {
          page: { type: 'number', minimum: 1, default: 1 },
          limit: { type: 'number', minimum: 1, maximum: 100, default: 20 },
          search: { type: 'string' },
          role: { type: 'string', enum: ['admin', 'user'] },
          status: { type: 'string', enum: ['active', 'inactive'] }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const options = request.query
      const result = await UserModel.getUsers(options)

      const response: ApiResponse = {
        success: true,
        message: '获取用户列表成功',
        data: result,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取用户列表失败')
      return reply.code(500).send({
        success: false,
        message: '获取用户列表失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取单个用户信息
  app.get<{
    Params: { userId: number }
  }>('/:userId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          userId: { type: 'number' }
        },
        required: ['userId']
      }
    }
  }, async (request, reply) => {
    try {
      const { userId } = request.params
      const user = await UserModel.getUserById(userId)

      if (!user) {
        return reply.code(404).send({
          success: false,
          message: '用户不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse<Omit<User, 'password_hash'>> = {
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

  // 创建用户
  app.post<{
    Body: {
      username: string
      email: string
      password: string
      full_name?: string
      role?: 'admin' | 'user'
      status?: 'active' | 'inactive'
      avatar?: string
      phone?: string
    }
  }>('/', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['username', 'email', 'password'],
        properties: {
          username: { type: 'string', minLength: 3, maxLength: 50 },
          email: { type: 'string', format: 'email' },
          password: { type: 'string', minLength: 6 },
          full_name: { type: 'string', maxLength: 100 },
          role: { type: 'string', enum: ['admin', 'user'], default: 'user' },
          status: { type: 'string', enum: ['active', 'inactive'], default: 'active' },
          avatar: { type: 'string', maxLength: 255 },
          phone: { type: 'string', maxLength: 20 }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const userData = request.body
      const user = await UserModel.createUser(userData)

      const response: ApiResponse<Omit<User, 'password_hash'>> = {
        success: true,
        message: '创建用户成功',
        data: user,
        timestamp: new Date().toISOString()
      }

      return reply.code(201).send(response)
    } catch (error) {
      app.log.error(error, '创建用户失败')
      
      if (error instanceof Error && (error.message.includes('已存在') || error.message.includes('UNIQUE'))) {
        return reply.code(409).send({
          success: false,
          message: '用户名或邮箱已存在',
          timestamp: new Date().toISOString()
        })
      }

      return reply.code(500).send({
        success: false,
        message: '创建用户失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 更新用户信息
  app.put<{
    Params: { userId: number }
    Body: {
      username?: string
      email?: string
      full_name?: string
      role?: 'admin' | 'user'
      status?: 'active' | 'inactive'
      avatar?: string
      phone?: string
    }
  }>('/:userId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          userId: { type: 'number' }
        },
        required: ['userId']
      },
      body: {
        type: 'object',
        properties: {
          username: { type: 'string', minLength: 3, maxLength: 50 },
          email: { type: 'string', format: 'email' },
          full_name: { type: 'string', maxLength: 100 },
          role: { type: 'string', enum: ['admin', 'user'] },
          status: { type: 'string', enum: ['active', 'inactive'] },
          avatar: { type: 'string', maxLength: 255 },
          phone: { type: 'string', maxLength: 20 }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { userId } = request.params
      const updateData = request.body

      const user = await UserModel.updateUser(userId, updateData)

      if (!user) {
        return reply.code(404).send({
          success: false,
          message: '用户不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse<Omit<User, 'password_hash'>> = {
        success: true,
        message: '更新用户信息成功',
        data: user,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '更新用户信息失败')
      
      if (error instanceof Error && (error.message.includes('已存在') || error.message.includes('UNIQUE'))) {
        return reply.code(409).send({
          success: false,
          message: '用户名或邮箱已存在',
          timestamp: new Date().toISOString()
        })
      }

      return reply.code(500).send({
        success: false,
        message: '更新用户信息失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 更新用户密码
  app.put<{
    Params: { userId: number }
    Body: {
      current_password?: string
      new_password: string
    }
  }>('/:userId/password', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          userId: { type: 'number' }
        },
        required: ['userId']
      },
      body: {
        type: 'object',
        required: ['new_password'],
        properties: {
          current_password: { type: 'string' },
          new_password: { type: 'string', minLength: 6 }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { userId } = request.params
      const { current_password, new_password } = request.body

      // 如果提供了当前密码，需要验证
      if (current_password) {
        const isValid = await UserModel.verifyPassword(userId.toString(), current_password)
        if (!isValid) {
          return reply.code(400).send({
            success: false,
            message: '当前密码不正确',
            timestamp: new Date().toISOString()
          })
        }
      }

      const success = await UserModel.updatePassword(userId.toString(), new_password)

      if (!success) {
        return reply.code(404).send({
          success: false,
          message: '用户不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse = {
        success: true,
        message: '更新密码成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '更新密码失败')
      return reply.code(500).send({
        success: false,
        message: '更新密码失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 重置用户密码
  app.post<{
    Params: { userId: number }
    Body: {
      new_password: string
    }
  }>('/:userId/reset-password', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          userId: { type: 'number' }
        },
        required: ['userId']
      },
      body: {
        type: 'object',
        required: ['new_password'],
        properties: {
          new_password: { type: 'string', minLength: 6 }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { userId } = request.params
      const { new_password } = request.body

      const success = await UserModel.resetPassword(userId, new_password)

      if (!success) {
        return reply.code(404).send({
          success: false,
          message: '用户不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse = {
        success: true,
        message: '重置密码成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '重置密码失败')
      return reply.code(500).send({
        success: false,
        message: '重置密码失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 删除用户
  app.delete<{
    Params: { userId: number }
  }>('/:userId', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          userId: { type: 'number' }
        },
        required: ['userId']
      }
    }
  }, async (request, reply) => {
    try {
      const { userId } = request.params
      const success = await UserModel.deleteUser(userId)

      if (!success) {
        return reply.code(404).send({
          success: false,
          message: '用户不存在',
          timestamp: new Date().toISOString()
        })
      }

      const response: ApiResponse = {
        success: true,
        message: '删除用户成功',
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '删除用户失败')
      return reply.code(500).send({
        success: false,
        message: '删除用户失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 批量删除用户
  app.delete<{
    Body: { user_ids: number[] }
  }>('/', {
    preHandler: [app.authenticate],
    schema: {
      body: {
        type: 'object',
        required: ['user_ids'],
        properties: {
          user_ids: {
            type: 'array',
            items: { type: 'number' },
            minItems: 1
          }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { user_ids } = request.body
      const deletedCount = await UserModel.batchDeleteUsers(user_ids)

      const response: ApiResponse = {
        success: true,
        message: `成功删除 ${deletedCount} 个用户`,
        data: { deletedCount },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '批量删除用户失败')
      return reply.code(500).send({
        success: false,
        message: '批量删除用户失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 获取用户统计信息
  app.get('/stats/overview', {
    preHandler: [app.authenticate]
  }, async (request, reply) => {
    try {
      const stats = await UserModel.getUserStats()

      const response: ApiResponse = {
        success: true,
        message: '获取用户统计成功',
        data: stats,
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '获取用户统计失败')
      return reply.code(500).send({
        success: false,
        message: '获取用户统计失败',
        timestamp: new Date().toISOString()
      })
    }
  })

  // 检查用户权限
  app.get<{
    Params: { userId: number }
    Querystring: { permission: string }
  }>('/:userId/permissions', {
    preHandler: [app.authenticate],
    schema: {
      params: {
        type: 'object',
        properties: {
          userId: { type: 'number' }
        },
        required: ['userId']
      },
      querystring: {
        type: 'object',
        required: ['permission'],
        properties: {
          permission: { type: 'string' }
        }
      }
    }
  }, async (request, reply) => {
    try {
      const { userId } = request.params
      const { permission } = request.query
      
      const hasPermission = await UserModel.checkUserPermission(userId, permission)

      const response: ApiResponse = {
        success: true,
        message: '权限检查完成',
        data: { hasPermission },
        timestamp: new Date().toISOString()
      }

      return reply.code(200).send(response)
    } catch (error) {
      app.log.error(error, '权限检查失败')
      return reply.code(500).send({
        success: false,
        message: '权限检查失败',
        timestamp: new Date().toISOString()
      })
    }
  })
}