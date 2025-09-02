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

<!-- JavaScript 模块已整合到 cloudclint-unified.js 中 -->
<!-- 特定页面功能模块（如需要的话可在这里添加） -->

<?php include __DIR__ . '/../includes/footer.php'; ?>