<?php
/**
 * 数据库配置文件
 * Armbian小盒子在线检测平台
 */

// 引入时区配置
require_once __DIR__ . '/timezone.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // 从环境变量读取数据库配置，如果没有则使用默认值
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'armbian_monitor';
        $this->username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '';
        
        // 如果环境变量不存在，尝试从配置文件读取
        if (file_exists(__DIR__ . '/db_config.php')) {
            $db_config = include __DIR__ . '/db_config.php';
            $this->host = $db_config['host'] ?? $this->host;
            $this->db_name = $db_config['db_name'] ?? $this->db_name;
            $this->username = $db_config['username'] ?? $this->username;
            $this->password = $db_config['password'] ?? $this->password;
        }
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true,  // 启用持久连接
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            
            // 性能优化设置
            $this->conn->exec("SET time_zone = '" . MYSQL_TIMEZONE . "'");
            $this->conn->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            // 注意：MySQL 8.0已移除query_cache功能，不再需要设置query_cache_type
            
        } catch(PDOException $exception) {
            // 不要在生产环境中暴露详细的数据库错误信息
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("数据库连接错误: " . $exception->getMessage());
                echo "数据库连接错误: " . $exception->getMessage();
            } else {
                error_log("数据库连接失败: " . $exception->getMessage());
                echo "数据库连接失败，请检查配置";
            }
        }
        
        return $this->conn;
    }
}
?>