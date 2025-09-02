<?php
/**
 * 安全配置文件
 * 集中管理系统安全相关设置
 */

// 防止直接访问
if (!defined('SECURITY_CONFIG_LOADED')) {
    define('SECURITY_CONFIG_LOADED', true);
}

/**
 * 设置安全HTTP头
 */
function setSecurityHeaders() {
    // 防止点击劫持
    header('X-Frame-Options: DENY');
    
    // 防止MIME类型嗅探
    header('X-Content-Type-Options: nosniff');
    
    // XSS保护
    header('X-XSS-Protection: 1; mode=block');
    
    // 引用来源策略
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // 内容安全策略
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com");
    
    // 如果是HTTPS，添加HSTS头
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * 验证CSRF令牌
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 生成CSRF令牌
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * 安全的输出转义
 */
function escapeOutput($data, $encoding = 'UTF-8') {
    if (is_array($data)) {
        return array_map(function($item) use ($encoding) {
            return escapeOutput($item, $encoding);
        }, $data);
    }
    
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, $encoding);
}

/**
 * 验证输入数据
 */
function validateInput($data, $type = 'string', $options = []) {
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
            
        case 'int':
            $min = $options['min'] ?? null;
            $max = $options['max'] ?? null;
            $flags = 0;
            $filter_options = [];
            
            if ($min !== null) {
                $filter_options['min_range'] = $min;
            }
            if ($max !== null) {
                $filter_options['max_range'] = $max;
            }
            
            if (!empty($filter_options)) {
                return filter_var($data, FILTER_VALIDATE_INT, [
                    'options' => $filter_options
                ]);
            }
            
            return filter_var($data, FILTER_VALIDATE_INT);
            
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT);
            
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
            
        case 'ip':
            return filter_var($data, FILTER_VALIDATE_IP);
            
        case 'mac':
            return filter_var($data, FILTER_VALIDATE_MAC);
            
        case 'string':
        default:
            $maxLength = $options['max_length'] ?? 255;
            $data = trim($data);
            
            if (strlen($data) > $maxLength) {
                return false;
            }
            
            // 移除潜在的危险字符
            return filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }
}

/**
 * 安全的随机字符串生成
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * 检查请求频率限制
 */
function checkRateLimit($identifier, $maxRequests = 60, $timeWindow = 3600) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'rate_limit_' . $identifier;
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'start_time' => $now];
        return true;
    }
    
    $rateData = $_SESSION[$key];
    
    // 如果时间窗口已过，重置计数
    if ($now - $rateData['start_time'] > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'start_time' => $now];
        return true;
    }
    
    // 检查是否超过限制
    if ($rateData['count'] >= $maxRequests) {
        return false;
    }
    
    // 增加计数
    $_SESSION[$key]['count']++;
    return true;
}

// 自动设置安全头（如果不是CLI模式）
if (php_sapi_name() !== 'cli') {
    setSecurityHeaders();
}
?>