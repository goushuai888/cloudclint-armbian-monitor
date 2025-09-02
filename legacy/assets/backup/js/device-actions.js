/**
 * 设备操作模块
 * 负责处理设备相关的操作：删除、编辑等
 */

/**
 * 删除设备
 * @param {string} deviceId - 设备ID
 * @param {string} deviceName - 设备名称
 */
function deleteDevice(deviceId, deviceName) {
    // 输入验证
    if (!deviceId || typeof deviceId !== 'string') {
        showToast('设备ID无效', 'error');
        return;
    }
    
    if (!deviceName || typeof deviceName !== 'string') {
        deviceName = '未知设备';
    }
    
    // XSS防护 - 转义HTML字符
    const safeDeviceName = deviceName
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
    
    // 确认对话框
    if (!confirm(`确定要删除设备 "${safeDeviceName}" 吗？\n\n此操作不可撤销！`)) {
        return;
    }
    
    // 创建并提交表单
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php';
    form.style.display = 'none';
    
    // 添加CSRF保护（如果需要）
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'delete_device';
    form.appendChild(actionInput);
    
    const deviceIdInput = document.createElement('input');
    deviceIdInput.type = 'hidden';
    deviceIdInput.name = 'device_id';
    deviceIdInput.value = deviceId;
    form.appendChild(deviceIdInput);
    
    document.body.appendChild(form);
    form.submit();
}

/**
 * 编辑设备创建时间
 * @param {string} deviceId - 设备ID
 * @param {string} currentTime - 当前时间
 */
function editCreatedTime(deviceId, currentTime) {
    // 输入验证
    if (!deviceId || typeof deviceId !== 'string') {
        showToast('设备ID无效', 'error');
        return;
    }

    // 调用模态框函数
    if (typeof showCreatedTimeModal === 'function') {
        showCreatedTimeModal(deviceId, currentTime);
    } else {
        showToast('模态框功能未加载，请刷新页面重试', 'error');
    }

}

/**
 * 编辑设备序号
 * @param {string} deviceId - 设备ID
 * @param {number} currentOrder - 当前序号
 */
function editOrderNumber(deviceId, currentOrder) {
    // 输入验证
    if (!deviceId || typeof deviceId !== 'string') {
        showToast('设备ID无效', 'error');
        return;
    }

    // 调用模态框函数
    if (typeof showOrderNumberModal === 'function') {
        showOrderNumberModal(deviceId, currentOrder);
    } else {
        showToast('模态框功能未加载，请刷新页面重试', 'error');
    }

}

/**
 * 编辑设备备注
 * @param {string} deviceId - 设备ID
 * @param {string} currentRemarks - 当前备注
 */
function editRemarks(deviceId, currentRemarks) {
    // 输入验证
    if (!deviceId || typeof deviceId !== 'string') {
        showToast('设备ID无效', 'error');
        return;
    }

    // 调用模态框函数
    if (typeof showRemarksModal === 'function') {
        showRemarksModal(deviceId, currentRemarks);
    } else {
        showToast('模态框功能未加载，请刷新页面重试', 'error');
    }

}

/**
 * 显示Toast通知
 * @param {string} message - 消息内容
 * @param {string} type - 消息类型 (success, error, info, warning)
 */
function showToast(message, type = 'info') {
    // 检查是否已有其他toast系统（避免与当前函数冲突）
    if (typeof window.showToastExternal === 'function') {
        window.showToastExternal(message, type);
        return;
    }
    
    // 简单的toast实现
    const toast = document.createElement('div');
    toast.className = `cc-toast cc-toast-${type}`;
    toast.textContent = message;
    
    // 样式
    Object.assign(toast.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '12px 20px',
        borderRadius: '6px',
        color: 'white',
        fontSize: '14px',
        zIndex: '10000',
        opacity: '0',
        transform: 'translateX(100%)',
        transition: 'all 0.3s ease'
    });
    
    // 根据类型设置背景色
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    toast.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(toast);
    
    // 显示动画
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    });
    
    // 自动隐藏
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}