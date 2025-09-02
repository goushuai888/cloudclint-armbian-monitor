/**
 * 设备编辑模态框管理
 * 处理设备信息的模态框编辑功能
 */

(function() {
    'use strict';

    // 等待DOM加载完成
    document.addEventListener('DOMContentLoaded', function() {
        initializeDeviceModals();
    });

    function initializeDeviceModals() {
        // 初始化备注编辑模态框
        initRemarksModal();
        
        // 初始化时间编辑模态框
        initCreatedTimeModal();
        
        // 初始化编号编辑模态框
        initOrderNumberModal();
    }

    // 备注编辑模态框
    function initRemarksModal() {
        const saveButton = document.getElementById('saveRemarks');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                saveRemarksData();
            });
        }

        // 回车键保存
        const remarksTextarea = document.getElementById('remarks');
        if (remarksTextarea) {
            remarksTextarea.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    saveRemarksData();
                }
            });
        }
    }

    // 时间编辑模态框
    function initCreatedTimeModal() {
        const saveButton = document.getElementById('saveCreatedTime');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                saveCreatedTimeData();
            });
        }
    }

    // 编号编辑模态框
    function initOrderNumberModal() {
        const saveButton = document.getElementById('saveOrderNumber');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                saveOrderNumberData();
            });
        }
    }

    // 显示备注编辑模态框
    window.showRemarksModal = function(deviceId, currentRemarks) {
        if (!deviceId) {
            showToast('设备ID无效', 'error');
            return;
        }

        const modal = document.getElementById('remarksModal');
        const deviceIdInput = document.getElementById('device_id');
        const remarksTextarea = document.getElementById('remarks');

        if (modal && deviceIdInput && remarksTextarea) {
            deviceIdInput.value = deviceId;
            remarksTextarea.value = currentRemarks || '';
            
            // 添加模态框显示动画
            modal.classList.add('modern-modal');
            
            // 添加打开前的预处理
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.9)';
            
            // 使用Bootstrap模态框
            const bsModal = new bootstrap.Modal(modal, {
                backdrop: 'static',
                keyboard: true
            });
            bsModal.show();
            
            // 模态框显示后的动画效果
            modal.addEventListener('shown.bs.modal', function() {
                // 淡入动画
                modal.style.transition = 'all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                modal.style.opacity = '1';
                modal.style.transform = 'scale(1)';
                
                // 聚焦并添加输入框动画
                setTimeout(() => {
                    remarksTextarea.focus();
                    remarksTextarea.setSelectionRange(remarksTextarea.value.length, remarksTextarea.value.length);
                    remarksTextarea.style.transform = 'scale(1.02)';
                    remarksTextarea.style.boxShadow = '0 0 20px rgba(102, 126, 234, 0.3)';
                    remarksTextarea.style.transition = 'all 0.3s ease';
                    setTimeout(() => {
                        remarksTextarea.style.transform = 'scale(1)';
                        remarksTextarea.style.boxShadow = '';
                    }, 300);
                }, 100);
            }, { once: true });
        }
    };

    // 显示时间编辑模态框
    window.showCreatedTimeModal = function(deviceId, currentTime) {
        if (!deviceId) {
            showToast('设备ID无效', 'error');
            return;
        }

        const modal = document.getElementById('createdTimeModal');
        const deviceIdInput = document.getElementById('time_device_id');
        const timeInput = document.getElementById('created_at');

        if (modal && deviceIdInput && timeInput) {
            deviceIdInput.value = deviceId;
            
            // 转换时间格式为datetime-local格式
            if (currentTime) {
                const date = new Date(currentTime);
                const localDateTime = new Date(date.getTime() - date.getTimezoneOffset() * 60000)
                    .toISOString()
                    .slice(0, 19);
                timeInput.value = localDateTime;
            }
            
            // 添加模态框显示动画
            modal.classList.add('modern-modal');
            
            // 添加打开前的预处理
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.9) translateY(-20px)';
            
            // 使用Bootstrap模态框
            const bsModal = new bootstrap.Modal(modal, {
                backdrop: 'static',
                keyboard: true
            });
            bsModal.show();
            
            // 模态框显示后的动画效果
            modal.addEventListener('shown.bs.modal', function() {
                // 滑入淡入动画
                modal.style.transition = 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
                modal.style.opacity = '1';
                modal.style.transform = 'scale(1) translateY(0)';
                
                // 聚焦并添加输入框动画
                setTimeout(() => {
                    timeInput.focus();
                    timeInput.style.transform = 'scale(1.02)';
                    timeInput.style.boxShadow = '0 0 20px rgba(34, 197, 94, 0.3)';
                    timeInput.style.transition = 'all 0.3s ease';
                    setTimeout(() => {
                        timeInput.style.transform = 'scale(1)';
                        timeInput.style.boxShadow = '';
                    }, 300);
                }, 150);
            }, { once: true });
        }
    };

    // 显示编号编辑模态框
    window.showOrderNumberModal = function(deviceId, currentOrder) {
        if (!deviceId) {
            showToast('设备ID无效', 'error');
            return;
        }

        const modal = document.getElementById('orderNumberModal');
        const deviceIdInput = document.getElementById('order_device_id');
        const orderInput = document.getElementById('order_number');

        if (modal && deviceIdInput && orderInput) {
            deviceIdInput.value = deviceId;
            orderInput.value = currentOrder || '0';
            
            // 添加模态框显示动画
            modal.classList.add('modern-modal');
            
            // 添加打开前的预处理
            modal.style.opacity = '0';
            modal.style.transform = 'scale(0.8) rotateX(10deg)';
            
            // 使用Bootstrap模态框
            const bsModal = new bootstrap.Modal(modal, {
                backdrop: 'static',
                keyboard: true
            });
            bsModal.show();
            
            // 模态框显示后的动画效果
            modal.addEventListener('shown.bs.modal', function() {
                // 3D翻转淡入动画
                modal.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                modal.style.opacity = '1';
                modal.style.transform = 'scale(1) rotateX(0deg)';
                
                // 聚焦并添加输入框动画
                setTimeout(() => {
                    orderInput.focus();
                    orderInput.select();
                    orderInput.style.transform = 'scale(1.05)';
                    orderInput.style.boxShadow = '0 0 25px rgba(245, 158, 11, 0.4)';
                    orderInput.style.transition = 'all 0.3s ease';
                    setTimeout(() => {
                        orderInput.style.transform = 'scale(1)';
                        orderInput.style.boxShadow = '';
                    }, 400);
                }, 200);
            }, { once: true });
        }
    };

    // 保存备注数据
    function saveRemarksData() {
        const deviceId = document.getElementById('device_id').value;
        const remarks = document.getElementById('remarks').value;

        if (!deviceId) {
            showToast('设备ID无效', 'error');
            return;
        }

        // 长度限制
        if (remarks.length > 200) {
            showToast('备注长度不能超过200个字符', 'error');
            return;
        }

        // 显示加载状态
        const saveButton = document.getElementById('saveRemarks');
        const originalText = saveButton.innerHTML;
        saveButton.classList.add('loading');
        saveButton.style.transform = 'scale(0.95)';
        saveButton.innerHTML = '<i class="bi bi-arrow-clockwise spin me-2"></i>保存中...';
        saveButton.disabled = true;
        
        // 添加脉冲动画
        setTimeout(() => {
            saveButton.style.transform = 'scale(1)';
            saveButton.style.boxShadow = '0 0 20px rgba(102, 126, 234, 0.4)';
        }, 100);

        // 发送API请求
        fetch('api/update_device.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                device_id: deviceId,
                field: 'remarks',
                value: remarks.trim()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('备注更新成功', 'success');
                
                // 更新UI
                updateDeviceFieldUI(deviceId, 'remarks', remarks.trim() || '点击添加备注');
                
                // 关闭模态框
                const modal = bootstrap.Modal.getInstance(document.getElementById('remarksModal'));
                if (modal) {
                    modal.hide();
                }
                
                // 触发智能刷新
                if (typeof triggerSmartRefresh === 'function') {
                    triggerSmartRefresh();
                }
            } else {
                showToast(data.message || '更新失败', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('网络错误，请稍后重试', 'error');
        })
        .finally(() => {
            // 恢复按钮状态
            saveButton.classList.remove('loading');
            saveButton.style.transition = 'all 0.3s ease';
            saveButton.style.transform = 'scale(0.95)';
            saveButton.style.boxShadow = '';
            setTimeout(() => {
                saveButton.style.transform = '';
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }, 150);
        });
    }

    // 保存时间数据
    function saveCreatedTimeData() {
        const deviceId = document.getElementById('time_device_id').value;
        const createdAt = document.getElementById('created_at').value;

        if (!deviceId) {
            showToast('设备ID无效', 'error');
            return;
        }

        if (!createdAt) {
            showToast('请选择时间', 'error');
            return;
        }

        // 转换为标准格式
        const formattedTime = new Date(createdAt).toISOString().slice(0, 19).replace('T', ' ');

        // 显示加载状态
        const saveButton = document.getElementById('saveCreatedTime');
        const originalText = saveButton.innerHTML;
        saveButton.classList.add('loading');
        saveButton.style.transform = 'scale(0.95)';
        saveButton.innerHTML = '<i class="bi bi-arrow-clockwise spin me-2"></i>保存中...';
        saveButton.disabled = true;
        
        // 添加脉冲动画
        setTimeout(() => {
            saveButton.style.transform = 'scale(1)';
            saveButton.style.boxShadow = '0 0 20px rgba(34, 197, 94, 0.4)';
        }, 100);

        // 发送API请求
        fetch('api/update_device.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                device_id: deviceId,
                field: 'created_at',
                value: formattedTime
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('创建时间更新成功', 'success');
                
                // 更新UI
                updateDeviceFieldUI(deviceId, 'created_time', formattedTime);
                
                // 关闭模态框
                const modal = bootstrap.Modal.getInstance(document.getElementById('createdTimeModal'));
                if (modal) {
                    modal.hide();
                }
                
                // 触发智能刷新
                if (typeof triggerSmartRefresh === 'function') {
                    triggerSmartRefresh();
                }
            } else {
                showToast(data.message || '更新失败', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('网络错误，请稍后重试', 'error');
        })
        .finally(() => {
            // 恢复按钮状态
            saveButton.classList.remove('loading');
            saveButton.style.transition = 'all 0.3s ease';
            saveButton.style.transform = 'scale(0.95)';
            saveButton.style.boxShadow = '';
            setTimeout(() => {
                saveButton.style.transform = '';
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }, 150);
        });
    }

    // 保存编号数据
    function saveOrderNumberData() {
        const deviceId = document.getElementById('order_device_id').value;
        const orderNumber = document.getElementById('order_number').value;

        if (!deviceId) {
            showToast('设备ID无效', 'error');
            return;
        }

        const orderNum = parseInt(orderNumber);
        if (isNaN(orderNum) || orderNum < 0 || orderNum > 9999) {
            showToast('请输入有效的序号（0-9999）', 'error');
            return;
        }

        // 显示加载状态
        const saveButton = document.getElementById('saveOrderNumber');
        const originalText = saveButton.innerHTML;
        saveButton.classList.add('loading');
        saveButton.style.transform = 'scale(0.95)';
        saveButton.innerHTML = '<i class="bi bi-arrow-clockwise spin me-2"></i>保存中...';
        saveButton.disabled = true;
        
        // 添加脉冲动画
        setTimeout(() => {
            saveButton.style.transform = 'scale(1)';
            saveButton.style.boxShadow = '0 0 20px rgba(245, 158, 11, 0.4)';
        }, 100);

        // 发送API请求
        fetch('api/update_device.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                device_id: deviceId,
                field: 'order_number',
                value: orderNum
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('序号更新成功', 'success');
                
                // 更新UI
                updateDeviceFieldUI(deviceId, 'order_number', orderNum);
                
                // 关闭模态框
                const modal = bootstrap.Modal.getInstance(document.getElementById('orderNumberModal'));
                if (modal) {
                    modal.hide();
                }
                
                // 触发智能刷新
                if (typeof triggerSmartRefresh === 'function') {
                    triggerSmartRefresh();
                }
            } else {
                showToast(data.message || '更新失败', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('网络错误，请稍后重试', 'error');
        })
        .finally(() => {
            // 恢复按钮状态
            saveButton.classList.remove('loading');
            saveButton.innerHTML = originalText;
            saveButton.disabled = false;
        });
    }

    // 更新设备字段UI
    function updateDeviceFieldUI(deviceId, field, value) {
        const element = document.querySelector(`[data-device-id="${deviceId}"][data-field="${field}"]`);
        if (element) {
            element.textContent = value;
            
            // 更新onclick属性
            if (field === 'remarks') {
                element.setAttribute('onclick', `showRemarksModal('${deviceId}', '${value.replace(/'/g, "\\'")}')`); 
            } else if (field === 'created_time') {
                element.setAttribute('onclick', `showCreatedTimeModal('${deviceId}', '${value}')`); 
            } else if (field === 'order_number') {
                element.setAttribute('onclick', `showOrderNumberModal('${deviceId}', '${value}')`); 
            }
        }
    }

    // 添加旋转动画样式
    const style = document.createElement('style');
    style.textContent = `
        .spin {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

})();