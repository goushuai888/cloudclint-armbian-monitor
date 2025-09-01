<?php
/**
 * 心跳API接口
 * 接收Armbian设备的心跳数据
 */

// 引入时区配置
require_once '../config/timezone.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../classes/Device.php';

/**
 * 返回JSON响应
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * 获取客户端IP
 */
function getClientIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
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
    
    // 验证必需字段
    if (empty($data['device_id'])) {
        jsonResponse(['error' => '缺少设备ID'], 400);
    }
    
    // 验证设备ID格式（只允许字母数字和连字符下划线）
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['device_id'])) {
        jsonResponse(['error' => '设备ID格式无效'], 400);
    }
    
    // 验证设备ID长度
    if (strlen($data['device_id']) > 50) {
        jsonResponse(['error' => '设备ID长度不能超过50个字符'], 400);
    }
    
    // 验证数值字段
    $numeric_fields = ['cpu_usage', 'memory_usage', 'disk_usage', 'temperature', 'uptime'];
    foreach ($numeric_fields as $field) {
        if (isset($data[$field]) && !is_numeric($data[$field])) {
            jsonResponse(['error' => "字段 {$field} 必须是数值"], 400);
        }
        if (isset($data[$field]) && ($data[$field] < 0 || $data[$field] > 999999)) {
            jsonResponse(['error' => "字段 {$field} 数值超出范围"], 400);
        }
    }
    
    // 验证设备名称长度
    if (isset($data['device_name']) && strlen($data['device_name']) > 100) {
        jsonResponse(['error' => '设备名称长度不能超过100个字符'], 400);
    }
    
    // 验证IP地址格式
    if (isset($data['ip_address']) && !filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
        jsonResponse(['error' => 'IP地址格式无效'], 400);
    }
    
    // 验证MAC地址格式
    if (isset($data['mac_address']) && !empty($data['mac_address'])) {
        if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data['mac_address'])) {
            jsonResponse(['error' => 'MAC地址格式无效'], 400);
        }
    }
    
    // 数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        jsonResponse(['error' => '数据库连接失败'], 500);
    }
    
    $device = new Device($db);
    
    // 准备设备数据
    $deviceData = [
        'device_id' => $data['device_id'],
        'device_name' => $data['device_name'] ?? 'Armbian Device',
        'ip_address' => $data['ip_address'] ?? getClientIP(),
        'mac_address' => $data['mac_address'] ?? null,
        'system_info' => isset($data['system_info']) ? json_encode($data['system_info']) : null,
        'cpu_usage' => $data['cpu_usage'] ?? 0,
        'memory_usage' => $data['memory_usage'] ?? 0,
        'disk_usage' => $data['disk_usage'] ?? 0,
        'temperature' => $data['temperature'] ?? null,
        'uptime' => $data['uptime'] ?? 0
    ];
    
    // 注册或更新设备
    $registerResult = $device->registerOrUpdate($deviceData);
    
    if ($registerResult['success']) {
        // 记录心跳日志
        $device->logHeartbeat($deviceData);
        
        // 构建响应数据
        $responseData = [
            'success' => true,
            'message' => '心跳接收成功',
            'timestamp' => format_datetime(),
            'device_id' => $data['device_id']
        ];
        
        // 如果是新设备或重新连接的设备，添加额外信息
        if ($registerResult['is_new_device']) {
            $responseData['is_new_device'] = true;
            $responseData['message'] = '设备已重新添加到监控系统';
            
            // 记录设备重新连接日志
            error_log('设备重新连接: ' . $data['device_id'] . ', 名称: ' . $deviceData['device_name'] . ', IP: ' . $deviceData['ip_address']);
        }
        
        jsonResponse($responseData);
    } else {
        jsonResponse(['error' => '设备信息更新失败'], 500);
    }
    
} catch (Exception $e) {
    error_log('Heartbeat API Error: ' . $e->getMessage());
    jsonResponse(['error' => '服务器内部错误'], 500);
}
?>