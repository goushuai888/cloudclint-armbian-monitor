<?php
/**
 * CloudClint 设备监控平台 - 登录页面
 * 全新UI设计，现代化登录体验
 */

// 开始会话
session_start();

// 引入必要文件
require_once 'config/timezone.php';
require_once 'config/database.php';
require_once 'classes/Auth.php';

// 数据库连接
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die('数据库连接失败');
}

$auth = new Auth($db);

// 如果用户已登录，重定向到首页
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// 生成CSRF令牌
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 处理登录请求
$error = '';
$success = '';

// 检查是否有密码修改成功的消息
if (isset($_SESSION['password_changed_message'])) {
    $success = $_SESSION['password_changed_message'];
    unset($_SESSION['password_changed_message']); // 显示一次后删除
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = '安全验证失败，请重新提交';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = '请输入用户名和密码';
        } else {
            $result = $auth->login($username, $password);
            if ($result['success']) {
                // 登录成功，重定向到首页
                header('Location: index.php');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CloudClint 设备监控平台 - 安全登录">
    <meta name="robots" content="noindex, nofollow">
    
    <title>登录 - CloudClint 设备监控平台</title>
    
    <!-- Google Fonts - Roboto 字体 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Material UI CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@mui/material@5.15.10/dist/mui.min.css" rel="stylesheet">
    
    <!-- 样式表 -->
    <link href="assets/css/bootstrap-icons.css" rel="stylesheet">
    
    <!-- 网站图标 -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%233b82f6%22><path d=%22M3 3h18a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm4 8v6h2v-6H7zm4-4v10h2V7h-2zm4 2v8h2V9h-2z%22/></svg>" type="image/svg+xml">
    
    <meta name="theme-color" content="#3b82f6">
    
    <style>
    /* Material Design 3 登录页面样式 */
    :root {
        --md-sys-color-primary: #1976d2;
        --md-sys-color-on-primary: #ffffff;
        --md-sys-color-primary-container: #e3f2fd;
        --md-sys-color-on-primary-container: #0d47a1;
        --md-sys-color-secondary: #42a5f5;
        --md-sys-color-surface: #ffffff;
        --md-sys-color-on-surface: #1c1b1f;
        --md-sys-color-surface-variant: #f5f5f5;
        --md-sys-color-outline: #79747e;
        --md-sys-color-error: #ba1a1a;
        --md-sys-color-on-error: #ffffff;
        --md-sys-color-error-container: #ffdad6;
        --md-sys-color-on-error-container: #410002;
        --md-sys-color-success: #4caf50;
        --md-sys-color-on-success: #ffffff;
        --md-sys-color-success-container: #e8f5e8;
        --md-sys-color-on-success-container: #1b5e20;
        
        --md-elevation-1: 0px 1px 3px rgba(0, 0, 0, 0.12), 0px 1px 2px rgba(0, 0, 0, 0.24);
        --md-elevation-2: 0px 3px 6px rgba(0, 0, 0, 0.16), 0px 3px 6px rgba(0, 0, 0, 0.23);
        --md-elevation-3: 0px 10px 20px rgba(0, 0, 0, 0.19), 0px 6px 6px rgba(0, 0, 0, 0.23);
        --md-elevation-4: 0px 14px 28px rgba(0, 0, 0, 0.25), 0px 10px 10px rgba(0, 0, 0, 0.22);
    }
    
    body {
        font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 50%, #90caf9 100%);
        min-height: 100vh;
    }
    
    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        position: relative;
        background-image: 
            radial-gradient(circle at 25% 25%, rgba(25, 118, 210, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 75% 75%, rgba(66, 165, 245, 0.1) 0%, transparent 50%);
    }
    
    .login-card {
        background: var(--md-sys-color-surface);
        border-radius: 28px;
        box-shadow: var(--md-elevation-3);
        width: 100%;
        max-width: 400px;
        padding: 32px;
        position: relative;
        animation: materialFadeIn 0.4s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 32px;
    }
    
    .login-logo {
        width: 64px;
        height: 64px;
        background: var(--md-sys-color-primary);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--md-sys-color-on-primary);
        margin: 0 auto 16px;
        box-shadow: var(--md-elevation-2);
        transition: transform 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    
    .login-logo:hover {
        transform: scale(1.05);
    }
    
    .login-title {
        font-size: 28px;
        font-weight: 500;
        color: var(--md-sys-color-on-surface);
        margin: 0 0 8px 0;
        letter-spacing: 0.25px;
    }
    
    .login-subtitle {
        font-size: 16px;
        color: var(--md-sys-color-outline);
        margin: 0;
        font-weight: 400;
    }
    
    .form-field {
        margin-bottom: 24px;
        position: relative;
    }
    
    .form-field-outlined {
        position: relative;
        border: 1px solid var(--md-sys-color-outline);
        border-radius: 4px;
        background: transparent;
        transition: all 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    
    .form-field-outlined:focus-within {
        border-color: var(--md-sys-color-primary);
        border-width: 2px;
    }
    
    .form-input {
        width: 100%;
        padding: 16px 16px 16px 56px;
        border: none;
        background: transparent;
        font-size: 16px;
        font-family: inherit;
        color: var(--md-sys-color-on-surface);
        outline: none;
        box-sizing: border-box;
        line-height: 1.5;
    }
    
    .form-input::placeholder {
        color: transparent;
    }
    
    .form-label {
        position: absolute;
        left: 56px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 16px;
        color: var(--md-sys-color-outline);
        pointer-events: none;
        transition: all 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
        background: var(--md-sys-color-surface);
        padding: 0 4px;
        z-index: 1;
    }
    
    .form-field-outlined:focus-within .form-label,
    .form-field-outlined.has-value .form-label {
        top: 0;
        transform: translateY(-50%);
        font-size: 12px;
        color: var(--md-sys-color-primary);
        left: 52px;
    }
    
    .form-field-outlined.has-value:not(:focus-within) .form-label {
        color: var(--md-sys-color-outline);
    }
    
    .form-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--md-sys-color-outline);
        transition: color 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
        font-size: 24px;
        z-index: 1;
    }
    
    .form-field-outlined:focus-within .form-icon {
        color: var(--md-sys-color-primary);
    }
    
    /* 错误状态 */
    .form-field-outlined.error {
        border-color: var(--md-sys-color-error);
    }
    
    .form-field-outlined.error .form-label {
        color: var(--md-sys-color-error);
    }
    
    .form-field-outlined.error .form-icon {
        color: var(--md-sys-color-error);
    }
    
    .login-button {
        width: 100%;
        padding: 16px;
        background: var(--md-sys-color-primary);
        color: var(--md-sys-color-on-primary);
        border: none;
        border-radius: 24px;
        font-size: 16px;
        font-weight: 500;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
        box-shadow: var(--md-elevation-1);
        position: relative;
        overflow: hidden;
        letter-spacing: 0.5px;
        text-transform: none;
    }
    
    .login-button:hover {
        box-shadow: var(--md-elevation-2);
        transform: translateY(-1px);
    }
    
    .login-button:active {
        transform: translateY(0);
    }
    
    .login-button:disabled {
        background: rgba(28, 27, 31, 0.12);
        color: rgba(28, 27, 31, 0.38);
        box-shadow: none;
        cursor: not-allowed;
        transform: none;
    }
    
    .login-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s;
    }
    
    .login-button:hover::before {
        left: 100%;
    }
    
    .alert {
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        animation: materialSlideDown 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    
    .alert-error {
        background: var(--md-sys-color-error-container);
        color: var(--md-sys-color-on-error-container);
    }
    
    .alert-success {
        background: var(--md-sys-color-success-container);
        color: var(--md-sys-color-on-success-container);
    }
    
    .login-footer {
        text-align: center;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid rgba(121, 116, 126, 0.12);
    }
    
    .login-footer-text {
        color: var(--md-sys-color-outline);
        font-size: 14px;
        margin: 0;
    }
    
    /* 加载状态 */
    .button-loading {
        position: relative;
    }
    
    .button-loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        border: 2px solid transparent;
        border-top: 2px solid var(--md-sys-color-on-primary);
        border-radius: 50%;
        animation: materialSpin 1s linear infinite;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
    }
    
    /* 响应式设计 */
    @media (max-width: 480px) {
        .login-container {
            padding: 16px;
        }
        
        .login-card {
            padding: 24px;
        }
        
        .login-title {
            font-size: 24px;
        }
        
        .login-subtitle {
            font-size: 14px;
        }
    }
    
    /* Material 动画 */
    @keyframes materialFadeIn {
        from {
            opacity: 0;
            transform: translateY(24px) scale(0.96);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    @keyframes materialSlideDown {
        from {
            opacity: 0;
            transform: translateY(-16px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes materialSpin {
        to {
            transform: translateY(-50%) rotate(360deg);
        }
    }
    
    /* 涟漪效果 */
    .ripple {
        position: relative;
        overflow: hidden;
    }
    
    .ripple::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    .ripple:active::after {
        width: 200px;
        height: 200px;
    }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- 登录头部 -->
            <div class="login-header">
                <div class="login-logo">
                    <span class="material-icons" style="font-size: 32px;">storage</span>
                </div>
                <h1 class="login-title">CloudClint</h1>
                <p class="login-subtitle">设备监控平台</p>
            </div>
            
            <!-- 错误/成功提示 -->
            <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="material-icons">error_outline</span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="material-icons">check_circle_outline</span>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- 登录表单 -->
            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-field">
                    <div class="form-field-outlined" id="usernameField">
                        <input type="text" 
                               name="username" 
                               class="form-input" 
                               id="username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required
                               autocomplete="username">
                        <label for="username" class="form-label">用户名</label>
                        <span class="material-icons form-icon">person</span>
                    </div>
                </div>
                
                <div class="form-field">
                    <div class="form-field-outlined" id="passwordField">
                        <input type="password" 
                               name="password" 
                               class="form-input" 
                               id="password"
                               required
                               autocomplete="current-password">
                        <label for="password" class="form-label">密码</label>
                        <span class="material-icons form-icon">lock</span>
                    </div>
                </div>
                
                <button type="submit" class="login-button ripple" id="loginBtn">
                    <span id="btnText">登录</span>
                </button>
            </form>
            
            <!-- 页脚 -->
            <div class="login-footer">
                <p class="login-footer-text">
                    &copy; <?php echo date('Y'); ?> CloudClint Team. All rights reserved.
                </p>
            </div>
        </div>
    </div>
    
    <script>
    // Material UI登录表单处理
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('loginBtn');
        const btnText = document.getElementById('btnText');
        
        // 显示加载状态
        btn.classList.add('button-loading');
        btn.disabled = true;
        btnText.textContent = '登录中...';
    });
    
    // Material Design浮动标签效果
    function handleFloatingLabel() {
        document.querySelectorAll('.form-input').forEach(input => {
            const container = input.closest('.form-field-outlined');
            
            // 检查是否有值的函数
            function checkValue() {
                if (input.value.trim() !== '') {
                    container.classList.add('has-value');
                } else {
                    container.classList.remove('has-value');
                }
            }
            
            // 初始检查
            checkValue();
            
            // 输入事件
            input.addEventListener('input', checkValue);
            
            // 焦点事件
            input.addEventListener('focus', function() {
                container.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                container.classList.remove('focused');
                checkValue(); // 失焦时再次检查
            });
        });
    }
    
    // 初始化浮动标签
    handleFloatingLabel();
    
    // 按钮涟漪效果
    document.querySelectorAll('.ripple').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: materialRipple 0.6s linear;
                pointer-events: none;
            `;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // 键盘快捷键
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.ctrlKey) {
            document.getElementById('loginForm').submit();
        }
    });
    
    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 重新初始化浮动标签（确保页面完全加载后处理）
        handleFloatingLabel();
        
        // 自动聚焦
        const usernameInput = document.querySelector('#username');
        if (usernameInput && !usernameInput.value.trim()) {
            usernameInput.focus();
        } else {
            const passwordInput = document.querySelector('#password');
            if (passwordInput) {
                passwordInput.focus();
            }
        }
    });
    
    // 动态涟漪动画样式
    const style = document.createElement('style');
    style.textContent = `
        @keyframes materialRipple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>