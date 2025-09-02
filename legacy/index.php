<?php
/**
 * CloudClint 设备监控平台 - 主页面
 * 重构版本 - 使用MVC架构
 */

// 开始会话
session_start();

// 引入依赖文件
require_once 'config/security.php';
require_once 'config/timezone.php';
require_once 'config/database.php';
require_once 'classes/Device.php';
require_once 'classes/Auth.php';
require_once 'controllers/DeviceController.php';
require_once 'includes/functions.php';

// 初始化数据库连接
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die('数据库连接失败');
}

$auth = new Auth($db);

// 检查用户是否已登录
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 获取当前用户信息
$current_user = $auth->getCurrentUser();

// 初始化设备控制器
$deviceController = new DeviceController($database);

// 处理设备删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_device') {
    $result = $deviceController->deleteDevice();
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

// 获取设备数据
$deviceData = $deviceController->getDeviceData();
$devices = $deviceData['devices'];
$stats = $deviceData['stats'];
$filters = $deviceData['filters'];
$delete_message = '';

// 包含主视图
require_once 'views/device_index.php';
?>