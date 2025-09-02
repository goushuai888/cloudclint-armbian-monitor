import { query, queryOne, insert, update, deleteRecord } from '@/config/database.js'
import type { User, PaginationMeta } from '@/types/index.js'
import bcrypt from 'bcrypt'
import dayjs from 'dayjs'

export class UserModel {
  // 获取用户列表（带分页）
  static async getUsers(options: {
    page?: number
    limit?: number
    search?: string
    role?: string
  } = {}): Promise<{
    users: Omit<User, 'password_hash'>[]
    pagination: PaginationMeta
  }> {
    const { page = 1, limit = 20, search, role } = options

    // 构建WHERE条件
    const whereConditions: string[] = []
    const params: any[] = []

    if (search) {
      whereConditions.push('(username LIKE ? OR email LIKE ?)')
      const searchTerm = `%${search}%`
      params.push(searchTerm, searchTerm)
    }

    if (role) {
      whereConditions.push('role = ?')
      params.push(role)
    }

    const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : ''

    // 获取总数
    const countSql = `SELECT COUNT(*) as total FROM users ${whereClause}`
    const [{ total }] = await query<{ total: number }>(countSql, params)

    // 计算分页
    const totalPages = Math.ceil(total / limit)
    const offset = (page - 1) * limit

    // 获取用户列表（排除密码字段）
    const usersSql = `
      SELECT id, username, email, role, last_login, created_at, updated_at
      FROM users
      ${whereClause}
      ORDER BY created_at DESC
      LIMIT ${limit} OFFSET ${offset}
    `
    const users = await query<Omit<User, 'password_hash'>>(usersSql, params)

    return {
      users,
      pagination: {
        page,
        limit,
        total,
        totalPages
      }
    }
  }

  // 根据ID获取用户（不包含密码）
  static async getUserById(userId: number): Promise<Omit<User, 'password_hash'> | null> {
    const sql = `
      SELECT id, username, email, role, last_login, created_at, updated_at
      FROM users
      WHERE id = ?
    `
    
    return await queryOne<Omit<User, 'password_hash'>>(sql, [userId])
  }

  // 根据用户名获取用户（包含密码，用于认证）
  static async getUserByUsername(username: string): Promise<User | null> {
    const sql = 'SELECT * FROM users WHERE username = ?'
    return await queryOne<User>(sql, [username])
  }

  // 根据邮箱获取用户
  static async getUserByEmail(email: string): Promise<User | null> {
    const sql = 'SELECT * FROM users WHERE email = ?'
    return await queryOne<User>(sql, [email])
  }

  // 创建用户
  static async createUser(userData: {
    username: string
    email?: string
    password: string
    role?: string
  }): Promise<Omit<User, 'password_hash'>> {
    const {
      username,
      email = null,
      password,
      role = 'user'
    } = userData

    // 检查用户名是否已存在
    const existingUser = await this.getUserByUsername(username)
    if (existingUser) {
      throw new Error('用户名已存在')
    }

    // 检查邮箱是否已存在（如果提供了邮箱）
    if (email) {
      const existingEmail = await this.getUserByEmail(email)
      if (existingEmail) {
        throw new Error('邮箱已存在')
      }
    }

    // 加密密码
    const saltRounds = 12
    const password_hash = await bcrypt.hash(password, saltRounds)

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const insertData = {
      username,
      email,
      password_hash,
      role,
      created_at: now,
      updated_at: now
    }

    const userId = await insert('users', insertData)
    return await this.getUserById(userId) as Omit<User, 'password_hash'>
  }

  // 更新用户信息
  static async updateUser(
    userId: number,
    updateData: {
      username?: string
      email?: string
      role?: string
    }
  ): Promise<Omit<User, 'password_hash'> | null> {
    const { username, email, role } = updateData

    // 检查用户名是否已被其他用户使用
    if (username) {
      const existingUser = await this.getUserByUsername(username)
      if (existingUser && existingUser.id !== userId) {
        throw new Error('用户名已存在')
      }
    }

    // 检查邮箱是否已被其他用户使用
    if (email) {
      const existingEmail = await this.getUserByEmail(email)
      if (existingEmail && existingEmail.id !== userId) {
        throw new Error('邮箱已存在')
      }
    }

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const finalUpdateData = {
      ...updateData,
      updated_at: now
    }

    const affectedRows = await update('users', finalUpdateData, 'id = ?', [userId])
    
    if (affectedRows === 0) {
      return null
    }

    return await this.getUserById(userId)
  }

