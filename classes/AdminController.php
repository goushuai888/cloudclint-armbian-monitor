<?php
/**
 * CloudClint 设备监控平台 - 管理控制器
 * 处理系统管理相关的业务逻辑
 */

class AdminController {
    private $db;
    private $auth;
    
    public function __construct($database, $auth) {
        $this->db = $database->getConnection();
        $this->auth = $auth;
    }
    
    public function checkAdminAccess() {
        error_log('checkAdminAccess called');
        if (!$this->auth->isLoggedIn()) {
            error_log('Not logged in, redirecting to login.php');
            header('Location: login.php');
            exit;
        }
        
        $current_user = $this->auth->getCurrentUser();
        error_log('Current user: ' . json_encode($current_user));
        if ($current_user['role'] !== 'admin') {
            error_log('Not admin, redirecting to index.php');
            header('Location: index.php');
            exit;
        }
        
        error_log('Admin access granted');
        return $current_user;
    }
    
    /**
     * 处理添加用户请求
     */
    public function handleAddUser($postData) {
        $result = ['success' => false, 'message' => '', 'token' => ''];
        
        // 验证表单令牌
        if (!isset($postData['token']) || !$this->auth->validateFormToken('add_user_form', $postData['token'])) {
            $result['message'] = '表单已过期或已提交，请重试';
            $result['token'] = $this->auth->generateFormToken('add_user_form');
            return $result;
        }
        
        $username = trim($postData['username'] ?? '');
        $password = $postData['password'] ?? '';
        $email = trim($postData['email'] ?? '');
        $role = $postData['role'] ?? 'user';
        
        // 验证输入
        if (empty($username) || empty($password)) {
            $result['message'] = '用户名和密码不能为空';
            $result['token'] = $this->auth->generateFormToken('add_user_form');
            return $result;
        }
        
        if (strlen($password) < 6) {
            $result['message'] = '密码长度必须至少为6个字符';
            $result['token'] = $this->auth->generateFormToken('add_user_form');
            return $result;
        }
        
        // 创建用户
        $createResult = $this->auth->createUser([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'role' => $role
        ]);
        
        if ($createResult['success']) {
            $result['success'] = true;
            $result['message'] = $createResult['message'];
        } else {
            $result['message'] = $createResult['message'];
            $result['token'] = $this->auth->generateFormToken('add_user_form');
        }
        
        return $result;
    }
    
    /**
     * 处理系统配置更新请求
     */
    public function handleUpdateConfig($postData) {
        $result = ['success' => false, 'message' => '', 'token' => ''];
        
        // 验证表单令牌
        if (!isset($postData['token']) || !$this->auth->validateFormToken('update_config_form', $postData['token'])) {
            $result['message'] = '表单已过期或已提交，请重试';
            $result['token'] = $this->auth->generateFormToken('update_config_form');
            return $result;
        }
        
        $configs = [
            'site_title' => trim($postData['site_title'] ?? ''),
            'heartbeat_timeout' => (int)($postData['heartbeat_timeout'] ?? 90),
            'login_attempts_limit' => (int)($postData['login_attempts_limit'] ?? 5),
            'login_lockout_time' => (int)($postData['login_lockout_time'] ?? 30),
            'session_lifetime' => (int)($postData['session_lifetime'] ?? 120)
        ];
        
        $updateSuccess = true;
        
        foreach ($configs as $key => $value) {
            $query = "UPDATE system_config SET config_value = :value WHERE config_key = :key";
            $stmt = $this->db->prepare($query);
            $updateResult = $stmt->execute([':value' => $value, ':key' => $key]);
            
            if (!$updateResult) {
                $updateSuccess = false;
                break;
            }
        }
        
        if ($updateSuccess) {
            $result['success'] = true;
            $result['message'] = '系统配置更新成功';
        } else {
            $result['message'] = '系统配置更新失败';
            $result['token'] = $this->auth->generateFormToken('update_config_form');
        }
        
        return $result;
    }

