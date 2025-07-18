<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

check_login('../login.php');
check_admin('../index.php');

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$confirm = isset($_GET['confirm']) ? $_GET['confirm'] : '';

if ($project_id <= 0) {
    header("Location: index.php?error=not_found");
    exit;
}

// 检查项目是否存在
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    $stmt->close();
    header("Location: index.php?error=not_found");
    exit;
}
$project = $result->fetch_assoc();
$stmt->close();

// 获取相关数据统计
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM work_logs_new WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$log_count = $result->fetch_assoc()['count'];
$stmt->close();

// 如果有工作日志且未确认，显示确认页面
if ($log_count > 0 && $confirm !== 'yes') {
    require_once '../templates/header.php';
    ?>
    <div class="max-w-lg mx-auto bg-gray-800 p-8 rounded-lg shadow-xl">
        <h1 class="text-3xl font-bold text-center text-red-500 mb-6">⚠️ 删除确认</h1>
        
        <div class="bg-red-900/50 border border-red-500 rounded-lg p-4 mb-6">
            <p class="text-white mb-4">
                您即将删除项目 "<strong><?php echo htmlspecialchars($project['name']); ?></strong>"
            </p>
            <p class="text-red-300 mb-2">
                该项目包含以下数据，删除后将<strong>无法恢复</strong>：
            </p>
            <ul class="list-disc list-inside text-gray-300 ml-4 space-y-1">
                <li><?php echo $log_count; ?> 条工作日志记录</li>
                <li>所有相关的杂物明细记录</li>
                <li>所有上传的截图文件</li>
            </ul>
        </div>
        
        <div class="flex gap-4">
            <a href="delete_project.php?id=<?php echo $project_id; ?>&confirm=yes" 
               class="flex-1 bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-4 rounded-lg text-center transition duration-300">
                确认删除
            </a>
            <a href="index.php" 
               class="flex-1 bg-gray-600 hover:bg-gray-500 text-white font-bold py-3 px-4 rounded-lg text-center transition duration-300">
                取消
            </a>
        </div>
    </div>
    <?php
    require_once '../templates/footer.php';
    exit;
}

// 开始事务，删除所有相关数据
$conn->begin_transaction();

try {
    // 1. 获取所有相关的工作日志ID（用于删除图片）
    $log_ids = [];
    $stmt = $conn->prepare("SELECT id FROM work_logs_new WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $log_ids[] = $row['id'];
    }
    $stmt->close();
    
    // 2. 删除物理图片文件
    if (!empty($log_ids)) {
        $placeholders = str_repeat('?,', count($log_ids) - 1) . '?';
        $stmt = $conn->prepare("SELECT image_path FROM work_log_images WHERE log_id IN ($placeholders)");
        $types = str_repeat('i', count($log_ids));
        $stmt->bind_param($types, ...$log_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $file_path = '../uploads/' . $row['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $stmt->close();
        
        // 3. 删除图片记录
        $stmt = $conn->prepare("DELETE FROM work_log_images WHERE log_id IN ($placeholders)");
        $stmt->bind_param($types, ...$log_ids);
        $stmt->execute();
        $stmt->close();
        
        // 4. 删除工作日志项目明细
        $stmt = $conn->prepare("DELETE FROM work_log_items WHERE log_id IN ($placeholders)");
        $stmt->bind_param($types, ...$log_ids);
        $stmt->execute();
        $stmt->close();
    }
    
    // 5. 删除工作日志
    $stmt = $conn->prepare("DELETE FROM work_logs_new WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $stmt->close();
    
    // 6. 最后删除项目
    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $stmt->close();
    
    // 提交事务
    $conn->commit();
    
    header("Location: index.php?success=delete");
    exit;
    
} catch (Exception $e) {
    // 回滚事务
    $conn->rollback();
    header("Location: index.php?error=delete_failed");
    exit;
}
?>