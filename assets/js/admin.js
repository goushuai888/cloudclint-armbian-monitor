/**
 * CloudClint 设备监控平台 - 管理页面 JavaScript
 * 处理管理页面的前端交互逻辑
 */

// 全局变量
let currentTab = 'users-tab';
let isProcessing = false;

// DOM 加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
});

/**
 * 初始化管理页面
 */
function initializeAdmin() {
    initTabs();
    initFormFields();
    initUserActions();
    initFormValidation();
    
    // 自动隐藏消息提示
    setTimeout(hideMessages, 5000);
}

/**
 * 初始化标签页功能
 */
function initTabs() {
    const tabs = document.querySelectorAll('.admin-tab');
    const panes = document.querySelectorAll('.tab-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            switchTab(targetTab);
        });
    });
}

/**
 * 切换标签页
 * @param {string} tabId - 目标标签页ID
 */
function switchTab(tabId) {
    if (currentTab === tabId) return;
    
    // 更新标签页状态
    document.querySelectorAll('.admin-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    
    // 激活目标标签页
    const targetTab = document.querySelector(`[data-tab="${tabId}"]`);
    const targetPane = document.getElementById(tabId);
    
    if (targetTab && targetPane) {
        targetTab.classList.add('active');
        targetPane.classList.add('active');
        currentTab = tabId;
    }
}

/**
 * 初始化表单字段
 */
function initFormFields() {
    const formFields = document.querySelectorAll('.form-field-outlined input, .form-field-outlined select');
    
    formFields.forEach(field => {
        // 检查初始值
        checkFieldValue(field);
        
        // 监听输入事件
        field.addEventListener('input', function() {
            checkFieldValue(this);
        });
        
        field.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        field.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            checkFieldValue(this);
        });
    });
}

/**
 * 检查字段值并更新样式
 * @param {HTMLElement} field - 表单字段
 */
function checkFieldValue(field) {
    const container = field.parentElement;
    
    if (field.value.trim() !== '') {
        container.classList.add('has-value');
    } else {
        container.classList.remove('has-value');
    }
}

/**
 * 初始化用户操作
 */
function initUserActions() {
    // 编辑用户按钮
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userData = {
                id: this.getAttribute('data-user-id'),
                username: this.getAttribute('data-username'),
                email: this.getAttribute('data-email'),
                role: this.getAttribute('data-role'),
                status: this.getAttribute('data-status')
            };
            showUserEditDialog(userData);
        });
    });
    
    // 锁定/解锁用户按钮
    document.querySelectorAll('.toggle-lock-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            
            toggleUserLock(action, userId, username, this);
        });
    });
}

/**
 * 显示用户编辑对话框
 * @param {Object} userData - 用户数据
 */
function showUserEditDialog(userData) {
    // 创建对话框HTML
    const dialogHtml = `
        <div class="dialog-overlay" id="user-edit-dialog">
            <div class="dialog">
                <div class="dialog-header">
                    <h3><i class="material-icons">edit</i>编辑用户</h3>
                    <button class="dialog-close" onclick="closeUserEditDialog()">
                        <i class="material-icons">close</i>
                    </button>
                </div>
                <div class="dialog-body">
                    <form id="edit-user-form">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" value="${userData.id}">
                        
                        <div class="form-field">
                            <div class="form-field-outlined has-value">
                                <input type="text" id="edit-username" name="username" value="${userData.username}" required>
                                <label class="form-field-label" for="edit-username">用户名</label>
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <div class="form-field-outlined ${userData.email ? 'has-value' : ''}">
                                <input type="email" id="edit-email" name="email" value="${userData.email || ''}">
                                <label class="form-field-label" for="edit-email">邮箱</label>
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <div class="form-field-outlined has-value">
                                <select id="edit-role" name="role">
                                    <option value="user" ${userData.role === 'user' ? 'selected' : ''}>普通用户</option>
                                    <option value="admin" ${userData.role === 'admin' ? 'selected' : ''}>管理员</option>
                                </select>
                                <label class="form-field-label" for="edit-role">用户角色</label>
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <div class="form-field-outlined">
                                <input type="password" id="edit-password" name="password" placeholder="留空则不修改密码">
                                <label class="form-field-label" for="edit-password">新密码（可选）</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="dialog-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeUserEditDialog()">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitUserEdit()">保存</button>
                </div>
            </div>
        </div>
    `;
    
    // 添加到页面
    document.body.insertAdjacentHTML('beforeend', dialogHtml);
    
    // 初始化对话框中的表单字段
    const dialog = document.getElementById('user-edit-dialog');
    const formFields = dialog.querySelectorAll('.form-field-outlined input, .form-field-outlined select');
    formFields.forEach(field => {
        checkFieldValue(field);
        field.addEventListener('input', function() {
            checkFieldValue(this);
        });
    });
    
    // 显示对话框
    setTimeout(() => {
        dialog.classList.add('show');
    }, 10);
}

/**
 * 关闭用户编辑对话框
 */
function closeUserEditDialog() {
    const dialog = document.getElementById('user-edit-dialog');
    if (dialog) {
        dialog.classList.remove('show');
        setTimeout(() => {
            dialog.remove();
        }, 300);
    }
}

/**
 * 提交用户编辑
 */
