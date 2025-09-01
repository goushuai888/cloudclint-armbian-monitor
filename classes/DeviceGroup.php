<?php
/**
 * 设备分组管理类
 * CloudClint 设备监控平台
 */

// 引入时区配置
require_once __DIR__ . '/../config/timezone.php';

class DeviceGroup {
    private $conn;
    private $table_name = "device_groups";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 获取所有分组
     * @return array 分组列表
     */
    public function getAllGroups() {
        $query = "SELECT g.*, 
                         COUNT(d.id) as device_count,
                         SUM(CASE WHEN d.status = 'online' THEN 1 ELSE 0 END) as online_count,
                         SUM(CASE WHEN d.status = 'offline' THEN 1 ELSE 0 END) as offline_count
                  FROM " . $this->table_name . " g 
                  LEFT JOIN devices d ON g.id = d.group_id 
                  GROUP BY g.id 
                  ORDER BY g.sort_order ASC, g.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * 根据ID获取分组
     * @param int $group_id 分组ID
     * @return array|false 分组信息
     */
    public function getGroupById($group_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :group_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':group_id' => $group_id]);
        return $stmt->fetch();
    }
    
    /**
     * 创建新分组
     * @param array $data 分组数据
     * @return array 操作结果
     */
    public function createGroup($data) {
        try {
            // 验证分组名称是否已存在
            $check_query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE group_name = :group_name";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute([':group_name' => $data['group_name']]);
            
            if ($check_stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => '分组名称已存在'];
            }
            
            // 获取下一个排序顺序
            $sort_query = "SELECT COALESCE(MAX(sort_order), 0) + 1 as next_sort FROM " . $this->table_name;
            $sort_stmt = $this->conn->prepare($sort_query);
            $sort_stmt->execute();
            $next_sort = $sort_stmt->fetchColumn();
            
            // 插入新分组
            $query = "INSERT INTO " . $this->table_name . " 
                      (group_name, group_description, group_color, group_icon, sort_order) 
                      VALUES 
                      (:group_name, :group_description, :group_color, :group_icon, :sort_order)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':group_name' => $data['group_name'],
                ':group_description' => $data['group_description'] ?? '',
                ':group_color' => $data['group_color'] ?? '#3b82f6',
                ':group_icon' => $data['group_icon'] ?? 'bi-folder',
                ':sort_order' => $next_sort
            ]);
            
            if ($result) {
                return [
                    'success' => true, 
                    'message' => '分组创建成功',
                    'group_id' => $this->conn->lastInsertId()
                ];
            } else {
                return ['success' => false, 'message' => '分组创建失败'];
            }
        } catch (Exception $e) {
            error_log('Create group error: ' . $e->getMessage());
            return ['success' => false, 'message' => '系统错误：' . $e->getMessage()];
        }
    }
    
    /**
     * 更新分组信息
     * @param int $group_id 分组ID
     * @param array $data 更新数据
     * @return array 操作结果
     */
    public function updateGroup($group_id, $data) {
        try {
            // 检查分组是否存在
            $group = $this->getGroupById($group_id);
            if (!$group) {
                return ['success' => false, 'message' => '分组不存在'];
            }
            
            // 如果是默认分组，不允许修改某些字段
            if ($group['is_default'] && isset($data['is_default']) && !$data['is_default']) {
                return ['success' => false, 'message' => '不能取消默认分组状态'];
            }
            
            // 检查分组名称是否重复
            if (isset($data['group_name']) && $data['group_name'] !== $group['group_name']) {
                $check_query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE group_name = :group_name AND id != :id";
                $check_stmt = $this->conn->prepare($check_query);
                $check_stmt->execute([
                    ':group_name' => $data['group_name'],
                    ':id' => $group_id
                ]);
                
                if ($check_stmt->fetchColumn() > 0) {
                    return ['success' => false, 'message' => '分组名称已存在'];
                }
            }
            
            // 构建更新字段
            $update_fields = [];
            $params = [':id' => $group_id];
            
            if (isset($data['group_name'])) {
                $update_fields[] = "group_name = :group_name";
                $params[':group_name'] = $data['group_name'];
            }
            
            if (isset($data['group_description'])) {
                $update_fields[] = "group_description = :group_description";
                $params[':group_description'] = $data['group_description'];
            }
            
            if (isset($data['group_color'])) {
                $update_fields[] = "group_color = :group_color";
                $params[':group_color'] = $data['group_color'];
            }
            
            if (isset($data['group_icon'])) {
                $update_fields[] = "group_icon = :group_icon";
                $params[':group_icon'] = $data['group_icon'];
            }
            
            if (isset($data['sort_order'])) {
                $update_fields[] = "sort_order = :sort_order";
                $params[':sort_order'] = (int)$data['sort_order'];
            }
            
            if (empty($update_fields)) {
                return ['success' => true, 'message' => '没有字段需要更新'];
            }
            
            // 执行更新
            $query = "UPDATE " . $this->table_name . " SET " . implode(", ", $update_fields) . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                return ['success' => true, 'message' => '分组更新成功'];
            } else {
                return ['success' => false, 'message' => '分组更新失败'];
            }
        } catch (Exception $e) {
            error_log('Update group error: ' . $e->getMessage());
            return ['success' => false, 'message' => '系统错误：' . $e->getMessage()];
        }
    }
    
    /**
     * 删除分组
     * @param int $group_id 分组ID
     * @return array 操作结果
     */
    public function deleteGroup($group_id) {
        try {
            // 检查分组是否存在
            $group = $this->getGroupById($group_id);
            if (!$group) {
                return ['success' => false, 'message' => '分组不存在'];
            }
            
            // 不允许删除默认分组
            if ($group['is_default']) {
                return ['success' => false, 'message' => '不能删除默认分组'];
            }
            
            // 获取默认分组ID
            $default_query = "SELECT id FROM " . $this->table_name . " WHERE is_default = TRUE LIMIT 1";
            $default_stmt = $this->conn->prepare($default_query);
            $default_stmt->execute();
            $default_group_id = $default_stmt->fetchColumn();
            
            if (!$default_group_id) {
                return ['success' => false, 'message' => '找不到默认分组，无法删除'];
            }
            
            // 开始事务
            $this->conn->beginTransaction();
            
            try {
                // 将该分组下的设备移动到默认分组
                $move_query = "UPDATE devices SET group_id = :default_group_id WHERE group_id = :group_id";
                $move_stmt = $this->conn->prepare($move_query);
                $move_stmt->execute([
                    ':default_group_id' => $default_group_id,
                    ':group_id' => $group_id
                ]);
                
                // 删除分组
                $delete_query = "DELETE FROM " . $this->table_name . " WHERE id = :group_id";
                $delete_stmt = $this->conn->prepare($delete_query);
                $delete_stmt->execute([':group_id' => $group_id]);
                
                // 提交事务
                $this->conn->commit();
                
                return ['success' => true, 'message' => '分组删除成功，设备已移动到默认分组'];
            } catch (Exception $e) {
                // 回滚事务
                $this->conn->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            error_log('Delete group error: ' . $e->getMessage());
            return ['success' => false, 'message' => '系统错误：' . $e->getMessage()];
        }
    }
    
    /**
     * 移动设备到指定分组
     * @param string $device_id 设备ID
     * @param int $group_id 分组ID
     * @return array 操作结果
     */
    public function moveDeviceToGroup($device_id, $group_id) {
        try {
            // 验证分组是否存在
            if ($group_id !== null) {
                $group = $this->getGroupById($group_id);
                if (!$group) {
                    return ['success' => false, 'message' => '目标分组不存在'];
                }
            }
            
            // 更新设备分组
            $query = "UPDATE devices SET group_id = :group_id WHERE device_id = :device_id";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':group_id' => $group_id,
                ':device_id' => $device_id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => '设备分组更新成功'];
            } else {
                return ['success' => false, 'message' => '设备分组更新失败'];
            }
        } catch (Exception $e) {
            error_log('Move device to group error: ' . $e->getMessage());
            return ['success' => false, 'message' => '系统错误：' . $e->getMessage()];
        }
    }
    
    /**
     * 获取分组下的设备列表
     * @param int $group_id 分组ID
     * @return array 设备列表
     */
    public function getDevicesByGroup($group_id) {
        $query = "SELECT * FROM devices WHERE group_id = :group_id ORDER BY status='online' DESC, order_number ASC, last_heartbeat DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':group_id' => $group_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * 获取默认分组ID
     * @return int|null 默认分组ID
     */
    public function getDefaultGroupId() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE is_default = TRUE LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * 更新分组排序
     * @param array $group_orders 分组排序数组 [group_id => sort_order]
     * @return array 操作结果
     */
    public function updateGroupOrder($group_orders) {
        try {
            $this->conn->beginTransaction();
            
            foreach ($group_orders as $group_id => $sort_order) {
                $query = "UPDATE " . $this->table_name . " SET sort_order = :sort_order WHERE id = :group_id";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([
                    ':sort_order' => (int)$sort_order,
                    ':group_id' => (int)$group_id
                ]);
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => '分组排序更新成功'];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log('Update group order error: ' . $e->getMessage());
            return ['success' => false, 'message' => '系统错误：' . $e->getMessage()];
        }
    }
}
?>