<?php
/**
 * 时区配置文件
 * 统一管理系统时区设置
 */

// 设置PHP默认时区为中国标准时间
date_default_timezone_set('Asia/Shanghai');

// MySQL时区设置（用于数据库连接时设置会话时区）
define('MYSQL_TIMEZONE', '+8:00');

/**
 * 格式化日期时间
 * 
 * @param string|int|null $timestamp 时间戳或日期字符串，null表示当前时间
 * @param string $format 日期格式
 * @return string 格式化后的日期时间字符串
 */
function format_datetime($timestamp = null, $format = 'Y-m-d H:i:s') {
    // 确保时区设置为中国时区
    date_default_timezone_set('Asia/Shanghai');
    
    if ($timestamp === null) {
        return date($format);
    } elseif (is_numeric($timestamp)) {
        return date($format, $timestamp);
    } else {
        // 显式将UTC/系统时间转换为中国时区
        $dt = new DateTime($timestamp);
        $dt->setTimezone(new DateTimeZone('Asia/Shanghai'));
        return $dt->format($format);
    }
}

/**
 * 获取当前时间戳
 * 
 * @return int 当前时间戳
 */
function current_timestamp() {
    return time();
}
?> 