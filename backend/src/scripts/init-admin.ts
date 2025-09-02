#!/usr/bin/env tsx

import { UserModel } from '../models/User.js'
import { testConnection } from '../config/database.js'
import { validateConfig } from '../config/index.js'

async function initAdmin() {
  try {
    // 验证配置
    validateConfig()
    
    // 测试数据库连接
    const dbConnected = await testConnection()
    if (!dbConnected) {
      throw new Error('数据库连接失败')
    }

    // 检查是否已存在 admin 用户
    const existingAdmin = await UserModel.getUserByUsername('admin')
    if (existingAdmin) {
      console.log('✅ Admin 用户已存在')
      return
    }

    // 创建默认 admin 用户
    const adminData = {
      username: 'admin',
      email: 'admin@armbian.local',
      password: 'admin123',
      role: 'admin' as const
    }

    console.log('🔧 正在创建默认 admin 用户...')
    await UserModel.createUser(adminData)
    
    console.log('✅ 默认用户创建成功:')
    console.log(`   用户名: admin`)
    console.log(`   密码: admin123`)
    console.log(`   角色: admin`)
    console.log(`   邮箱: admin@armbian.local`)
    console.log('')
    console.log('⚠️  请在生产环境中修改默认密码!')

  } catch (error) {
    console.error('❌ 初始化失败:', error instanceof Error ? error.message : error)
    process.exit(1)
  }
}

// 如果直接运行此脚本
if (import.meta.url === `file://${process.argv[1]}`) {
  initAdmin().then(() => {
    console.log('🎉 初始化完成')
    process.exit(0)
  }).catch(error => {
    console.error('初始化失败:', error)
    process.exit(1)
  })
}

export { initAdmin }