function submitUserEdit() {
    if (isProcessing) return;
    
    const form = document.getElementById('edit-user-form');
    const formData = new FormData(form);
    
    isProcessing = true;
    
    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // 刷新页面以显示更新结果
        window.location.reload();
    })
    .catch(error => {
        console.error('编辑用户失败:', error);
        showMessage('编辑用户失败，请重试', 'error');
    })
    .finally(() => {
        isProcessing = false;
        closeUserEditDialog();
    });
}

/**
 * 切换用户锁定状态
 * @param {string} action - 操作类型 (lock/unlock)
 * @param {string} userId - 用户ID
 * @param {string} username - 用户名
 * @param {HTMLElement} button - 按钮元素
 */
function toggleUserLock(action, userId, username, button) {
    if (isProcessing) return;
    
    const actionText = action === 'lock' ? '锁定' : '解锁';
    
    if (!confirm(`确定要${actionText}用户 "${username}" 吗？`)) {
        return;
    }
    
    isProcessing = true;
    button.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'toggle_user_lock');
    formData.append('user_id', userId);
    formData.append('lock_action', action);
    
    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // 刷新页面以显示更新结果
        window.location.reload();
    })
    .catch(error => {
        console.error(`${actionText}用户失败:`, error);
        showMessage(`${actionText}用户失败，请重试`, 'error');
    })
    .finally(() => {
        isProcessing = false;
        button.disabled = false;
    });
}

/**
 * 初始化表单验证
 */
function initFormValidation() {
    // 添加用户表单验证
    const addUserForm = document.querySelector('form[action=""] input[value="add_user"]');
    if (addUserForm) {
        const form = addUserForm.closest('form');
        form.addEventListener('submit', function(e) {
            if (!validateAddUserForm(this)) {
                e.preventDefault();
            }
        });
    }
    
    // 系统配置表单验证
    const configForm = document.querySelector('form[action=""] input[value="update_config"]');
    if (configForm) {
        const form = configForm.closest('form');
        form.addEventListener('submit', function(e) {
            if (!validateConfigForm(this)) {
                e.preventDefault();
            }
        });
    }
}

/**
 * 验证添加用户表单
 * @param {HTMLFormElement} form - 表单元素
 * @returns {boolean} 验证结果
 */
function validateAddUserForm(form) {
    const username = form.querySelector('[name="username"]').value.trim();
    const password = form.querySelector('[name="password"]').value;
    const email = form.querySelector('[name="email"]').value.trim();
    
    // 用户名验证
    if (username.length < 3) {
        showMessage('用户名长度至少3个字符', 'error');
        return false;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        showMessage('用户名只能包含字母、数字和下划线', 'error');
        return false;
    }
    
    // 密码验证
    if (password.length < 6) {
        showMessage('密码长度至少6个字符', 'error');
        return false;
    }
    
    // 邮箱验证（如果填写）
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showMessage('请输入有效的邮箱地址', 'error');
        return false;
    }
    
    return true;
}

/**
 * 验证系统配置表单
 * @param {HTMLFormElement} form - 表单元素
 * @returns {boolean} 验证结果
 */
function validateConfigForm(form) {
    const siteTitle = form.querySelector('[name="site_title"]').value.trim();
    const heartbeatTimeout = parseInt(form.querySelector('[name="heartbeat_timeout"]').value);
    const loginAttemptsLimit = parseInt(form.querySelector('[name="login_attempts_limit"]').value);
    const loginLockoutTime = parseInt(form.querySelector('[name="login_lockout_time"]').value);
    const sessionLifetime = parseInt(form.querySelector('[name="session_lifetime"]').value);
    
    // 网站标题验证
    if (siteTitle.length < 1) {
        showMessage('网站标题不能为空', 'error');
        return false;
    }
    
    // 数值范围验证
    if (heartbeatTimeout < 30 || heartbeatTimeout > 300) {
        showMessage('心跳超时时间必须在30-300秒之间', 'error');
        return false;
    }
    
    if (loginAttemptsLimit < 3 || loginAttemptsLimit > 10) {
        showMessage('登录尝试次数限制必须在3-10次之间', 'error');
        return false;
    }
    
    if (loginLockoutTime < 5 || loginLockoutTime > 120) {
        showMessage('登录锁定时间必须在5-120分钟之间', 'error');
        return false;
    }
    
    if (sessionLifetime < 30 || sessionLifetime > 480) {
        showMessage('会话生命周期必须在30-480分钟之间', 'error');
        return false;
    }
    
    return true;
}

/**
 * 显示消息提示
 * @param {string} message - 消息内容
 * @param {string} type - 消息类型 (success/error)
 */
function showMessage(message, type = 'info') {
    const messageContainer = document.querySelector('.message-container');
    if (!messageContainer) return;
    
    const alertHtml = `
        <div class="alert alert-${type}">
            <i class="material-icons">${type === 'error' ? 'error' : 'check_circle'}</i>
            <span>${message}</span>
        </div>
    `;
    
    messageContainer.innerHTML = alertHtml;
    
    // 自动隐藏
    setTimeout(hideMessages, 5000);
}

/**
 * 隐藏消息提示
 */
function hideMessages() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 300);
    });
}

/**
 * 工具函数：防抖
 * @param {Function} func - 要防抖的函数
 * @param {number} wait - 等待时间
 * @returns {Function} 防抖后的函数
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * 工具函数：节流
 * @param {Function} func - 要节流的函数
 * @param {number} limit - 限制时间
 * @returns {Function} 节流后的函数
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}