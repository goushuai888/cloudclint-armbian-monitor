<?php
/**
 * 错误日志API
 * 用于前端记录错误日志
 */

// 引入时区配置
require_once '../config/timezone.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * 返回JSON响应
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // 只接受POST请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => '只支持POST请求'], 405);
    }
    
    // 获取POST数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['error' => 'JSON格式错误'], 400);
    }
    
    // 记录错误日志
    $message = isset($data['message']) ? $data['message'] : 'Unknown error';
    $source = isset($data['source']) ? $data['source'] : 'frontend';
    $details = isset($data['details']) ? $data['details'] : '{}';
    
    // 写入日志，使用统一的时间格式化函数
    $log_entry = format_datetime() . " - [{$source}] - {$message} - " . json_encode($details, JSON_UNESCAPED_UNICODE) . "\n";
    error_log($log_entry);
    
    jsonResponse([
        'success' => true,
        'message' => '错误已记录'
    ]);
    
} catch (Exception $e) {
    error_log('Error Log API Error: ' . $e->getMessage());
    jsonResponse(['error' => '服务器内部错误'], 500);
}
?> 