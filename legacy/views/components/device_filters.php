<?php
/**
 * 设备筛选器组件
 */
?>

<!-- 筛选器 -->
<section class="cc-filters-section">
    <!-- 快速筛选选项卡 -->
    <div class="cc-filter-tabs">
        <a href="index.php" class="cc-filter-tab <?php echo empty($filters['filter_status']) && empty($filters['search_keyword']) && empty($filters['filter_year']) && empty($filters['filter_month']) ? 'active' : ''; ?>">
            <i class="bi bi-list-ul"></i>
            <span>所有设备</span>
        </a>
        <a href="index.php?status=online" class="cc-filter-tab <?php echo $filters['filter_status'] === 'online' ? 'active' : ''; ?>">
            <i class="bi bi-check-circle"></i>
            <span>在线设备</span>
            <span class="cc-badge cc-badge-success cc-ml-2"><?php echo $stats['online_devices']; ?></span>
        </a>
        <a href="index.php?status=offline" class="cc-filter-tab <?php echo $filters['filter_status'] === 'offline' ? 'active' : ''; ?>">
            <i class="bi bi-x-circle"></i>
            <span>离线设备</span>
            <span class="cc-badge cc-badge-danger cc-ml-2"><?php echo $stats['offline_devices']; ?></span>
        </a>
    </div>
    
    <!-- 时间筛选触发按钮 -->
    <div class="cc-filter-trigger-section">
        <button class="cc-filter-trigger-btn" onclick="toggleDateFilter()" id="dateFilterTrigger">
            <i class="bi bi-calendar-range"></i>
            <span>时间筛选</span>
            <?php if ($filters['filter_year'] > 0 || $filters['filter_month'] > 0): ?>
                <span class="cc-filter-badge">
                    <?php 
                    if ($filters['filter_year'] > 0 && $filters['filter_month'] > 0) {
                        echo $filters['filter_year'] . '年' . $filters['filter_month'] . '月';
                    } elseif ($filters['filter_year'] > 0) {
                        echo $filters['filter_year'] . '年';
                    }
                    ?>
                </span>
            <?php endif; ?>
            <i class="bi bi-chevron-down cc-dropdown-arrow"></i>
        </button>
        
        <?php if ($filters['filter_year'] > 0 || $filters['filter_month'] > 0): ?>
        <button class="cc-clear-filters-compact-btn" onclick="clearFilters()" title="清除筛选">
            <i class="bi bi-x-circle"></i>
        </button>
        <?php endif; ?>

        <!-- 时间筛选弹出窗口 -->
        <?php include 'views/components/date_filter_popup.php'; ?>
    </div>
</section>
