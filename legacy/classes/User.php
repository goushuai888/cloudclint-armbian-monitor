<?php
/**
 * CloudClint 设备监控平台 - 用户管理类
 * Material Design 3 风格重构版本
 */

class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * 根据用户名获取用户信息
     */
    public function getUserByUsername($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("获取用户失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 根据用户ID获取用户信息
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("获取用户失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 创建新用户
     */
    public function createUser($username, $password, $email = '', $role = 'user') {
        try {
            // 检查用户名是否已存在
            if ($this->getUserByUsername($username)) {
                throw new Exception('用户名已存在');
            }
            
            // 密码哈希
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // 插入用户
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, email, role, is_locked, created_at) 
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            
            $result = $stmt->execute([$username, $hashedPassword, $email, $role]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("创建用户失败: " . $e->getMessage());
            throw new Exception('创建用户失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 更新用户锁定状态
     */
    public function updateUserLockStatus($userId, $isLocked) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET is_locked = ? WHERE id = ?");
            return $stmt->execute([$isLocked, $userId]);
        } catch (PDOException $e) {
            error_log("更新用户锁定状态失败: " . $e->getMessage());
            throw new Exception('更新用户锁定状态失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 更新用户信息
     */
    public function updateUser($userId, $username, $email = '', $role = 'user') {
        try {
            // 检查用户名是否被其他用户使用
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $userId]);
            if ($stmt->fetch()) {
                throw new Exception('用户名已被其他用户使用');
            }
            
            // 更新用户信息
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, role = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            return $stmt->execute([$username, $email, $role, $userId]);
        } catch (PDOException $e) {
            error_log("更新用户信息失败: " . $e->getMessage());
            throw new Exception('更新用户信息失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 更新用户密码
     */
    public function updatePassword($userId, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET password = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (PDOException $e) {
            error_log("更新密码失败: " . $e->getMessage());
            throw new Exception('更新密码失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 验证用户密码
     */
    public function verifyPassword($username, $password) {
        try {
            $user = $this->getUserByUsername($username);
            
            if (!$user) {
                return false;
            }
            
            // 检查用户是否被锁定
            if ($user['is_locked']) {
                throw new Exception('用户账户已被锁定');
            }
            
            // 验证密码
            if (password_verify($password, $user['password'])) {
                return $user;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("验证密码失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取所有用户列表
     */
    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("
                SELECT id, username, email, role, is_locked, created_at, updated_at 
                FROM users 
                ORDER BY id ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("获取用户列表失败: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 删除用户
     */
    public function deleteUser($userId) {
        try {
            // 不能删除自己
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                throw new Exception('不能删除自己的账户');
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("删除用户失败: " . $e->getMessage());
            throw new Exception('删除用户失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查用户是否为管理员
     */
    public function isAdmin($userId) {
        try {
            $user = $this->getUserById($userId);
            return $user && $user['role'] === 'admin';
        } catch (Exception $e) {
            error_log("检查管理员权限失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取用户统计信息
     */
    public function getUserStats() {
        try {
            $stats = [];
            
            // 总用户数
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM users");
            $stats['total'] = $stmt->fetchColumn();
            
            // 管理员数量
            $stmt = $this->pdo->query("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
            $stats['admin_count'] = $stmt->fetchColumn();
            
            // 普通用户数量
            $stmt = $this->pdo->query("SELECT COUNT(*) as user_count FROM users WHERE role = 'user'");
            $stats['user_count'] = $stmt->fetchColumn();
            
            // 锁定用户数量
            $stmt = $this->pdo->query("SELECT COUNT(*) as locked_count FROM users WHERE is_locked = 1");
            $stats['locked_count'] = $stmt->fetchColumn();
            
            // 活跃用户数量
            $stmt = $this->pdo->query("SELECT COUNT(*) as active_count FROM users WHERE is_locked = 0");
            $stats['active_count'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("获取用户统计失败: " . $e->getMessage());
            return [
                'total' => 0,
                'admin_count' => 0,
                'user_count' => 0,
                'locked_count' => 0,
                'active_count' => 0
            ];
        }
    }
    
    /**
     * 确保用户表结构正确
     */
    public function ensureUserTableStructure() {
        try {
            // 检查并创建用户表
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    email VARCHAR(100) DEFAULT '',
                    role ENUM('user', 'admin') DEFAULT 'user',
                    is_locked TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_username (username),
                    INDEX idx_role (role),
                    INDEX idx_is_locked (is_locked)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // 检查是否存在管理员用户，如果不存在则创建默认管理员
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                // 创建默认管理员账户
                $defaultPassword = 'admin123';
                $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, password, email, role, is_locked, created_at) 
                    VALUES ('admin', ?, 'admin@example.com', 'admin', 0, NOW())
                ");
                
                $stmt->execute([$hashedPassword]);
                
                error_log("已创建默认管理员账户: admin / admin123");
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("确保用户表结构失败: " . $e->getMessage());
            return false;
        }
    }
}
?>