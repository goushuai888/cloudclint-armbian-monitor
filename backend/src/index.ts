import { buildApp } from './app.js'

async function main() {
  try {
    const app = await buildApp()
    
    await app.listen({
      host: '0.0.0.0',
      port: 3000
    })
    
  } catch (error) {
    console.error('启动失败:', error)
    process.exit(1)
  }
}

main()