    /**
     * 处理编辑用户请求
     */
    public function handleEditUser($postData) {
        // 编辑用户操作使用动态生成的令牌
        $editToken = $this->generateToken('edit_user');
        if (!$this->validateToken($postData['token'] ?? '', 'add_user')) {
            return ['success' => false, 'message' => 'CSRF 令牌验证失败'];
        }
        
        $userId = intval($postData['user_id'] ?? 0);
        $username = trim($postData['username'] ?? '');
        $email = trim($postData['email'] ?? '');
        $role = $postData['role'] ?? 'user';
        $password = $postData['password'] ?? '';
        
        // 验证输入
        if (empty($username) || $userId <= 0) {
            return ['success' => false, 'message' => '用户名和用户ID不能为空'];
        }
        
        if (strlen($username) < 3) {
            return ['success' => false, 'message' => '用户名长度至少3个字符'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['success' => false, 'message' => '用户名只能包含字母、数字和下划线'];
        }
        
        if (!in_array($role, ['user', 'admin'])) {
            return ['success' => false, 'message' => '无效的用户角色'];
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => '邮箱格式不正确'];
        }
        
        if (!empty($password) && strlen($password) < 6) {
            return ['success' => false, 'message' => '密码长度至少6个字符'];
        }
        
        try {
            // 检查用户名是否已存在（排除当前用户）
            $checkQuery = "SELECT id FROM users WHERE username = ? AND id != ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$username, $userId]);
            
            if ($checkStmt->fetch()) {
                return ['success' => false, 'message' => '用户名已存在'];
            }
            
            // 构建更新查询
            $updateFields = ['username = ?', 'email = ?', 'role = ?'];
            $updateValues = [$username, $email, $role];
            
            if (!empty($password)) {
                $updateFields[] = 'password = ?';
                $updateValues[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $updateValues[] = $userId;
            
            $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute($updateValues);
            
            return ['success' => true, 'message' => '用户信息更新成功'];
            
        } catch (Exception $e) {
            error_log('Edit user error: ' . $e->getMessage());
            return ['success' => false, 'message' => '更新用户失败'];
        }
    }
    
    /**
     * 处理切换用户锁定状态请求
     */
    public function handleToggleUserLock($postData) {
        // 切换锁定状态操作使用动态生成的令牌
        if (!$this->validateToken($postData['token'] ?? '', 'add_user')) {
            return ['success' => false, 'message' => 'CSRF 令牌验证失败'];
        }
        
        $userId = intval($postData['user_id'] ?? 0);
        $lockAction = $postData['lock_action'] ?? '';
        
        if ($userId <= 0 || !in_array($lockAction, ['lock', 'unlock'])) {
            return ['success' => false, 'message' => '无效的参数'];
        }
        
        // 防止锁定当前用户
        if ($userId == $_SESSION['user_id']) {
            return ['success' => false, 'message' => '不能锁定当前登录用户'];
        }
        
        try {
            $isLocked = ($lockAction === 'lock') ? 1 : 0;
            $actionText = ($lockAction === 'lock') ? '锁定' : '解锁';
            
            $updateQuery = "UPDATE users SET is_locked = ? WHERE id = ?";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([$isLocked, $userId]);
            
            if ($updateStmt->rowCount() > 0) {
                return ['success' => true, 'message' => "用户${actionText}成功"];
            } else {
                return ['success' => false, 'message' => '用户不存在'];
            }
            
        } catch (Exception $e) {
            error_log('Toggle user lock error: ' . $e->getMessage());
            return ['success' => false, 'message' => '操作失败'];
        }
    }
    
    /**
     * 获取所有用户
     */
    public function getAllUsers() {
        $query = "SELECT * FROM users ORDER BY id ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * 获取系统配置
     */
    public function getSystemConfigs() {
        $query = "SELECT config_key, config_value FROM system_config";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    /**
     * 生成表单令牌
     */
    public function generateTokens() {
        return [
            'add_user_token' => $this->auth->generateFormToken('add_user_form'),
            'update_config_token' => $this->auth->generateFormToken('update_config_form')
        ];
    }

    /**
     * 生成令牌
     */
    public function generateToken($action) {
        return $this->auth->generateFormToken($action . '_form');
    }
    
    /**
     * 验证令牌
     */
    public function validateToken($token, $action) {
        return $this->auth->validateFormToken($action . '_form', $token);
    }
    
    /**
     * 获取用户列表（别名方法）
     */
    public function getUserList() {
        return $this->getAllUsers();
    }
}