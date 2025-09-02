/**
 * 设备交互功能
 * 处理设备卡片的动态行为和用户交互
 */

(function() {
    'use strict';

    // 等待DOM加载完成
    document.addEventListener('DOMContentLoaded', function() {
        initializeDeviceInteractions();
    });

    function initializeDeviceInteractions() {
        // 初始化内联编辑功能
        initInlineEdit();
        
        // 初始化设备卡片悬停效果
        initCardHoverEffects();
        
        // 初始化状态指示器动画
        initStatusAnimations();
        
        // 初始化响应式布局调整
        initResponsiveAdjustments();
    }

    // 内联编辑功能
    function initInlineEdit() {
        const editButtons = document.querySelectorAll('.cc-inline-edit-btn');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const deviceCard = this.closest('.cc-device-card');
                const deviceId = deviceCard.dataset.deviceId;
                
                if (deviceId) {
                    // 触发编辑模态框或内联编辑
                    triggerDeviceEdit(deviceId);
                }
            });
        });
    }

    // 设备卡片悬停效果
    function initCardHoverEffects() {
        const deviceCards = document.querySelectorAll('.cc-device-card');
        
        deviceCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
            });
        });
    }

    // 状态指示器动画
    function initStatusAnimations() {
        const onlineIndicators = document.querySelectorAll('.cc-status-indicator.online .cc-status-dot');
        
        onlineIndicators.forEach(dot => {
            // 添加脉冲动画
            dot.style.animation = 'pulse-online 2s infinite';
        });
    }

    // 响应式布局调整
    function initResponsiveAdjustments() {
        let resizeTimer;
        
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                adjustDeviceLayout();
            }, 250);
        });
        
        // 初始调整
        adjustDeviceLayout();
    }

    function adjustDeviceLayout() {
        const deviceGrid = document.querySelector('.cc-devices-grid');
        if (!deviceGrid) return;
        
        const containerWidth = deviceGrid.offsetWidth;
        const cards = deviceGrid.querySelectorAll('.cc-device-card');
        
        // 根据容器宽度调整卡片样式
        if (containerWidth < 480) {
            // 移动端：单列布局
            cards.forEach(card => {
                card.style.maxWidth = '100%';
            });
        } else if (containerWidth < 768) {
            // 小平板：可能2列
            cards.forEach(card => {
                card.style.maxWidth = 'calc(50% - 0.5rem)';
            });
        } else {
            // 桌面端：自适应
            cards.forEach(card => {
                card.style.maxWidth = '';
            });
        }
    }

    // 触发设备编辑
    function triggerDeviceEdit(deviceId) {
        // 检查是否存在编辑函数
        if (typeof window.editDevice === 'function') {
            window.editDevice(deviceId);
        } else if (typeof window.showEditModal === 'function') {
            window.showEditModal(deviceId);
        } else {
            // 可以在这里添加默认的编辑行为
        }
    }

    // 工具函数：防抖
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

    // 工具函数：节流
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

    // 导出到全局作用域（如果需要）
    window.DeviceInteractions = {
        init: initializeDeviceInteractions,
        adjustLayout: adjustDeviceLayout
    };

})();