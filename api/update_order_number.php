<?php
/**
 * 更新设备编号API
 * 
 * 请求参数:
 * - device_id: 设备ID
 * - order_number: 设备编号
 * 
 * 返回JSON:
 * - success: 是否成功
 * - message: 消息
 * - order_number: 更新后的编号
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 引入必要的文件
require_once '../config/database.php';
require_once 'auth_check.php';
require_once '../classes/Device.php';

// 确保jsonResponse函数可用
if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $status_code = 200) {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// 允许的请求方法
$allowed_methods = ['POST', 'OPTIONS'];

// 处理OPTIONS请求（用于CORS预检）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// 检查请求方法
if (!in_array($_SERVER['REQUEST_METHOD'], $allowed_methods)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
    exit;
}

try {
    // 检查用户认证和状态
    checkAuth();
    
    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 如果使用表单提交，合并POST数据
    if (empty($input)) {
        $input = $_POST;
    }
    
    // 验证必要参数
    if (empty($input['device_id'])) {
        throw new Exception('缺少设备ID参数');
    }
    
    if (!isset($input['order_number'])) {
        throw new Exception('缺少编号参数');
    }
    
    // 数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('数据库连接失败');
    }
    
    // 创建设备对象
    $device = new Device($db);
    
    // 更新设备编号
    $result = $device->updateOrderNumber($input['device_id'], $input['order_number']);
    
    // 返回结果
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 