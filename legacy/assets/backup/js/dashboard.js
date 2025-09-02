/**
 * CloudClint 仪表板 JavaScript - 整合版
 * 设备监控平台前端脚本
 * 整合了 dashboard.js 和 dashboard_simple.js 的所有功能
 */

// 通用的模态框清理函数
function cleanupModalBackdrop() {
    document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
        backdrop.remove();
    });
    
    // 强制解锁页面
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    document.body.style.pointerEvents = '';
    document.documentElement.style.pointerEvents = '';
    
    // 确保所有元素可点击
    document.querySelectorAll('.navbar, .container, .card, .btn').forEach(function(el) {
        el.style.pointerEvents = '';
    });
}

// 通用的模态框位置调整函数 (优化版)
function adjustModalPosition(modal) {
    try {
        if (!modal) return;
        
        // 获取当前滚动位置和窗口高度
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        
        // 获取模态框对话框
        const modalDialog = modal.querySelector('.modal-dialog');
        if (modalDialog) {
            // 重置之前的样式
            modalDialog.style.marginTop = '';
            modalDialog.style.marginBottom = '';
            modalDialog.style.position = '';
            modalDialog.style.top = '';
            
            // 强制重新计算布局
            modalDialog.offsetHeight;
            
            // 计算模态框应该显示的位置
            const modalHeight = modalDialog.offsetHeight;
            
            // 将模态框定位在当前视口中心，考虑滚动位置
            modalDialog.style.position = 'relative';
            modalDialog.style.top = '0';
            
            // 计算最佳的顶部位置，限制在视口内
            const optimalTop = Math.max(20, Math.min((windowHeight - modalHeight) / 2, windowHeight * 0.4));
            
            // 设置模态框位置
            modalDialog.style.marginTop = optimalTop + 'px';
            modalDialog.style.marginBottom = '20px';
        }
        
        // 确保模态框内容不超出视口
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.maxHeight = (windowHeight - 80) + 'px';
            modalContent.style.overflowY = 'auto';
        }
    } catch (error) {
        // 模态框位置调整失败，继续执行
    }
}

