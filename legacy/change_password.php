<?php
/**
 * CloudClint 设备监控平台 - 修改密码页面
 * Material Design风格重构版本
 */

// 开始会话
session_start();

// 引入依赖文件
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

// 检查用户是否已登录
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 获取当前用户信息
$current_user = $auth->getCurrentUser();

$error = '';
$success = '';

// 处理密码修改请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = '请填写所有密码字段';
    } elseif ($new_password !== $confirm_password) {
        $error = '新密码与确认密码不匹配';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码长度必须至少为6个字符';
    } else {
        $result = $auth->changePassword($current_user['id'], $current_password, $new_password);
        
        if ($result['success']) {
            // 密码修改成功，为安全起见自动登出用户
            $auth->logout();
            
            // 设置会话消息，在登录页面显示
            $_SESSION['password_changed_message'] = '密码修改成功！请使用新密码重新登录。';
            
            // 重定向到登录页面
            header('Location: login.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CloudClint 设备监控平台 - 修改密码">
    <meta name="robots" content="noindex, nofollow">
    
    <title>修改密码 - CloudClint 设备监控平台</title>
    
    <!-- Google Fonts - Roboto 字体 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- 网站图标 -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%231976d2%22><path d=%22M3 3h18a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm4 8v6h2v-6H7zm4-4v10h2V7h-2zm4 2v8h2V9h-2z%22/></svg>" type="image/svg+xml">
    
    <meta name="theme-color" content="#1976d2">
    
    <style>
    /* Material Design 3 修改密码页面样式 */
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
        --md-sys-color-warning: #ff9800;
        --md-sys-color-on-warning: #ffffff;
        --md-sys-color-warning-container: #fff3e0;
        --md-sys-color-on-warning-container: #e65100;
        
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
    
    .page-container {
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
    
    .password-card {
        background: var(--md-sys-color-surface);
        border-radius: 28px;
        box-shadow: var(--md-elevation-3);
        width: 100%;
        max-width: 480px;
        padding: 32px;
        position: relative;
        animation: materialFadeIn 0.4s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 32px;
    }
    
    .page-logo {
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
    
    .page-logo:hover {
        transform: scale(1.05);
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 500;
        color: var(--md-sys-color-on-surface);
        margin: 0 0 8px 0;
        letter-spacing: 0.25px;
    }
    
    .page-subtitle {
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
    
    .button-group {
        display: flex;
        gap: 16px;
        margin-top: 32px;
        flex-wrap: wrap;
    }
    
    .button-secondary {
        flex: 1;
        padding: 16px 24px;
        background: var(--md-sys-color-surface);
        color: var(--md-sys-color-primary);
        border: 1px solid var(--md-sys-color-outline);
        border-radius: 24px;
        font-size: 16px;
        font-weight: 500;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
        box-shadow: var(--md-elevation-1);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 120px;
        text-align: center;
    }
    
    .button-secondary:hover {
        background: var(--md-sys-color-primary-container);
        border-color: var(--md-sys-color-primary);
        transform: translateY(-1px);
        box-shadow: var(--md-elevation-2);
        text-decoration: none;
        color: var(--md-sys-color-on-primary-container);
    }
    
    .button-primary {
        flex: 2;
        padding: 16px 24px;
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
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 120px;
    }
    
    .button-primary:hover {
        background: var(--md-sys-color-primary);
        filter: brightness(0.9);
        box-shadow: var(--md-elevation-2);
        transform: translateY(-1px);
    }
    
    .button-primary:disabled {
        background: rgba(28, 27, 31, 0.12);
        color: rgba(28, 27, 31, 0.38);
        box-shadow: none;
        cursor: not-allowed;
        transform: none;
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
    
    .security-tips {
        margin-top: 32px;
        padding: 20px;
        background: var(--md-sys-color-primary-container);
        border-radius: 16px;
        border: 1px solid rgba(25, 118, 210, 0.2);
    }
    
    .tips-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .tips-title {
        font-size: 16px;
        font-weight: 500;
        color: var(--md-sys-color-on-primary-container);
        margin: 0;
    }
    
    .tips-list {
        color: var(--md-sys-color-on-primary-container);
        font-size: 14px;
        line-height: 1.6;
        margin: 0;
        padding-left: 20px;
    }
    
    .tips-list li {
        margin-bottom: 4px;
    }
    
    .field-helper {
        margin-top: 8px;
        font-size: 12px;
        color: var(--md-sys-color-outline);
        line-height: 1.4;
    }
    
    .strength-indicator {
        margin-top: 8px;
        padding: 12px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 500;
        animation: materialSlideDown 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    
    .strength-weak {
        background: var(--md-sys-color-error-container);
        color: var(--md-sys-color-on-error-container);
        border: 1px solid rgba(186, 26, 26, 0.2);
    }
    
    .strength-medium {
        background: var(--md-sys-color-warning-container);
        color: var(--md-sys-color-on-warning-container);
        border: 1px solid rgba(255, 152, 0, 0.2);
    }
    
    .strength-strong {
        background: var(--md-sys-color-success-container);
        color: var(--md-sys-color-on-success-container);
        border: 1px solid rgba(76, 175, 80, 0.2);
    }
    
    .match-indicator {
        margin-top: 8px;
        padding: 12px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 500;
        animation: materialSlideDown 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    
    .match-success {
        background: var(--md-sys-color-success-container);
        color: var(--md-sys-color-on-success-container);
        border: 1px solid rgba(76, 175, 80, 0.2);
    }
    
    .match-error {
        background: var(--md-sys-color-error-container);
        color: var(--md-sys-color-on-error-container);
        border: 1px solid rgba(186, 26, 26, 0.2);
    }
    
    /* 响应式设计 */
    @media (max-width: 480px) {
        .page-container {
            padding: 16px;
        }
        
        .password-card {
            padding: 24px;
        }
        
        .page-title {
            font-size: 24px;
        }
        
        .page-subtitle {
            font-size: 14px;
        }
        
        .button-group {
            flex-direction: column;
        }
        
        .button-secondary,
        .button-primary {
            flex: none;
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
            transform: rotate(360deg);
        }
    }
    
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: materialSpin 1s linear infinite;
    }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="password-card">
            <!-- 页面头部 -->
            <div class="page-header">
                <div class="page-logo">
                    <span class="material-icons" style="font-size: 32px;">vpn_key</span>
                </div>
                <h1 class="page-title">修改密码</h1>
                <p class="page-subtitle">更新您的账户密码以确保安全</p>
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
            
            <!-- 修改密码表单 -->
            <form method="POST" id="passwordForm">
                <!-- 当前密码 -->
                <div class="form-field">
                    <div class="form-field-outlined" id="currentPasswordField">
                        <input type="password" 
                               name="current_password" 
                               class="form-input" 
                               id="currentPassword"
                               required
                               autocomplete="current-password">
                        <label for="currentPassword" class="form-label">当前密码</label>
                        <span class="material-icons form-icon">lock</span>
                    </div>
                </div>
                
                <!-- 新密码 -->
                <div class="form-field">
                    <div class="form-field-outlined" id="newPasswordField">
                        <input type="password" 
                               name="new_password" 
                               class="form-input" 
                               id="newPassword"
                               required
                               minlength="6"
                               autocomplete="new-password">
                        <label for="newPassword" class="form-label">新密码</label>
                        <span class="material-icons form-icon">enhanced_encryption</span>
                    </div>
                    <div class="field-helper">
                        密码长度至少6个字符，建议包含字母、数字和特殊字符
                    </div>
                    <div id="passwordStrength" style="display: none;"></div>
                </div>
                
                <!-- 确认新密码 -->
                <div class="form-field">
                    <div class="form-field-outlined" id="confirmPasswordField">
                        <input type="password" 
                               name="confirm_password" 
                               class="form-input" 
                               id="confirmPassword"
                               required
                               minlength="6"
                               autocomplete="new-password">
                        <label for="confirmPassword" class="form-label">确认新密码</label>
                        <span class="material-icons form-icon">verified</span>
                    </div>
                    <div id="passwordMatch" style="display: none;"></div>
                </div>
                
                <!-- 操作按钮 -->
                <div class="button-group">
                    <a href="index.php" class="button-secondary">
                        <span class="material-icons" style="font-size: 18px;">arrow_back</span>
                        返回
                    </a>
                    <button type="submit" class="button-primary" id="submitBtn">
                        <span class="material-icons" style="font-size: 18px;">save</span>
                        <span id="btnText">保存修改</span>
                    </button>
                </div>
            </form>
            
            <!-- 安全提示 -->
            <div class="security-tips">
                <div class="tips-header">
                    <span class="material-icons" style="color: var(--md-sys-color-primary);">security</span>
                    <h3 class="tips-title">安全提示</h3>
                </div>
                <ul class="tips-list">
                    <li>使用包含大小写字母、数字和特殊字符的强密码</li>
                    <li>不要使用与其他网站相同的密码</li>
                    <li>定期更新密码以确保账户安全</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
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
    
    // 密码强度检查
    function checkPasswordStrength(password) {
        const strengthDiv = document.getElementById('passwordStrength');
        
        if (password.length === 0) {
            strengthDiv.style.display = 'none';
            return;
        }
        
        let score = 0;
        let feedback = [];
        
        // 长度检查
        if (password.length >= 8) {
            score += 2;
        } else if (password.length >= 6) {
            score += 1;
            feedback.push('建议至少8个字符');
        }
        
        // 包含小写字母
        if (/[a-z]/.test(password)) score += 1;
        else feedback.push('建议包含小写字母');
        
        // 包含大写字母
        if (/[A-Z]/.test(password)) score += 1;
        else feedback.push('建议包含大写字母');
        
        // 包含数字
        if (/[0-9]/.test(password)) score += 1;
        else feedback.push('建议包含数字');
        
        // 包含特殊字符
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        else feedback.push('建议包含特殊字符');
        
        let level, className, icon;
        if (score < 3) {
            level = '弱';
            className = 'strength-weak';
            icon = 'shield';
        } else if (score < 5) {
            level = '中等';
            className = 'strength-medium';
            icon = 'verified_user';
        } else {
            level = '强';
            className = 'strength-strong';
            icon = 'gpp_good';
        }
        
        strengthDiv.style.display = 'block';
        strengthDiv.innerHTML = `
            <div class="strength-indicator ${className}">
                <span class="material-icons" style="font-size: 16px;">${icon}</span>
                <span>密码强度：${level}</span>
            </div>
        `;
    }
    
    // 密码匹配检查
    function checkPasswordMatch() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const matchDiv = document.getElementById('passwordMatch');
        
        if (confirmPassword.length === 0) {
            matchDiv.style.display = 'none';
            return;
        }
        
        matchDiv.style.display = 'block';
        
        if (newPassword === confirmPassword) {
            matchDiv.innerHTML = `
                <div class="match-indicator match-success">
                    <span class="material-icons" style="font-size: 16px;">check_circle</span>
                    <span>密码匹配</span>
                </div>
            `;
        } else {
            matchDiv.innerHTML = `
                <div class="match-indicator match-error">
                    <span class="material-icons" style="font-size: 16px;">cancel</span>
                    <span>密码不匹配</span>
                </div>
            `;
        }
    }
    
    // 表单提交处理
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('新密码与确认密码不匹配');
            return false;
        }
        
        // 显示提交状态
        submitBtn.disabled = true;
        btnText.textContent = '处理中...';
        submitBtn.insertAdjacentHTML('afterbegin', '<span class="loading-spinner" style="margin-right: 8px;"></span>');
    });
    
    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化浮动标签
        handleFloatingLabel();
        
        // 绑定密码强度检查
        document.getElementById('newPassword').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
        
        // 绑定密码匹配检查
        document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);
        
        // 自动聚焦到当前密码输入框
        document.getElementById('currentPassword').focus();
    });
    </script>
</body>
</html>