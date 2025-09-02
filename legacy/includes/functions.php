<?php
/**
 * 通用函数库
 */

/**
 * 安全地输出HTML内容，防止XSS攻击
 * @param string $string 要输出的字符串
 * @return string 转义后的字符串
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 格式化文件大小
 * @param int $bytes 字节数
 * @return string 格式化后的大小
 */
function formatBytes($bytes) {
    if ($bytes == 0) return '0 B';
    
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

/**
 * 格式化时间差
 * @param string $datetime 时间字符串
 * @return string 格式化后的时间差
 */
function timeAgo($datetime) {
    if (empty($datetime)) {
        return '从未连接';
    }
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return $time . '秒前';
    } elseif ($time < 3600) {
        return floor($time / 60) . '分钟前';
    } elseif ($time < 86400) {
        return floor($time / 3600) . '小时前';
    } elseif ($time < 2592000) {
        return floor($time / 86400) . '天前';
    } elseif ($time < 31536000) {
        return floor($time / 2592000) . '个月前';
    } else {
        return floor($time / 31536000) . '年前';
    }
}

/**
 * 检查字符串是否为有效的JSON
 * @param string $string 要检查的字符串
 * @return bool 是否为有效JSON
 */
function isValidJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * 生成随机字符串
 * @param int $length 长度
 * @return string 随机字符串
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * 验证IP地址
 * @param string $ip IP地址
 * @return bool 是否为有效IP
 */
function isValidIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * 获取客户端真实IP地址
 * @return string IP地址
 */
function getRealIP() {
    $ip = '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // 如果是多个IP，取第一个
    if (strpos($ip, ',') !== false) {
        $ip = explode(',', $ip)[0];
    }
    
    return trim($ip);
}

/**
 * 记录操作日志
 * @param string $action 操作类型
 * @param string $details 操作详情
 * @param string $level 日志级别 (info, warning, error)
 */
function logAction($action, $details = '', $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $ip = getRealIP();
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'anonymous';
    
    $logMessage = "[{$timestamp}] [{$level}] User: {$user}, IP: {$ip}, Action: {$action}";
    if (!empty($details)) {
        $logMessage .= ", Details: {$details}";
    }
    
    error_log($logMessage);
}

/**
 * 清理和验证输入数据
 * @param mixed $data 输入数据
 * @return mixed 清理后的数据
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}