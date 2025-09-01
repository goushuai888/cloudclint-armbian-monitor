<?php
/**
 * 设备页面主视图
 */
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- 删除结果提示 -->
<?php if ($delete_message): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showToast('<?php echo addslashes($delete_message); ?>', '<?php echo strpos($delete_message, "成功") !== false ? "success" : "error"; ?>');
    });
</script>
<?php endif; ?>

<div class="cc-page-content">
    <!-- 统计概览 -->
    <?php include 'views/components/device_stats.php'; ?>
    
    <!-- 筛选器 -->
    <?php include 'views/components/device_filters.php'; ?>
    
    <!-- 设备列表 -->
    <?php include 'views/components/device_list.php'; ?>
</div>

<!-- 模态框 -->
<?php include __DIR__ . '/../includes/modals.php'; ?>

<!-- JavaScript 模块 -->
<script src="assets/js/date-filter.js"></script>
<script src="assets/js/device-actions.js"></script>
<script src="assets/js/smart-refresh.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>