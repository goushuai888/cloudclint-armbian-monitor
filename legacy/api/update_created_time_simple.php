<?php
/**
 * 简化版设备添加时间更新API
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// JSON响应函数
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    error_log('=== Simple Update Created Time API 开始 ===');
    
    // 只接受POST请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => '只支持POST请求'], 405);
    }
    
    // 获取POST数据
    $input = file_get_contents('php://input');
    error_log('收到的原始数据: ' . $input);
    
    if (empty($input)) {
        jsonResponse(['error' => '请求体为空'], 400);
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['error' => 'JSON格式错误: ' . json_last_error_msg()], 400);
    }
    
    error_log('解析后的数据: ' . print_r($data, true));
    
    // 验证必需字段
    if (empty($data['device_id'])) {
        jsonResponse(['error' => '缺少设备ID'], 400);
    }
    
    if (empty($data['created_at'])) {
        jsonResponse(['error' => '缺少添加时间'], 400);
    }
    
    // 验证日期格式
    $created_time = date_create_from_format('Y-m-d H:i:s', $data['created_at']);
    if (!$created_time) {
        jsonResponse(['error' => '日期格式无效，请使用YYYY-MM-DD HH:MM:SS格式'], 400);
    }
    
    // 包含数据库配置和类
    require_once '../config/database.php';
    require_once '../classes/Device.php';
    
    // 数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        jsonResponse(['error' => '数据库连接失败'], 500);
    }
    
    $device = new Device($db);
    
    // 更新设备添加时间
    $result = $device->updateCreatedTime($data['device_id'], $data['created_at']);
    
    if ($result === true || (is_array($result) && $result['success'] === true)) {
        jsonResponse([
            'success' => true, 
            'message' => '添加时间更新成功',
            'created_at' => $data['created_at']
        ]);
    } else {
        $error_msg = is_array($result) ? ($result['message'] ?? '更新失败') : '更新失败';
        jsonResponse(['success' => false, 'message' => $error_msg], 400);
    }
    
} catch (Exception $e) {
    error_log('Simple Update Created Time API Error: ' . $e->getMessage());
    jsonResponse(['error' => '服务器内部错误: ' . $e->getMessage()], 500);
}
?>