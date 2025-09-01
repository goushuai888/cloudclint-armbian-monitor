<?php
/**
 * CloudClint 现代化头部组件
 * 采用全新UI设计系统
 */

// 确保已经启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 设置安全HTTP头
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// 获取页面标题，默认为网站标题
$page_title = isset($page_title) ? $page_title : 'CloudClint 设备监控平台';
$show_search = isset($show_search) ? $show_search : true;
$search_keyword = isset($search_keyword) ? $search_keyword : '';
$filter_status = isset($filter_status) ? $filter_status : '';
?>
<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CloudClint - 专业的Armbian设备监控平台，实时监控设备状态、系统资源和健康状况">
    <meta name="keywords" content="Armbian监控,设备管理,系统监控,云端管理,实时监控">
    <meta name="author" content="CloudClint Team">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- 性能优化 - 增强版 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    
    <!-- 关键CSS内联加载 - 增强版 -->
    <style>
        /* Critical CSS - 首屏快速渲染 */
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; overflow-x: hidden; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; background: #f8fafc; line-height: 1.5; 
            -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;
            transition: background-color 0.3s ease;
        }
        
        /* 预加载器优化 */
        .cc-preloader { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; align-items: center; justify-content: center; z-index: 10000;
            opacity: 1; visibility: visible; transition: all 0.3s ease;
        }
        .cc-preloader-content { text-align: center; color: white; animation: fadeInUp 0.6s ease-out; }
        .cc-spinner { 
            width: 50px; height: 50px; border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid white; border-radius: 50%; 
            animation: spin 1s linear infinite; margin: 0 auto 1rem; 
        }
        
        /* 骨架屏样式 */
        .cc-skeleton { 
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%; animation: shimmer 1.5s infinite;
            border-radius: 4px; display: inline-block;
        }
        
        /* 导航栏骨架 */
        .cc-navbar-skeleton {
            height: 72px; background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(12px); border-bottom: 1px solid #e5e7eb;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.5rem; position: sticky; top: 0; z-index: 1000;
        }
        
        /* 暗色主题基础 */
        .dark-theme { background: #111827; color: #f9fafb; }
        .dark-theme .cc-navbar-skeleton { background: rgba(17, 24, 39, 0.95); border-bottom-color: #374151; }
        
        /* 动画定义 */
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        @keyframes fadeInUp { 
            from { opacity: 0; transform: translateY(30px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        /* 性能优化 */
        img { max-width: 100%; height: auto; }
        [data-lazy] { opacity: 0; transition: opacity 0.3s; }
        [data-lazy].loaded { opacity: 1; }
        
        /* 减少重绘和重排 */
        .cc-animate-optimized { 
            will-change: transform; 
            transform: translateZ(0); 
            backface-visibility: hidden; 
        }
    </style>
    
    <!-- 字体加载 - 优化版 -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- 样式表 - 按优先级加载 -->
    <link href="assets/css/bootstrap.min.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <link href="assets/css/cloudclint-ui.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <link href="assets/css/cloudclint-components.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <link href="assets/css/cloudclint-visual.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <link href="assets/css/cloudclint-accessibility.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <link href="assets/css/cloudclint-mobile.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <link href="assets/css/cloudclint-interactive.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 设备网格自适应布局 -->
    <link href="assets/css/device-grid-layout.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 设备显示信息优化样式 -->
    <link href="assets/css/device-display-optimized.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 设备精致化样式 -->
    <link href="assets/css/device-refinements.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 大屏幕优化样式 - 针对苹果16寸等高分辨率屏幕 -->
    <link href="assets/css/large-screen-optimization.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 日期筛选器修复样式 -->
    <link href="assets/css/date-filter-fix.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 设备卡片增强UI样式 -->
    <link href="assets/css/device-card-enhanced.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 设备网格增强布局样式 -->
    <link href="assets/css/device-grid-enhanced.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 现代化模态框样式 -->
    <link href="assets/css/modern-modal.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 设备卡片空间优化样式 -->
    <link href="assets/css/device-space-optimized.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 设备水平空间优化样式 -->
    <link href="assets/css/device-horizontal-optimized.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 中性主题样式 - 替换紫色主题 -->
    <link href="assets/css/neutral-theme.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 增强网格布局 - 16寸显示器优化 -->
    <link href="assets/css/enhanced-grid-layout.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 垂直间距优化 - 所有设备通用 -->
    <link href="assets/css/vertical-spacing-optimized.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 毛玻璃拟物化设计增强样式 -->
    <link href="assets/css/glassmorphism-enhancement.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 组件毛玻璃效果增强样式 -->
    <link href="assets/css/components-glassmorphism.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 现代极简设计细节优化 -->
    <link href="assets/css/modern-minimalist-details.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 微交互优化 -->
    <link href="assets/css/micro-interactions.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 模态框层级修复 -->
    <link href="assets/css/modal-z-index-fix.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 弹窗层级强制修复 -->
    <link href="assets/css/popup-layer-fix.css?v=<?php echo time(); ?>&nocache=true" rel="stylesheet">
    <!-- 性能优化样式 -->
    <!-- 性能监控CSS已移除 -->
    
    <!-- 性能监控脚本 -->
    <!-- 性能监控JS已移除 -->
    <!-- Bootstrap JavaScript -->
    <script src="assets/js/bootstrap.bundle.min.js?v=<?php echo time(); ?>" defer></script>
    <!-- 设备交互功能脚本 -->
    <script src="assets/js/device-interactions.js?v=<?php echo time(); ?>" defer></script>
    <!-- 设备工具函数脚本 -->
    <script src="assets/js/device-utils.js?v=<?php echo time(); ?>" defer></script>
    <!-- 设备卡片增强交互脚本 -->
    <script src="assets/js/device-card-enhanced.js?v=<?php echo time(); ?>" defer></script>
    <!-- 设备模态框脚本 -->
    <script src="assets/js/device-modal.js?v=<?php echo time(); ?>" defer></script>
    
    <!-- 页面优化样式 -->
    <style>
        /* 确保没有任何调试元素 */
        body::before,
        body::after {
            display: none !important;
            content: none !important;
        }
        
        /* 时间筛选器平滑滚动优化 */
        #dateFilterPopup {
            will-change: transform !important;
            transition: transform 0.1s ease-out, top 0.1s ease-out, left 0.1s ease-out !important;
        }
        
        #dateFilterPopup.show {
            transform: translateY(0) !important;
        }
        
        /* 确保绝对定位时的流畅移动 */
        @media (min-width: 1024px) {
            #dateFilterPopup[style*="position: absolute"] {
                transition: top 0.08s linear, left 0.08s linear !important;
            }
        }
        
        /* 搜索清除按钮样式 */
        .cc-search-clear-btn {
            position: absolute !important;
            right: 12px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            background: none !important;
            border: none !important;
            color: #6b7280 !important;
            cursor: pointer !important;
            padding: 4px !important;
            border-radius: 4px !important;
            transition: all 0.2s ease !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        .cc-search-clear-btn:hover {
            color: #374151 !important;
            background: rgba(0, 0, 0, 0.05) !important;
        }
        
        .cc-search-input-group {
            position: relative !important;
        }
        
        /* 筛选选项卡蓝色边框修复 - 简化强效版 */
        .cc-filter-tab.active {
            border: 3px solid #3b82f6 !important;
            border-radius: 8px !important;
            background: white !important;
            color: #1d4ed8 !important;
            font-weight: 600 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15) !important;
            position: relative !important;
            z-index: 100 !important;
        }
        
        /* 移除伪元素，避免冲突 */
        .cc-filter-tab.active::before,
        .cc-filter-tab.active::after {
            display: none !important;
        }
        
        /* 确保容器不裁剪 */
        .cc-filter-tabs {
            padding: 4px !important;
            overflow: visible !important;
        }
        
        .cc-filters-section {
            overflow: visible !important;
        }
        
        /* 强制注册时间和按钮同行显示 - 最高优先级 */
        .cc-info-item-with-action {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            flex-wrap: nowrap !important;
            flex-direction: row !important;
        }
        
        .cc-info-item-with-action .cc-info-item {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            flex-shrink: 0 !important;
        }
        
        .cc-info-item-with-action .cc-inline-edit-btn {
            flex-shrink: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* IP地址长内容处理 */
        .cc-info-value {
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
            hyphens: auto !important;
            line-height: 1.4 !important;
            max-width: 100% !important;
        }
        
        /* 设备基本信息网格优化 */
        .cc-device-basic-info {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important;
            gap: 1rem !important;
        }
        
        /* 信息项容器优化 */
        .cc-info-item {
            min-width: 0 !important;
            overflow: hidden !important;
        }
        
        .cc-info-content {
            min-width: 0 !important;
            overflow: hidden !important;
        }
        
        /* 针对移动端的响应式优化 */
        @media (max-width: 768px) {
            .cc-device-basic-info {
                grid-template-columns: 1fr !important;
                gap: 0.75rem !important;
            }
        }
    </style>
    
    <!-- 网站图标 - 增强版 -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%233b82f6%22><path d=%22M3 3h18a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm4 8v6h2v-6H7zm4-4v10h2V7h-2zm4 2v8h2V9h-2z%22/></svg>" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 180 180%22><rect width=%22180%22 height=%22180%22 fill=%22%233b82f6%22 rx=%2240%22/><path d=%22M45 60h90c6 0 10 4 10 10v40c0 6-4 10-10 10H45c-6 0-10-4-10-10V70c0-6 4-10 10-10zm15 25v20h10V85H60zm15-10v30h10V75H75zm15 5v25h10V80H90z%22 fill=%22white%22/></svg>">
    
    <!-- 用户下拉菜单专用CSS -->
    <style>
    .user-dropdown-menu {
        position: fixed !important;
        top: 60px !important;
        right: 20px !important;
        z-index: 999999 !important;
        background: white !important;
        border: 2px solid #3b82f6 !important;
        border-radius: 8px !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2) !important;
        padding: 12px !important;
        min-width: 220px !important;
        display: none !important;
        opacity: 0 !important;
        transform: translateY(-8px) !important;
        transition: all 0.15s ease !important;
        /* 强制置顶 */
        margin: 0 !important;
        max-width: none !important;
        max-height: none !important;
        overflow: visible !important;
        clip: auto !important;
        clip-path: none !important;
    }
    
    .user-dropdown-menu.show {
        display: block !important;
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    
    .user-dropdown-menu div,
    .user-dropdown-menu a {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 10px 14px !important;
        color: #374151 !important;
        text-decoration: none !important;
        border-radius: 6px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        transition: background-color 0.15s ease !important;
        margin: 2px 0 !important;
    }
    
    .user-dropdown-menu a:hover {
        background-color: #3b82f6 !important;
        color: white !important;
    }
    
    .user-dropdown-menu hr {
        height: 1px !important;
        background: #e5e7eb !important;
        border: none !important;
        margin: 8px 0 !important;
    }
    
    /* 强制覆盖可能的父级样式 */
    .user-dropdown-container {
        position: relative !important;
        z-index: 999998 !important;
    }
    
    /* 添加一个强制的覆盖层 */
    .user-dropdown-menu::before {
        content: '' !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        z-index: -1 !important;
        background: rgba(0, 0, 0, 0.1) !important;
        pointer-events: none !important;
    }
    </style>
    
    <!-- PWA Meta标签 - 增强版 -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CloudClint">
    
    <!-- SEO和性能优化 -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="专业的Armbian设备监控平台，实时监控设备状态、系统资源和健康状况">
    <meta property="og:type" content="website">
    <meta property="og:image" content="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 1200 630%22><rect width=%221200%22 height=%22630%22 fill=%22%233b82f6%22/><text x=%22600%22 y=%22315%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2280%22 font-family=%22Inter%22>CloudClint</text></svg>">
    
    <!-- 安全和隐私 -->
    <meta name="referrer" content="strict-origin-when-cross-origin">
</head>
<body class="cc-bg-gray-50">
    <!-- 可访问性跳过链接 -->
    <a href="#main-content" class="cc-skip-link">跳转到主要内容</a>
    
    <!-- 预加载器 - 提供流畅的加载体验 -->
    <div class="cc-preloader" id="ccPreloader">
        <div class="cc-preloader-content">
            <div class="cc-spinner"></div>
            <h3 style="color: white; margin: 0; font-weight: 600;">CloudClint</h3>
            <p style="color: rgba(255,255,255,0.9); margin: 0.5rem 0 0; font-size: 14px;">正在加载设备监控平台...</p>
        </div>
    </div>
    <!-- 导航栏 -->
    <nav class="cc-navbar">
        <div class="cc-navbar-container">
            <!-- 品牌标识 -->
            <a href="index.php" class="cc-navbar-brand">
                <div class="cc-navbar-brand-icon">
                    <i class="bi bi-hdd-network"></i>
                </div>
                <span>CloudClint</span>
            </a>
            
            <!-- 导航内容 -->
            <div class="cc-navbar-content">
                <!-- 搜索框 -->
                <?php if ($show_search): ?>
                <div class="cc-navbar-search" id="searchContainer">
                    <!-- 搜索表单 -->
                    <form action="index.php" method="GET" class="cc-search-form" id="searchForm">
                        <div class="cc-search-input-group">
                            <div class="cc-search-icon-wrapper">
                                <i class="bi bi-search cc-search-icon"></i>
                            </div>
                            <input type="text" 
                                   name="search" 
                                   class="cc-search-input" 
                                   id="searchInput"
                                   placeholder="搜索设备..." 
                                   value="<?php echo htmlspecialchars($search_keyword); ?>"
                                   autocomplete="off">
                            <?php if (!empty($search_keyword)): ?>
                            <button type="button" class="cc-search-clear-btn" onclick="clearSearch()" title="清除搜索">
                                <i class="bi bi-x"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- 用户菜单 -->
                <div class="cc-navbar-user">
                    <div class="user-dropdown-container" id="userDropdown" style="position: relative; display: inline-block;">
                        <div class="user-avatar-btn" onclick="toggleUserDropdown(event)" style="
                            width: 2.5rem; 
                            height: 2.5rem; 
                            background: linear-gradient(135deg, #9ca3af, #6b7280); 
                            border-radius: 50%; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center; 
                            color: white; 
                            cursor: pointer; 
                            transition: transform 0.15s ease;
                        ">
                            <i class="bi bi-person-circle" style="font-size: 1.2rem;"></i>
                        </div>
                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <div>
                                <i class="bi bi-person"></i>
                                <span><?php echo isset($current_user) ? htmlspecialchars($current_user['username']) : '用户'; ?></span>
                            </div>
                            <hr>
                            <a href="change_password.php">
                                <i class="bi bi-key"></i>
                                <span>修改密码</span>
                            </a>
                            <?php if (isset($current_user) && $current_user['role'] === 'admin'): ?>
                            <a href="admin.php">
                                <i class="bi bi-gear"></i>
                                <span>系统管理</span>
                            </a>
                            <?php endif; ?>
                            <hr>
                            <a href="logout.php" style="color: #dc2626;">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>退出登录</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要内容区域 -->
    <main class="cc-container cc-mt-6" id="main-content">

    <!-- 用户下拉菜单脚本 - 完全独立版本 -->
    <script>
    function toggleUserDropdown(event) {
        // 如果点击的是链接，直接返回，不处理下拉菜单
        if (event.target.tagName === 'A' || event.target.closest('a')) {
            return;
        }
        
        event.preventDefault();
        event.stopPropagation();
        
        // 移除已存在的动态菜单
        const existingMenu = document.getElementById('dynamicUserMenu');
        if (existingMenu) {
            existingMenu.remove();
            return;
        }
        
        const avatar = event.target.closest('.user-avatar-btn');
        
        if (avatar) {
            // 计算正确的位置
            const rect = avatar.getBoundingClientRect();
            const top = rect.bottom + 8;
            const right = window.innerWidth - rect.right;
            
            // 动态创建菜单
            const newMenu = document.createElement('div');
            newMenu.id = 'dynamicUserMenu';
            newMenu.style.cssText = `
                position: fixed !important;
                top: ${top}px !important;
                right: ${right}px !important;
                z-index: 2147483647 !important;
                background: white !important;
                border: 2px solid #3b82f6 !important;
                border-radius: 8px !important;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3) !important;
                padding: 12px !important;
                min-width: 220px !important;
                display: block !important;
                opacity: 0 !important;
                transform: translateY(-8px) !important;
                transition: all 0.15s ease !important;
                visibility: visible !important;
            `;
            
            // 根据用户角色动态生成菜单内容
            let menuContent = `
                <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; color: #374151; font-size: 14px; font-weight: 500; margin: 2px 0;">
                    <i class="bi bi-person"></i>
                    <span><?php echo isset($current_user) ? htmlspecialchars($current_user['username']) : '用户'; ?></span>
                </div>
                <hr style="height: 1px; background: #e5e7eb; border: none; margin: 8px 0;">
                <a href="change_password.php" style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; color: #374151; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500; margin: 2px 0; transition: background-color 0.15s ease;" onmouseover="this.style.backgroundColor='#3b82f6'; this.style.color='white'" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#374151'">
                    <i class="bi bi-key"></i>
                    <span>修改密码</span>
                </a>`;
            
            // 检查用户是否为管理员（从页面中获取信息）
            const isAdmin = document.querySelector('a[href="admin.php"]') !== null;
            if (isAdmin) {
                menuContent += `
                <a href="admin.php" style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; color: #374151; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500; margin: 2px 0; transition: background-color 0.15s ease;" onmouseover="this.style.backgroundColor='#3b82f6'; this.style.color='white'" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#374151'">
                    <i class="bi bi-gear"></i>
                    <span>系统管理</span>
                </a>`;
            }
            
            menuContent += `
                <hr style="height: 1px; background: #e5e7eb; border: none; margin: 8px 0;">
                <a href="logout.php" style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; color: #dc2626; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500; margin: 2px 0; transition: background-color 0.15s ease;" onmouseover="this.style.backgroundColor='#fef2f2'" onmouseout="this.style.backgroundColor='transparent'">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>退出登录</span>
                </a>
            `;
            
            newMenu.innerHTML = menuContent;
            
            // 添加到body最后
            document.body.appendChild(newMenu);
            
            // 触发动画
            setTimeout(() => {
                newMenu.style.opacity = '1';
                newMenu.style.transform = 'translateY(0)';
            }, 10);
            
            // 点击其他地方关闭菜单
            const closeHandler = (e) => {
                if (!newMenu.contains(e.target) && !avatar.contains(e.target)) {
                    newMenu.remove();
                    document.removeEventListener('click', closeHandler);
                }
            };
            
            setTimeout(() => {
                document.addEventListener('click', closeHandler);
            }, 100);
        }
    }
    
    function closeAllUserDropdowns() {
        const dropdownMenu = document.getElementById('userDropdownMenu');
        if (dropdownMenu) {
            dropdownMenu.classList.remove('show');
        }
    }
    
    // 用户下拉菜单辅助函数
    function closeAllUserDropdowns() {
        const existingMenu = document.getElementById('dynamicUserMenu');
        if (existingMenu) {
            existingMenu.remove();
        }
    }
    
    // 点击页面其他地方关闭下拉菜单
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown && !dropdown.contains(event.target)) {
            closeAllUserDropdowns();
        }
    });
    
    // 添加hover效果和测试按钮
    document.addEventListener('DOMContentLoaded', function() {
        const avatar = document.querySelector('.user-avatar-btn');
        if (avatar) {
            avatar.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            avatar.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        }
        
    });
    </script>

    <!-- 自适应搜索框JavaScript -->
    <script>
    // 预加载器管理系统 - 增强版
    document.addEventListener('DOMContentLoaded', function() {
        const preloader = document.getElementById('ccPreloader');
        
        // 性能优化：使用 requestAnimationFrame
        const hidePreloader = () => {
            if (preloader) {
                requestAnimationFrame(() => {
                    preloader.style.opacity = '0';
                    preloader.style.visibility = 'hidden';
                    
                    setTimeout(() => {
                        preloader.style.display = 'none';
                        
                        // 触发页面进入动画
                        document.body.classList.add('page-loaded');
                        
                        // 初始化懒加载和交互增强
                        initLazyLoading();
                        initVisualEnhancements();
                    }, 300);
                });
            }
        };
        
        // 确保内容已完全加载
        if (document.readyState === 'complete') {
            setTimeout(hidePreloader, 100);
        } else {
            window.addEventListener('load', () => setTimeout(hidePreloader, 100));
        }
    });
    
    // 懒加载系统
    function initLazyLoading() {
        const lazyImages = document.querySelectorAll('img[data-lazy]');
        const lazyImageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.lazy;
                    img.classList.add('loaded');
                    img.removeAttribute('data-lazy');
                    lazyImageObserver.unobserve(img);
                }
            });
        }, { rootMargin: '50px' });
        
        lazyImages.forEach(img => lazyImageObserver.observe(img));
    }
    
    // 视觉增强功能
    function initVisualEnhancements() {
        // 添加涟漪效果到按钮
        document.querySelectorAll('.cc-filter-trigger-btn, .cc-btn-primary, .cc-clear-filters-compact-btn').forEach(btn => {
            if (!btn.classList.contains('cc-ripple')) {
                btn.classList.add('cc-ripple');
            }
        });
        
        // 为卡片添加动画延迟
        document.querySelectorAll('.cc-stats-card, .cc-device-card').forEach((card, index) => {
            card.classList.add('cc-animate-fadeIn');
            card.classList.add(`cc-animate-delay-${Math.min((index % 8 + 1) * 100, 400)}`);
            card.classList.add('cc-gpu-optimized');
        });
        
        // 初始化磁性效果
        document.querySelectorAll('.cc-stats-card, .cc-device-card').forEach(element => {
            element.classList.add('cc-magnetic');
            
            // 添加鼠标跟踪效果（仅在桌面端）
            if (window.innerWidth > 768) {
                element.addEventListener('mousemove', handleMouseMove);
                element.addEventListener('mouseleave', handleMouseLeave);
            }
        });
        
        // 为导航栏添加滚动效果
        initScrollEffects();
        
        // 初始化主题切换动画
        initThemeTransitions();
    }
    
    // 鼠标跟踪效果
    function handleMouseMove(e) {
        const rect = e.currentTarget.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;
        
        const tiltX = (y / rect.height) * 10;
        const tiltY = (x / rect.width) * -10;
        
        e.currentTarget.style.transform = `perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateZ(10px)`;
    }
    
    function handleMouseLeave(e) {
        e.currentTarget.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
    }
    
    // 滚动效果
    function initScrollEffects() {
        let lastScrollY = window.scrollY;
        let navbarHeight = 0;
        
        const navbar = document.querySelector('.cc-navbar');
        if (navbar) {
            navbarHeight = navbar.offsetHeight;
        }
        
        const handleScroll = () => {
            const currentScrollY = window.scrollY;
            
            // 导航栏背景透明度
            if (navbar) {
                const opacity = Math.min(currentScrollY / 100, 0.95);
                navbar.style.background = `rgba(255, 255, 255, ${0.85 + opacity * 0.1})`;
                
                // 添加阴影
                if (currentScrollY > 10) {
                    navbar.style.boxShadow = 'var(--cc-shadow-magical)';
                } else {
                    navbar.style.boxShadow = 'none';
                }
            }
            
            // 视差效果 - 暂时禁用以修复显示问题
            // document.querySelectorAll('.cc-stats-card').forEach((card, index) => {
            //     const speed = 0.5 + (index % 3) * 0.1;
            //     const yPos = -(currentScrollY * speed);
            //     card.style.transform = `translate3d(0, ${yPos}px, 0)`;
            // });
            
            lastScrollY = currentScrollY;
        };
        
        // 使用 requestAnimationFrame 优化滚动性能
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });
    }
    
    // 主题切换动画
    function initThemeTransitions() {
        const style = document.createElement('style');
        style.textContent = `
            * {
                transition: background-color 0.3s ease, 
                           color 0.3s ease, 
                           border-color 0.3s ease,
                           box-shadow 0.3s ease !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // 性能监控和优化
    function initPerformanceOptimizations() {
        // 减少不必要的重绘
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('cc-gpu-optimized');
                } else {
                    entry.target.classList.remove('cc-gpu-optimized');
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.cc-device-card, .cc-stats-card').forEach(el => {
            observer.observe(el);
        });
        
        // 防抖动的窗口大小调整
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                // 重新初始化响应式效果
                if (window.innerWidth <= 768) {
                    document.querySelectorAll('.cc-magnetic').forEach(el => {
                        el.removeEventListener('mousemove', handleMouseMove);
                        el.removeEventListener('mouseleave', handleMouseLeave);
                    });
                } else {
                    document.querySelectorAll('.cc-magnetic').forEach(el => {
                        el.addEventListener('mousemove', handleMouseMove);
                        el.addEventListener('mouseleave', handleMouseLeave);
                    });
                }
            }, 250);
        });
    }
    
    // 页面加载完成后初始化性能优化
    window.addEventListener('load', initPerformanceOptimizations);
    
    // 搜索框智能管理
    class SmartSearchBox {
        constructor() {
            this.searchInput = document.getElementById('searchInput');
            this.searchContainer = document.getElementById('searchContainer');
            this.isExpanded = false;
            this.isMobile = false;
            
            // 如果搜索元素不存在，直接返回
            if (!this.searchInput || !this.searchContainer) {
                return;
            }
            
            this.init();
        }
        
        init() {
            this.checkScreenSize();
            this.bindEvents();
            
            // 如果有搜索内容，初始化为展开状态
            if (this.searchInput && this.searchInput.value.trim()) {
                this.expand(false);
            }
        }
        
        checkScreenSize() {
            // 如果元素不存在，直接返回
            if (!this.searchContainer || !this.searchInput) {
                return;
            }
            
            this.isMobile = window.innerWidth <= 768;
            
            if (this.isMobile) {
                this.searchContainer.classList.add('mobile-mode');
                // 移动端默认收起状态
                if (!this.searchInput.value.trim()) {
                    this.collapse(false);
                }
            } else {
                this.searchContainer.classList.remove('mobile-mode');
                this.searchContainer.classList.remove('collapsed', 'expanded');
            }
        }
        
        bindEvents() {
            // 如果元素不存在，直接返回
            if (!this.searchContainer || !this.searchInput) {
                return;
            }
            
            // 输入框焦点事件
            this.searchInput.addEventListener('focus', () => {
                if (this.isMobile) {
                    this.expand();
                }
                this.searchContainer.classList.add('focused');
            });
            
            this.searchInput.addEventListener('blur', () => {
                this.searchContainer.classList.remove('focused');
                // 延迟收起，避免点击清除按钮时提前收起
                setTimeout(() => {
                    if (this.isMobile && !this.searchInput.value.trim() && !this.searchContainer.matches(':hover')) {
                        this.collapse();
                    }
                }, 150);
            });
            
            // 输入内容变化
            this.searchInput.addEventListener('input', () => {
                // 不再需要更新清除按钮
            });
            
            // 点击搜索图标展开（移动端）
            const iconWrapper = this.searchContainer.querySelector('.cc-search-icon-wrapper');
            if (iconWrapper) {
                iconWrapper.addEventListener('click', () => {
                    if (this.isMobile && !this.isExpanded) {
                        this.expand();
                        this.searchInput.focus();
                    }
                });
            }
            
            // 窗口大小变化
            window.addEventListener('resize', () => {
                this.checkScreenSize();
            });
            
            // 点击外部收起（移动端）
            document.addEventListener('click', (e) => {
                if (this.isMobile && !this.searchContainer.contains(e.target)) {
                    if (!this.searchInput.value.trim()) {
                        this.collapse();
                    }
                }
            });
        }
        
        expand(animate = true) {
            if (!this.isMobile || !this.searchContainer) return;
            
            this.isExpanded = true;
            this.searchContainer.classList.remove('collapsed');
            this.searchContainer.classList.add('expanded');
            
            if (animate) {
                this.searchContainer.style.animation = 'searchExpand 0.3s ease-out';
            }
        }
        
        collapse(animate = true) {
            if (!this.isMobile || !this.searchContainer) return;
            
            this.isExpanded = false;
            this.searchContainer.classList.remove('expanded');
            this.searchContainer.classList.add('collapsed');
            
            if (animate) {
                this.searchContainer.style.animation = 'searchCollapse 0.3s ease-out';
            }
        }
    }
    
    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        window.searchBox = new SmartSearchBox();
    });
    
    // 清除搜索功能
    function clearSearch() {
        const searchInput = document.querySelector('.cc-search-input');
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        // 清除搜索结果或重置搜索状态
        const searchForm = document.querySelector('.cc-search-form');
        if (searchForm) {
            // 如果有搜索参数，重新加载当前页面但不带搜索参数
            const url = new URL(window.location);
            if (url.searchParams.has('search') || url.searchParams.has('q')) {
                url.searchParams.delete('search');
                url.searchParams.delete('q');
                window.location.href = url.toString();
            }
        }
    }
    </script>