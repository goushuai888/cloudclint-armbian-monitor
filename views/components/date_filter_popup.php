<?php
/**
 * 日期筛选弹出窗口组件
 */
?>

<div class="cc-date-filter-popup" id="dateFilterPopup">
    <div class="cc-date-filter-header">
        <h3>时间筛选</h3>
        <button class="cc-close-btn" onclick="hideDateFilter()">
            <i class="bi bi-x"></i>
        </button>
    </div>
    
    <div class="cc-date-filter-content">
        <!-- 年份选择 -->
        <div class="cc-year-section">
            <div class="cc-year-header">
                <button class="cc-year-nav-btn" onclick="changeYear(-1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span class="cc-year-display" id="currentYear"><?php echo date('Y'); ?></span>
                <button class="cc-year-nav-btn" onclick="changeYear(1)">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            
            <!-- 移除快速年份按钮，简化界面 -->
        </div>
        
        <!-- 月份选择 -->
        <div class="cc-month-section">
            <div class="cc-month-grid">
                <?php 
                $months = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
                for ($i = 1; $i <= 12; $i++): 
                ?>
                <button class="cc-month-btn" data-month="<?php echo $i; ?>" onclick="selectMonth(<?php echo $i; ?>)">
                    <span class="cc-month-name"><?php echo $months[$i-1]; ?></span>
                    <span class="cc-month-num"><?php echo $i; ?></span>
                </button>
                <?php endfor; ?>
            </div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="cc-date-filter-actions">
            <button class="cc-btn cc-btn-secondary" onclick="clearDateFilter()">清除筛选</button>
            <button class="cc-btn cc-btn-primary" onclick="applyDateFilter()">应用筛选</button>
        </div>
    </div>
</div>

<!-- 年份选择器已移除，简化界面 -->