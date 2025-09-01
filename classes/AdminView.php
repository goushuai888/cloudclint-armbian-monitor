<?php
/**
 * CloudClint 设备监控平台 - 管理视图类
 * Material Design 3 风格重构版本
 */

class AdminView {
    private $data;
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * 渲染完整的管理页面
     */
    public function render() {
        $this->renderHeader();
        $this->renderBody();
        $this->renderFooter();
    }
    
    /**
     * 渲染页面头部
     */
    private function renderHeader() {
        echo '<!DOCTYPE html>';
        echo '<html lang="zh-CN" class="h-full">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<meta name="description" content="CloudClint 设备监控平台 - 系统管理">';
        echo '<meta name="robots" content="noindex, nofollow">';
        echo '<title>系统管理 - CloudClint 设备监控平台</title>';
        
        // Google Fonts
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">';
        echo '<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">';
        
        // 网站图标
        echo '<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%231976d2%22><path d=%22M3 3h18a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm4 8v6h2v-6H7zm4-4v10h2V7h-2zm4 2v8h2V9h-2z%22/></svg>" type="image/svg+xml">';
        echo '<meta name="theme-color" content="#1976d2">';
        
        // 内联样式
        $this->renderStyles();
        
        echo '</head>';
    }
    
    /**
     * 渲染样式
     */
    private function renderStyles() {
        echo '<style>';
        echo '
        /* 重新设计的系统管理页面样式 - 现代化布局 */
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --background-color: #f8fafc;
            --surface-color: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }
        
        /* 限制样式作用域到管理页面容器 */
        .admin-page-wrapper * {
            box-sizing: border-box;
        }
        
