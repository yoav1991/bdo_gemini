<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

check_login('../login.php');
check_admin('../index.php');

$log_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($log_id > 0) {
    $stmt = $conn->prepare("UPDATE work_logs_new SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $log_id);
    $stmt->execute();
    $stmt->close();
}

// 跳转回审核页面
if ($project_id > 0) {
    header("Location: view_project_new.php?id=" . $project_id);
} else {
    header("Location: index.php");
}
exit;
?>