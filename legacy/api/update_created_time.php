<?php
/**
 * 设备添加时间更新API
 */

// 引入时区配置
require_once '../config/timezone.php';

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
    // 用户认证检查
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
    
    if (empty($data['created_at'])) {
        jsonResponse(['error' => '缺少添加时间'], 400);
    }
    
    // 尝试各种日期格式
    $created_time = null;
    $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'];
    
    foreach ($formats as $format) {
        $test_time = date_create_from_format($format, $data['created_at']);
        if ($test_time) {
            $created_time = $test_time;
            break;
        }
    }
    
    if (!$created_time) {
        jsonResponse(['error' => '日期格式无效，请使用YYYY-MM-DD HH:MM:SS格式'], 400);
    }
    
    // 格式化为统一格式
    $formatted_date = $created_time->format('Y-m-d H:i:s');
    
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
            $stmt = $db->prepare("INSERT INTO request_log (request_id, request_time, api_name) VALUES (?, NOW(), 'update_created_time')");
            $stmt->execute([$request_id]);
        } else {
        }
    } catch (PDOException $e) {
        // 继续执行，不要因为日志错误阻止主要功能
    }
    
    // 更新设备添加时间
    $result = $device->updateCreatedTime($data['device_id'], $formatted_date);
    
    // 确保返回正确的格式
    if ($result === true || (is_array($result) && $result['success'] === true)) {
        // 返回更新后的时间
        $formatted_time = is_array($result) ? $result['created_at'] : $formatted_date;
        jsonResponse([
            'success' => true, 
            'message' => '添加时间更新成功',
            'created_at' => $formatted_time
        ]);
    } else {
        $error_msg = is_array($result) ? ($result['message'] ?? '更新失败') : '更新失败';
        jsonResponse(['success' => false, 'message' => $error_msg], 400);
    }
    
} catch (Exception $e) {
    jsonResponse(['error' => '服务器内部错误'], 500);
}
?> 