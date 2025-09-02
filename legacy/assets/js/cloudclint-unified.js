/**
 * CloudClint 统一JavaScript文件
 * 整合所有JS资源，消除重复代码
 * Version: 3.0 - 优化版
 */

/* ======================
   全局变量和配置
   ====================== */
window.CloudClint = window.CloudClint || {
  config: {
    toastDelay: 5000,
    modalFadeDelay: 300,
    scrollRestoreDelay: 100
  },
  utils: {},
  ui: {},
  device: {},
  initialized: false
};

/* ======================
   工具函数模块
   ====================== */
CloudClint.utils = {
  /**
   * 生成唯一请求ID
   */
  generateRequestId: function() {
    return 'req_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  },

  /**
   * XSS防护 - HTML转义
   */
  escapeHtml: function(text) {
    if (typeof text !== 'string') return text;
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#x27;');
  },

  /**
   * 防抖函数
   */
  debounce: function(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
      const context = this;
      const args = arguments;
      const later = function() {
        timeout = null;
        if (!immediate) func.apply(context, args);
      };
      const callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(context, args);
    };
  },

  /**
   * 节流函数
   */
  throttle: function(func, limit) {
    let lastFunc;
    let lastRan;
    return function() {
      const context = this;
      const args = arguments;
      if (!lastRan) {
        func.apply(context, args);
        lastRan = Date.now();
      } else {
        clearTimeout(lastFunc);
        lastFunc = setTimeout(function() {
          if ((Date.now() - lastRan) >= limit) {
            func.apply(context, args);
            lastRan = Date.now();
          }
        }, limit - (Date.now() - lastRan));
      }
    };
  },

  /**
   * 保存滚动位置
   */
  saveScrollPosition: function() {
    sessionStorage.setItem('scrollPosition', window.pageYOffset);
  },

  /**
   * 恢复滚动位置
   */
  restoreScrollPosition: function() {
    setTimeout(function() {
      const scrollPos = sessionStorage.getItem('scrollPosition');
      if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos, 10));
        sessionStorage.removeItem('scrollPosition');
      }
    }, CloudClint.config.scrollRestoreDelay);
  }
};

/* ======================
   UI组件模块
   ====================== */
