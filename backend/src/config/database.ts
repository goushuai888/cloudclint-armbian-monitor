import mysql from 'mysql2/promise'
import { config } from './index.js'

// 数据库连接配置
const dbConfig = {
  host: config.database.host,
  port: config.database.port,
  user: config.database.user,
  password: config.database.password,
  database: config.database.name,
  charset: 'utf8mb4',
  timezone: '+00:00',
  dateStrings: ['DATE', 'DATETIME', 'TIMESTAMP'] as ('DATE' | 'DATETIME' | 'TIMESTAMP')[],
  supportBigNumbers: true,
  bigNumberStrings: true,
  acquireTimeout: 60000,
  timeout: 60000,
  reconnect: true,
  connectionLimit: 10,
  queueLimit: 0
}

// 创建连接池
export const pool = mysql.createPool(dbConfig)

// 测试数据库连接
export async function testConnection(): Promise<boolean> {
  try {
    const connection = await pool.getConnection()
    await connection.ping()
    connection.release()
    console.log('✅ 数据库连接成功')
    return true
  } catch (error) {
    console.error('❌ 数据库连接失败:', error)
    return false
  }
}

// 执行查询
export async function query<T = any>(
  sql: string, 
  params?: any[]
): Promise<T[]> {
  try {
    const [rows] = await pool.execute(sql, params)
    return rows as T[]
  } catch (error) {
    console.error('❌ SQL查询失败:', { sql, params, error })
    throw error
  }
}

// 执行单个查询并返回第一条记录
export async function queryOne<T = any>(
  sql: string, 
  params?: any[]
): Promise<T | null> {
  const rows = await query<T>(sql, params)
  return rows.length > 0 ? rows[0] : null
}

// 执行插入并返回插入ID
export async function insert(
  table: string,
  data: Record<string, any>
): Promise<number> {
  const keys = Object.keys(data)
  const values = Object.values(data)
  const placeholders = keys.map(() => '?').join(', ')
  
  const sql = `INSERT INTO ${table} (${keys.join(', ')}) VALUES (${placeholders})`
  
  try {
    const [result] = await pool.execute(sql, values)
    return (result as any).insertId
  } catch (error) {
    console.error('❌ 插入数据失败:', { table, data, error })
    throw error
  }
}

// 执行更新
export async function update(
  table: string,
  data: Record<string, any>,
  where: string,
  whereParams: any[] = []
): Promise<number> {
  const keys = Object.keys(data)
  const values = Object.values(data)
  const setClause = keys.map(key => `${key} = ?`).join(', ')
  
  const sql = `UPDATE ${table} SET ${setClause} WHERE ${where}`
  const params = [...values, ...whereParams]
  
  try {
    const [result] = await pool.execute(sql, params)
    return (result as any).affectedRows
  } catch (error) {
    console.error('❌ 更新数据失败:', { table, data, where, error })
    throw error
  }
}

// 执行删除
export async function deleteRecord(
  table: string,
  where: string,
  whereParams: any[] = []
): Promise<number> {
  const sql = `DELETE FROM ${table} WHERE ${where}`
  
  try {
    const [result] = await pool.execute(sql, whereParams)
    return (result as any).affectedRows
  } catch (error) {
    console.error('❌ 删除数据失败:', { table, where, error })
    throw error
  }
}

// 开启事务
export async function withTransaction<T>(
  callback: (connection: mysql.PoolConnection) => Promise<T>
): Promise<T> {
  const connection = await pool.getConnection()
  
  try {
    await connection.beginTransaction()
    const result = await callback(connection)
    await connection.commit()
    return result
  } catch (error) {
    await connection.rollback()
    throw error
  } finally {
    connection.release()
  }
}

// 数据库健康检查
export async function healthCheck(): Promise<{
  status: 'healthy' | 'unhealthy'
  details: {
    connected: boolean
    poolConnections: number
    error?: string
  }
}> {
  try {
    const connection = await pool.getConnection()
    await connection.ping()
    connection.release()
    
    return {
      status: 'healthy',
      details: {
        connected: true,
        poolConnections: (pool as any)._allConnections?.length || 0
      }
    }
  } catch (error) {
    return {
      status: 'unhealthy',
      details: {
        connected: false,
        poolConnections: 0,
        error: error instanceof Error ? error.message : String(error)
      }
    }
  }
}

// 优雅关闭数据库连接
export async function closePool(): Promise<void> {
  try {
    await pool.end()
    console.log('✅ 数据库连接池已关闭')
  } catch (error) {
    console.error('❌ 关闭数据库连接池失败:', error)
  }
}