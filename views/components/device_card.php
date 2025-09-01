<?php
/**
 * 设备卡片组件
 */

// 计算设备状态
$is_online = (time() - strtotime($device['last_heartbeat'])) <= 300;
$status_class = $is_online ? 'online' : 'offline';
$status_text = $is_online ? '在线' : '离线';
$status_icon = $is_online ? 'check-circle' : 'x-circle';

// 格式化时间
$last_heartbeat_formatted = $device['last_heartbeat'] ? date('Y-m-d H:i:s', strtotime($device['last_heartbeat'])) : '从未';
$created_at_formatted = date('Y-m-d H:i:s', strtotime($device['created_at']));
$updated_at_formatted = $device['updated_at'] ? date('Y-m-d H:i:s', strtotime($device['updated_at'])) : '从未';
?>

<div class="cc-device-card cc-animate-fadeIn cc-gpu-optimized cc-animate-delay-400 cc-magnetic">
    <!-- 设备头部 -->
    <div class="cc-device-header">
        <div class="cc-device-title-section">
            <h3 class="cc-device-name"><?php echo htmlspecialchars($device['device_name']); ?></h3>
            <span class="cc-device-status cc-status-<?php echo $status_class; ?>">
                <i class="bi bi-<?php echo $status_icon; ?>"></i>
                <span><?php echo $status_text; ?></span>
            </span>
        </div>
    </div>
    
    <!-- 设备信息 -->
    <div class="cc-device-info">
        <!-- 主要信息 -->
        <div class="cc-device-primary-info">
            <div class="cc-info-item">
                <span class="cc-info-label">设备ID:</span>
                <span class="cc-info-value cc-device-id"><?php echo htmlspecialchars($device['device_id']); ?></span>
            </div>
            <div class="cc-info-item">
                <span class="cc-info-label">设备编号:</span>
                <span class="cc-info-value cc-editable-field" 
                      data-field="order_number" 
                      data-device-id="<?php echo $device['device_id']; ?>" 
                      onclick="showOrderNumberModal('<?php echo $device['device_id']; ?>', '<?php echo $device['order_number'] ?? 0; ?>')">
                    <?php echo $device['order_number'] ?? 0; ?>
                    <i class="bi bi-pencil cc-edit-icon"></i>
                </span>
            </div>
        </div>
        
        <!-- 次要信息 -->
        <div class="cc-device-secondary-info">
            <div class="cc-info-item">
                <span class="cc-info-label">IP地址:</span>
                <span class="cc-info-value"><?php echo htmlspecialchars($device['ip_address'] ?: '未知'); ?></span>
            </div>
            <div class="cc-info-item">
                <span class="cc-info-label">最后心跳:</span>
                <span class="cc-info-value"><?php echo $last_heartbeat_formatted; ?></span>
            </div>
            <div class="cc-info-item">
                <span class="cc-info-label">注册时间:</span>
                <span class="cc-info-value cc-editable-field" 
                      data-field="created_time" 
                      data-device-id="<?php echo $device['device_id']; ?>" 
                      onclick="showCreatedTimeModal('<?php echo $device['device_id']; ?>', '<?php echo $created_at_formatted; ?>')">
                    <?php echo $created_at_formatted; ?>
                    <i class="bi bi-pencil cc-edit-icon"></i>
                </span>
            </div>
        </div>
        
        <!-- 备注信息 -->
        <div class="cc-device-remarks">
            <div class="cc-info-item">
                <span class="cc-info-label">备注:</span>
                <span class="cc-info-value cc-editable-field cc-remarks-field" 
                      data-field="remarks" 
                      data-device-id="<?php echo $device['device_id']; ?>" 
                      onclick="showRemarksModal('<?php echo $device['device_id']; ?>', '<?php echo htmlspecialchars($device['remarks'] ?: '', ENT_QUOTES); ?>')">
                    <?php echo htmlspecialchars($device['remarks'] ?: '点击添加备注'); ?>
                    <i class="bi bi-pencil cc-edit-icon"></i>
                </span>
            </div>
        </div>
        
        <!-- 资源监控信息 (仅在线设备显示) -->
        <?php if ($is_online && !empty($device['cpu_usage'])): ?>
        <div class="cc-device-resources">
            <h4 class="cc-resources-title">资源监控</h4>
            <div class="cc-resources-grid">
                <div class="cc-resource-item">
                    <span class="cc-resource-label">CPU使用率:</span>
                    <div class="cc-resource-value">
                        <span class="cc-resource-text"><?php echo number_format($device['cpu_usage'], 1); ?>%</span>
                        <div class="cc-progress-bar">
                            <div class="cc-progress-fill" style="width: <?php echo min($device['cpu_usage'], 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($device['memory_usage'])): ?>
                <div class="cc-resource-item">
                    <span class="cc-resource-label">内存使用率:</span>
                    <div class="cc-resource-value">
                        <span class="cc-resource-text"><?php echo number_format($device['memory_usage'], 1); ?>%</span>
                        <div class="cc-progress-bar">
                            <div class="cc-progress-fill" style="width: <?php echo min($device['memory_usage'], 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($device['disk_usage'])): ?>
                <div class="cc-resource-item">
                    <span class="cc-resource-label">存储使用率:</span>
                    <div class="cc-resource-value">
                        <span class="cc-resource-text"><?php echo number_format($device['disk_usage'], 1); ?>%</span>
                        <div class="cc-progress-bar">
                            <div class="cc-progress-fill" style="width: <?php echo min($device['disk_usage'], 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($device['temperature'])): ?>
                <div class="cc-resource-item">
                    <span class="cc-resource-label">温度:</span>
                    <div class="cc-resource-value">
                        <span class="cc-resource-text"><?php echo number_format($device['temperature'], 1); ?>°C</span>
                        <div class="cc-progress-bar cc-temp-bar">
                            <div class="cc-progress-fill cc-temp-fill" style="width: <?php echo min($device['temperature'], 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- 设备底部 -->
    <div class="cc-device-footer">
        <div class="cc-device-meta">
            <i class="bi bi-clock"></i>
            <span class="cc-update-time"><?php echo $updated_at_formatted; ?></span>
        </div>
        
        <?php if (!$is_online): ?>
        <div class="cc-device-actions">
            <button class="cc-btn cc-btn-danger cc-btn-sm" 
                    onclick="deleteDevice('<?php echo $device['device_id']; ?>', '<?php echo htmlspecialchars($device['device_name']); ?>')">
                <i class="bi bi-trash"></i>
                删除设备
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>