CloudClint.ui = {
  /**
   * 统一的Toast通知系统
   */
  showToast: function(message, type = 'info') {
    try {
      // 确保Toast容器存在
      let toastContainer = document.getElementById('toast-container');
      if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
      }

      // 创建Toast元素
      const toastId = 'toast-' + Date.now();
      const toastEl = document.createElement('div');
      toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
      toastEl.id = toastId;
      toastEl.setAttribute('role', 'alert');
      toastEl.setAttribute('aria-live', 'assertive');
      toastEl.setAttribute('aria-atomic', 'true');

      // 安全的消息内容
      const safeMessage = CloudClint.utils.escapeHtml(message);
      
      toastEl.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">${safeMessage}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                  data-bs-dismiss="toast" aria-label="关闭"></button>
        </div>
      `;

      toastContainer.appendChild(toastEl);

      // 初始化并显示Toast
      if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const toast = new bootstrap.Toast(toastEl, {
          animation: true,
          autohide: true,
          delay: CloudClint.config.toastDelay
        });
        toast.show();

        // 自动清理
        toastEl.addEventListener('hidden.bs.toast', function() {
          if (toastEl.parentNode) {
            toastEl.parentNode.removeChild(toastEl);
          }
        });
      }

    } catch (error) {
      // 备用显示方式
      console.error('Toast显示失败:', error);
      if (typeof alert !== 'undefined') {
        alert(message);
      }
    }
  },

  /**
   * 统一的模态框显示函数
   */
  showModal: function(modalId, deviceId, data) {
    try {
      const modal = document.getElementById(modalId);
      if (!modal) {
        CloudClint.ui.showToast(`模态框 ${modalId} 未找到`, 'danger');
        return false;
      }

      // 填充数据
      CloudClint.ui.fillModalData(modalId, deviceId, data);

      // 检查Bootstrap
      if (typeof bootstrap === 'undefined') {
        CloudClint.ui.showToast('Bootstrap未加载，无法显示模态框', 'danger');
        return false;
      }

      // 初始化模态框
      const modalInstance = new bootstrap.Modal(modal, {
        backdrop: true,
        keyboard: true,
        focus: true
      });

      // 监听事件
      CloudClint.ui.setupModalEvents(modal, modalInstance);
      
      modalInstance.show();
      return true;

    } catch (error) {
      CloudClint.ui.showToast('模态框显示错误: ' + error.message, 'danger');
      return false;
    }
  },

  /**
   * 填充模态框数据
   */
  fillModalData: function(modalId, deviceId, data) {
    if (modalId === 'remarksModal') {
      const deviceIdInput = document.getElementById('device_id');
      const remarksInput = document.getElementById('remarks');
      if (deviceIdInput) deviceIdInput.value = deviceId || '';
      if (remarksInput) remarksInput.value = data || '';

    } else if (modalId === 'createdTimeModal') {
      const deviceIdInput = document.getElementById('time_device_id');
      const createdAtInput = document.getElementById('created_at');
      if (deviceIdInput) deviceIdInput.value = deviceId || '';
      if (createdAtInput) {
        let formattedDate = CloudClint.utils.formatDateForInput(data);
        createdAtInput.value = formattedDate;
      }

    } else if (modalId === 'orderNumberModal') {
      const deviceIdInput = document.getElementById('order_device_id');
      const orderNumberInput = document.getElementById('order_number');
      if (deviceIdInput) deviceIdInput.value = deviceId || '';
      if (orderNumberInput) orderNumberInput.value = data || 0;
    }
  },

  /**
   * 设置模态框事件
   */
  setupModalEvents: function(modal, modalInstance) {
    // 显示时调整位置
    modal.addEventListener('shown.bs.modal', function() {
      CloudClint.ui.adjustModalPosition(modal);
    });

    // 窗口大小变化时重新调整
    const resizeHandler = CloudClint.utils.throttle(function() {
      if (modal.classList.contains('show')) {
        CloudClint.ui.adjustModalPosition(modal);
      }
    }, 250);

    window.addEventListener('resize', resizeHandler);

    // 模态框关闭时清理事件
    modal.addEventListener('hidden.bs.modal', function() {
      window.removeEventListener('resize', resizeHandler);
    });
  },

  /**
   * 调整模态框位置
   */
  adjustModalPosition: function(modal) {
    try {
      if (!modal) return;

      const modalDialog = modal.querySelector('.modal-dialog');
      const modalContent = modal.querySelector('.modal-content');
      
      if (!modalDialog) return;

      const windowHeight = window.innerHeight;
      const modalHeight = modalDialog.offsetHeight;
      
      // 重置样式
      modalDialog.style.marginTop = '';
      modalDialog.style.marginBottom = '';
      
      // 计算最佳位置
      const optimalTop = Math.max(20, Math.min((windowHeight - modalHeight) / 2, windowHeight * 0.4));
      modalDialog.style.marginTop = optimalTop + 'px';
      modalDialog.style.marginBottom = '20px';

      // 限制内容高度
      if (modalContent) {
        modalContent.style.maxHeight = (windowHeight - 80) + 'px';
        modalContent.style.overflowY = 'auto';
      }

    } catch (error) {
      console.warn('模态框位置调整失败:', error);
    }
  },

  /**
   * 清理模态框背景
   */
  cleanupModalBackdrop: function() {
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
};

/* ======================
   设备管理模块
   ====================== */
CloudClint.device = {
  /**
   * 删除设备
   */
  deleteDevice: function(deviceId, deviceName) {
    // 输入验证
    if (!deviceId || typeof deviceId !== 'string') {
      CloudClint.ui.showToast('设备ID无效', 'error');
      return false;
    }

    if (!deviceName || typeof deviceName !== 'string') {
      deviceName = '未知设备';
    }

    // 安全转义设备名称
    const safeDeviceName = CloudClint.utils.escapeHtml(deviceName);

    // 确认对话框
    if (!confirm(`确定要删除设备 "${safeDeviceName}" 吗？\n\n此操作不可撤销！`)) {
      return false;
    }

    // 显示删除进度提示
    CloudClint.ui.showToast('正在删除设备...', 'info');

    // 创建表单并提交
    CloudClint.device.submitDeleteForm(deviceId);
    return true;
  },

  /**
   * 提交删除表单
   */
  submitDeleteForm: function(deviceId) {
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

    form.submit();
  },

  /**
   * 保存设备备注
   */
  saveRemarks: function(deviceId, remarks, button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> 保存中...';

    const data = {
      device_id: deviceId,
      remarks: remarks
    };

    CloudClint.device.apiRequest('api/update_remarks.php', data)
      .then(function(response) {
        if (response.success) {
          CloudClint.ui.hideModal('remarksModal');
          CloudClint.ui.showToast('备注保存成功', 'success');
          CloudClint.device.refreshPage();
        } else {
          CloudClint.ui.showToast('保存失败: ' + (response.message || response.error || '未知错误'), 'danger');
        }
      })
      .catch(function(error) {
        CloudClint.ui.showToast('保存失败: ' + error.message, 'danger');
      })
      .finally(function() {
        button.disabled = false;
        button.innerHTML = originalText;
      });
  },

  /**
   * 保存创建时间
   */
  saveCreatedTime: function(deviceId, createdTime, button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> 保存中...';

    // 格式化时间
    let formattedTime = CloudClint.utils.formatTimeForAPI(createdTime);

    if (!deviceId || !formattedTime) {
      CloudClint.ui.showToast('请输入有效的设备ID和时间', 'danger');
      button.disabled = false;
      button.innerHTML = originalText;
      return;
    }

    const data = {
      device_id: deviceId,
      created_at: formattedTime
    };

    CloudClint.device.apiRequest('api/update_created_time.php', data)
      .then(function(response) {
        if (response.success) {
          CloudClint.ui.hideModal('createdTimeModal');
          CloudClint.ui.showToast('创建时间保存成功', 'success');
          CloudClint.device.refreshPage();
        } else {
          CloudClint.ui.showToast('保存失败: ' + (response.message || response.error || '未知错误'), 'danger');
        }
      })
      .catch(function(error) {
        CloudClint.ui.showToast('保存失败: ' + error.message, 'danger');
      })
      .finally(function() {
        button.disabled = false;
        button.innerHTML = originalText;
      });
  },

  /**
   * 保存编号
   */
  saveOrderNumber: function(deviceId, orderNumber, button) {
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
    .then(function(response) {
      if (!response.ok) {
        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
      }
      return response.json();
    })
    .then(function(data) {
      if (data.success) {
        CloudClint.ui.hideModal('orderNumberModal');
        CloudClint.ui.showToast('编号保存成功', 'success');
        CloudClint.device.refreshPage();
      } else {
        CloudClint.ui.showToast('保存失败: ' + (data.message || data.error || '未知错误'), 'danger');
      }
    })
    .catch(function(error) {
      CloudClint.ui.showToast('保存失败: ' + error.message, 'danger');
    })
    .finally(function() {
      button.disabled = false;
      button.innerHTML = originalText;
    });
  },

  /**
   * 统一的API请求函数
   */
  apiRequest: function(url, data) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8'
      },
      body: JSON.stringify(data)
    })
    .then(function(response) {
      if (!response.ok) {
        return response.text().then(function(text) {
          throw new Error('API调用失败: ' + response.status + ' - ' + text);
        });
      }
      return response.json();
    });
  },

  /**
   * 刷新页面并保持滚动位置
   */
  refreshPage: function() {
    CloudClint.utils.saveScrollPosition();
    window.location.reload();
  }
};

/* ======================
   日期时间工具函数
   ====================== */
CloudClint.utils.formatDateForInput = function(dateString) {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
  } catch (e) {
    return '';
  }
};

CloudClint.utils.formatTimeForAPI = function(timeString) {
  if (!timeString) return '';
  
  let formattedTime = timeString;
  
  // 替换T为空格
  if (timeString.includes('T')) {
    formattedTime = timeString.replace('T', ' ');
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
  
  return formattedTime;
};

/* ======================
   模态框辅助函数
   ====================== */
CloudClint.ui.hideModal = function(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    const modalInstance = bootstrap.Modal.getInstance(modal);
    if (modalInstance) {
      modalInstance.hide();
    }
  }
};

/* ======================
   全局事件绑定
   ====================== */
CloudClint.init = function() {
  if (CloudClint.initialized) return;

  // 恢复滚动位置
  CloudClint.utils.restoreScrollPosition();

  // 绑定全局事件
  CloudClint.bindGlobalEvents();

  // 自动关闭提示消息
  CloudClint.setupAutoCloseAlerts();

  // 修复按钮事件
  CloudClint.fixButtonEvents();

  CloudClint.initialized = true;
};

CloudClint.bindGlobalEvents = function() {
  // 处理认证错误
  window.addEventListener('beforeunload', function() {
    CloudClint.utils.saveScrollPosition();
  });

  // ESC键关闭模态框
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      CloudClint.ui.cleanupModalBackdrop();
    }
  });
};

CloudClint.setupAutoCloseAlerts = function() {
  const alerts = document.querySelectorAll('.alert');
  
  alerts.forEach(function(alert) {
    if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
      const bsAlert = new bootstrap.Alert(alert);
      
      let delay = 3000; // 默认3秒
      if (alert.classList.contains('alert-danger')) {
        delay = 5000;
      } else if (alert.classList.contains('alert-warning')) {
        delay = 4000;
      }
      
      setTimeout(function() {
        bsAlert.close();
      }, delay);
    }
  });
};

CloudClint.fixButtonEvents = function() {
  // 修复设备备注编辑按钮
  document.querySelectorAll('.remarks-edit').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const deviceId = this.getAttribute('data-device-id');
      const remarks = this.getAttribute('data-remarks');
      CloudClint.ui.showModal('remarksModal', deviceId, remarks);
    });
  });

  // 修复设备时间编辑按钮
  document.querySelectorAll('.edit-created-time').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const deviceId = this.getAttribute('data-device-id');
      const createdAt = this.getAttribute('data-created-at');
      CloudClint.ui.showModal('createdTimeModal', deviceId, createdAt);
    });
  });

  // 修复设备编号编辑按钮
  document.querySelectorAll('.edit-order-number').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const deviceId = this.getAttribute('data-device-id');
      const orderNumber = this.getAttribute('data-order-number');
      CloudClint.ui.showModal('orderNumberModal', deviceId, orderNumber);
    });
  });
};

/* ======================
   向后兼容的全局函数
   ====================== */
window.showToast = CloudClint.ui.showToast;
window.showModal = CloudClint.ui.showModal;
window.deleteDevice = CloudClint.device.deleteDevice;
window.saveRemarks = CloudClint.device.saveRemarks;
window.saveCreatedTime = CloudClint.device.saveCreatedTime;
window.saveOrderNumber = CloudClint.device.saveOrderNumber;

// 编辑函数的全局暴露
window.editRemarks = function(deviceId, remarks) {
  CloudClint.ui.showModal('remarksModal', deviceId, remarks);
};

window.editCreatedTime = function(deviceId, createdAt) {
  CloudClint.ui.showModal('createdTimeModal', deviceId, createdAt);
};

window.editOrderNumber = function(deviceId, orderNumber) {
  CloudClint.ui.showModal('orderNumberModal', deviceId, orderNumber);
};

/* ======================
   自动初始化
   ====================== */
document.addEventListener('DOMContentLoaded', function() {
  CloudClint.init();
});

// 如果DOM已经加载完成，立即初始化
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', CloudClint.init);
} else {
  CloudClint.init();
}