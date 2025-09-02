    </main>

    <!-- 增强版页脚 -->
    <footer class="cc-footer">
        <div class="cc-footer-content">
            <div class="cc-footer-section">
                <div class="cc-footer-text">
                    &copy; <?php echo date('Y'); ?> CloudClint. 专业的设备监控解决方案
                </div>
                <div class="cc-footer-meta">
                    <span class="cc-footer-version">
                        <i class="bi bi-info-circle"></i>
                        版本 2.0.0 Pro
                    </span>
                    <span class="cc-footer-separator">•</span>
                    <span class="cc-footer-uptime" id="ccFooterUptime">
                        <i class="bi bi-clock"></i>
                        运行时间统计中...
                    </span>
                </div>
            </div>
            <div class="cc-footer-links">
                <span class="cc-footer-brand">
                    <i class="bi bi-code-slash"></i>
                    Built with <span class="cc-text-danger">&hearts;</span> by CloudClint Team
                </span>
                <div class="cc-footer-actions">
                    <button class="cc-footer-btn" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="返回顶部">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button class="cc-footer-btn" onclick="toggleTheme()" title="切换主题">
                        <i class="bi bi-moon" id="themeIcon"></i>
                    </button>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- 性能监控脚本 -->
    <script>
    // 页面性能监控
    document.addEventListener('DOMContentLoaded', function() {
        // 显示页面加载时间
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            
            // 如果有调试模式，显示性能信息
            if (window.location.search.includes('debug=1')) {
                const perfInfo = document.createElement('div');
                perfInfo.style.cssText = `
                    position: fixed; bottom: 10px; left: 10px; 
                    background: rgba(0,0,0,0.8); color: white; 
                    padding: 8px 12px; border-radius: 6px; 
                    font-size: 12px; z-index: 9999;
                `;
                perfInfo.innerHTML = `加载时间: ${Math.round(loadTime)}ms`;
                document.body.appendChild(perfInfo);
                
                setTimeout(() => perfInfo.remove(), 5000);
            }
        });
        
        // 简单的运行时间显示
        updateFooterUptime();
        setInterval(updateFooterUptime, 30000); // 每30秒更新一次
    });
    
    function updateFooterUptime() {
        const uptimeElement = document.getElementById('ccFooterUptime');
        if (uptimeElement) {
            const now = new Date();
            const uptime = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
            uptimeElement.innerHTML = `<i class="bi bi-clock"></i> 当前时间 ${uptime}`;
        }
    }
    
    // 主题切换功能（简化版）
    function toggleTheme() {
        const body = document.body;
        const themeIcon = document.getElementById('themeIcon');
        
        if (body.classList.contains('dark-theme')) {
            body.classList.remove('dark-theme');
            themeIcon.className = 'bi bi-moon';
            localStorage.setItem('theme', 'light');
        } else {
            body.classList.add('dark-theme');
            themeIcon.className = 'bi bi-sun';
            localStorage.setItem('theme', 'dark');
        }
    }
    
    // 初始化主题
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('theme');
        const themeIcon = document.getElementById('themeIcon');
        
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-theme');
            if (themeIcon) themeIcon.className = 'bi bi-sun';
        }
    });
    </script>

    <!-- Toast 通知容器 -->
    <div id="ccToastContainer" class="position-fixed" style="top: 1rem; right: 1rem; z-index: var(--cc-z-toast);"></div>

    <!-- JavaScript 库 -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- CloudClint UI 交互脚本 -->
    <script>
    /**
     * CloudClint UI 交互系统
     */
    class CloudClintUI {
        constructor() {
            this.init();
        }
        
        init() {
            this.initDropdowns();
            this.initTooltips();
            this.initAnimations();
        }
        
        // 下拉菜单功能 (简化版)
        initDropdowns() {
            // 这个方法现在由header.php中的简单脚本处理
        }
        
        toggleDropdown(dropdown) {
            const isOpen = dropdown.classList.contains('show');
            this.closeAllDropdowns();
            if (!isOpen) {
                dropdown.classList.add('show');
            }
        }
        
        closeAllDropdowns() {
            const openDropdowns = document.querySelectorAll('.cc-dropdown.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
        
        // 工具提示
        initTooltips() {
            document.querySelectorAll('[title]').forEach(element => {
                element.addEventListener('mouseenter', this.showTooltip);
                element.addEventListener('mouseleave', this.hideTooltip);
            });
        }
        
        showTooltip(e) {
            // 简单的工具提示实现
            const tooltip = document.createElement('div');
            tooltip.className = 'cc-tooltip';
            tooltip.textContent = e.target.title;
            tooltip.style.cssText = `
                position: absolute;
                background: var(--cc-gray-900);
                color: white;
                padding: var(--cc-space-2) var(--cc-space-3);
                border-radius: var(--cc-radius);
                font-size: var(--cc-text-xs);
                z-index: var(--cc-z-overlay);
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s;
            `;
            document.body.appendChild(tooltip);
            
            // 移除原title避免浏览器默认提示
            e.target.dataset.originalTitle = e.target.title;
            e.target.removeAttribute('title');
            
            // 定位和显示
            setTimeout(() => {
                const rect = e.target.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.bottom + 8 + 'px';
                tooltip.style.opacity = '1';
            }, 0);
        }
        
        hideTooltip(e) {
            document.querySelectorAll('.cc-tooltip').forEach(tooltip => {
                tooltip.remove();
            });
            if (e.target.dataset.originalTitle) {
                e.target.title = e.target.dataset.originalTitle;
                delete e.target.dataset.originalTitle;
            }
        }
        
        // 动画观察器
        initAnimations() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('cc-animate-fadeIn');
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.cc-device-card, .cc-stats-card').forEach(card => {
                observer.observe(card);
            });
        }
        
        // Toast 通知
        showToast(message, type = 'info', duration = 5000) {
            const container = document.getElementById('ccToastContainer');
            if (!container) return;
            
            const toast = document.createElement('div');
            toast.className = `cc-toast cc-toast-${type} cc-animate-slideIn`;
            
            const icon = this.getToastIcon(type);
            toast.innerHTML = `
                <div class="cc-flex cc-items-center cc-gap-3">
                    <i class="bi bi-${icon}"></i>
                    <span class="cc-flex-1">${message}</span>
                    <button class="cc-btn-ghost cc-btn-sm" onclick="this.parentElement.parentElement.remove()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(toast);
            
            // 自动移除
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                }
            }, duration);
        }
        
        getToastIcon(type) {
            const icons = {
                success: 'check-circle-fill',
                error: 'exclamation-triangle-fill',
                warning: 'exclamation-circle-fill',
                info: 'info-circle-fill'
            };
            return icons[type] || icons.info;
        }
        
        // 加载状态
        showLoading(element) {
            element.disabled = true;
            const originalContent = element.innerHTML;
            element.dataset.originalContent = originalContent;
            element.innerHTML = `
                <span class="cc-spinner cc-animate-spin"></span>
                <span>处理中...</span>
            `;
        }
        
        hideLoading(element) {
            element.disabled = false;
            if (element.dataset.originalContent) {
                element.innerHTML = element.dataset.originalContent;
                delete element.dataset.originalContent;
            }
        }
        
        // 模态对话框管理 - 完全内联样式版本
        showModal(modalHtml) {
            // 移除已存在的模态框
            this.closeModal();
            
            // 创建完全内联样式的模态框
            const modalWrapper = document.createElement('div');
            modalWrapper.id = 'ccCurrentModal';
            modalWrapper.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                background: rgba(0, 0, 0, 0.6) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                z-index: 999999 !important;
                opacity: 0 !important;
                transition: opacity 0.3s ease !important;
                padding: 20px !important;
                box-sizing: border-box !important;
                margin: 0 !important;
            `;
            
            // 插入模态框内容
            modalWrapper.innerHTML = modalHtml;
            
            // 给内容容器添加内联样式
            const content = modalWrapper.querySelector('.cc-modal-content');
            if (content) {
                // 检查是否需要自适应高度（没有设置特殊样式的情况下）
                const hasCustomHeight = content.style.maxHeight || content.getAttribute('style')?.includes('max-height');
                
                content.style.cssText = `
                    background: white !important;
                    border-radius: 1rem !important;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
                    width: 100% !important;
                    max-width: 500px !important;
                    ${hasCustomHeight ? 'max-height: calc(100vh - 40px) !important; overflow-y: auto !important;' : 'max-height: none !important; overflow-y: visible !important;'}
                    transform: scale(0.9) translateY(-20px) !important;
                    transition: transform 0.3s ease !important;
                    position: relative !important;
                    margin: 0 !important;
                `;
            }
            
            // 添加到body
            document.body.appendChild(modalWrapper);
            document.body.style.overflow = 'hidden';
            
            // 绑定事件
            modalWrapper.addEventListener('click', (e) => {
                if (e.target === modalWrapper) {
                    this.closeModal();
                }
            });
            
            const closeBtn = modalWrapper.querySelector('.cc-modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.closeModal();
                });
            }
            
            // ESC键关闭
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    this.closeModal();
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);
            modalWrapper._escHandler = escHandler;
            
            // 显示动画
            setTimeout(() => {
                modalWrapper.style.opacity = '1';
                if (content) {
                    content.style.transform = 'scale(1) translateY(0)';
                }
            }, 50);
            
            return modalWrapper;
        }
        
        closeModal() {
            const modal = document.getElementById('ccCurrentModal');
            if (modal) {
                const content = modal.querySelector('.cc-modal-content');
                modal.style.opacity = '0';
                if (content) {
                    content.style.transform = 'scale(0.9) translateY(-20px)';
                }
                
                document.body.style.overflow = '';
                if (modal._escHandler) {
                    document.removeEventListener('keydown', modal._escHandler);
                }
                
                setTimeout(() => {
                    if (modal && modal.parentElement) {
                        modal.parentElement.removeChild(modal);
                    }
                }, 300);
            }
        }
        
        // 确认对话框
        showConfirmDialog(title, message, submessage = '', onConfirm = null, onCancel = null) {
            const modalHtml = `
                <div class="cc-modal-content">
                    <div class="cc-modal-header" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                        <h3 class="cc-modal-title" style="font-size: 1.25rem; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 0.75rem; margin: 0;">
                            <i class="bi bi-exclamation-triangle"></i>
                            ${title}
                        </h3>
                        <button type="button" class="cc-modal-close" style="background: none; border: none; font-size: 1.25rem; color: #6b7280; cursor: pointer; padding: 0.5rem; border-radius: 0.5rem;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="cc-modal-body" style="padding: 1.5rem;">
                        <div style="text-align: center;">
                            <div style="width: 4rem; height: 4rem; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: #dc2626; font-size: 1.5rem;">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 0.75rem;">${title}</div>
                            <div style="color: #4b5563; line-height: 1.5; margin-bottom: 0.5rem;">${message}</div>
                            ${submessage ? `<div style="color: #6b7280; font-size: 0.875rem;">${submessage}</div>` : ''}
                        </div>
                    </div>
                    <div class="cc-modal-footer" style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" id="ccConfirmCancel" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-x"></i>
                            取消
                        </button>
                        <button type="button" id="ccConfirmOk" style="background: #ef4444; color: white; border: 1px solid #ef4444; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-check"></i>
                            确认
                        </button>
                    </div>
                </div>
            `;
            
            const modal = this.showModal(modalHtml);
            
            // 绑定确认和取消事件
            const confirmBtn = modal.querySelector('#ccConfirmOk');
            const cancelBtn = modal.querySelector('#ccConfirmCancel');
            
            confirmBtn.addEventListener('click', () => {
                this.closeModal();
                if (onConfirm) onConfirm();
            });
            
            cancelBtn.addEventListener('click', () => {
                this.closeModal();
                if (onCancel) onCancel();
            });
            
            return modal;
        }
        
        // 输入对话框
        showInputDialog(title, label, placeholder = '', defaultValue = '', helpText = '', onConfirm = null, onCancel = null) {
            const modalHtml = `
                <div class="cc-modal-content">
                    <div class="cc-modal-header" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                        <h3 class="cc-modal-title" style="font-size: 1.25rem; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 0.75rem; margin: 0;">
                            <i class="bi bi-pencil-square"></i>
                            ${title}
                        </h3>
                        <button type="button" class="cc-modal-close" style="background: none; border: none; font-size: 1.25rem; color: #6b7280; cursor: pointer; padding: 0.5rem; border-radius: 0.5rem;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="cc-modal-body" style="padding: 1.5rem;">
                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">${label}</label>
                            <input type="text" id="ccInputValue" placeholder="${placeholder}" value="${defaultValue}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem; transition: all 0.15s ease; background: white;">
                            ${helpText ? `<div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">${helpText}</div>` : ''}
                        </div>
                    </div>
                    <div class="cc-modal-footer" style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" id="ccInputCancel" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-x"></i>
                            取消
                        </button>
                        <button type="button" id="ccInputOk" style="background: #3b82f6; color: white; border: 1px solid #3b82f6; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-check"></i>
                            确认
                        </button>
                    </div>
                </div>
            `;
            
            const modal = this.showModal(modalHtml);
            const input = modal.querySelector('#ccInputValue');
            const confirmBtn = modal.querySelector('#ccInputOk');
            const cancelBtn = modal.querySelector('#ccInputCancel');
            
            // 自动聚焦并选中文本
            setTimeout(() => {
                input.focus();
                input.select();
            }, 100);
            
            // 回车确认
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    confirmBtn.click();
                } else if (e.key === 'Escape') {
                    cancelBtn.click();
                }
            });
            
            confirmBtn.addEventListener('click', () => {
                const value = input.value;
                this.closeModal();
                if (onConfirm) onConfirm(value);
            });
            
            cancelBtn.addEventListener('click', () => {
                this.closeModal();
                if (onCancel) onCancel();
            });
            
            return modal;
        }
        
        // 日期时间选择对话框
        showDateTimeDialog(title, label, defaultValue = '', helpText = '', onConfirm = null, onCancel = null) {
            // 处理默认值格式转换
            let dateValue = '';
            let timeValue = '';
            if (defaultValue) {
                try {
                    const date = new Date(defaultValue);
                    if (!isNaN(date.getTime())) {
                        dateValue = date.getFullYear() + '-' + 
                                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                                   String(date.getDate()).padStart(2, '0');
                        timeValue = String(date.getHours()).padStart(2, '0') + ':' + 
                                   String(date.getMinutes()).padStart(2, '0');
                    }
                } catch (e) {
                    // 如果解析失败，使用当前时间
                    const now = new Date();
                    dateValue = now.getFullYear() + '-' + 
                               String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                               String(now.getDate()).padStart(2, '0');
                    timeValue = String(now.getHours()).padStart(2, '0') + ':' + 
                               String(now.getMinutes()).padStart(2, '0');
                }
            } else {
                const now = new Date();
                dateValue = now.getFullYear() + '-' + 
                           String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(now.getDate()).padStart(2, '0');
                timeValue = String(now.getHours()).padStart(2, '0') + ':' + 
                           String(now.getMinutes()).padStart(2, '0');
            }

            const modalHtml = `
                <div class="cc-modal-content">
                    <div class="cc-modal-header" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                        <h3 class="cc-modal-title" style="font-size: 1.25rem; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 0.75rem; margin: 0;">
                            <i class="bi bi-calendar-event"></i>
                            ${title}
                        </h3>
                        <button type="button" class="cc-modal-close" style="background: none; border: none; font-size: 1.25rem; color: #6b7280; cursor: pointer; padding: 0.5rem; border-radius: 0.5rem;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="cc-modal-body" style="padding: 1.5rem;">
                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">${label}</label>
                            
                            <div style="display: grid; grid-template-columns: 1fr 100px; gap: 0.75rem; margin-bottom: 1rem;">
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">日期</label>
                                    <input type="date" id="ccDateValue" value="${dateValue}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem; transition: all 0.15s ease; background: white;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">时间</label>
                                    <input type="time" id="ccTimeValue" value="${timeValue}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem; transition: all 0.15s ease; background: white;">
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.25rem;">预览格式</label>
                                <div id="ccDateTimePreview" style="padding: 0.75rem; background: #f3f4f6; border-radius: 0.5rem; font-size: 0.875rem; color: #374151; border: 1px solid #e5e7eb; min-height: 3rem;">
                                    <!-- 预览内容将由JavaScript动态生成 -->
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <button type="button" id="ccNowBtn" style="background: #e5e7eb; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; cursor: pointer;">
                                    <i class="bi bi-clock"></i> 现在
                                </button>
                                <button type="button" id="ccTodayBtn" style="background: #e5e7eb; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; cursor: pointer;">
                                    <i class="bi bi-calendar-day"></i> 今天 00:00
                                </button>
                                <button type="button" id="ccYesterdayBtn" style="background: #e5e7eb; color: #374151; border: 1px solid #d1d5db; padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; cursor: pointer;">
                                    <i class="bi bi-calendar-minus"></i> 昨天 00:00
                                </button>
                            </div>
                            
                            ${helpText ? `<div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.75rem;">${helpText}</div>` : ''}
                        </div>
                    </div>
                    <div class="cc-modal-footer" style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" id="ccDateTimeCancel" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-x"></i>
                            取消
                        </button>
                        <button type="button" id="ccDateTimeOk" style="background: #3b82f6; color: white; border: 1px solid #3b82f6; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-check"></i>
                            确认
                        </button>
                    </div>
                </div>
            `;
            
            const modal = this.showModal(modalHtml);
            const dateInput = modal.querySelector('#ccDateValue');
            const timeInput = modal.querySelector('#ccTimeValue');
            const preview = modal.querySelector('#ccDateTimePreview');
            const confirmBtn = modal.querySelector('#ccDateTimeOk');
            const cancelBtn = modal.querySelector('#ccDateTimeCancel');
            const nowBtn = modal.querySelector('#ccNowBtn');
            const todayBtn = modal.querySelector('#ccTodayBtn');
            const yesterdayBtn = modal.querySelector('#ccYesterdayBtn');
            
            // 更新预览
            const updatePreview = () => {
                const date = dateInput.value;
                const time = timeInput.value;
                if (date && time) {
                    // 创建日期对象用于格式化显示
                    const dateObj = new Date(`${date}T${time}:00`);
                    const year = dateObj.getFullYear();
                    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                    const day = String(dateObj.getDate()).padStart(2, '0');
                    const hour = String(dateObj.getHours()).padStart(2, '0');
                    const minute = String(dateObj.getMinutes()).padStart(2, '0');
                    
                    // 显示友好的格式给用户
                    const friendlyFormat = `${year}年${month}月${day}日 ${hour}:${minute}`;
                    const weekdays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
                    const weekday = weekdays[dateObj.getDay()];
                    
                    preview.innerHTML = `
                        <div style="font-size: 1rem; font-weight: 500; color: #111827;">${friendlyFormat}</div>
                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">${weekday} · 提交格式: ${date}T${time}:00</div>
                    `;
                } else {
                    preview.innerHTML = `
                        <div style="color: #9ca3af; font-style: italic;">请选择日期和时间</div>
                    `;
                }
            };
            
            // 监听输入变化
            dateInput.addEventListener('change', updatePreview);
            timeInput.addEventListener('change', updatePreview);
            
            // 初始化预览
            updatePreview();
            
            // 快捷按钮功能
            nowBtn.addEventListener('click', () => {
                const now = new Date();
                dateInput.value = now.getFullYear() + '-' + 
                                 String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                                 String(now.getDate()).padStart(2, '0');
                timeInput.value = String(now.getHours()).padStart(2, '0') + ':' + 
                                 String(now.getMinutes()).padStart(2, '0');
                updatePreview();
            });
            
            todayBtn.addEventListener('click', () => {
                const today = new Date();
                dateInput.value = today.getFullYear() + '-' + 
                                 String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                                 String(today.getDate()).padStart(2, '0');
                timeInput.value = '00:00';
                updatePreview();
            });
            
            yesterdayBtn.addEventListener('click', () => {
                const yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);
                dateInput.value = yesterday.getFullYear() + '-' + 
                                 String(yesterday.getMonth() + 1).padStart(2, '0') + '-' + 
                                 String(yesterday.getDate()).padStart(2, '0');
                timeInput.value = '00:00';
                updatePreview();
            });
            
            // 自动聚焦
            setTimeout(() => {
                dateInput.focus();
            }, 100);
            
            // 键盘事件
            [dateInput, timeInput].forEach(input => {
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        confirmBtn.click();
                    } else if (e.key === 'Escape') {
                        cancelBtn.click();
                    }
                });
            });
            
            confirmBtn.addEventListener('click', () => {
                const date = dateInput.value;
                const time = timeInput.value;
                if (date && time) {
                    const value = `${date}T${time}:00`;
                    this.closeModal();
                    if (onConfirm) onConfirm(value);
                } else {
                    alert('请选择完整的日期和时间');
                }
            });
            
            cancelBtn.addEventListener('click', () => {
                this.closeModal();
                if (onCancel) onCancel();
            });
            
            return modal;
        }
        
        // 文本区域对话框
        showTextareaDialog(title, label, placeholder = '', defaultValue = '', helpText = '', onConfirm = null, onCancel = null) {
            const modalHtml = `
                <div class="cc-modal-content">
                    <div class="cc-modal-header" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                        <h3 class="cc-modal-title" style="font-size: 1.25rem; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 0.75rem; margin: 0;">
                            <i class="bi bi-textarea-t"></i>
                            ${title}
                        </h3>
                        <button type="button" class="cc-modal-close" style="background: none; border: none; font-size: 1.25rem; color: #6b7280; cursor: pointer; padding: 0.5rem; border-radius: 0.5rem;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="cc-modal-body" style="padding: 1.5rem;">
                        <div style="margin-bottom: 1.25rem;">
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">${label}</label>
                            <textarea id="ccTextareaValue" placeholder="${placeholder}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem; transition: all 0.15s ease; background: white; resize: vertical; min-height: 100px;">${defaultValue}</textarea>
                            ${helpText ? `<div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">${helpText}</div>` : ''}
                        </div>
                    </div>
                    <div class="cc-modal-footer" style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" id="ccTextareaCancel" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-x"></i>
                            取消
                        </button>
                        <button type="button" id="ccTextareaOk" style="background: #3b82f6; color: white; border: 1px solid #3b82f6; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-check"></i>
                            确认
                        </button>
                    </div>
                </div>
            `;
            
            const modal = this.showModal(modalHtml);
            const textarea = modal.querySelector('#ccTextareaValue');
            const confirmBtn = modal.querySelector('#ccTextareaOk');
            const cancelBtn = modal.querySelector('#ccTextareaCancel');
            
            // 自动聚焦
            setTimeout(() => {
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            }, 100);
            
            // Ctrl+Enter确认
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.ctrlKey) {
                    confirmBtn.click();
                } else if (e.key === 'Escape') {
                    cancelBtn.click();
                }
            });
            
            confirmBtn.addEventListener('click', () => {
                const value = textarea.value;
                this.closeModal();
                if (onConfirm) onConfirm(value);
            });
            
            cancelBtn.addEventListener('click', () => {
                this.closeModal();
                if (onCancel) onCancel();
            });
            
            return modal;
        }
    }
    
    // 初始化 UI 系统
    document.addEventListener('DOMContentLoaded', () => {
        window.ccUI = new CloudClintUI();
        
        // 初始化全局智能刷新管理器
        initGlobalSmartRefresh();
        
        // 确保下拉菜单立即初始化
        setTimeout(() => {
            if (window.ccUI && window.ccUI.initDropdowns) {
                window.ccUI.initDropdowns();
            }
        }, 100);
    });
    
    // 全局智能刷新管理器
    function initGlobalSmartRefresh() {
        // 如果页面已经有智能刷新系统，不重复初始化
        if (window.smartRefresh) {
            return;
        }
        
        // 简化版智能刷新，适用于所有页面
        window.smartRefresh = {
            isActive: false,
            hasModalOpen: false,
            
            // 检查是否应该允许刷新
            canRefresh: function() {
                // 如果有模态框打开，不允许刷新
                if (this.hasModalOpen) {
                    return false;
                }
                
                // 如果用户正在输入，不允许刷新
                const activeElement = document.activeElement;
                if (activeElement && (
                    activeElement.tagName === 'INPUT' || 
                    activeElement.tagName === 'TEXTAREA' || 
                    activeElement.tagName === 'SELECT'
                )) {
                    return false;
                }
                
                // 如果页面不可见，不允许刷新
                if (document.hidden) {
                    return false;
                }
                
                return true;
            },
            
            // 设置模态框状态
            setModalState: function(isOpen) {
                this.hasModalOpen = isOpen;
            },
            
            // 安全刷新（只在合适的时候刷新）
            safeRefresh: function() {
                if (this.canRefresh()) {
                    window.location.reload();
                } else {
                    // 如果当前不能刷新，等待一段时间后再尝试
                    setTimeout(() => {
                        this.safeRefresh();
                    }, 2000);
                }
            },
            
            // 手动触发刷新（在数据更新后）
            refreshAfterUpdate: function() {
                setTimeout(() => {
                    this.safeRefresh();
                }, 1000);
            }
        };
        
        // 重写模态框函数，跟踪模态框状态
        if (window.ccUI) {
            const originalShowModal = window.ccUI.showModal;
            const originalCloseModal = window.ccUI.closeModal;
            
            window.ccUI.showModal = function(...args) {
                window.smartRefresh.setModalState(true);
                return originalShowModal.apply(this, args);
            };
            
            window.ccUI.closeModal = function(...args) {
                window.smartRefresh.setModalState(false);
                return originalCloseModal.apply(this, args);
            };
        }
    }
    
    // 全局工具函数
    window.ccUtils = {
        formatBytes: (bytes) => {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        formatUptime: (seconds) => {
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            
            if (days > 0) return `${days}天 ${hours}小时`;
            if (hours > 0) return `${hours}小时 ${minutes}分钟`;
            return `${minutes}分钟`;
        },
        
        debounce: (func, wait) => {
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
    };
    
    // 强制移除设备卡片选择框
    function removeDeviceCardSelectionBox() {
        const deviceCards = document.querySelectorAll('.cc-device-card');
        deviceCards.forEach(card => {
            // 移除所有可能的边框和轮廓
            card.style.border = 'none';
            card.style.outline = 'none';
            card.style.setProperty('border', 'none', 'important');
            card.style.setProperty('outline', 'none', 'important');
            
            // 移除可能的选择状态类
            card.classList.remove('selected', 'active', 'focused');
            
            // 检查并移除子元素的边框
            const allChildren = card.querySelectorAll('*');
            allChildren.forEach(child => {
                child.style.setProperty('border', 'none', 'important');
                child.style.setProperty('outline', 'none', 'important');
            });
        });
    }
    
    // 页面加载完成后执行
    document.addEventListener('DOMContentLoaded', removeDeviceCardSelectionBox);
    
    // 监听点击事件，确保点击后也移除选择框
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cc-device-card')) {
            setTimeout(removeDeviceCardSelectionBox, 10);
        }
    });
    
    // 定期检查并移除选择框（临时解决方案）
    setInterval(removeDeviceCardSelectionBox, 1000);
    
    </script>
</body>
</html>