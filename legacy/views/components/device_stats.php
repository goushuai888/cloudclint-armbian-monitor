<?php
/**
 * 设备统计组件
 */
?>

<!-- 统计概览 - ULTRATHINK 紧凑版 -->
<section class="cc-stats-grid">
    <div class="cc-stats-card">
        <div class="cc-stats-icon">
            <i class="bi bi-hdd-network"></i>
        </div>
        <div class="cc-stats-info">
            <div class="cc-stats-number"><?php echo $stats['total_devices']; ?></div>
            <div class="cc-stats-label">总设备</div>
        </div>
    </div>
    
    <div class="cc-stats-card cc-stats-success">
        <div class="cc-stats-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="cc-stats-info">
            <div class="cc-stats-number"><?php echo $stats['online_devices']; ?></div>
            <div class="cc-stats-label">在线</div>
        </div>
    </div>
    
    <div class="cc-stats-card cc-stats-danger">
        <div class="cc-stats-icon">
            <i class="bi bi-x-circle-fill"></i>
        </div>
        <div class="cc-stats-info">
            <div class="cc-stats-number"><?php echo $stats['offline_devices']; ?></div>
            <div class="cc-stats-label">离线</div>
        </div>
    </div>
    
    <div class="cc-stats-card cc-stats-info">
        <div class="cc-stats-icon">
            <i class="bi bi-activity"></i>
        </div>
        <div class="cc-stats-info">
            <div class="cc-stats-number"><?php 
                // 计算在线率
                echo $stats['total_devices'] > 0 ? round($stats['online_devices'] / $stats['total_devices'] * 100) : 0;
            ?>%</div>
            <div class="cc-stats-label">在线率</div>
        </div>
</section>