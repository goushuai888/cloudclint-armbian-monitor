<?php
/**
 * 设备备注更新API
 */

// 引入时区配置
require_once '../config/timezone.php';

// 设置错误日志记录
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Request-ID, X-Request-Timestamp');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

try {
    // 检查用户认证和状态
    checkAuth();
    
    // 只接受POST请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => '只支持POST请求'], 405);
    }
    
    // 获取POST数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['error' => 'JSON格式错误: ' . json_last_error_msg()], 400);
    }
    
    
    // 验证必需字段
    if (empty($data['device_id'])) {
        jsonResponse(['error' => '缺少设备ID'], 400);
    }
    
    // 可选的请求ID和时间戳验证（如果提供的话）
    $request_id = $_SERVER['HTTP_X_REQUEST_ID'] ?? 'req_' . time() . '_' . rand(1000, 9999);
    $request_timestamp = $_SERVER['HTTP_X_REQUEST_TIMESTAMP'] ?? time();
    
    // 数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        jsonResponse(['error' => '数据库连接失败'], 500);
    }
    
    $device = new Device($db);
    
    // 检查请求是否已经处理过
    try {
        // 检查request_log表是否存在
        $table_check = $db->query("SHOW TABLES LIKE 'request_log'");
        $table_exists = $table_check->rowCount() > 0;
        
        if ($table_exists) {
            $stmt = $db->prepare("SELECT id FROM request_log WHERE request_id = ? LIMIT 1");
            $stmt->execute([$request_id]);
            
            if ($stmt->rowCount() > 0) {
                jsonResponse(['error' => '请求已处理，请勿重复提交'], 409);
            }
            
            // 记录请求ID
            $stmt = $db->prepare("INSERT INTO request_log (request_id, request_time, api_name) VALUES (?, NOW(), 'update_remarks')");
            $stmt->execute([$request_id]);
        }
        // 如果表不存在，跳过重复请求检查
    } catch (PDOException $e) {
        // 继续执行，不要因为日志错误阻止主要功能
    }
    
    // 更新设备备注
    $result = $device->updateRemarks($data['device_id'], $data['remarks'] ?? '');
    
    // 确保返回正确的格式
    if ($result === true || (is_array($result) && $result['success'] === true)) {
        jsonResponse(['success' => true, 'message' => '备注更新成功']);
    } else {
        $error_msg = is_array($result) ? ($result['message'] ?? '更新失败') : '更新失败';
        jsonResponse(['success' => false, 'message' => $error_msg], 400);
    }
    
} catch (Exception $e) {
    jsonResponse(['error' => '服务器内部错误: ' . $e->getMessage()], 500);
}
?> 