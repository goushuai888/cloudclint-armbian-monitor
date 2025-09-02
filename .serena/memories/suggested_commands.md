# 建议命令列表

## 必须知道的开发命令

### 工作目录切换
```bash
cd frontend/              # 切换到前端项目目录 (所有开发工作都在此目录)
```

### 依赖管理
```bash
pnpm install             # 安装项目依赖 (首次运行或依赖更新后)
```

### 开发服务器
```bash
pnpm dev                 # 启动开发服务器，支持热重载 (默认端口)
quasar dev               # 备用命令，功能相同
```

### 代码质量检查 (任务完成后必须执行)
```bash
pnpm lint               # ESLint 代码检查，修复可自动修复的问题
pnpm format             # Prettier 代码格式化，统一代码风格
npx vue-tsc --noEmit    # TypeScript 类型检查，确保类型安全 (修改代码后必须运行)
```

### 生产构建
```bash
pnpm build              # 生产环境构建，生成优化后的静态文件
quasar build            # 备用命令，功能相同
```

### 系统命令 (macOS Darwin)
```bash
ls -la                  # 列出文件和目录 (包含隐藏文件)
find . -name "*.vue"    # 查找Vue文件
grep -r "search_term"   # 递归搜索文本
git status              # 查看Git状态
git add .               # 添加所有更改到暂存区
git commit -m "message" # 提交更改
```

## 任务完成后的检查清单

### 1. 代码质量检查 (必须全部通过)
```bash
cd frontend/
pnpm lint               # 必须无错误
pnpm format             # 自动格式化代码
npx vue-tsc --noEmit    # 必须无TypeScript错误
```

### 2. 功能测试
```bash
pnpm dev                # 启动开发服务器测试功能
```

### 3. 构建测试 (可选)
```bash
pnpm build              # 确保生产构建成功
```

## 紧急命令

### 重置开发环境
```bash
rm -rf node_modules/    # 删除依赖
rm pnpm-lock.yaml       # 删除锁文件
pnpm install            # 重新安装依赖
```

### 快速修复常见问题
```bash
pnpm format             # 修复格式问题
pnpm lint --fix         # 自动修复ESLint问题
```

## 重要提醒

1. **必须在 frontend/ 目录下执行所有命令**
2. **每次代码修改后必须运行 TypeScript 类型检查**
3. **提交代码前必须通过 lint 和 format 检查**
4. **推荐使用 pnpm 作为包管理器**
5. **开发服务器会自动打开浏览器 (配置了 open: true)**