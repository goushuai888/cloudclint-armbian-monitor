<?php
/**
 * 锁定/解锁用户API
 * 
 * 请求参数:
 * - user_id: 用户ID
 * - action: 操作类型 ('lock' 或 'unlock')
 * 
 * 返回JSON:
 * - success: 是否成功
 * - message: 消息
 * - new_status: 新状态 (如果成功)
 * - user_id: 用户ID (如果成功)
 */

// 设置错误日志记录
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 引入必要的文件
require_once 'auth_check.php';

// 处理OPTIONS请求（用于CORS预检）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
    exit;
}

// 获取请求数据
$user_id = $_POST['user_id'] ?? '';
$action = $_POST['action'] ?? '';


// 验证参数
if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => '缺少用户ID参数']);
    exit;
}

if (empty($action) || !in_array($action, ['lock', 'unlock'])) {
    echo json_encode(['success' => false, 'message' => '缺少或无效的操作类型参数']);
    exit;
}

try {
    // 检查用户认证和状态
    $auth = checkAuth();
    
    // 检查是否为管理员
    if (!$auth->hasRole('admin')) {
        echo json_encode(['success' => false, 'message' => '权限不足']);
        exit;
    }
    
    // 执行锁定/解锁操作
    $result = $auth->toggleUserLock($user_id, $action);
    
    // 返回结果
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 