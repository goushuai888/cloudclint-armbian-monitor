<?php
/**
 * 设备分组管理API
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
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path_info = $_SERVER['PATH_INFO'] ?? '';
    
    switch ($method) {
        case 'GET':
            // 获取所有分组
            if (empty($path_info) || $path_info === '/') {
                $groups = $deviceGroup->getAllGroups();
                jsonResponse(['success' => true, 'data' => $groups]);
            }
            // 获取特定分组
            elseif (preg_match('/^\/(\d+)$/', $path_info, $matches)) {
                $group_id = (int)$matches[1];
                $group = $deviceGroup->getGroupById($group_id);
                if ($group) {
                    jsonResponse(['success' => true, 'data' => $group]);
                } else {
                    jsonResponse(['error' => '分组不存在'], 404);
                }
            }
            else {
                jsonResponse(['error' => '无效的请求路径'], 400);
            }
            break;
            
        case 'POST':
            // 创建新分组
            if (empty($path_info) || $path_info === '/') {
                // 检查管理员权限
                $current_user = $auth->getCurrentUser();
                if ($current_user['role'] !== 'admin') {
                    jsonResponse(['error' => '权限不足'], 403);
                }
                
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    jsonResponse(['error' => 'JSON格式错误'], 400);
                }
                
                // 验证必需字段
                if (empty($data['group_name'])) {
                    jsonResponse(['error' => '分组名称不能为空'], 400);
                }
                
                $result = $deviceGroup->createGroup($data);
                jsonResponse($result, $result['success'] ? 201 : 400);
            }
            else {
                jsonResponse(['error' => '无效的请求路径'], 400);
            }
            break;
            
        case 'PUT':
            // 更新分组
            if (preg_match('/^\/(\d+)$/', $path_info, $matches)) {
                // 检查管理员权限
                $current_user = $auth->getCurrentUser();
                if ($current_user['role'] !== 'admin') {
                    jsonResponse(['error' => '权限不足'], 403);
                }
                
                $group_id = (int)$matches[1];
                
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    jsonResponse(['error' => 'JSON格式错误'], 400);
                }
                
                $result = $deviceGroup->updateGroup($group_id, $data);
                jsonResponse($result, $result['success'] ? 200 : 400);
            }
            else {
                jsonResponse(['error' => '无效的请求路径'], 400);
            }
            break;
            
        case 'DELETE':
            // 删除分组
            if (preg_match('/^\/(\d+)$/', $path_info, $matches)) {
                // 检查管理员权限
                $current_user = $auth->getCurrentUser();
                if ($current_user['role'] !== 'admin') {
                    jsonResponse(['error' => '权限不足'], 403);
                }
                
                $group_id = (int)$matches[1];
                $result = $deviceGroup->deleteGroup($group_id);
                jsonResponse($result, $result['success'] ? 200 : 400);
            }
            else {
                jsonResponse(['error' => '无效的请求路径'], 400);
            }
            break;
            
        default:
            jsonResponse(['error' => '不支持的请求方法'], 405);
            break;
    }
    
} catch (Exception $e) {
    error_log('Device Groups API Error: ' . $e->getMessage());
    jsonResponse(['error' => '服务器内部错误'], 500);
}
?>