        .admin-page-wrapper {
            font-family: "Inter", "Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        /* 主容器布局 */
        .page-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--surface-color);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            min-height: calc(100vh - 2rem);
            display: flex;
            flex-direction: column;
        }
        
        /* 顶部导航栏 */
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .admin-header::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(45deg);
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .admin-logo {
            width: 3rem;
            height: 3rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        
        .header-title {
            display: flex;
            flex-direction: column;
        }
        
        .header-title h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.025em;
        }
        
        .header-title p {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .back-button {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
            color: white;
            text-decoration: none;
        }
        
        /* 消息提示 */
        .message-container {
            padding: 1.5rem 2rem 0;
        }
        
        .alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            font-weight: 500;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-left-color: var(--success-color);
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: var(--error-color);
        }
        
        /* 标签页导航 */
        .admin-tabs {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .admin-tabs-nav {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            padding: 0 2rem;
        }
        
        .admin-tab {
            background: none;
            border: none;
            padding: 1rem 1.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            margin-right: 0.25rem;
        }
        
        .admin-tab:hover {
            color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .admin-tab.active {
            color: var(--primary-color);
            background: var(--surface-color);
            border-bottom: 2px solid var(--primary-color);
        }
        
        /* 标签页内容 */
        .tab-pane {
            display: none;
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        /* 网格布局 */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
            height: 100%;
        }
        
        /* 卡片样式 */
        .admin-card {
            background: var(--surface-color);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.2s ease;
            height: fit-content;
        }
        
        .admin-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-1px);
        }
        
        .admin-card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .admin-card-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .admin-card-body {
            padding: 1.5rem;
        }
        
        /* 表单样式 */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: var(--surface-color);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: var(--surface-color);
            cursor: pointer;
        }
        
        /* 按钮样式 */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-family: inherit;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-secondary {
            background: var(--surface-color);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-error {
            background: var(--error-color);
            color: white;
        }
        
        .btn-error:hover {
            background: #dc2626;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }
        
        /* 表格样式 */
        .table-container {
            background: var(--surface-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: #f8fafc;
        }
        
        .data-table th {
            padding: 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.875rem;
        }
        
        .data-table tbody tr:hover {
            background: #f8fafc;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* 状态标签 */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-locked {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .role-admin {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .role-user {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* 响应式设计 */
        @media (max-width: 1024px) {
            .grid-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .page-container {
                padding: 0.5rem;
            }
            
            .admin-container {
                min-height: calc(100vh - 1rem);
                border-radius: var(--radius-lg);
            }
            
            .admin-header {
                padding: 1.5rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .header-title h1 {
                font-size: 1.5rem;
            }
            
            .admin-tabs-nav {
                padding: 0 1rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .admin-tab {
                flex-shrink: 0;
                min-width: 120px;
            }
            
            .tab-pane {
                padding: 1.5rem;
            }
            
            .grid-container {
                gap: 1rem;
            }
            
            .admin-card-header,
            .admin-card-body {
                padding: 1rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 480px) {
            .admin-header {
                padding: 1rem;
            }
            
            .header-title h1 {
                font-size: 1.25rem;
            }
            
            .tab-pane {
                padding: 1rem;
            }
            
            .admin-card-header,
            .admin-card-body {
                padding: 0.75rem;
            }
            
            .btn {
                padding: 0.625rem 1rem;
                font-size: 0.75rem;
            }
        }
        
        /* 动画效果 */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .admin-container {
            animation: fadeIn 0.3s ease-out;
        }
        
        .tab-pane.active {
            animation: fadeIn 0.2s ease-out;
        }
        ';
        echo '</style>';
    }
    
    /**
     * 渲染页面主体
     */
    private function renderBody() {
        echo '<body>';
        echo '<div class="admin-page-wrapper">';
        echo '<div class="page-container">';
        echo '<div class="admin-container">';
        
        // 页面标题
        $this->renderPageHeader();
        
        // 消息提示
        $this->renderMessages();
        
        // 标签页
        $this->renderTabs();
        
        echo '</div>';
        echo '</div>';
        echo '</div>'; // 关闭 admin-page-wrapper
        
        // JavaScript
        $this->renderJavaScript();
        echo '</body>';
    }
    
    /**
     * 渲染返回按钮
     */
    
    
    /**
     * 渲染页面标题
     */
    private function renderPageHeader() {
        echo '<div class="admin-header">';
        echo '<div class="header-content">';
        echo '<div class="header-left">';
        echo '<div class="admin-logo">';
        echo '<span class="material-icons" style="font-size: 24px; color: white;">admin_panel_settings</span>';
        echo '</div>';
        echo '<div class="header-title">';
        echo '<h1>系统管理</h1>';
        echo '<p>管理系统用户和配置参数</p>';
        echo '</div>';
        echo '</div>';
        echo '<a href="index.php" class="back-button">';
        echo '<span class="material-icons">arrow_back</span>';
        echo '返回首页';
        echo '</a>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * 渲染消息提示
     */
    private function renderMessages() {
        echo '<div class="message-container">';
        
        if (!empty($this->data['error'])) {
            echo '<div class="alert alert-error">';
            echo '<span class="material-icons">error</span>';
            echo '<span>' . htmlspecialchars($this->data['error']) . '</span>';
            echo '</div>';
        }
        
        if (!empty($this->data['success'])) {
            echo '<div class="alert alert-success">';
            echo '<span class="material-icons">check_circle</span>';
            echo '<span>' . htmlspecialchars($this->data['success']) . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * 渲染标签页
     */
    private function renderTabs() {
        echo '<div class="admin-tabs">';
        
        // 标签页导航
        echo '<div class="admin-tabs-nav">';
        echo '<button class="admin-tab active" data-tab="users-tab">';
        echo '<span class="material-icons">people</span>用户管理';
        echo '</button>';
        echo '<button class="admin-tab" data-tab="settings-tab">';
        echo '<span class="material-icons">settings</span>系统设置';
        echo '</button>';
        echo '</div>';
        
        // 标签页内容
        echo '<div id="tab-content">';
        $this->renderUsersTab();
        $this->renderSettingsTab();
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * 渲染用户管理标签页
     */
    private function renderUsersTab() {
        echo '<div id="users-tab" class="tab-pane active">';
        echo '<div class="grid-container">';
        
        // 添加用户表单
        $this->renderAddUserForm();
        
        // 用户列表
        $this->renderUsersList();
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * 渲染添加用户表单
     */
    private function renderAddUserForm() {
        echo '<div class="admin-card">';
        echo '<div class="admin-card-header">';
        echo '<span class="material-icons">person_add</span>';
        echo '<h3>添加新用户</h3>';
        echo '</div>';
        echo '<div class="admin-card-body">';
        echo '<form method="POST" action="admin.php">';
        
        echo '<div class="form-group">';
        echo '<label class="form-label" for="username">用户名</label>';
        echo '<input type="text" id="username" name="username" class="form-input" placeholder="请输入用户名" required>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label class="form-label" for="password">密码</label>';
        echo '<input type="password" id="password" name="password" class="form-input" placeholder="请输入密码" required>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label class="form-label" for="email">邮箱</label>';
        echo '<input type="email" id="email" name="email" class="form-input" placeholder="请输入邮箱地址">';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label class="form-label" for="role">角色</label>';
        echo '<select id="role" name="role" class="form-select">';
        echo '<option value="user">普通用户</option>';
        echo '<option value="admin">管理员</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<button type="submit" name="add_user" class="btn btn-primary">';
        echo '<span class="material-icons">add</span>';
        echo '添加用户';
        echo '</button>';
        
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * 渲染用户列表
     */
    private function renderUsersList() {
        echo '<div class="admin-card">';
        echo '<div class="admin-card-header">';
        echo '<span class="material-icons">people</span>';
        echo '<h3>用户列表</h3>';
        echo '</div>';
        echo '<div class="admin-card-body">';
        echo '<div class="table-container">';
        echo '<table class="data-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>用户名</th>';
        echo '<th>邮箱</th>';
        echo '<th>角色</th>';
        echo '<th>状态</th>';
        echo '<th>操作</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        if (!empty($this->data['users'])) {
            foreach ($this->data['users'] as $user) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                echo '<td>' . htmlspecialchars($user['email'] ?? '-') . '</td>';
                echo '<td><span class="status-badge role-' . htmlspecialchars($user['role']) . '">' . 
                     ($user['role'] === 'admin' ? '管理员' : '普通用户') . '</span></td>';
                echo '<td><span class="status-badge status-' . ($user['is_locked'] ? 'locked' : 'active') . '">' . 
                     ($user['is_locked'] ? '已锁定' : '正常') . '</span></td>';
                echo '<td>';
                echo '<div style="display: flex; gap: 0.5rem;">';
                if ($user['is_locked']) {
                    echo '<a href="admin.php?unlock_user=' . $user['id'] . '" class="btn btn-sm btn-success">';
                    echo '<span class="material-icons" style="font-size: 16px;">lock_open</span>';
                    echo '解锁';
                    echo '</a>';
                } else {
                    echo '<a href="admin.php?lock_user=' . $user['id'] . '" class="btn btn-sm btn-secondary">';
                    echo '<span class="material-icons" style="font-size: 16px;">lock</span>';
                    echo '锁定';
                    echo '</a>';
                }
                echo '<a href="admin.php?delete_user=' . $user['id'] . '" class="btn btn-sm btn-error" onclick="return confirm(\'确定要删除这个用户吗？\')">';
                echo '<span class="material-icons" style="font-size: 16px;">delete</span>';
                echo '删除';
                echo '</a>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr>';
            echo '<td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-secondary);">暂无用户数据</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * 渲染系统设置标签页
     */
    private function renderSettingsTab() {
        echo '<div id="settings-tab" class="tab-pane">';
        echo '<div class="grid-container">';
        
        // 系统配置表单
        echo '<div class="admin-card">';
        echo '<div class="admin-card-header">';
        echo '<span class="material-icons">settings</span>';
        echo '<h3>系统配置</h3>';
        echo '</div>';
        echo '<div class="admin-card-body">';
        echo '<form method="POST" action="admin.php">';
        
        echo '<div class="form-group">';
        echo '<label class="form-label" for="site_name">站点名称</label>';
        echo '<input type="text" id="site_name" name="site_name" class="form-input" value="CloudClint 设备监控平台" placeholder="请输入站点名称">';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label class="form-label" for="admin_email">管理员邮箱</label>';
        echo '<input type="email" id="admin_email" name="admin_email" class="form-input" placeholder="请输入管理员邮箱">';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label class="form-label" for="session_timeout">会话超时时间（分钟）</label>';
        echo '<input type="number" id="session_timeout" name="session_timeout" class="form-input" value="30" min="5" max="1440">';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label class="form-label" for="max_login_attempts">最大登录尝试次数</label>';
        echo '<input type="number" id="max_login_attempts" name="max_login_attempts" class="form-input" value="5" min="1" max="20">';
        echo '</div>';
        
        echo '<button type="submit" name="save_settings" class="btn btn-primary">';
        echo '<span class="material-icons">save</span>';
        echo '保存设置';
        echo '</button>';
        
        echo '</form>';
        echo '</div>';
        echo '</div>';
        
        // 系统信息
        echo '<div class="admin-card">';
        echo '<div class="admin-card-header">';
        echo '<span class="material-icons">info</span>';
        echo '<h3>系统信息</h3>';
        echo '</div>';
        echo '<div class="admin-card-body">';
        echo '<div style="display: grid; gap: 1rem;">';
        
        echo '<div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">';
        echo '<span style="font-weight: 500;">PHP 版本</span>';
        echo '<span>' . PHP_VERSION . '</span>';
        echo '</div>';
        
        echo '<div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">';
        echo '<span style="font-weight: 500;">服务器软件</span>';
        echo '<span>' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</span>';
        echo '</div>';
        
        echo '<div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">';
        echo '<span style="font-weight: 500;">数据库</span>';
        echo '<span>SQLite</span>';
        echo '</div>';
        
        echo '<div style="display: flex; justify-content: space-between; padding: 0.75rem 0;">';
        echo '<span style="font-weight: 500;">系统版本</span>';
        echo '<span>v1.0.0</span>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * 渲染JavaScript
     */
    private function renderJavaScript() {
        echo '<script>';
        echo '
        // Material Design 表单交互
        document.addEventListener("DOMContentLoaded", function() {
            // 表单字段焦点处理
            const formFields = document.querySelectorAll(".form-field-outlined");
            formFields.forEach(field => {
                const input = field.querySelector("input, select");
                if (input) {
                    // 检查初始值
                    if (input.value.trim() !== "") {
                        field.classList.add("has-value");
                    }
                    
                    // 焦点事件
                    input.addEventListener("focus", () => {
                        field.classList.add("focused");
                    });
                    
                    input.addEventListener("blur", () => {
                        field.classList.remove("focused");
                        if (input.value.trim() !== "") {
                            field.classList.add("has-value");
                        } else {
                            field.classList.remove("has-value");
                        }
                    });
                    
                    // 输入事件
                    input.addEventListener("input", () => {
                        if (input.value.trim() !== "") {
                            field.classList.add("has-value");
                        } else {
                            field.classList.remove("has-value");
                        }
                    });
                }
            });
            
            // 标签页切换
            const tabButtons = document.querySelectorAll(".admin-tab");
            const tabPanes = document.querySelectorAll(".tab-pane");
            
            tabButtons.forEach(button => {
                button.addEventListener("click", () => {
                    const targetTab = button.getAttribute("data-tab");
                    
                    // 移除所有活动状态
                    tabButtons.forEach(btn => btn.classList.remove("active"));
                    tabPanes.forEach(pane => pane.classList.remove("active"));
                    
                    // 添加活动状态
                    button.classList.add("active");
                    document.getElementById(targetTab).classList.add("active");
                });
            });
            
            // 编辑用户按钮功能将由admin.js处理
            
            // 锁定/解锁用户按钮
            const toggleButtons = document.querySelectorAll(".toggle-lock-btn");
            toggleButtons.forEach(button => {
                button.addEventListener("click", () => {
                    const action = button.getAttribute("data-action");
                    const userId = button.getAttribute("data-user-id");
                    const username = button.getAttribute("data-username");
                    
                    const actionText = action === "lock" ? "锁定" : "解锁";
                    if (confirm(`确定要${actionText}用户 "${username}" 吗？`)) {
                        // 创建表单并提交
                        const form = document.createElement("form");
                        form.method = "POST";
                        form.innerHTML = `
                            <input type="hidden" name="action" value="toggle_user_lock">
                            <input type="hidden" name="user_id" value="${userId}">
                            <input type="hidden" name="lock_action" value="${action}">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
        ';
        echo '</script>';
        
        // 引入外部JavaScript文件
        echo '<script src="assets/js/admin.js"></script>';
    }
    
    /**
     * 渲染页面底部
     */
    private function renderFooter() {
        echo '</html>';
    }
}