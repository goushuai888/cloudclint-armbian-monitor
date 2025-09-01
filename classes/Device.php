<?php
/**
 * 设备管理类
 * Armbian小盒子在线检测平台
 */

// 引入时区配置
require_once __DIR__ . '/../config/timezone.php';
require_once __DIR__ . '/../classes/ConnectionPool.php';

class Device {
    private $pool;
    private $table_name = "devices";
    private $heartbeat_table = "heartbeat_logs";
    
    // 缓存机制
    private static $cache = [];
    private static $cache_ttl = 300; // 5分钟缓存
    
    public function __construct($db = null) {
        if ($db) {
            // 兼容旧的构造方式
            $this->conn = $db;
        } else {
            // 使用新的连接池
            $this->pool = ConnectionPool::getInstance();
        }
    }
    
    /**
     * 获取数据库连接
     */
    private function getConnection() {
        if (isset($this->conn)) {
            return $this->conn;
        }
        return $this->pool->getConnection();
    }
    
    /**
     * 释放数据库连接
     */
    private function releaseConnection($conn) {
        if (isset($this->pool) && $conn !== $this->conn) {
            $this->pool->releaseConnection($conn);
        }
    }
    
    /**
     * 缓存辅助方法
     */
    private function getCacheKey($method, $params = []) {
        return md5($method . serialize($params));
    }
    
    private function getFromCache($key) {
        if (isset(self::$cache[$key])) {
            $data = self::$cache[$key];
            if (time() - $data['timestamp'] < self::$cache_ttl) {
                return $data['value'];
            }
            unset(self::$cache[$key]);
        }
        return null;
    }
    
    private function setCache($key, $value) {
        self::$cache[$key] = [
            'value' => $value,
            'timestamp' => time()
        ];
    }
    
    private function clearCache() {
        self::$cache = [];
    }
    
