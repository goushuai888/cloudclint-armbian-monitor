<?php
/**
 * 更新用户信息API
 * 
 * 请求参数:
 * - user_id: 用户ID
 * - username: 用户名
 * - email: 邮箱
 * - role: 角色
 * - status: 状态
 * - password: 新密码（可选）
 * 
 * 返回JSON:
 * - success: 是否成功
 * - message: 消息
 */

// 开始会话
session_start();

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 引入必要的文件
require_once '../config/database.php';
require_once '../classes/Auth.php';

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
    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 如果使用表单提交，合并POST数据
    if (empty($input)) {
        $input = $_POST;
    }
    
    // 验证必要参数
    if (empty($input['user_id'])) {
        throw new Exception('缺少用户ID参数');
    }
    
    // 数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('数据库连接失败');
    }
    
    // 创建认证对象
    $auth = new Auth($db);
    
    // 检查是否为管理员
    if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
        throw new Exception('权限不足');
    }
    
    // 更新用户信息
    $userData = [
        'username' => $input['username'] ?? '',
        'email' => $input['email'] ?? '',
        'role' => $input['role'] ?? '',
        'status' => $input['status'] ?? '',
    ];
    
    // 如果提供了密码，则更新密码
    if (!empty($input['password'])) {
        $userData['password'] = $input['password'];
    }
    
    $result = $auth->updateUser($input['user_id'], $userData);
    
    // 如果成功更新，获取更新后的用户信息
    if ($result['success']) {
        // 获取更新后的用户信息
        $user_query = "SELECT * FROM users WHERE id = :id";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([':id' => $input['user_id']]);
        $updated_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($updated_user) {
            // 添加用户信息到响应中
            $result['user'] = [
                'id' => $updated_user['id'],
                'username' => $updated_user['username'],
                'email' => $updated_user['email'],
                'role' => $updated_user['role'],
                'status' => $updated_user['status']
            ];
        }
    }
    
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