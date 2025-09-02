<?php
/**
 * 设备控制器 - 处理设备相关的业务逻辑
 */

class DeviceController {
    private $db;
    private $database;
    private $device;
    private $auth;
    
    public function __construct($database) {
        $this->database = $database;
        $this->db = $database->getConnection();
        if (!$this->db) {
            throw new Exception('数据库连接失败');
        }
        
        $this->device = new Device($this->db);
        $this->auth = new Auth($this->db);
    }
    
    /**
     * 获取数据库连接池
     */
    public function getPool() {
        return $this->database;
    }
    
    /**
     * 处理设备页面的主要逻辑
     */
    public function index() {
        // 检查用户是否已登录
        if (!$this->auth->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        
        // 获取当前用户信息
        $current_user = $this->auth->getCurrentUser();
        
        // 处理删除设备请求
        $delete_message = null;
        if (isset($_POST['action']) && $_POST['action'] === 'delete_device' && !empty($_POST['device_id'])) {
            $delete_message = $this->device->deleteDevice($_POST['device_id']);
        }
        
        // 更新设备状态
        $this->device->updateDeviceStatus();
        
        // 处理搜索和筛选参数
        $filters = $this->processFilters();
        
        // 获取设备列表
        $devices = $this->getDeviceList($filters);
        
        // 获取统计信息
        $stats = $this->getDeviceStats();
        
        return [
            'current_user' => $current_user,
            'delete_message' => $delete_message,
            'devices' => $devices,
            'stats' => $stats,
            'filters' => $filters
        ];
    }
    
    /**
     * 处理筛选参数
     */
    private function processFilters() {
        $search_keyword = '';
        if (isset($_GET['search'])) {
            $search_keyword = trim($_GET['search']);
            if (mb_strlen($search_keyword) > 50) {
                $search_keyword = mb_substr($search_keyword, 0, 50);
            }
        }
        
        $filter_status = '';
        if (isset($_GET['status']) && in_array($_GET['status'], ['online', 'offline'])) {
            $filter_status = $_GET['status'];
        }
        
        $filter_year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
        $filter_month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
        
        return [
            'search_keyword' => $search_keyword,
            'filter_status' => $filter_status,
            'filter_year' => $filter_year,
            'filter_month' => $filter_month
        ];
    }
    
    /**
     * 获取设备列表
     */
    private function getDeviceList($filters) {
        try {
            if (!empty($filters['search_keyword'])) {
                return $this->device->searchDevices($filters['search_keyword']);
            } elseif ($filters['filter_year'] > 0 || $filters['filter_month'] > 0) {
                return $this->device->filterDevicesByDate(
                    $filters['filter_year'] > 0 ? $filters['filter_year'] : null,
                    $filters['filter_month'] > 0 ? $filters['filter_month'] : null
                );
            } elseif (!empty($filters['filter_status'])) {
                return $this->device->filterDevicesByStatus($filters['filter_status']);
            } else {
                return $this->device->getAllDevices();
            }
        } catch (Exception $e) {
            error_log('Search error in DeviceController: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 获取设备统计信息
     */
    private function getDeviceStats() {
        try {
            $stats = $this->device->getDeviceStats();
            // 确保统计数据不为空
            if (!$stats || !is_array($stats)) {
                $stats = [
                    'total_devices' => 0,
                    'online_devices' => 0,
                    'offline_devices' => 0
                ];
            }
            // 确保所有必需的键都存在
            $stats['total_devices'] = $stats['total_devices'] ?? 0;
            $stats['online_devices'] = $stats['online_devices'] ?? 0;
            $stats['offline_devices'] = $stats['offline_devices'] ?? 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log('Stats error in DeviceController: ' . $e->getMessage());
            return [
                'total_devices' => 0,
                'online_devices' => 0,
                'offline_devices' => 0
            ];
        }
    }
    
    /**
     * 获取筛选条件下的设备数量
     */
    public function getFilteredDeviceCount($filter_year, $filter_month) {
        $filter_count_query = "SELECT COUNT(*) as count FROM devices WHERE 1=1";
        $filter_params = [];
        
        if ($filter_year > 0 && $filter_month > 0) {
            // 年月组合筛选 - 使用范围查询
            $startDate = sprintf('%04d-%02d-01 00:00:00', $filter_year, $filter_month);
            $nextMonth = $filter_month == 12 ? 1 : $filter_month + 1;
            $nextYear = $filter_month == 12 ? $filter_year + 1 : $filter_year;
            $endDate = sprintf('%04d-%02d-01 00:00:00', $nextYear, $nextMonth);
            
            $filter_count_query .= " AND created_at >= ? AND created_at < ?";
            $filter_params = [$startDate, $endDate];
        } elseif ($filter_year > 0) {
            // 仅年份筛选 - 使用范围查询
            $startDate = sprintf('%04d-01-01 00:00:00', $filter_year);
            $endDate = sprintf('%04d-01-01 00:00:00', $filter_year + 1);
            
            $filter_count_query .= " AND created_at >= ? AND created_at < ?";
            $filter_params = [$startDate, $endDate];
        } elseif ($filter_month > 0) {
            // 仅月份筛选 - 使用 MONTH() 函数
            $filter_count_query .= " AND MONTH(created_at) = ?";
            $filter_params = [$filter_month];
        }
        
        $filter_count_stmt = $this->db->prepare($filter_count_query);
        $filter_count_stmt->execute($filter_params);
        return $filter_count_stmt->fetchColumn();
    }
    
    /**
     * 处理设备删除请求
     */
    public function deleteDevice() {
        if (!isset($_POST['device_id']) || empty($_POST['device_id'])) {
            return ['success' => false, 'message' => '设备ID不能为空'];
        }
        
        try {
            $result = $this->device->deleteDevice($_POST['device_id']);
            return $result;
        } catch (Exception $e) {
            error_log('Delete device error in DeviceController: ' . $e->getMessage());
            return ['success' => false, 'message' => '删除设备时发生错误'];
        }
    }
    
    /**
     * 获取设备页面所需的所有数据
     */
    public function getDeviceData() {
        // 更新设备状态
        $this->device->updateDeviceStatus();
        
        // 处理搜索和筛选参数
        $filters = $this->processFilters();
        
        // 获取设备列表
        $devices = $this->getDeviceList($filters);
        
        // 获取统计信息
        $stats = $this->getDeviceStats();
        
        return [
            'devices' => $devices,
            'stats' => $stats,
            'filters' => $filters
        ];
    }
}