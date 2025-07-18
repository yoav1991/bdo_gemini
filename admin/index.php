<?php
require_once '../templates/header.php';
check_login('../login.php');
check_admin('../index.php');

// 显示消息
$message = '';
$message_type = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'delete') {
        $message = '项目及所有相关数据已成功删除！';
        $message_type = 'success';
    }
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'not_found':
            $message = '项目不存在。';
            $message_type = 'error';
            break;
        case 'delete_failed':
            $message = '删除失败，请稍后再试。';
            $message_type = 'error';
            break;
    }
}
?>
<h1 class="text-3xl font-bold text-yellow-400 mb-6">管理员控制台</h1>

<?php if ($message): ?>
    <div class="<?php echo $message_type == 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white p-3 rounded mb-4">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <a href="manage_items.php" class="block bg-gray-800 rounded-lg shadow-xl p-6 hover:bg-gray-700 transition-colors duration-300">
        <h2 class="text-2xl font-bold text-yellow-300 mb-2">管理杂物价格</h2>
        <p class="text-gray-400">添加、编辑或删除游戏中的杂物及其单价。</p>
    </a>
    <a href="create_project.php" class="block bg-gray-800 rounded-lg shadow-xl p-6 hover:bg-gray-700 transition-colors duration-300">
        <h2 class="text-2xl font-bold text-yellow-300 mb-2">创建新项目</h2>
        <p class="text-gray-400">创建新的每月代练大项目。</p>
    </a>
    <div class="md:col-span-2 lg:col-span-3">
         <h2 class="text-2xl font-bold text-yellow-300 mt-8 mb-4">查看和审核项目</h2>
         <div class="bg-gray-800 rounded-lg shadow-xl">
            <ul class="divide-y divide-gray-700">
            <?php
            $projects = $conn->query("SELECT * FROM projects ORDER BY created_at DESC");
            if ($projects->num_rows > 0) {
                while($project = $projects->fetch_assoc()) {
                    // 获取待审核数量
                    $stmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM work_logs_new WHERE project_id = ? AND status = 'pending'");
                    $stmt->bind_param("i", $project['id']);
                    $stmt->execute();
                    $pending_count = $stmt->get_result()->fetch_assoc()['pending_count'];
                    $stmt->close();
                    
                    // 获取总日志数量
                    $stmt = $conn->prepare("SELECT COUNT(*) as total_logs FROM work_logs_new WHERE project_id = ?");
                    $stmt->bind_param("i", $project['id']);
                    $stmt->execute();
                    $total_logs = $stmt->get_result()->fetch_assoc()['total_logs'];
                    $stmt->close();
                    
                    echo '<li class="p-4 flex justify-between items-center hover:bg-gray-700/50">';
                    echo '<div>';
                    echo '<span class="font-medium">' . htmlspecialchars($project['name']) . '</span>';
                    echo '<span class="text-sm text-gray-400 ml-2">(共' . $total_logs . '条记录)</span>';
                    echo '</div>';
                    echo '<div class="flex items-center">';
                    if ($pending_count > 0) {
                        echo '<span class="mr-4 inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-red-800 text-red-200">' . $pending_count . '条待审核</span>';
                    }
                    echo '<a href="view_project_new.php?id=' . $project['id'] . '" class="bg-blue-500 hover:bg-blue-400 text-white font-bold py-1 px-3 rounded text-sm transition duration-300 mr-2">查看/审核</a>';
                    echo '<a href="edit_project.php?id=' . $project['id'] . '" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-1 px-3 rounded text-sm transition duration-300 mr-2">编辑</a>';
                    echo '<a href="delete_project.php?id=' . $project['id'] . '" onclick="return confirm(\'确定要删除这个项目吗？\\n\\n警告：删除项目将同时删除所有相关的工作日志、杂物记录和上传的图片文件！\\n此操作无法撤销！\');" class="bg-red-600 hover:bg-red-500 text-white font-bold py-1 px-3 rounded text-sm transition duration-300">删除</a>';
                    echo '</div>';
                    echo '</li>';
                }
            } else {
                echo '<li class="p-4 text-gray-400">没有项目。</li>';
            }
            ?>
            </ul>
         </div>
    </div>
</div>
<?php require_once '../templates/footer.php'; ?>