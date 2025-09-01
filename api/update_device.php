<?php
/**
 * 设备更新API接口
 * 处理设备信息的更新请求
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '只允许POST请求'
    ]);
    exit;
}

try {
    // 启动会话
    session_start();
    
    // 引入必要的文件
    require_once '../config/timezone.php';
    require_once '../config/database.php';
    require_once '../classes/Device.php';
    require_once '../classes/Auth.php';
    
    // 初始化数据库连接
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('数据库连接失败');
    }
    
    // 检查用户认证
    $auth = new Auth($db);
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '用户未登录'
        ]);
        exit;
    }
    
    // 获取请求数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('无效的JSON数据');
    }
    
    // 验证必需参数
    if (empty($data['device_id'])) {
        throw new Exception('设备ID不能为空');
    }
    
    if (empty($data['field'])) {
        throw new Exception('更新字段不能为空');
    }
    
    if (!isset($data['value'])) {
        throw new Exception('更新值不能为空');
    }
    
    $device_id = trim($data['device_id']);
    $field = trim($data['field']);
    $value = $data['value'];
    
    // 验证设备ID格式
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $device_id)) {
        throw new Exception('设备ID格式无效');
    }
    
    // 验证字段名（白名单）
    $allowed_fields = ['created_at', 'remarks', 'order_number'];
    if (!in_array($field, $allowed_fields)) {
        throw new Exception('不允许更新该字段');
    }
    
    // 初始化Device类
    $device = new Device($db);
    
    // 验证设备是否存在
    if (!$device->deviceExists($device_id)) {
        throw new Exception('设备不存在');
    }
    
    // 根据字段类型进行特定验证和处理
    switch ($field) {
        case 'created_at':
            // 验证时间格式
            $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
            if (!$datetime || $datetime->format('Y-m-d H:i:s') !== $value) {
                throw new Exception('时间格式无效，请使用 YYYY-MM-DD HH:MM:SS 格式');
            }
            
            // 检查时间是否合理（不能是未来时间）
            if ($datetime > new DateTime()) {
                throw new Exception('创建时间不能是未来时间');
            }
            
            // 检查时间是否过于久远（比如不能早于2000年）
            $minDate = new DateTime('2000-01-01');
            if ($datetime < $minDate) {
                throw new Exception('创建时间不能早于2000年1月1日');
            }
            break;
            
        case 'remarks':
            // 验证备注长度
            if (mb_strlen($value) > 200) {
                throw new Exception('备注长度不能超过200个字符');
            }
            
            // 过滤HTML标签和特殊字符
            $value = strip_tags(trim($value));
            break;
            
        case 'order_number':
            // 验证序号
            if (!is_numeric($value) || $value < 0) {
                throw new Exception('序号必须是非负整数');
            }
            $value = (int)$value;
            break;
    }
    
    // 执行更新
    $result = $device->updateDeviceField($device_id, $field, $value);
    
    if ($result) {
        // 记录操作日志
        $current_user = $auth->getCurrentUser();
        error_log(sprintf(
            'Device update: User %s updated %s for device %s to %s',
            $current_user['username'] ?? 'unknown',
            $field,
            $device_id,
            is_string($value) ? $value : json_encode($value)
        ));
        
        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'data' => [
                'device_id' => $device_id,
                'field' => $field,
                'value' => $value
            ]
        ]);
    } else {
        throw new Exception('更新失败，请稍后重试');
    }
    
} catch (Exception $e) {
    // 记录错误日志
    error_log('Device update API error: ' . $e->getMessage());
    
    // 返回错误响应
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    // 处理致命错误
    error_log('Device update API fatal error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误'
    ]);
}
?>