// 统一的模态框显示函数 (来自 dashboard_simple.js)
function showModal(modalId, deviceId, data) {
    try {
        var modal = document.getElementById(modalId);
        if (!modal) {
            showToast('模态框 ' + modalId + ' 未找到', 'danger');
            return;
        }
        
        // 填充数据
        if (modalId === 'remarksModal') {
            const deviceIdInput = document.getElementById('device_id');
            const remarksInput = document.getElementById('remarks');
            
            if (!deviceIdInput || !remarksInput) {
                showToast('备注模态框输入框未找到', 'danger');
                return;
            }
            
            deviceIdInput.value = deviceId || '';
            remarksInput.value = data || '';
        } else if (modalId === 'createdTimeModal') {
            const deviceIdInput = document.getElementById('time_device_id');
            const createdAtInput = document.getElementById('created_at');
            
            if (!deviceIdInput || !createdAtInput) {
                showToast('时间模态框输入框未找到', 'danger');
                return;
            }
            
            deviceIdInput.value = deviceId || '';
            
            // 格式化时间数据为datetime-local格式
            let formattedDate = data || '';
            if (formattedDate) {
                try {
                    const date = new Date(formattedDate);
                    if (!isNaN(date.getTime())) {
                        // 转换为本地时间格式 YYYY-MM-DDTHH:mm:ss
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        const hours = String(date.getHours()).padStart(2, '0');
                        const minutes = String(date.getMinutes()).padStart(2, '0');
                        const seconds = String(date.getSeconds()).padStart(2, '0');
                        formattedDate = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
                    }
                } catch (e) {
                    // 时间格式化失败
                }
            }
            
            createdAtInput.value = formattedDate;
        } else if (modalId === 'orderNumberModal') {
            const deviceIdInput = document.getElementById('order_device_id');
            const orderNumberInput = document.getElementById('order_number');
            
            if (!deviceIdInput || !orderNumberInput) {
                showToast('编号模态框输入框未找到', 'danger');
                return;
            }
            
            deviceIdInput.value = deviceId || '';
            orderNumberInput.value = data || 0;
        }
        
        // 检查Bootstrap
        if (typeof bootstrap === 'undefined') {
            showToast('Bootstrap未加载，无法显示模态框', 'danger');
            return;
        }
        
        // 初始化模态框
        var modalInstance = new bootstrap.Modal(modal, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // 监听模态框显示事件，调整位置
        modal.addEventListener('shown.bs.modal', function() {
            adjustModalPosition(modal);
            
            // 300ms后再次调整，确保在DOM完全渲染后调整位置
            setTimeout(function() {
                adjustModalPosition(modal);
            }, 300);
        });
        
        // 监听窗口大小变化，重新调整模态框位置
        const resizeHandler = function() {
            if (modal.classList.contains('show')) {
                adjustModalPosition(modal);
            }
        };
        
        // 添加resize事件监听
        window.addEventListener('resize', resizeHandler);
        
        // 模态框关闭时，移除resize事件监听
        modal.addEventListener('hidden.bs.modal', function() {
            window.removeEventListener('resize', resizeHandler);
        });
        
        modalInstance.show();
        
    } catch (error) {
        showToast('模态框错误: ' + error.message, 'danger');
    }
}

// 生成唯一请求ID
function generateRequestId() {
    return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// 防止重复提交API请求
function preventDuplicateRequest(callback) {
    // 生成请求ID和时间戳
    const requestId = generateRequestId();
    const timestamp = Math.floor(Date.now() / 1000);
    
    // 返回包含请求ID和时间戳的函数
    return function() {
        return callback(requestId, timestamp);
    };
}

// 显示提示消息 (Toast系统)
function showToast(message, type = 'info') {
    try {
        // 创建Toast元素
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            // 如果容器不存在，创建一个
            const newContainer = document.createElement('div');
            newContainer.id = 'toast-container';
            newContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(newContainer);
        }
        
        // 创建Toast
        const toastId = 'toast-' + Date.now();
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        toastEl.id = toastId;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        // 设置Toast内容
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="关闭"></button>
            </div>
        `;
        
        // 添加到容器
        document.getElementById('toast-container').appendChild(toastEl);
        
        // 初始化并显示Toast
        const toast = new bootstrap.Toast(toastEl, {
            animation: true,
            autohide: true,
            delay: 5000
        });
        toast.show();
        
    } catch (error) {
        // 备用显示方式
        try {
            // 使用alert作为备用
            alert(message);
        } catch (alertError) {
            console.error('Toast and alert both failed:', error, alertError);
        }
    }
}

// 保存备注功能
function saveRemarks(deviceId, remarks, button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> 保存中...';
    
    const data = {
        device_id: deviceId,
        remarks: remarks
    };
    
    fetch('api/update_remarks.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // 关闭模态框
            const modal = document.getElementById('remarksModal');
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) modalInstance.hide();
            
            showToast('备注保存成功', 'success');
            // 平滑刷新页面以显示更新
            var currentScroll = window.pageYOffset;
            sessionStorage.setItem('scrollPosition', currentScroll);
            window.location.reload();
        } else {
            showToast('保存失败: ' + (data.message || data.error || '未知错误'), 'danger');
        }
    })
    .catch(error => {
        showToast('保存失败: ' + error.message, 'danger');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// 保存创建时间功能
function saveCreatedTime(deviceId, createdTime, button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> 保存中...';
    
    // 转换时间格式：从 YYYY-MM-DDTHH:mm:ss 到 YYYY-MM-DD HH:MM:SS
    let formattedTime = createdTime;
    if (createdTime && typeof createdTime === 'string') {
        // 替换T为空格
        if (createdTime.includes('T')) {
            formattedTime = createdTime.replace('T', ' ');
        }
        
        // 确保时间格式完整（包含秒数）
        const timeParts = formattedTime.split(' ');
        if (timeParts.length === 2) {
            const datePart = timeParts[0];
            const timePart = timeParts[1];
            const timeComponents = timePart.split(':');
            
            if (timeComponents.length === 2) {
                // 缺少秒数，添加 :00
                formattedTime = datePart + ' ' + timePart + ':00';
            }
        }
    }
    
    // 验证参数
    if (!deviceId || !formattedTime) {
        showToast('请输入有效的设备ID和时间', 'danger');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }
    
    const data = {
        device_id: deviceId,
        created_at: formattedTime
    };
    
    // 调用时间更新API
    fetch('api/update_created_time.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json; charset=utf-8'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            // 尝试读取错误响应
            return response.text().then(text => {
                throw new Error('API调用失败: ' + response.status + ' - ' + text);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // 关闭模态框
            const modal = document.getElementById('createdTimeModal');
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) modalInstance.hide();
            
            showToast('创建时间保存成功', 'success');
            // 平滑刷新页面以显示更新
            var currentScroll = window.pageYOffset;
            sessionStorage.setItem('scrollPosition', currentScroll);
            window.location.reload();
        } else {
            showToast('保存失败: ' + (data.message || data.error || '未知错误'), 'danger');
        }
    })
    .catch(error => {
        showToast('保存失败: ' + error.message, 'danger');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// 保存编号功能
function saveOrderNumber(deviceId, orderNumber, button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> 保存中...';
    
    const formData = new FormData();
    formData.append('device_id', deviceId);
    formData.append('order_number', orderNumber);
    
    fetch('api/update_order_number.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // 关闭模态框
            const modal = document.getElementById('orderNumberModal');
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) modalInstance.hide();
            
            showToast('编号保存成功', 'success');
            // 平滑刷新页面以显示更新
            var currentScroll = window.pageYOffset;
            sessionStorage.setItem('scrollPosition', currentScroll);
            window.location.reload();
        } else {
            showToast('保存失败: ' + (data.message || data.error || '未知错误'), 'danger');
        }
    })
    .catch(error => {
        showToast('保存失败: ' + error.message, 'danger');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// 自动关闭提示消息
function setupAutoCloseAlerts() {
    // 获取所有提示消息
    const alerts = document.querySelectorAll('.alert');
    
    // 为每个提示消息设置自动关闭
    alerts.forEach(function(alert) {
        // 创建Bootstrap Alert实例
        const bsAlert = new bootstrap.Alert(alert);
        
        // 根据提示类型设置不同的关闭时间
        let delay = 3000; // 默认3秒
        
        // 成功提示3秒，错误提示5秒
        if (alert.classList.contains('alert-danger')) {
            delay = 5000;
        } else if (alert.classList.contains('alert-warning')) {
            delay = 4000;
        }
        
        // 设置定时器自动关闭
        setTimeout(function() {
            bsAlert.close();
        }, delay);
    });
}

// 处理认证错误
function handleAuthError() {
    showToast('登录已过期，即将跳转到登录页面', 'warning');
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 2000);
}

// 全局函数，供onclick使用
window.editRemarks = function(deviceId, remarks) {
    try {
        showModal('remarksModal', deviceId, remarks);
    } catch (error) {
        showToast('备注编辑功能出错：' + error.message, 'danger');
    }
};

window.editCreatedTime = function(deviceId, createdAt) {
    try {
        showModal('createdTimeModal', deviceId, createdAt);
    } catch (error) {
        showToast('时间编辑功能出错：' + error.message, 'danger');
    }
};

window.editOrderNumber = function(deviceId, orderNumber) {
    try {
        showModal('orderNumberModal', deviceId, orderNumber);
    } catch (error) {
        showToast('编号编辑功能出错：' + error.message, 'danger');
    }
};

// 删除设备函数 (带确认对话框)
window.deleteDevice = function(deviceId, deviceName) {
    if (confirm('确定要删除设备 "' + deviceName + '" 吗？\n\n注意：只能删除离线设备，此操作不可恢复！')) {
        // 显示删除进度提示
        showToast('正在删除设备...', 'info');
        
        // 创建表单并提交
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_device';
        
        const deviceIdInput = document.createElement('input');
        deviceIdInput.type = 'hidden';
        deviceIdInput.name = 'device_id';
        deviceIdInput.value = deviceId;
        
        form.appendChild(actionInput);
        form.appendChild(deviceIdInput);
        document.body.appendChild(form);

        // 提交表单
        form.submit();
    }
};

// 修复按钮事件函数
function fixButtonEvents() {
    // 修复设备备注编辑按钮
    document.querySelectorAll('.remarks-edit').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const deviceId = this.getAttribute('data-device-id');
            const remarks = this.getAttribute('data-remarks');
            window.editRemarks(deviceId, remarks);
        });
    });
    
    // 修复设备时间编辑按钮
    document.querySelectorAll('.edit-created-time').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const deviceId = this.getAttribute('data-device-id');
            const createdAt = this.getAttribute('data-created-at');
            window.editCreatedTime(deviceId, createdAt);
        });
    });
    
    // 修复设备编号编辑按钮
    document.querySelectorAll('.edit-order-number').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const deviceId = this.getAttribute('data-device-id');
            const orderNumber = this.getAttribute('data-order-number');
            window.editOrderNumber(deviceId, orderNumber);
        });
    });
    
    // 修复删除按钮
    const deleteButtons = document.querySelectorAll('.delete-device-btn, button[onclick*="deleteDevice"]');
    
    deleteButtons.forEach(function(button) {
        // 检查是否已经有onclick属性
        const onclickAttr = button.getAttribute('onclick');
        if (onclickAttr && onclickAttr.includes('deleteDevice')) {
            // 如果已经有onclick，则不需要额外绑定
            return;
        }
        
        // 为没有onclick的按钮添加事件监听器
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const deviceId = this.getAttribute('data-device-id');
            const deviceName = this.getAttribute('data-device-name');
            window.deleteDevice(deviceId, deviceName);
        });
    });
    
    // 修复用户编辑按钮
    document.querySelectorAll('.edit-user-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            const email = this.getAttribute('data-email');
            const role = this.getAttribute('data-role');
            const status = this.getAttribute('data-status');
            openEditUserModal(userId, username, email, role, status);
        });
    });
    
    // 修复用户锁定/解锁按钮
    document.querySelectorAll('.toggle-lock-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            const action = this.getAttribute('data-action');
            const username = this.getAttribute('data-username');
            toggleUserLock(userId, action, username, this);
        });
    });
}

// 初始化用户管理功能
function initUserManagement() {
    // 绑定用户编辑保存按钮
    const saveUserBtn = document.getElementById('saveUserBtn');
    if (saveUserBtn) {
        saveUserBtn.addEventListener('click', function() {
            const formData = new FormData(document.getElementById('editUserForm'));
            saveUser(formData, this);
        });
    }
}

// 打开用户编辑模态框
function openEditUserModal(userId, username, email, role, status) {
    try {
        const modal = document.getElementById('editUserModal');
        if (!modal) {
            showToast('用户编辑模态框未找到', 'danger');
            return;
        }
        
        // 填充表单数据
        document.getElementById('edit_user_id').value = userId || '';
        document.getElementById('edit_username').value = username || '';
        document.getElementById('edit_email').value = email || '';
        document.getElementById('edit_role').value = role || '';
        document.getElementById('edit_status').value = status || '';
        document.getElementById('edit_password').value = ''; // 清空密码字段
        
        // 监听模态框显示事件，调整位置
        modal.addEventListener('shown.bs.modal', function() {
            adjustModalPosition(modal);
        });
        
        // 显示模态框
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
    } catch (error) {
        showToast('打开用户编辑窗口失败: ' + error.message, 'danger');
    }
}

// 切换用户锁定状态
function toggleUserLock(userId, action, username, button) {
    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> 处理中...';
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);
    
    fetch('api/toggle_user_lock.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // 刷新页面以更新UI
            location.reload();
        } else {
            showToast('操作失败: ' + (data.message || '未知错误'), 'danger');
        }
    })
    .catch(error => {
        showToast('操作失败: ' + error.message, 'danger');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
}

// 保存用户信息
function saveUser(formData, button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> 保存中...';
    
    fetch('api/update_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('用户信息更新成功', 'success');
            // 关闭模态框
            const modal = document.getElementById('editUserModal');
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) modalInstance.hide();
            
            // 刷新页面
            location.reload();
        } else {
            showToast('更新失败: ' + (data.message || '未知错误'), 'danger');
        }
    })
    .catch(error => {
        showToast('更新失败: ' + error.message, 'danger');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// 防止页面闪白的初始化
(function() {
    // 立即添加loading类，防止闪白
    document.documentElement.classList.add('loading');
    
    // 立即设置滚动行为，防止闪白
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    
    // 记录滚动位置
    var scrollPos = 0;
    
    window.addEventListener('beforeunload', function() {
        scrollPos = window.pageYOffset;
        sessionStorage.setItem('scrollPosition', scrollPos);
    });
    
    // 页面加载时恢复滚动位置
    window.addEventListener('load', function() {
        var savedPos = sessionStorage.getItem('scrollPosition');
        if (savedPos && !isNaN(savedPos)) {
            // 延迟恢复，确保页面完全加载
            setTimeout(function() {
                window.scrollTo(0, parseInt(savedPos));
                sessionStorage.removeItem('scrollPosition');
                // 移除loading类，显示页面
                document.body.classList.remove('loading');
                document.body.classList.add('loaded');
            }, 50);
        } else {
            // 没有保存的滚动位置时，直接显示页面
            setTimeout(function() {
                document.body.classList.remove('loading');
                document.body.classList.add('loaded');
            }, 100);
        }
    });
})();

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    // 设置自动关闭提示消息
    setupAutoCloseAlerts();
    
    // 绑定保存按钮事件
    const saveRemarksBtn = document.getElementById('saveRemarks');
    if (saveRemarksBtn) {
        saveRemarksBtn.addEventListener('click', function() {
            const deviceId = document.getElementById('device_id').value;
            const remarksText = document.getElementById('remarks').value;
            
            if (deviceId) {
                saveRemarks(deviceId, remarksText, this);
            }
        });
    }
    
    const saveCreatedTimeBtn = document.getElementById('saveCreatedTime');
    if (saveCreatedTimeBtn) {
        saveCreatedTimeBtn.addEventListener('click', function() {
            const deviceId = document.getElementById('time_device_id').value;
            const createdTime = document.getElementById('created_at').value;
            
            if (deviceId && createdTime) {
                saveCreatedTime(deviceId, createdTime, this);
            } else {
                showToast('请输入有效的日期时间', 'warning');
            }
        });
    }
    
    const saveOrderNumberBtn = document.getElementById('saveOrderNumber');
    if (saveOrderNumberBtn) {
        saveOrderNumberBtn.addEventListener('click', function() {
            const deviceId = document.getElementById('order_device_id').value;
            const orderNumber = document.getElementById('order_number').value;
            
            if (deviceId) {
                saveOrderNumber(deviceId, orderNumber, this);
            } else {
                showToast('设备ID无效', 'warning');
            }
        });
    }
    
    // 初始化用户管理功能（如果在admin页面）
    if (document.getElementById('editUserModal')) {
        initUserManagement();
    }
    
    // 修复所有按钮的点击事件
    fixButtonEvents();
    
    // 为所有模态框添加位置调整
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('shown.bs.modal', function() {
            adjustModalPosition(modal);
        });
    });
});