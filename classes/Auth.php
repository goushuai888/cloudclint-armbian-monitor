<?php
/**
 * 用户认证类
 * Armbian设备监控平台
 */

// 引入时区配置
require_once __DIR__ . '/../config/timezone.php';

class Auth {
    private $conn;
    private $table_name = "users";
    private $log_table = "login_logs";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 用户登录
     * @param string $username 用户名
     * @param string $password 密码
     * @return array 登录结果
     */
    public function login($username, $password) {
        // 检查用户名是否存在
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':username' => $username]);
        
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => '用户名或密码错误'];
        }
        
        // 检查账户状态
        if ($user['status'] === 'locked') {
            return ['success' => false, 'message' => '账户已被锁定，请联系管理员'];
        }
        
        if ($user['status'] === 'inactive') {
            return ['success' => false, 'message' => '账户未激活，请联系管理员'];
        }
        
        // 验证密码
        if (password_verify($password, $user['password'])) {
            // 密码正确，创建会话
            // 重新生成会话ID防止会话固定攻击
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // 更新最后登录时间
            $update_query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->execute([':id' => $user['id']]);
            
            // 记录登录成功日志
            $this->logLogin($user['id'], $user['username'], 'success');
            
            return [
                'success' => true, 
                'message' => '登录成功', 
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ];
        } else {
            // 密码错误，记录失败日志
            $this->logLogin($user['id'], $user['username'], 'failed');
            
            // 检查失败次数，可能需要锁定账户
            $this->checkFailedAttempts($user['id'], $user['username']);
            
            return ['success' => false, 'message' => '用户名或密码错误'];
        }
    }
    
    /**
     * 记录登录日志
     */
    private function logLogin($user_id, $username, $status) {
        $ip_address = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $query = "INSERT INTO " . $this->log_table . " 
                  (user_id, username, ip_address, user_agent, status) 
                  VALUES 
                  (:user_id, :username, :ip_address, :user_agent, :status)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':username' => $username,
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent,
            ':status' => $status
        ]);
    }
    
    /**
     * 检查失败登录尝试次数
     */
    private function checkFailedAttempts($user_id, $username) {
        // 获取配置的失败次数限制
        $config_query = "SELECT config_value FROM system_config WHERE config_key = 'login_attempts_limit'";
        $config_stmt = $this->conn->prepare($config_query);
        $config_stmt->execute();
        $attempts_limit = (int)$config_stmt->fetchColumn() ?: 5;
        
        // 获取锁定时间
        $lockout_query = "SELECT config_value FROM system_config WHERE config_key = 'login_lockout_time'";
        $lockout_stmt = $this->conn->prepare($lockout_query);
        $lockout_stmt->execute();
        $lockout_time = (int)$lockout_stmt->fetchColumn() ?: 30; // 默认30分钟
        
        // 检查最近失败次数
        $recent_time = date('Y-m-d H:i:s', strtotime("-{$lockout_time} minutes"));
        $attempts_query = "SELECT COUNT(*) FROM " . $this->log_table . " 
                          WHERE user_id = :user_id 
                          AND status = 'failed' 
                          AND created_at > :recent_time";
        
        $attempts_stmt = $this->conn->prepare($attempts_query);
        $attempts_stmt->execute([
            ':user_id' => $user_id,
            ':recent_time' => $recent_time
        ]);
        
        $failed_attempts = (int)$attempts_stmt->fetchColumn();
        
        // 如果失败次数超过限制，锁定账户
        if ($failed_attempts >= $attempts_limit) {
            $lock_query = "UPDATE " . $this->table_name . " 
                          SET status = 'locked' 
                          WHERE id = :user_id";
            
            $lock_stmt = $this->conn->prepare($lock_query);
            $lock_stmt->execute([':user_id' => $user_id]);
            
            // 记录锁定日志
            error_log("User {$username} (ID: {$user_id}) has been locked due to {$failed_attempts} failed login attempts");
        }
    }
    
    /**
     * 用户注销
     */
    public function logout() {
        // 清除会话数据
        $_SESSION = [];
        
        // 如果使用了会话cookie，清除它
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // 销毁会话
        session_destroy();
        
        return ['success' => true, 'message' => '已成功注销'];
    }
    
    /**
     * 检查用户是否已登录
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            return false;
        }
        
        // 使用checkUserStatus方法检查用户状态
        $status = $this->checkUserStatus();
        return $status['valid'];
    }
    
    /**
     * 检查会话是否过期
     */
    private function isSessionExpired() {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        // 获取会话生存时间配置
        $config_query = "SELECT config_value FROM system_config WHERE config_key = 'session_lifetime'";
        $config_stmt = $this->conn->prepare($config_query);
        $config_stmt->execute();
        $session_lifetime = (int)$config_stmt->fetchColumn() ?: 120; // 默认120分钟
        
        // 检查是否超过生存时间
        if (time() - $_SESSION['last_activity'] > $session_lifetime * 60) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 检查用户当前状态
     * 用于实时验证用户是否被锁定或停用
     * 
     * @return array 包含状态信息的数组
     */
    public function checkUserStatus() {
        if (!isset($_SESSION['user_id'])) {
            return ['valid' => false, 'message' => '用户未登录'];
        }
        
        // 查询用户当前状态
        $query = "SELECT status FROM " . $this->table_name . " WHERE id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        
        $user_status = $stmt->fetchColumn();
        
        if ($user_status === 'locked') {
            // 用户已被锁定，清除会话
            $this->logout();
            return ['valid' => false, 'message' => '您的账户已被锁定，请联系管理员'];
        }
        
        if ($user_status === 'inactive') {
            // 用户已被停用，清除会话
            $this->logout();
            return ['valid' => false, 'message' => '您的账户已被停用，请联系管理员'];
        }
        
        // 检查会话是否过期
        if ($this->isSessionExpired()) {
            $this->logout();
            return ['valid' => false, 'message' => '会话已过期，请重新登录'];
        }
        
        // 更新最后活动时间
        $_SESSION['last_activity'] = time();
        
        return ['valid' => true];
    }
    
    /**
     * 检查用户是否有特定角色
     */
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['role'] === $role;
    }
    
    /**
     * 获取当前登录用户信息
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $query = "SELECT id, username, email, role, last_login, status, created_at 
                 FROM " . $this->table_name . " 
                 WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $_SESSION['user_id']]);
        
        return $stmt->fetch();
    }
    
    /**
     * 获取客户端IP
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * 创建新用户（仅限管理员）
     */
    public function createUser($userData) {
        // 检查是否为管理员
        if (!$this->hasRole('admin')) {
            return ['success' => false, 'message' => '权限不足'];
        }
        
        // 验证用户名是否已存在
        $check_query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE username = :username";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->execute([':username' => $userData['username']]);
        
        if ($check_stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => '用户名已存在'];
        }
        
        // 验证邮箱是否已存在
        if (!empty($userData['email'])) {
            $email_query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE email = :email";
            $email_stmt = $this->conn->prepare($email_query);
            $email_stmt->execute([':email' => $userData['email']]);
            
            if ($email_stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => '邮箱已被使用'];
            }
        }
        
        // 创建用户
        $query = "INSERT INTO " . $this->table_name . " 
                 (username, password, email, role, status) 
                 VALUES 
                 (:username, :password, :email, :role, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        // 哈希密码
        $password_hash = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $result = $stmt->execute([
            ':username' => $userData['username'],
            ':password' => $password_hash,
            ':email' => $userData['email'] ?? null,
            ':role' => $userData['role'] ?? 'user',
            ':status' => $userData['status'] ?? 'active'
        ]);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => '用户创建成功',
                'user_id' => $this->conn->lastInsertId()
            ];
        } else {
            return ['success' => false, 'message' => '用户创建失败'];
        }
    }
    
    /**
     * 修改密码
     */
    public function changePassword($user_id, $current_password, $new_password) {
        // 检查当前用户是否有权限
        $current_user = $this->getCurrentUser();
        
        if (!$current_user) {
            return ['success' => false, 'message' => '未登录'];
        }
        
        // 只有自己或管理员可以修改密码
        if ($current_user['id'] != $user_id && $current_user['role'] !== 'admin') {
            return ['success' => false, 'message' => '权限不足'];
        }
        
        // 获取用户信息
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $user_id]);
        
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }
        
        // 如果不是管理员，需要验证当前密码
        if ($current_user['role'] !== 'admin' || $current_user['id'] == $user_id) {
            if (!password_verify($current_password, $user['password'])) {
                return ['success' => false, 'message' => '当前密码错误'];
            }
        }
        
        // 更新密码
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE " . $this->table_name . " 
                        SET password = :password 
                        WHERE id = :id";
        
        $update_stmt = $this->conn->prepare($update_query);
        $result = $update_stmt->execute([
            ':password' => $password_hash,
            ':id' => $user_id
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => '密码修改成功'];
        } else {
            return ['success' => false, 'message' => '密码修改失败'];
        }
    }
    
    /**
     * 生成防重复提交令牌
     * @param string $form_name 表单名称
     * @return string 令牌
     */
    public function generateFormToken($form_name) {
        // 创建一个唯一的令牌
        $token = bin2hex(random_bytes(32));
        
        // 存储令牌到会话中
        if (!isset($_SESSION['form_tokens'])) {
            $_SESSION['form_tokens'] = [];
        }
        
        $_SESSION['form_tokens'][$form_name] = [
            'token' => $token,
            'time' => time()
        ];
        
        return $token;
    }
    
    /**
     * 验证防重复提交令牌
     * @param string $form_name 表单名称
     * @param string $token 令牌
     * @param int $expiry_time 令牌过期时间（秒）
     * @return bool 是否有效
     */
    public function validateFormToken($form_name, $token, $expiry_time = 3600) {
        error_log("验证表单令牌: $form_name, 提交的令牌: $token");
        
        // 检查令牌是否存在
        if (!isset($_SESSION['form_tokens'][$form_name])) {
            error_log("表单令牌验证失败: 会话中不存在此表单的令牌 ($form_name)");
            return false;
        }
        
        $stored_token = $_SESSION['form_tokens'][$form_name];
        error_log("存储的令牌: " . $stored_token['token'] . ", 时间: " . date('Y-m-d H:i:s', $stored_token['time']));
        
        // 检查令牌是否过期
        if (time() - $stored_token['time'] > $expiry_time) {
            // 令牌过期，删除它
            error_log("表单令牌已过期: " . (time() - $stored_token['time']) . "秒 > $expiry_time 秒");
            unset($_SESSION['form_tokens'][$form_name]);
            return false;
        }
        
        // 检查令牌是否匹配
        if ($stored_token['token'] !== $token) {
            error_log("表单令牌不匹配: 存储={$stored_token['token']}, 提交=$token");
            return false;
        }
        
        // 令牌有效，使用后删除（一次性使用）
        error_log("表单令牌验证成功，删除使用过的令牌");
        unset($_SESSION['form_tokens'][$form_name]);
        
        return true;
    }
    
    /**
     * 更新用户信息（仅限管理员）
     * 
     * @param int $user_id 用户ID
     * @param array $userData 用户数据
     * @return array 操作结果
     */
    public function updateUser($user_id, $userData) {
        // 检查是否为管理员
        if (!$this->hasRole('admin')) {
            return ['success' => false, 'message' => '权限不足'];
        }
        
        // 验证用户是否存在
        $check_query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->execute([':id' => $user_id]);
        
        $user = $check_stmt->fetch();
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }
        
        // 如果要更新用户名，检查是否已存在
        if (!empty($userData['username']) && $userData['username'] !== $user['username']) {
            $username_query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE username = :username AND id != :id";
            $username_stmt = $this->conn->prepare($username_query);
            $username_stmt->execute([
                ':username' => $userData['username'],
                ':id' => $user_id
            ]);
            
            if ($username_stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => '用户名已存在'];
            }
        }
        
        // 如果要更新邮箱，检查是否已存在
        if (!empty($userData['email']) && $userData['email'] !== $user['email']) {
            $email_query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE email = :email AND id != :id";
            $email_stmt = $this->conn->prepare($email_query);
            $email_stmt->execute([
                ':email' => $userData['email'],
                ':id' => $user_id
            ]);
            
            if ($email_stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => '邮箱已被使用'];
            }
        }
        
        // 构建更新字段
        $update_fields = [];
        $params = [':id' => $user_id];
        
        // 用户名
        if (!empty($userData['username'])) {
            $update_fields[] = "username = :username";
            $params[':username'] = $userData['username'];
        }
        
        // 邮箱
        if (isset($userData['email'])) {
            $update_fields[] = "email = :email";
            $params[':email'] = $userData['email'];
        }
        
        // 角色
        if (!empty($userData['role']) && in_array($userData['role'], ['admin', 'user'])) {
            $update_fields[] = "role = :role";
            $params[':role'] = $userData['role'];
        }
        
        // 状态
        if (!empty($userData['status']) && in_array($userData['status'], ['active', 'inactive', 'locked'])) {
            $update_fields[] = "status = :status";
            $params[':status'] = $userData['status'];
        }
        
        // 密码（如果提供了新密码）
        if (!empty($userData['password'])) {
            $update_fields[] = "password = :password";
            $params[':password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        // 如果没有要更新的字段，返回成功
        if (empty($update_fields)) {
            return ['success' => true, 'message' => '没有字段需要更新'];
        }
        
        // 构建并执行更新查询
        $update_query = "UPDATE " . $this->table_name . " SET " . implode(", ", $update_fields) . " WHERE id = :id";
        $update_stmt = $this->conn->prepare($update_query);
        $result = $update_stmt->execute($params);
        
        if ($result) {
            return ['success' => true, 'message' => '用户信息更新成功'];
        } else {
            return ['success' => false, 'message' => '用户信息更新失败'];
        }
    }
    
    /**
     * 锁定或解锁用户（仅限管理员）
     * 
     * @param int $user_id 用户ID
     * @param string $action 操作类型：'lock'或'unlock'
     * @return array 操作结果
     */
    public function toggleUserLock($user_id, $action) {
        error_log("开始执行toggleUserLock: user_id=$user_id, action=$action");
        
        // 检查是否为管理员
        if (!$this->hasRole('admin')) {
            error_log("toggleUserLock失败: 当前用户不是管理员");
            return ['success' => false, 'message' => '权限不足'];
        }
        
        // 验证操作类型
        if (!in_array($action, ['lock', 'unlock'])) {
            error_log("toggleUserLock失败: 无效的操作类型 - $action");
            return ['success' => false, 'message' => '无效的操作类型'];
        }
        
        try {
            // 验证用户是否存在
            $check_query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute([$user_id]);
            
            $user = $check_stmt->fetch();
            if (!$user) {
                error_log("toggleUserLock失败: 用户不存在 - user_id=$user_id");
                return ['success' => false, 'message' => '用户不存在'];
            }
            
            // 防止管理员锁定自己
            if ($user['id'] == $_SESSION['user_id']) {
                error_log("toggleUserLock失败: 尝试锁定当前登录账户");
                return ['success' => false, 'message' => '不能锁定当前登录的账户'];
            }
            
            // 设置新状态
            $new_status = ($action === 'lock') ? 'locked' : 'active';
            
            // 如果状态已经是目标状态，则无需更改
            if ($user['status'] === $new_status) {
                $message = ($action === 'lock') ? '用户已经是锁定状态' : '用户已经是正常状态';
                error_log("toggleUserLock: 用户状态已经是 $new_status，无需更改");
                return ['success' => true, 'message' => $message, 'no_change' => true];
            }
            
            // 更新用户状态
            $update_query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $result = $update_stmt->execute([$new_status, $user_id]);
            
            if ($result) {
                $message = ($action === 'lock') ? '用户已锁定' : '用户已解锁';
                error_log("toggleUserLock成功: 用户状态已更新为 $new_status");
                return [
                    'success' => true, 
                    'message' => $message,
                    'new_status' => $new_status,
                    'user_id' => $user_id
                ];
            } else {
                error_log("toggleUserLock失败: 数据库更新失败");
                return ['success' => false, 'message' => '操作失败: 数据库更新错误'];
            }
        } catch (PDOException $e) {
            error_log("toggleUserLock数据库错误: " . $e->getMessage());
            return ['success' => false, 'message' => '数据库错误: ' . $e->getMessage()];
        }
    }
}
?> 