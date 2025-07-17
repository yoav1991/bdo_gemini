<?php
$db_host = 'localhost'; // 或者你的数据库主机
$db_user = 'bdo_game';      // 你的数据库用户名
$db_pass = 'w4Thiz8TSDdT1DpX';          // 你的数据库密码
$db_name = 'bdo_game'; // 你的数据库名称

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset("utf8mb4");

// 开启会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>