  // 更新用户密码
  static async updatePassword(
    userId: number,
    currentPassword: string,
    newPassword: string
  ): Promise<boolean> {
    // 获取用户当前密码
    const user = await queryOne<User>('SELECT * FROM users WHERE id = ?', [userId])
    if (!user) {
      throw new Error('用户不存在')
    }

    // 验证当前密码
    const passwordValid = await bcrypt.compare(currentPassword, user.password_hash)
    if (!passwordValid) {
      throw new Error('当前密码错误')
    }

    // 加密新密码
    const saltRounds = 12
    const newPasswordHash = await bcrypt.hash(newPassword, saltRounds)

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const affectedRows = await update(
      'users',
      { password_hash: newPasswordHash, updated_at: now },
      'id = ?',
      [userId]
    )

    return affectedRows > 0
  }

  // 重置用户密码（管理员功能）
  static async resetPassword(
    userId: number,
    newPassword: string
  ): Promise<boolean> {
    // 加密新密码
    const saltRounds = 12
    const newPasswordHash = await bcrypt.hash(newPassword, saltRounds)

    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const affectedRows = await update(
      'users',
      { password_hash: newPasswordHash, updated_at: now },
      'id = ?',
      [userId]
    )

    return affectedRows > 0
  }

  // 删除用户
  static async deleteUser(userId: number): Promise<boolean> {
    // 检查是否为系统管理员（假设ID为1的用户是系统管理员）
    if (userId === 1) {
      throw new Error('不能删除系统管理员账户')
    }

    const affectedRows = await deleteRecord('users', 'id = ?', [userId])
    return affectedRows > 0
  }

  // 批量删除用户
  static async batchDeleteUsers(userIds: number[]): Promise<number> {
    if (userIds.length === 0) return 0

    // 检查是否包含系统管理员
    if (userIds.includes(1)) {
      throw new Error('不能删除系统管理员账户')
    }

    const placeholders = userIds.map(() => '?').join(',')
    const affectedRows = await deleteRecord('users', `id IN (${placeholders})`, userIds)
    return affectedRows
  }

  // 更新最后登录时间
  static async updateLastLogin(userId: number): Promise<boolean> {
    const now = dayjs().format('YYYY-MM-DD HH:mm:ss')
    const affectedRows = await update(
      'users',
      { last_login: now, updated_at: now },
      'id = ?',
      [userId]
    )

    return affectedRows > 0
  }

  // 获取用户统计信息
  static async getUserStats(): Promise<{
    total: number
    admins: number
    users: number
    recentLogins: number
  }> {
    const sql = `
      SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as users,
        SUM(CASE 
          WHEN last_login IS NOT NULL AND last_login > DATE_SUB(NOW(), INTERVAL 7 DAY) 
          THEN 1 
          ELSE 0 
        END) as recentLogins
      FROM users
    `
    
    const [result] = await query<{
      total: number
      admins: number
      users: number
      recentLogins: number
    }>(sql)

    return result
  }

  // 检查用户权限
  static async checkUserPermission(userId: number, requiredRole: string = 'admin'): Promise<boolean> {
    const user = await this.getUserById(userId)
    if (!user) return false

    // 简单的角色检查，可以根据需要扩展
    if (requiredRole === 'admin') {
      return user.role === 'admin'
    }

    return true
  }

  // 验证用户密码
  static async verifyPassword(username: string, password: string): Promise<User | null> {
    const user = await this.getUserByUsername(username)
    if (!user) return null

    const passwordValid = await bcrypt.compare(password, user.password_hash)
    if (!passwordValid) return null

    return user
  }
}