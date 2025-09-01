<?php
/**
 * Armbian设备监控平台 - 注销页面
 */

// 开始会话
session_start();

// 引入时区配置
require_once 'config/timezone.php';
require_once 'config/database.php';
require_once 'classes/Auth.php';

// 数据库连接
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die('数据库连接失败');
}

$auth = new Auth($db);

// 执行注销
$auth->logout();

// 重定向到登录页面
header('Location: login.php');
exit;
?> 