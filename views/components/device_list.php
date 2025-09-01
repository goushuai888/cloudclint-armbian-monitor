<?php
/**
 * 设备列表组件
 */
?>

<!-- 设备列表 -->
<section class="cc-devices-section">
    <?php if (empty($devices)): ?>
        <!-- 空状态 -->
        <div class="cc-empty-state">
            <div class="cc-empty-icon">
                <i class="bi bi-hdd-network"></i>
            </div>
            <h3 class="cc-empty-title">
                <?php 
                if (!empty($filters['search_keyword'])) {
                    echo '未找到匹配的设备';
                } elseif ($filters['filter_year'] > 0 || $filters['filter_month'] > 0) {
                    echo '该时间段内没有设备';
                } elseif (!empty($filters['filter_status'])) {
                    echo $filters['filter_status'] === 'online' ? '暂无在线设备' : '暂无离线设备';
                } else {
                    echo '暂无设备';
                }
                ?>
            </h3>
            <p class="cc-empty-description">
                <?php 
                if (!empty($filters['search_keyword'])) {
                    echo '尝试使用其他关键词搜索';
                } elseif ($filters['filter_year'] > 0 || $filters['filter_month'] > 0) {
                    echo '请选择其他时间段或清除筛选条件';
                } elseif (!empty($filters['filter_status'])) {
                    echo '请检查设备连接状态或查看所有设备';
                } else {
                    echo '请添加您的第一台设备';
                }
                ?>
            </p>
            <?php if (!empty($filters['search_keyword']) || $filters['filter_year'] > 0 || $filters['filter_month'] > 0 || !empty($filters['filter_status'])): ?>
            <a href="index.php" class="cc-btn cc-btn-primary">
                <i class="bi bi-arrow-clockwise"></i>
                清除筛选
            </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- 设备网格 -->
        <div class="cc-devices-grid">
            <?php foreach ($devices as $device): ?>
                <?php include 'views/components/device_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>