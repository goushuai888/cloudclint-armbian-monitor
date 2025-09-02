/**
 * 日期筛选器控制器
 * 负责处理时间筛选相关的所有交互
 */

class DateFilterController {
    constructor() {
        this.currentYear = new Date().getFullYear();
        this.selectedYear = null;
        this.selectedMonth = null;
        this.isVisible = false;
        this.popup = null;
        this.trigger = null;
        
        this.init();
    }
    
    init() {
        this.popup = document.getElementById('dateFilterPopup');
        this.trigger = document.getElementById('dateFilterTrigger');
        
        if (!this.popup || !this.trigger) {
            console.warn('Date filter elements not found');
            return;
        }
        
        this.bindEvents();
        this.updateYearDisplay();
        this.updateMonthButtons();
        this.updateQuickButtons();
    }
    
    bindEvents() {
        // 点击外部关闭
        document.addEventListener('click', (e) => {
            if (this.isVisible && !this.popup.contains(e.target) && !this.trigger.contains(e.target)) {
                this.hide();
            }
        });
        
        // 滚动时调整位置
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            if (this.isVisible) {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    this.adjustPosition();
                }, 10);
            }
        }, { passive: true });
        
        // 窗口大小改变时调整位置
        window.addEventListener('resize', () => {
            if (this.isVisible) {
                this.adjustPosition();
            }
        });
    }
    
    toggle() {
        if (this.isVisible) {
            this.hide();
        } else {
            this.show();
        }
    }
    
    show() {
        if (this.isVisible) return;
        
        this.popup.style.display = 'block';
        this.adjustPosition();
        
        // 添加动画
        requestAnimationFrame(() => {
            this.popup.classList.add('show');
            this.trigger.classList.add('active');
        });
        
        this.isVisible = true;
    }
    
    hide() {
        if (!this.isVisible) return;
        
        this.popup.classList.remove('show');
        this.trigger.classList.remove('active');
        
        setTimeout(() => {
            this.popup.style.display = 'none';
        }, 200);
        
        this.isVisible = false;
    }
    
    adjustPosition() {
        if (!this.popup || !this.trigger) return;
        
        const triggerRect = this.trigger.getBoundingClientRect();
        const popupRect = this.popup.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // 重置位置
        this.popup.style.position = 'fixed';
        this.popup.style.zIndex = '2147483647';
        this.popup.style.top = '';
        this.popup.style.left = '';
        this.popup.style.right = '';
        this.popup.style.bottom = '';
        
        // 计算最佳位置
        let top = triggerRect.bottom + 8;
        let left = triggerRect.left;
        
        // 检查右边界
        if (left + popupRect.width > viewportWidth - 20) {
            left = viewportWidth - popupRect.width - 20;
        }
        
        // 检查左边界
        if (left < 20) {
            left = 20;
        }
        
        // 检查下边界
        if (top + popupRect.height > viewportHeight - 20) {
            top = triggerRect.top - popupRect.height - 8;
            
            // 如果上方也放不下，使用固定位置
            if (top < 20) {
                top = 20;
                this.popup.style.maxHeight = (viewportHeight - 40) + 'px';
                this.popup.style.overflowY = 'auto';
            }
        }
        
        this.popup.style.top = top + 'px';
        this.popup.style.left = left + 'px';
    }
    
    changeYear(delta) {
        this.currentYear += delta;
        this.selectedYear = this.currentYear; // 自动设置选中的年份
        this.updateYearDisplay();
        this.updateQuickButtons();
        this.updateMonthButtons(); // 更新月份按钮状态
    }
    
    setYear(year) {
        this.currentYear = year;
        this.selectedYear = year;
        this.updateYearDisplay();
        this.updateQuickButtons();
    }
    
    selectMonth(month) {
        this.selectedMonth = month;
        this.updateMonthButtons();
        
        // 触觉反馈
        if (navigator.vibrate) {
            navigator.vibrate(10);
        }
    }
    
    updateYearDisplay() {
        const yearDisplay = document.getElementById('currentYear');
        if (yearDisplay) {
            yearDisplay.textContent = this.currentYear;
        }
    }
    
    updateMonthButtons() {
        const monthButtons = document.querySelectorAll('.cc-month-btn');
        monthButtons.forEach(btn => {
            const month = parseInt(btn.dataset.month);
            btn.classList.toggle('selected', month === this.selectedMonth);
        });
    }
    
    updateQuickButtons() {
        const quickButtons = document.querySelectorAll('.cc-quick-year-btn');
        const currentYear = new Date().getFullYear();
        
        quickButtons.forEach(btn => {
            btn.classList.remove('active');
            const btnText = btn.textContent;
            if ((btnText === '今年' && this.currentYear === currentYear) ||
                (btnText === '去年' && this.currentYear === currentYear - 1)) {
                btn.classList.add('active');
            }
        });
    }
    
    // 年份选择器相关方法已移除
    
    applyFilter() {
        const params = new URLSearchParams();
        
        if (this.selectedYear) {
            params.set('year', this.selectedYear);
        }
        
        if (this.selectedMonth) {
            params.set('month', this.selectedMonth);
        }
        
        const url = 'index.php' + (params.toString() ? '?' + params.toString() : '');
        window.location.href = url;
    }
    
    clearFilter() {
        this.selectedYear = null;
        this.selectedMonth = null;
        this.updateMonthButtons();
        this.updateQuickButtons();
    }
    
    clearAndRedirect() {
        window.location.href = 'index.php';
    }
}

// 全局实例
let dateFilterController;

// 全局函数（保持向后兼容）
function toggleDateFilter() {
    if (dateFilterController) {
        dateFilterController.toggle();
    }
}

function hideDateFilter() {
    if (dateFilterController) {
        dateFilterController.hide();
    }
}

function changeYear(delta) {
    if (dateFilterController) {
        dateFilterController.changeYear(delta);
    }
}

function setYear(year) {
    if (dateFilterController) {
        dateFilterController.setYear(year);
    }
}

function selectMonth(month) {
    if (dateFilterController) {
        dateFilterController.selectMonth(month);
    }
}

// 年份选择器全局函数已移除

function applyDateFilter() {
    if (dateFilterController) {
        dateFilterController.applyFilter();
    }
}

function clearDateFilter() {
    if (dateFilterController) {
        dateFilterController.clearFilter();
    }
}

function clearFilters() {
    if (dateFilterController) {
        dateFilterController.clearAndRedirect();
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    dateFilterController = new DateFilterController();
    
    // 从URL参数初始化状态
    const urlParams = new URLSearchParams(window.location.search);
    const year = urlParams.get('year');
    const month = urlParams.get('month');
    
    if (year) {
        dateFilterController.selectedYear = parseInt(year);
        dateFilterController.currentYear = parseInt(year);
        dateFilterController.updateYearDisplay();
        dateFilterController.updateQuickButtons();
    } else {
        // 默认选择当前年份
        dateFilterController.selectedYear = dateFilterController.currentYear;
        dateFilterController.updateYearDisplay();
        dateFilterController.updateQuickButtons();
    }
    
    if (month) {
        dateFilterController.selectedMonth = parseInt(month);
        dateFilterController.updateMonthButtons();
    }
});