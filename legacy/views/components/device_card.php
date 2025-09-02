<?php
/**
 * 设备卡片组件 - ULTRATHINK 精简重构版
 */

// 计算设备状态
$is_online = (time() - strtotime($device['last_heartbeat'])) <= 300;
$status_class = $is_online ? 'online' : 'offline';
$status_text = $is_online ? '在线' : '离线';
$status_icon = $is_online ? 'check-circle-fill' : 'x-circle-fill';

// 格式化时间 - 紧凑显示
$heartbeat_time = $device['last_heartbeat'] ? date('m-d H:i', strtotime($device['last_heartbeat'])) : '未知';
$created_time = date('m-d H:i', strtotime($device['created_at']));

// 计算最后心跳时间差
$last_seen = '刚刚';
if ($device['last_heartbeat']) {
    $time_diff = time() - strtotime($device['last_heartbeat']);
    if ($time_diff > 86400) $last_seen = floor($time_diff/86400) . '天前';
    elseif ($time_diff > 3600) $last_seen = floor($time_diff/3600) . '小时前';  
    elseif ($time_diff > 60) $last_seen = floor($time_diff/60) . '分钟前';
}
?>

<div class="cc-device-card cc-animate-fadeIn <?php echo $status_class; ?>">
    <!-- 设备头部 - 横向布局 -->
    <div class="cc-device-header">
        <div class="cc-device-title-section">
            <h3 class="cc-device-name"><?php echo htmlspecialchars($device['device_name']); ?></h3>
        </div>
        <div class="cc-device-status cc-status-<?php echo $status_class; ?>">
            <i class="bi bi-<?php echo $status_icon; ?>"></i>
            <span><?php echo $status_text; ?></span>
        </div>
    </div>
    
    <!-- 设备信息 - 2列紧凑网格 -->
    <div class="cc-device-info">
        <!-- 设备ID -->
        <div class="cc-info-item">
            <span class="cc-info-label">设备ID</span>
            <span class="cc-info-value"><?php echo htmlspecialchars($device['device_id']); ?></span>
        </div>
        
        <!-- 编号 -->
        <div class="cc-info-item">
            <span class="cc-info-label">编号</span>
            <span class="cc-info-value cc-editable-field" 
                  data-field="order_number" 
                  data-device-id="<?php echo $device['device_id']; ?>" 
                  onclick="showOrderNumberModal('<?php echo $device['device_id']; ?>', '<?php echo $device['order_number'] ?? 0; ?>')">
                #<?php echo $device['order_number'] ?? 0; ?>
                <i class="bi bi-pencil cc-edit-icon"></i>
            </span>
        </div>
        
        <!-- IP地址 -->
        <div class="cc-info-item">
            <span class="cc-info-label">IP地址</span>
            <span class="cc-info-value"><?php echo htmlspecialchars($device['ip_address'] ?: '未知'); ?></span>
        </div>
        
        <!-- 最后活跃 -->
        <div class="cc-info-item">
            <span class="cc-info-label">最后活跃</span>
            <span class="cc-info-value" title="<?php echo $heartbeat_time; ?>"><?php echo $last_seen; ?></span>
        </div>
        
        <!-- 注册时间 -->
        <div class="cc-info-item">
            <span class="cc-info-label">注册时间</span>
            <span class="cc-info-value cc-editable-field" 
                  data-field="created_time" 
                  data-device-id="<?php echo $device['device_id']; ?>" 
                  onclick="showCreatedTimeModal('<?php echo $device['device_id']; ?>', '<?php echo date('Y-m-d H:i:s', strtotime($device['created_at'])); ?>')">
                <?php echo $created_time; ?>
                <i class="bi bi-pencil cc-edit-icon"></i>
            </span>
        </div>
        
        <!-- 操作按钮 -->
        <div class="cc-info-item">
            <span class="cc-info-label">操作</span>
            <span class="cc-info-value">
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="deleteDevice('<?php echo $device['device_id']; ?>')"
                        title="删除设备">
                    <i class="bi bi-trash3"></i>
                </button>
            </span>
        </div>
        
        <!-- 备注信息 - 全宽显示 -->
        <div class="cc-device-remarks">
            <div class="cc-info-item">
                <span class="cc-info-label">备注</span>
                <span class="cc-info-value cc-editable-field cc-remarks-field" 
                      data-field="remarks" 
                      data-device-id="<?php echo $device['device_id']; ?>" 
                      onclick="showRemarksModal('<?php echo $device['device_id']; ?>', '<?php echo htmlspecialchars($device['remarks'] ?: '', ENT_QUOTES); ?>')">
                    <?php echo htmlspecialchars($device['remarks'] ?: '点击添加备注'); ?>
                    <i class="bi bi-pencil cc-edit-icon"></i>
                </span>
            </div>
        </div>
    </div>
    
    <!-- 资源监控信息 - 紧凑显示 -->
    <?php if ($is_online && !empty($device['cpu_usage'])): ?>
    <div class="cc-device-resources">
        <div class="cc-resources-title">资源监控</div>
        <div class="cc-resources-grid">
            <!-- CPU -->
            <div class="cc-resource-item">
                <span class="cc-resource-label">CPU</span>
                <div class="cc-resource-value">
                    <span class="cc-resource-text"><?php echo number_format($device['cpu_usage'], 1); ?>%</span>
                    <div class="cc-progress-bar">
                        <div class="cc-progress-fill" style="width: <?php echo min($device['cpu_usage'], 100); ?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- 内存 -->
            <?php if (!empty($device['memory_usage'])): ?>
            <div class="cc-resource-item">
                <span class="cc-resource-label">内存</span>
                <div class="cc-resource-value">
                    <span class="cc-resource-text"><?php echo number_format($device['memory_usage'], 1); ?>%</span>
                    <div class="cc-progress-bar">
                        <div class="cc-progress-fill" style="width: <?php echo min($device['memory_usage'], 100); ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>