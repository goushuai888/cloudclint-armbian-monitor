#!/usr/bin/env tsx

import { query } from '../config/database.js'
import { validateConfig } from '../config/index.js'

async function checkUsers() {
  try {
    validateConfig()
    
    console.log('📋 检查用户数据...')
    const users = await query('SELECT id, username, email, role, created_at, password_hash FROM users LIMIT 10')
    
    if (users.length === 0) {
      console.log('❌ 没有找到任何用户数据')
      return
    }
    
    console.log(`✅ 找到 ${users.length} 个用户:`)
    users.forEach((user: any) => {
      console.log(`   ID: ${user.id}, 用户名: ${user.username}, 邮箱: ${user.email}, 角色: ${user.role}`)
      console.log(`   密码哈希: ${user.password_hash ? '✅' : '❌'}`)
      console.log(`   创建时间: ${user.created_at}`)
      console.log('   ---')
    })
    
  } catch (error) {
    if (error instanceof Error && error.message.includes("doesn't exist")) {
      console.log('❌ 用户表不存在，需要先创建数据库表')
    } else {
      console.error('❌ 查询失败:', error instanceof Error ? error.message : error)
    }
  }
}

checkUsers().then(() => {
  console.log('🎉 检查完成')
  process.exit(0)
}).catch(error => {
  console.error('检查失败:', error)
  process.exit(1)
})