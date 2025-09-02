/**
 * 智能刷新系统
 * 优化的自动刷新机制，避免在用户活动期间刷新
 */

class SmartRefreshSystem {
    constructor() {
        this.refreshInterval = 30000; // 30秒刷新间隔
        this.userActivityTimeout = 10000; // 用户活动后10秒内不刷新
        this.lastUserActivity = Date.now();
        this.refreshTimer = null;
        this.isPageHidden = false;
        this.hasModalOpen = false;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.startRefreshTimer();
    }
    
    bindEvents() {
        // 监听用户活动
        const userActivityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        userActivityEvents.forEach(event => {
            document.addEventListener(event, () => {
                this.updateUserActivity();
            }, { passive: true });
        });
        
        // 监听页面可见性变化
        document.addEventListener('visibilitychange', () => {
            this.isPageHidden = document.hidden;
            if (!this.isPageHidden) {
                // 页面重新可见时，重置刷新计时器
                this.resetRefreshTimer();
            }
        });
        
        // 监听模态框状态
        this.observeModalState();
        
        // 监听窗口焦点
        window.addEventListener('focus', () => {
            this.resetRefreshTimer();
        });
        
        window.addEventListener('blur', () => {
            // 窗口失去焦点时可以继续刷新，但降低频率
            this.updateUserActivity();
        });
    }
    
    updateUserActivity() {
        this.lastUserActivity = Date.now();
    }
    
    observeModalState() {
        // 监听模态框的打开和关闭
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    // 检查是否有模态框被添加或移除
                    const modals = document.querySelectorAll('.modal, .cc-date-filter-popup.show, .cc-year-picker-overlay.show');
                    this.hasModalOpen = modals.length > 0;
                }
                
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // 检查类名变化（如模态框显示/隐藏）
                    const target = mutation.target;
                    if (target.classList.contains('modal') || 
                        target.classList.contains('cc-date-filter-popup') ||
                        target.classList.contains('cc-year-picker-overlay')) {
                        const modals = document.querySelectorAll('.modal.show, .cc-date-filter-popup.show, .cc-year-picker-overlay.show');
                        this.hasModalOpen = modals.length > 0;
                    }
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    shouldRefresh() {
        const now = Date.now();
        
        // 检查各种阻止刷新的条件
        if (this.isPageHidden) {
            return false; // 页面隐藏时不刷新
        }
        
        if (this.hasModalOpen) {
            return false; // 有模态框打开时不刷新
        }
        
        if (now - this.lastUserActivity < this.userActivityTimeout) {
            return false; // 用户最近有活动时不刷新
        }
        
        return true;
    }
    
    performRefresh() {
        if (!this.shouldRefresh()) {
            // 如果不应该刷新，延迟5秒后再检查
            this.scheduleNextRefresh(5000);
            return;
        }
        
        // 执行刷新
        try {
            window.location.reload();
        } catch (error) {
            console.error('Smart refresh error:', error);
            // 如果刷新失败，重新安排下次刷新
            this.scheduleNextRefresh();
        }
    }
    
    startRefreshTimer() {
        this.refreshTimer = setTimeout(() => {
            this.performRefresh();
        }, this.refreshInterval);
    }
    
    resetRefreshTimer() {
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
        }
        this.startRefreshTimer();
    }
    
    scheduleNextRefresh(delay = null) {
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
        }
        
        const nextDelay = delay || this.refreshInterval;
        this.refreshTimer = setTimeout(() => {
            this.performRefresh();
        }, nextDelay);
    }
    
    // 手动触发刷新（用于数据更新后）
    triggerRefresh(delay = 1000) {
        setTimeout(() => {
            if (this.shouldRefresh()) {
                window.location.reload();
            }
        }, delay);
    }
    
    // 暂停自动刷新
    pause() {
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
            this.refreshTimer = null;
        }
    }
    
    // 恢复自动刷新
    resume() {
        if (!this.refreshTimer) {
            this.startRefreshTimer();
        }
    }
    
    // 设置刷新间隔
    setRefreshInterval(interval) {
        this.refreshInterval = interval;
        this.resetRefreshTimer();
    }
}

// 全局实例
let smartRefreshSystem;

// 全局函数
function triggerSmartRefresh(delay = 1000) {
    if (smartRefreshSystem) {
        smartRefreshSystem.triggerRefresh(delay);
    }
}

function pauseSmartRefresh() {
    if (smartRefreshSystem) {
        smartRefreshSystem.pause();
    }
}

function resumeSmartRefresh() {
    if (smartRefreshSystem) {
        smartRefreshSystem.resume();
    }
}

function setSmartRefreshInterval(interval) {
    if (smartRefreshSystem) {
        smartRefreshSystem.setRefreshInterval(interval);
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    // 只在设备页面启用智能刷新
    if (document.querySelector('.cc-devices-section') || 
        document.querySelector('.cc-stats-grid')) {
        smartRefreshSystem = new SmartRefreshSystem();
    }
});

// 页面卸载时清理
window.addEventListener('beforeunload', function() {
    if (smartRefreshSystem) {
        smartRefreshSystem.pause();
    }
});