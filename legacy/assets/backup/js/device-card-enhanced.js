/**
 * 设备卡片增强交互功能
 * 提供现代化的用户体验和动画效果
 */

(function() {
    'use strict';
    
    // 设备卡片增强类
    class DeviceCardEnhancer {
        constructor() {
            this.cards = [];
            this.observers = new Map();
            this.animationQueue = [];
            this.isAnimating = false;
            
            this.init();
        }
        
        init() {
            // 等待DOM加载完成
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        }
        
        setup() {
            this.findCards();
            this.setupIntersectionObserver();
            this.setupEventListeners();
            this.setupProgressAnimations();
            this.setupMagneticEffect();
            this.setupTooltips();
            this.setupKeyboardNavigation();
            
            // 延迟执行动画以提升性能
            requestAnimationFrame(() => {
                this.animateCardsIn();
            });
        }
        
        findCards() {
            this.cards = Array.from(document.querySelectorAll('.cc-device-card'));
        }
        
        setupIntersectionObserver() {
            // 创建交叉观察器用于懒加载动画
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCardIn(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '50px'
            });
            
            this.cards.forEach(card => {
                observer.observe(card);
            });
        }
        
        setupEventListeners() {
            this.cards.forEach((card, index) => {
                // 鼠标悬停效果
                card.addEventListener('mouseenter', (e) => this.handleCardHover(e, true));
                card.addEventListener('mouseleave', (e) => this.handleCardHover(e, false));
                
                // 点击效果
                card.addEventListener('click', (e) => this.handleCardClick(e));                // 键盘导航 - 移除tabindex以避免选中框
                // card.setAttribute('tabindex', '0');
                card.addEventListener('keydown', (e) => this.handleKeydown(e));
                
                // 触摸设备支持
                card.addEventListener('touchstart', (e) => this.handleTouchStart(e), { passive: true });
                card.addEventListener('touchend', (e) => this.handleTouchEnd(e), { passive: true });
            });
            
            // 全局事件监听
            window.addEventListener('resize', this.debounce(() => this.handleResize(), 250));
            document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
        }
        
        setupProgressAnimations() {
            // 为进度条添加动画效果
            this.cards.forEach(card => {
                const progressBars = card.querySelectorAll('.cc-progress-fill');
                progressBars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    bar.style.transition = 'width 1.5s cubic-bezier(0.4, 0, 0.2, 1)';
                    
                    // 延迟动画以创建层次感
                    setTimeout(() => {
                        bar.style.width = width;
                    }, Math.random() * 500 + 200);
                });
            });
        }
        
        setupMagneticEffect() {
            // 磁性效果 - 鼠标跟随
            this.cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    if (!card.classList.contains('cc-magnetic')) return;
                    
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    
                    const deltaX = (x - centerX) / centerX;
                    const deltaY = (y - centerY) / centerY;
                    
                    const maxTilt = 5; // 最大倾斜角度
                    const tiltX = deltaY * maxTilt;
                    const tiltY = -deltaX * maxTilt;
                    
                    card.style.transform = `perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateZ(10px)`;
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
                });
            });
        }
        
        setupTooltips() {
            // 为可编辑字段添加工具提示
            const editableFields = document.querySelectorAll('.cc-editable-field');
            editableFields.forEach(field => {
                field.setAttribute('title', '点击编辑');
                field.setAttribute('aria-label', '可编辑字段，点击进行编辑');
            });
            
            // 为状态指示器添加工具提示
            const statusElements = document.querySelectorAll('.cc-device-status');
            statusElements.forEach(status => {
                const isOnline = status.classList.contains('cc-status-online');
                status.setAttribute('title', isOnline ? '设备在线' : '设备离线');
            });
        }
        
        setupKeyboardNavigation() {
            // 键盘导航支持
            let currentFocus = -1;
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab' && e.shiftKey) {
                    // Shift+Tab - 向前导航
                    e.preventDefault();
                    currentFocus = Math.max(0, currentFocus - 1);
                    this.focusCard(currentFocus);
                } else if (e.key === 'Tab') {
                    // Tab - 向后导航
                    e.preventDefault();
                    currentFocus = Math.min(this.cards.length - 1, currentFocus + 1);
                    this.focusCard(currentFocus);
                }
            });
        }
        
        animateCardsIn() {
            // 批量动画入场
            this.cards.forEach((card, index) => {
                setTimeout(() => {
                    this.animateCardIn(card);
                }, index * 100); // 错开动画时间
            });
        }
        
        animateCardIn(card) {
            if (card.classList.contains('animated-in')) return;
            
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px) scale(0.95)';
            
            requestAnimationFrame(() => {
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0) scale(1)';
                
                card.classList.add('animated-in');
                
                // 动画完成后清理样式
                setTimeout(() => {
                    card.style.transition = '';
                }, 600);
            });
        }
        
        handleCardHover(event, isEntering) {
            const card = event.currentTarget;
            
            if (isEntering) {
                // 悬停进入
                card.classList.add('hovered');
                this.animateResourceBars(card);
                this.showHoverEffects(card);
            } else {
                // 悬停离开
                card.classList.remove('hovered');
                this.hideHoverEffects(card);
            }
        }
        
        handleCardClick(event) {
            const card = event.currentTarget;
            
            // 点击波纹效果
            this.createRippleEffect(card, event);
            
            // 轻微的点击反馈
            card.style.transform = 'scale(0.98)';
            setTimeout(() => {
                card.style.transform = '';
            }, 150);
        }
        
        handleKeydown(event) {
            const card = event.currentTarget;
            
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.handleCardClick(event);
            }
        }
        
        handleTouchStart(event) {
            const card = event.currentTarget;
            card.classList.add('touched');
        }
        
        handleTouchEnd(event) {
            const card = event.currentTarget;
            setTimeout(() => {
                card.classList.remove('touched');
            }, 150);
        }
        
        animateResourceBars(card) {
            const progressBars = card.querySelectorAll('.cc-progress-fill');
            progressBars.forEach((bar, index) => {
                setTimeout(() => {
                    bar.style.transform = 'scaleX(1.02)';
                    setTimeout(() => {
                        bar.style.transform = 'scaleX(1)';
                    }, 200);
                }, index * 50);
            });
        }
        
        showHoverEffects(card) {
            // 显示编辑图标
            const editIcons = card.querySelectorAll('.cc-edit-icon');
            editIcons.forEach(icon => {
                icon.style.opacity = '1';
                icon.style.transform = 'scale(1.1)';
            });
            
            // 增强阴影效果
            card.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.15)';
        }
        
        hideHoverEffects(card) {
            // 隐藏编辑图标
            const editIcons = card.querySelectorAll('.cc-edit-icon');
            editIcons.forEach(icon => {
                icon.style.opacity = '';
                icon.style.transform = '';
            });
            
            // 恢复阴影效果
            card.style.boxShadow = '';
        }
        
        createRippleEffect(card, event) {
            const ripple = document.createElement('div');
            const rect = card.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
                z-index: 1;
            `;
            
            card.style.position = 'relative';
            card.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        }
        
        focusCard(index) {
            if (index >= 0 && index < this.cards.length) {
                this.cards[index].focus();
                this.cards[index].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }
        
        handleResize() {
            // 响应式调整
            this.cards.forEach(card => {
                // 重新计算磁性效果的参数
                card.style.transform = '';
            });
        }
        
        handleVisibilityChange() {
            if (document.hidden) {
                // 页面隐藏时暂停动画
                this.pauseAnimations();
            } else {
                // 页面显示时恢复动画
                this.resumeAnimations();
            }
        }
        
        pauseAnimations() {
            this.cards.forEach(card => {
                card.style.animationPlayState = 'paused';
            });
        }
        
        resumeAnimations() {
            this.cards.forEach(card => {
                card.style.animationPlayState = 'running';
            });
        }
        
        // 工具函数
        debounce(func, wait) {
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
        
        // 公共API
        refresh() {
            this.findCards();
            this.setup();
        }
        
        destroy() {
            // 清理事件监听器和观察器
            this.observers.forEach(observer => observer.disconnect());
            this.observers.clear();
            
            this.cards.forEach(card => {
                card.removeEventListener('mouseenter', this.handleCardHover);
                card.removeEventListener('mouseleave', this.handleCardHover);
                card.removeEventListener('click', this.handleCardClick);
                card.removeEventListener('keydown', this.handleKeydown);
            });
        }
    }
    
    // 添加必要的CSS动画
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
        
        .cc-device-card.touched {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
        
        .cc-device-card:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
        
        .cc-device-card.hovered .cc-progress-fill::after {
            animation-duration: 1s;
        }
        
        /* 减少动画对性能的影响 */
        @media (prefers-reduced-motion: reduce) {
            .cc-device-card,
            .cc-progress-fill,
            .cc-edit-icon {
                animation: none !important;
                transition: none !important;
            }
        }
    `;
    document.head.appendChild(style);
    
    // 初始化增强器
    const enhancer = new DeviceCardEnhancer();
    
    // 暴露到全局作用域以便其他脚本使用
    window.DeviceCardEnhancer = enhancer;
    
    // 监听动态内容更新
    if (window.MutationObserver) {
        const observer = new MutationObserver((mutations) => {
            let shouldRefresh = false;
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1 && 
                            (node.classList?.contains('cc-device-card') || 
                             node.querySelector?.('.cc-device-card'))) {
                            shouldRefresh = true;
                        }
                    });
                }
            });
            
            if (shouldRefresh) {
                setTimeout(() => enhancer.refresh(), 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
})();