    /**
     * 检查设备是否存在
     */
    public function deviceExists($device_id) {
        $conn = $this->getConnection();
        try {
            $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE device_id = :device_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':device_id' => $device_id]);
            return (int)$stmt->fetchColumn() > 0;
        } finally {
            $this->releaseConnection($conn);
        }
    }
    
    /**
     * 备份设备信息
     */
    private function backupDeviceInfo($device_id) {
        try {
            // 获取设备信息
            $device = $this->getDeviceById($device_id);
            if (!$device) {
                return false;
            }
            
            // 备份设备信息
            $query = "INSERT INTO device_backup 
                      (device_id, device_name, remarks, order_number) 
                      VALUES 
                      (:device_id, :device_name, :remarks, :order_number)
                      ON DUPLICATE KEY UPDATE
                      device_name = VALUES(device_name),
                      remarks = VALUES(remarks),
                      order_number = VALUES(order_number),
                      deleted_at = NOW()";
            
            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            return $stmt->execute([
                ':device_id' => $device_id,
                ':device_name' => $device['device_name'],
                ':remarks' => $device['remarks'] ?? null,
                ':order_number' => $device['order_number'] ?? 0
            ]);
        } catch (Exception $e) {
            error_log('设备备份错误: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 恢复设备信息
     */
    private function restoreDeviceInfo($device_id) {
        try {
            // 检查是否有备份信息
            $conn = $this->getConnection();
            $query = "SELECT * FROM device_backup WHERE device_id = :device_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':device_id' => $device_id]);
            $backup = $stmt->fetch();
            
            if (!$backup) {
                $this->releaseConnection($conn);
                return false;
            }
            
            // 恢复备注和编号
            $update_query = "UPDATE " . $this->table_name . " 
                            SET remarks = :remarks, order_number = :order_number
                            WHERE device_id = :device_id";
            
            $update_stmt = $conn->prepare($update_query);
            $result = $update_stmt->execute([
                ':device_id' => $device_id,
                ':remarks' => $backup['remarks'],
                ':order_number' => $backup['order_number']
            ]);
            
            if ($result) {
                error_log('设备信息已恢复: ' . $device_id . ', 备注: ' . ($backup['remarks'] ? '有' : '无') . ', 编号: ' . $backup['order_number']);
            }
            
            $this->releaseConnection($conn);
            return $result;
        } catch (Exception $e) {
            error_log('设备恢复错误: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 注册或更新设备信息
     * @return array 包含操作结果和是否为新设备的信息
     */
    public function registerOrUpdate($data) {
        // 检查设备是否已存在
        $is_new_device = !$this->deviceExists($data['device_id']);
        
        $conn = $this->getConnection();
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (device_id, device_name, ip_address, mac_address, system_info, 
                       cpu_usage, memory_usage, disk_usage, temperature, uptime, 
                       status, last_heartbeat) 
                      VALUES 
                      (:device_id, :device_name, :ip_address, :mac_address, :system_info, 
                       :cpu_usage, :memory_usage, :disk_usage, :temperature, :uptime, 
                       'online', NOW()) 
                      ON DUPLICATE KEY UPDATE 
                      device_name = VALUES(device_name),
                      ip_address = VALUES(ip_address),
                      mac_address = VALUES(mac_address),
                      system_info = VALUES(system_info),
                      cpu_usage = VALUES(cpu_usage),
                      memory_usage = VALUES(memory_usage),
                      disk_usage = VALUES(disk_usage),
                      temperature = VALUES(temperature),
                      uptime = VALUES(uptime),
                      status = 'online',
                      last_heartbeat = NOW(),
                      updated_at = NOW()";
            
            $stmt = $conn->prepare($query);
        
        $result = $stmt->execute([
            ':device_id' => $data['device_id'],
            ':device_name' => $data['device_name'] ?? 'Unknown Device',
            ':ip_address' => $data['ip_address'] ?? null,
            ':mac_address' => $data['mac_address'] ?? null,
            ':system_info' => $data['system_info'] ?? null,
            ':cpu_usage' => $data['cpu_usage'] ?? 0,
            ':memory_usage' => $data['memory_usage'] ?? 0,
            ':disk_usage' => $data['disk_usage'] ?? 0,
            ':temperature' => $data['temperature'] ?? null,
            ':uptime' => $data['uptime'] ?? 0
        ]);
        
        // 如果是新设备，尝试恢复之前的备注和编号
        if ($is_new_device && $result) {
            $this->restoreDeviceInfo($data['device_id']);
        }
        
            return [
                'success' => $result,
                'is_new_device' => $is_new_device
            ];
        } finally {
            $this->releaseConnection($conn);
        }
    }
    
    /**
     * 获取所有设备 - 带缓存优化
     */
    public function getAllDevices() {
        $cacheKey = $this->getCacheKey('getAllDevices');
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // 修改查询，按照在线状态和编号排序
        $conn = $this->getConnection();
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY status='online' DESC, order_number ASC, last_heartbeat DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll();
            $this->setCache($cacheKey, $result);
            
            return $result;
        } finally {
            $this->releaseConnection($conn);
        }
    }
    
    /**
     * 根据ID获取设备
     */
    public function getDeviceById($device_id) {
        $conn = $this->getConnection();
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE device_id = :device_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':device_id' => $device_id]);
            return $stmt->fetch();
        } finally {
            $this->releaseConnection($conn);
        }
    }
    
    /**
     * 更新设备状态
     */
    public function updateDeviceStatus() {
        $conn = $this->getConnection();
        try {
            // 获取心跳超时时间
            $timeout_query = "SELECT config_value FROM system_config WHERE config_key = 'heartbeat_timeout'";
            $timeout_stmt = $conn->prepare($timeout_query);
            $timeout_stmt->execute();
            $timeout = $timeout_stmt->fetchColumn() ?: 90;
            
            // 更新离线设备
            $query = "UPDATE " . $this->table_name . " 
                      SET status = 'offline' 
                      WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL :timeout SECOND) 
                      AND status != 'offline'";
            
            $stmt = $conn->prepare($query);
            return $stmt->execute([':timeout' => $timeout]);
        } finally {
            $this->releaseConnection($conn);
        }
    }
    
    /**
     * 记录心跳日志
     */
    public function logHeartbeat($data) {
        $conn = $this->getConnection();
        try {
            $query = "INSERT INTO " . $this->heartbeat_table . " 
                      (device_id, ip_address, cpu_usage, memory_usage, disk_usage, temperature, uptime) 
                      VALUES 
                      (:device_id, :ip_address, :cpu_usage, :memory_usage, :disk_usage, :temperature, :uptime)";
            
            $stmt = $conn->prepare($query);
        
            return $stmt->execute([
                ':device_id' => $data['device_id'],
                ':ip_address' => $data['ip_address'] ?? null,
                ':cpu_usage' => $data['cpu_usage'] ?? null,
                ':memory_usage' => $data['memory_usage'] ?? null,
                ':disk_usage' => $data['disk_usage'] ?? null,
                ':temperature' => $data['temperature'] ?? null,
                ':uptime' => $data['uptime'] ?? null
            ]);
        } finally {
            $this->releaseConnection($conn);
        }
    }
    
    /**
     * 获取设备统计信息 - 带缓存优化
     */
    public function getDeviceStats() {
        // 先强制更新设备状态
        $this->updateDeviceStatus();
        
        $conn = $this->getConnection();
        try {
            $query = "SELECT 
                        COUNT(*) as total_devices,
                        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_devices,
                        SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_devices
                      FROM " . $this->table_name;
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            // 确保返回的数据类型正确
            if ($result) {
                $result['total_devices'] = (int)$result['total_devices'];
                $result['online_devices'] = (int)$result['online_devices'];
                $result['offline_devices'] = (int)$result['offline_devices'];
            }
            
            return $result;
        } finally {
            $this->releaseConnection($conn);
        }
    }
    
    /**
     * 删除设备（仅限离线设备）
     */
    public function deleteDevice($device_id) {
        $conn = $this->getConnection();
        try {
            // 首先检查设备状态
            $check_query = "SELECT status FROM " . $this->table_name . " WHERE device_id = :device_id";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([':device_id' => $device_id]);
            $device = $check_stmt->fetch();
        
        if (!$device) {
            return ['success' => false, 'message' => '设备不存在'];
        }
        
        if ($device['status'] === 'online') {
            return ['success' => false, 'message' => '在线设备不能删除'];
        }
        
        // 备份设备信息
        $this->backupDeviceInfo($device_id);
        
            // 删除心跳日志
            $delete_logs_query = "DELETE FROM " . $this->heartbeat_table . " WHERE device_id = :device_id";
            $delete_logs_stmt = $conn->prepare($delete_logs_query);
            $delete_logs_stmt->execute([':device_id' => $device_id]);
            
            // 删除设备
            $delete_query = "DELETE FROM " . $this->table_name . " WHERE device_id = :device_id";
            $delete_stmt = $conn->prepare($delete_query);
            $result = $delete_stmt->execute([':device_id' => $device_id]);
        
            if ($result) {
                // 清除缓存
                $this->clearCache();
                return ['success' => true, 'message' => '设备删除成功'];
            } else {
                return ['success' => false, 'message' => '删除失败'];
            }
        } finally {
            $this->releaseConnection($conn);
        }
    }

    /**
     * 更新设备备注
     */
    public function updateRemarks($device_id, $remarks) {
        $conn = $this->getConnection();
        try {
            $query = "UPDATE " . $this->table_name . "
                      SET remarks = :remarks
                      WHERE device_id = :device_id";
                      
            $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':device_id' => $device_id,
            ':remarks' => $remarks
        ]);
        
            if ($result) {
                return ['success' => true, 'message' => '备注更新成功'];
            } else {
                return ['success' => false, 'message' => '备注更新失败'];
            }
        } finally {
            $this->releaseConnection($conn);
        }
    }

    /**
     * 更新设备字段
     */
    public function updateDeviceField($device_id, $field, $value) {
        $conn = $this->getConnection();
        try {
            // 验证字段名（白名单）
            $allowed_fields = ['created_at', 'remarks', 'order_number', 'device_name'];
            if (!in_array($field, $allowed_fields)) {
                return false;
            }
            
            $query = "UPDATE " . $this->table_name . "
                      SET {$field} = :value
                      WHERE device_id = :device_id";
                      
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':device_id' => $device_id,
                ':value' => $value
            ]);
        
            if ($result) {
                // 清除缓存
                $this->clearCache();
                return true;
            } else {
                return false;
            }
        } finally {
            $this->releaseConnection($conn);
        }
    }

    /**
     * 更新设备编号
     */
    public function updateOrderNumber($device_id, $order_number) {
        $conn = $this->getConnection();
        try {
            $query = "UPDATE " . $this->table_name . "
                      SET order_number = :order_number
                      WHERE device_id = :device_id";
                      
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':device_id' => $device_id,
                ':order_number' => $order_number
            ]);
        
            if ($result) {
                // 清除缓存
                $this->clearCache();
                return ['success' => true, 'message' => '编号更新成功'];
            } else {
                return ['success' => false, 'message' => '编号更新失败'];
            }
        } finally {
            $this->releaseConnection($conn);
        }
    }
    
    /**
     * 获取设备备注
     */
    public function getRemarks($device_id) {
        $conn = $this->getConnection();
        try {
            $query = "SELECT remarks FROM " . $this->table_name . " WHERE device_id = :device_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':device_id' => $device_id]);
            
            $result = $stmt->fetch();
            return $result ? $result['remarks'] : '';
        } finally {
            $this->releaseConnection($conn);
        }
    }
    
    /**
     * 搜索设备（基于备注和设备名称）- 性能优化版本
     */
    public function searchDevices($keyword) {
        try {
            if (empty($keyword)) {
                return $this->getAllDevices();
            }
            
            // 安全处理搜索关键词
            $searchTerm = "%" . trim($keyword) . "%";
            
            // 使用数据库LIKE查询替代PHP循环，大幅提升性能
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE (device_id LIKE :search1 
                             OR device_name LIKE :search2 
                             OR remarks LIKE :search3)
                      ORDER BY status='online' DESC, order_number ASC, last_heartbeat DESC";
            
            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':search1' => $searchTerm,
                ':search2' => $searchTerm,
                ':search3' => $searchTerm
            ]);
            
            $result = $stmt->fetchAll();
            $this->releaseConnection($conn);
            return $result;
            
        } catch (PDOException $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log('Search error: ' . $e->getMessage() . ', keyword="' . $keyword . '"');
            }
            return [];
        } catch (Exception $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log('General search error: ' . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * 按月份筛选设备
     * 
     * @param int $year 年份
     * @param int $month 月份
     * @return array 筛选后的设备列表
     */
    public function filterDevicesByMonth($year, $month) {
        try {
            // 验证年份和月份
            $year = (int)$year;
            $month = (int)$month;
            
            if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
                return $this->getAllDevices(); // 如果参数无效，返回所有设备
            }
            
            // 构建查询
            $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
            
            // 计算下个月的第一天
            $nextMonth = $month == 12 ? 1 : $month + 1;
            $nextYear = $month == 12 ? $year + 1 : $year;
            $endDate = sprintf('%04d-%02d-01 00:00:00', $nextYear, $nextMonth);
            
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE created_at >= :start_date AND created_at < :end_date 
                      ORDER BY status='online' DESC, order_number ASC, last_heartbeat DESC";
            
            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $result = $stmt->fetchAll();
            $this->releaseConnection($conn);
            return $result;
            
        } catch (Exception $e) {
            error_log('Month filter error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 按年份筛选设备（新方法）
     * @param int $year 年份
     * @return array 筛选后的设备列表
     */
    public function filterDevicesByYear($year) {
        try {
            $year = (int)$year;
            
            if ($year < 2000 || $year > 2100) {
                return $this->getAllDevices();
            }
            
            $startDate = sprintf('%04d-01-01 00:00:00', $year);
            $endDate = sprintf('%04d-01-01 00:00:00', $year + 1);
            
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE created_at >= :start_date AND created_at < :end_date 
                      ORDER BY status='online' DESC, order_number ASC, last_heartbeat DESC";
            
            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $result = $stmt->fetchAll();
            $this->releaseConnection($conn);
            return $result;
            
        } catch (Exception $e) {
            error_log('Year filter error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 灵活的日期筛选方法（新方法）
     * @param int|null $year 年份（空值表示不筛选年份）
     * @param int|null $month 月份（空值表示不筛选月份）
     * @return array 筛选后的设备列表
     */
    public function filterDevicesByDate($year = null, $month = null) {
        try {
            $conditions = [];
            $params = [];
            
            // 处理年份筛选
            if (!empty($year) && $year > 0) {
                $year = (int)$year;
                if ($year >= 2000 && $year <= 2100) {
                    if (!empty($month) && $month > 0) {
                        // 年月组合筛选 - 使用优化的范围查询
                        $month = (int)$month;
                        if ($month >= 1 && $month <= 12) {
                            $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
                            $nextMonth = $month == 12 ? 1 : $month + 1;
                            $nextYear = $month == 12 ? $year + 1 : $year;
                            $endDate = sprintf('%04d-%02d-01 00:00:00', $nextYear, $nextMonth);
                            
                            $conditions[] = "created_at >= :start_date AND created_at < :end_date";
                            $params[':start_date'] = $startDate;
                            $params[':end_date'] = $endDate;
                        }
                    } else {
                        // 仅年份筛选 - 使用优化的范围查询
                        $startDate = sprintf('%04d-01-01 00:00:00', $year);
                        $endDate = sprintf('%04d-01-01 00:00:00', $year + 1);
                        
                        $conditions[] = "created_at >= :start_date AND created_at < :end_date";
                        $params[':start_date'] = $startDate;
                        $params[':end_date'] = $endDate;
                    }
                }
            } elseif (!empty($month) && $month > 0) {
                // 仅月份筛选（所有年份） - 这种情况较少使用，可以使用 MONTH() 函数
                $month = (int)$month;
                if ($month >= 1 && $month <= 12) {
                    $conditions[] = "MONTH(created_at) = :month";
                    $params[':month'] = $month;
                }
            }
            
            // 构建查询
            $query = "SELECT * FROM " . $this->table_name;
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }
            $query .= " ORDER BY status='online' DESC, order_number ASC, last_heartbeat DESC";
            
            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll();
            $this->releaseConnection($conn);
            return $result;
            
        } catch (Exception $e) {
            error_log('Date filter error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 按状态筛选设备
     * 
     * @param string $status 设备状态 ('online' 或 'offline')
     * @return array 筛选后的设备列表
     */
    public function filterDevicesByStatus($status) {
        try {
            // 验证状态参数
            if (!in_array($status, ['online', 'offline'])) {
                return $this->getAllDevices(); // 如果参数无效，返回所有设备
            }
            
            // 构建查询
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE status = :status 
                      ORDER BY order_number ASC, last_heartbeat DESC";
            
            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':status' => $status
            ]);
            
            $result = $stmt->fetchAll();
            $this->releaseConnection($conn);
            return $result;
            
        } catch (Exception $e) {
            error_log('Status filter error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 格式化时间显示
     * 
     * @param string $datetime 数据库中的时间字符串
     * @return string 格式化后的时间字符串
     */
    public function formatDateTime($datetime) {
        if (empty($datetime)) {
            return '从未连接';
        }
        
        // 使用统一的时间格式化函数
        return format_datetime($datetime);
    }

    /**
     * 计算设备离线时间
     * 
     * @param string $last_heartbeat 最后心跳时间
     * @return string 格式化的离线时间
     */
    public function getOfflineTime($last_heartbeat) {
        if (empty($last_heartbeat)) {
            return '从未连接';
        }
        
        $last_time = strtotime($last_heartbeat);
        $now = current_timestamp();
        $diff = $now - $last_time;
        
        if ($diff < 60) {
            return $diff . '秒';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . '分钟';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . '小时' . floor(($diff % 3600) / 60) . '分钟';
        } else {
            return floor($diff / 86400) . '天' . floor(($diff % 86400) / 3600) . '小时';
        }
    }
    
    /**
     * 获取设备详细的离线时间
     * 
     * @param string $last_heartbeat 最后心跳时间
     * @return string 格式化的离线时间
     */
    public function getDetailedOfflineTime($last_heartbeat) {
        if (empty($last_heartbeat)) {
            return '从未连接';
        }
        
        $last_time = strtotime($last_heartbeat);
        $current_time = time();
        $offline_seconds = $current_time - $last_time;
        
        if ($offline_seconds < 60) {
            return '刚刚离线';
        } elseif ($offline_seconds < 3600) {
            $minutes = floor($offline_seconds / 60);
            return $minutes . '分钟前离线';
        } elseif ($offline_seconds < 86400) {
            $hours = floor($offline_seconds / 3600);
            return $hours . '小时前离线';
        } else {
            $days = floor($offline_seconds / 86400);
            return $days . '天前离线';
        }
    }
    
    /**
     * 更新设备创建时间
     * 
     * @param string $device_id 设备ID
     * @param string $created_at 新的创建时间
     * @return array 操作结果
     */
    public function updateCreatedTime($device_id, $created_at) {
        $conn = $this->getConnection();
        try {
            $query = "UPDATE " . $this->table_name . "
                      SET created_at = :created_at
                      WHERE device_id = :device_id";
                      
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':device_id' => $device_id,
                ':created_at' => $created_at
            ]);
            
            if ($result) {
                // 清除缓存
                $this->clearCache();
                return ['success' => true, 'message' => '创建时间更新成功'];
            } else {
                return ['success' => false, 'message' => '创建时间更新失败'];
            }
        } finally {
            $this->releaseConnection($conn);
        }
    }
    

    
    /**
     * 综合筛选设备（支持多条件组合）
     * 
     * @param array $filters 筛选条件数组
     * @return array 筛选后的设备列表
     */
    public function filterDevices($filters = []) {
        try {
            $conditions = [];
            $params = [];
            
            // 状态筛选
            if (!empty($filters['status']) && in_array($filters['status'], ['online', 'offline'])) {
                $conditions[] = "status = :status";
                $params[':status'] = $filters['status'];
            }
            
            // 搜索关键词筛选
            if (!empty($filters['search'])) {
                $searchTerm = "%" . trim($filters['search']) . "%";
                $conditions[] = "(device_id LIKE :search1 OR device_name LIKE :search2 OR remarks LIKE :search3)";
                $params[':search1'] = $searchTerm;
                $params[':search2'] = $searchTerm;
                $params[':search3'] = $searchTerm;
            }
            
            // 日期筛选
            if (!empty($filters['year']) && $filters['year'] > 0) {
                $year = (int)$filters['year'];
                if ($year >= 2000 && $year <= 2100) {
                    if (!empty($filters['month']) && $filters['month'] > 0) {
                        // 年月组合筛选
                        $month = (int)$filters['month'];
                        if ($month >= 1 && $month <= 12) {
                            $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
                            $nextMonth = $month == 12 ? 1 : $month + 1;
                            $nextYear = $month == 12 ? $year + 1 : $year;
                            $endDate = sprintf('%04d-%02d-01 00:00:00', $nextYear, $nextMonth);
                            
                            $conditions[] = "created_at >= :start_date AND created_at < :end_date";
                            $params[':start_date'] = $startDate;
                            $params[':end_date'] = $endDate;
                        }
                    } else {
                        // 仅年份筛选
                        $startDate = sprintf('%04d-01-01 00:00:00', $year);
                        $endDate = sprintf('%04d-01-01 00:00:00', $year + 1);
                        
                        $conditions[] = "created_at >= :start_date AND created_at < :end_date";
                        $params[':start_date'] = $startDate;
                        $params[':end_date'] = $endDate;
                    }
                }
            }
            
            // 构建查询
            $query = "SELECT * FROM " . $this->table_name;
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }
            $query .= " ORDER BY status='online' DESC, order_number ASC, last_heartbeat DESC";
            
            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll();
            $this->releaseConnection($conn);
            return $result;
            
        } catch (Exception $e) {
            error_log('Filter devices error: ' . $e->getMessage());
            return [];
        }
    }
}
?>