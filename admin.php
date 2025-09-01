<?php
/**
 * CloudClint 设备监控平台 - 系统管理页面
 * Material Design 3 风格重构版本
 */

session_start();

// 安全头部
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// 引入必要的类
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/AdminView.php';

// 检查用户是否已登录且为管理员
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// 数据库连接
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (PDOException $e) {
    die('数据库连接失败: ' . $e->getMessage());
}

// 初始化用户类
$userClass = new User($pdo);

// 初始化数据数组
$data = [
    'error' => '',
    'success' => '',
    'users' => [],
    'configs' => [],
    'add_user_token' => bin2hex(random_bytes(32)),
    'update_config_token' => bin2hex(random_bytes(32))
];

// 存储令牌到会话
$_SESSION['add_user_token'] = $data['add_user_token'];
$_SESSION['update_config_token'] = $data['update_config_token'];

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_user':
            handleAddUser($userClass, $data);
            break;
            
        case 'toggle_user_lock':
            handleToggleUserLock($userClass, $data);
            break;
            
        case 'update_config':
            handleUpdateConfig($pdo, $data);
            break;
    }
}

// 获取用户列表
try {
    $stmt = $pdo->query("SELECT id, username, email, role, is_locked FROM users ORDER BY id ASC");
    $data['users'] = $stmt->fetchAll();
} catch (PDOException $e) {
    $data['error'] = '获取用户列表失败: ' . $e->getMessage();
}

// 获取系统配置
try {
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
    $configs = $stmt->fetchAll();
    foreach ($configs as $config) {
        $data['configs'][$config['config_key']] = $config['config_value'];
    }
} catch (PDOException $e) {
    // 如果配置表不存在，使用默认值
    $data['configs'] = [
        'site_title' => 'CloudClint 设备监控平台',
        'heartbeat_timeout' => 90,
        'login_attempts_limit' => 5,
        'login_lockout_time' => 30,
        'session_lifetime' => 120
    ];
}

/**
 * 处理添加用户
 */
function handleAddUser($userClass, &$data) {
    // 验证令牌
    if (!isset($_POST['token']) || !isset($_SESSION['add_user_token']) || 
        !hash_equals($_SESSION['add_user_token'], $_POST['token'])) {
        $data['error'] = '无效的请求令牌';
        return;
    }
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    
    // 验证输入
    if (empty($username) || empty($password)) {
        $data['error'] = '用户名和密码不能为空';
        return;
    }
    
    if (strlen($password) < 6) {
        $data['error'] = '密码长度至少6个字符';
        return;
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        $data['error'] = '无效的用户角色';
        return;
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $data['error'] = '邮箱格式不正确';
        return;
    }
    
    // 检查用户名是否已存在
    if ($userClass->getUserByUsername($username)) {
        $data['error'] = '用户名已存在';
        return;
    }
    
    // 创建用户
    try {
        $result = $userClass->createUser($username, $password, $email, $role);
        if ($result) {
            $data['success'] = '用户创建成功';
            // 重新生成令牌
            $data['add_user_token'] = bin2hex(random_bytes(32));
            $_SESSION['add_user_token'] = $data['add_user_token'];
        } else {
            $data['error'] = '用户创建失败';
        }
    } catch (Exception $e) {
        $data['error'] = '用户创建失败: ' . $e->getMessage();
    }
}

/**
 * 处理用户锁定/解锁
 */
function handleToggleUserLock($userClass, &$data) {
    $userId = intval($_POST['user_id'] ?? 0);
    $lockAction = $_POST['lock_action'] ?? '';
    
    if ($userId <= 0 || !in_array($lockAction, ['lock', 'unlock'])) {
        $data['error'] = '无效的请求参数';
        return;
    }
    
    // 不能锁定自己
    if ($userId == $_SESSION['user_id']) {
        $data['error'] = '不能锁定自己的账户';
        return;
    }
    
    try {
        $isLocked = ($lockAction === 'lock') ? 1 : 0;
        $result = $userClass->updateUserLockStatus($userId, $isLocked);
        
        if ($result) {
            $actionText = ($lockAction === 'lock') ? '锁定' : '解锁';
            $data['success'] = '用户' . $actionText . '成功';
        } else {
            $data['error'] = '操作失败';
        }
    } catch (Exception $e) {
        $data['error'] = '操作失败: ' . $e->getMessage();
    }
}

/**
 * 处理更新系统配置
 */
function handleUpdateConfig($pdo, &$data) {
    // 验证令牌
    if (!isset($_POST['token']) || !isset($_SESSION['update_config_token']) || 
        !hash_equals($_SESSION['update_config_token'], $_POST['token'])) {
        $data['error'] = '无效的请求令牌';
        return;
    }
    
    $configs = [
        'site_title' => trim($_POST['site_title'] ?? ''),
        'heartbeat_timeout' => intval($_POST['heartbeat_timeout'] ?? 90),
        'login_attempts_limit' => intval($_POST['login_attempts_limit'] ?? 5),
        'login_lockout_time' => intval($_POST['login_lockout_time'] ?? 30),
        'session_lifetime' => intval($_POST['session_lifetime'] ?? 120)
    ];
    
    // 验证配置值
    if (empty($configs['site_title'])) {
        $data['error'] = '网站标题不能为空';
        return;
    }
    
    if ($configs['heartbeat_timeout'] < 30 || $configs['heartbeat_timeout'] > 300) {
        $data['error'] = '心跳超时时间必须在30-300秒之间';
        return;
    }
    
    if ($configs['login_attempts_limit'] < 3 || $configs['login_attempts_limit'] > 10) {
        $data['error'] = '登录尝试次数限制必须在3-10次之间';
        return;
    }
    
    if ($configs['login_lockout_time'] < 5 || $configs['login_lockout_time'] > 120) {
        $data['error'] = '登录锁定时间必须在5-120分钟之间';
        return;
    }
    
    if ($configs['session_lifetime'] < 30 || $configs['session_lifetime'] > 480) {
        $data['error'] = '会话生命周期必须在30-480分钟之间';
        return;
    }
    
    try {
        // 确保配置表存在
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                config_key VARCHAR(100) NOT NULL UNIQUE,
                config_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // 更新配置
        $stmt = $pdo->prepare("
            INSERT INTO system_config (config_key, config_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
        ");
        
        foreach ($configs as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        $data['success'] = '系统配置更新成功';
        $data['configs'] = $configs;
        
        // 重新生成令牌
        $data['update_config_token'] = bin2hex(random_bytes(32));
        $_SESSION['update_config_token'] = $data['update_config_token'];
        
    } catch (PDOException $e) {
        $data['error'] = '配置更新失败: ' . $e->getMessage();
    }
}

// 渲染页面
$adminView = new AdminView($data);
$adminView->render();
?>