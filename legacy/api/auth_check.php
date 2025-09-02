<?php
/**
 * API认证检查
 * 用于所有需要认证的API接口
 */

// 开始会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 引入时区配置和数据库连接
require_once __DIR__ . '/../config/timezone.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';

/**
 * 返回JSON响应
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * 检查用户认证和状态
 */
function checkAuth() {
    // 数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        jsonResponse(['error' => '数据库连接失败'], 500);
    }
    
    $auth = new Auth($db);
    
    // 检查用户状态
    $status = $auth->checkUserStatus();
    
    if (!$status['valid']) {
        jsonResponse([
            'success' => false,
            'auth_error' => true,
            'message' => $status['message']
        ], 401);
    }
    
    return $auth;
} 