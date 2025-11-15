<?php

// 设置时区为中国上海
date_default_timezone_set('Asia/Shanghai');
// 设置DEBUG模式
define('DEBUG', true);

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 数据库配置
$host = 'localhost';
$dbname = '7sebook_com';
$username = '7sebook_com';
$password = 'inSZGYaiQhJxPTwQ';
$charset = 'utf8';
?>