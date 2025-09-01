<?php
/**
 * 移动设备到分组API
 * CloudClint 设备监控平台
 */

// 引入时区配置
require_once '../config/timezone.php';

header('Content-Type: application/json; charset=utf-8');
// 限制CORS来源，生产环境应该指定具体域名
$allowed_origins = [
    'https://ztao.langne.com',
    'http://localhost:8080',
    'http://127.0.0.1:8080'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://ztao.langne.com');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Access-Control-Allow-Credentials: true');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/DeviceGroup.php';

/**
 * 返回JSON响应
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// 开始会话
session_start();

try {
    // 只接受POST请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => '只支持POST请求'], 405);
    }
    
    // 数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        jsonResponse(['error' => '数据库连接失败'], 500);
    }
    
    $auth = new Auth($db);
    $deviceGroup = new DeviceGroup($db);
    
    // 检查用户是否已登录
    if (!$auth->isLoggedIn()) {
        jsonResponse(['error' => '未登录或会话已过期'], 401);
    }
    
    // 检查管理员权限
    $current_user = $auth->getCurrentUser();
    if ($current_user['role'] !== 'admin') {
        jsonResponse(['error' => '权限不足'], 403);
    }
    
    // 获取POST数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['error' => 'JSON格式错误'], 400);
    }
    
    // 验证必需字段
    if (empty($data['device_id'])) {
        jsonResponse(['error' => '设备ID不能为空'], 400);
    }
    
    if (!isset($data['group_id'])) {
        jsonResponse(['error' => '分组ID参数缺失'], 400);
    }
    
    $device_id = $data['device_id'];
    $group_id = $data['group_id'] === null ? null : (int)$data['group_id'];
    
    // 移动设备到分组
    $result = $deviceGroup->moveDeviceToGroup($device_id, $group_id);
    
    jsonResponse($result, $result['success'] ? 200 : 400);
    
} catch (Exception $e) {
    error_log('Move Device Group API Error: ' . $e->getMessage());
    jsonResponse(['error' => '服务器内部错误'], 500);
}
?>