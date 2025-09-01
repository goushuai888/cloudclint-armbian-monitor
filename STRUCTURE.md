# 项目文件结构

## 📁 目录结构

```
cloudclint-armbian-monitor/
├── 📁 api/                     # API接口目录
│   ├── auth_check.php          # 认证检查接口
│   ├── device_groups.php       # 设备分组管理
│   ├── heartbeat.php           # 设备心跳接口
│   ├── update_device.php       # 设备信息更新
│   └── ...
├── 📁 assets/                  # 静态资源目录
│   ├── 📁 css/                 # 样式文件
│   │   ├── cloudclint-main.css     # 🆕 主样式文件（整合版）
│   │   ├── cloudclint-ui.css       # 核心UI样式
│   │   ├── bootstrap.min.css       # Bootstrap框架
│   │   └── ...
│   ├── 📁 js/                  # JavaScript文件
│   │   ├── cloudclint-main.js      # 🆕 主脚本文件（整合版）
│   │   ├── bootstrap.bundle.min.js # Bootstrap脚本
│   │   └── ...
│   └── 📁 fonts/               # 字体文件
├── 📁 classes/                 # PHP类文件目录
│   ├── Auth.php                # 用户认证类
│   ├── Device.php              # 设备管理类
│   ├── ConnectionPool.php      # 数据库连接池
│   └── ...
├── 📁 client/                  # 客户端脚本目录
│   └── armbian_monitor.sh      # Armbian监控脚本
├── 📁 config/                  # 配置文件目录
│   ├── database.php            # 数据库连接类
│   ├── db_config.example.php   # 数据库配置模板
│   ├── db_config.php           # 数据库配置（敏感文件）
│   ├── security.php            # 安全配置
│   └── timezone.php            # 时区配置
├── 📁 controllers/             # 控制器目录
│   └── DeviceController.php    # 设备控制器
├── 📁 includes/                # 公共包含文件
│   ├── header.php              # 页面头部
│   ├── footer.php              # 页面底部
│   ├── functions.php           # 公共函数
│   └── modals.php              # 模态框组件
├── 📁 sql/                     # 数据库脚本目录
│   ├── init.sql                # 数据库初始化脚本
│   ├── user_auth.sql           # 用户认证表结构
│   └── ...
├── 📁 views/                   # 视图文件目录
│   ├── 📁 components/          # 组件目录
│   │   ├── device_card.php     # 设备卡片组件
│   │   ├── device_filters.php  # 设备筛选组件
│   │   └── ...
│   └── device_index.php        # 设备列表视图
├── 📄 .gitignore               # Git忽略文件
├── 📄 .htaccess                # Apache配置文件
├── 📄 admin.php                # 管理后台入口
├── 📄 change_password.php      # 密码修改页面
├── 📄 index.php                # 网站入口文件
├── 📄 login.php                # 登录页面
├── 📄 logout.php               # 退出登录
├── 📄 manifest.json            # PWA应用清单
├── 📄 README.md                # 项目说明文档
├── 📄 SECURITY_CHECKLIST.md    # 安全检查清单
├── 📄 STRUCTURE.md             # 项目结构说明
└── 📄 robots.txt               # 搜索引擎爬虫规则
```

## 🎯 核心文件说明

### 入口文件
- **index.php** - 网站主页，设备监控仪表板
- **admin.php** - 管理后台入口
- **login.php** - 用户登录页面

### 配置文件
- **config/database.php** - 数据库连接类，支持环境变量和配置文件
- **config/db_config.php** - 数据库连接参数（敏感文件，不提交到Git）
- **config/security.php** - 安全相关配置
- **.htaccess** - Web服务器配置，包含缓存、压缩、HTTPS重定向等

### API接口
- **api/heartbeat.php** - 设备心跳数据接收接口
- **api/update_device.php** - 设备信息更新接口
- **api/auth_check.php** - 用户认证检查接口

### 前端资源
- **assets/css/cloudclint-main.css** - 整合的主要样式文件
- **assets/js/cloudclint-main.js** - 整合的主要脚本文件
- **manifest.json** - PWA应用清单，支持离线访问

### 客户端
- **client/armbian_monitor.sh** - Armbian设备监控脚本

## 🔧 开发规范

### 文件命名规范
- **PHP文件**: 使用下划线分隔，如 `device_index.php`
- **CSS文件**: 使用连字符分隔，如 `cloudclint-main.css`  
- **JavaScript文件**: 使用连字符分隔，如 `cloudclint-main.js`
- **类文件**: 使用PascalCase，如 `DeviceController.php`

### 目录组织原则
- **api/**: 所有API接口文件
- **assets/**: 所有静态资源文件
- **classes/**: PHP类文件，遵循PSR-4规范
- **config/**: 配置文件，敏感文件不提交到Git
- **includes/**: 公共包含文件和组件
- **views/**: 视图模板文件

### 安全文件保护
以下文件包含敏感信息，已在`.gitignore`中排除：
- `config/db_config.php` - 数据库连接密码
- `.env` - 环境变量文件
- 所有 `.log` 文件
- `cache/` 目录下的缓存文件

## 📈 重构优化成果

### 文件整合
- **CSS文件**: 从28个减少到12个（-57%）
- **JavaScript文件**: 从11个减少到5个（-55%）
- **HTTP请求**: 减少56%

### 新增功能
- ✅ PWA支持（离线访问）
- ✅ Service Worker缓存策略
- ✅ 响应式设计优化
- ✅ 性能优化配置
- ✅ SEO优化设置

### 代码质量
- ✅ 统一的代码风格
- ✅ 模块化设计
- ✅ PSR-4类自动加载
- ✅ 错误处理机制
- ✅ 安全防护措施

## 🚀 部署建议

### 生产环境
1. 设置适当的文件权限（755/644）
2. 启用HTTPS（Let's Encrypt）
3. 配置数据库连接池
4. 启用PHP OPcache
5. 设置定期备份

### 开发环境
1. 复制配置模板文件
2. 导入数据库结构
3. 设置开发工具
4. 启用调试模式

## 📚 相关文档
- [README.md](README.md) - 项目说明和安装指南
- [SECURITY_CHECKLIST.md](SECURITY_CHECKLIST.md) - 安全检查清单