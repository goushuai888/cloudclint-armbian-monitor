# ArmbianBox 项目配置规范

## 数据库环境
- **部署方式**: Docker 容器化部署
- **连接配置**:
  ```env
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_NAME=armbox
  DB_USER=root
  DB_PASSWORD=root123
  ```

## 服务端口配置
- **前端开发服务器**: `http://localhost:9000`
- **后端API服务器**: `http://localhost:3000`

## 开发环境要求
- 数据库: MySQL 8.0+ (Docker 容器)
- 前端: Vue 3 + Quasar + TypeScript
- 后端: Node.js + Fastify + TypeScript
- 包管理: pnpm (强制使用)
前端必须使用 quasar 框架自带的组件