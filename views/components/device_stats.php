<?php
/**
 * 设备统计组件
 */
?>

<!-- 统计概览 -->
<section class="cc-stats-grid">
    <div class="cc-stats-card cc-animate-fadeIn">
        <div class="cc-stats-content">
            <div class="cc-stats-icon">
                <i class="bi bi-hdd-network"></i>
            </div>
            <div class="cc-stats-details">
                <div class="cc-stats-number"><?php echo $stats['total_devices']; ?></div>
                <div class="cc-stats-label">总设备数</div>
            </div>
        </div>
    </div>
    
    <div class="cc-stats-card cc-stats-card-success cc-animate-fadeIn cc-animate-delay-100">
        <div class="cc-stats-content">
            <div class="cc-stats-icon cc-stats-icon-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="cc-stats-details">
                <div class="cc-stats-number"><?php echo $stats['online_devices']; ?></div>
                <div class="cc-stats-label">在线设备</div>
            </div>
        </div>
    </div>
    
    <div class="cc-stats-card cc-stats-card-danger cc-animate-fadeIn cc-animate-delay-200">
        <div class="cc-stats-content">
            <div class="cc-stats-icon cc-stats-icon-danger">
                <i class="bi bi-x-circle"></i>
            </div>
            <div class="cc-stats-details">
                <div class="cc-stats-number"><?php echo $stats['offline_devices']; ?></div>
                <div class="cc-stats-label">离线设备</div>
            </div>
        </div>
    </div>
    
    <div class="cc-stats-card cc-stats-card-info cc-animate-fadeIn cc-animate-delay-300">
        <div class="cc-stats-content">
            <div class="cc-stats-icon cc-stats-icon-info">
                <i class="bi bi-activity"></i>
            </div>
            <div class="cc-stats-details">
                <div class="cc-stats-number"><?php echo number_format(($stats['online_devices'] / max($stats['total_devices'], 1)) * 100, 1); ?>%</div>
                <div class="cc-stats-label">在线率</div>
            </div>
        </div>
    </div>
</section>