<?php
require_once '../templates/header.php';
check_login('../login.php');
check_admin('../index.php');
?>
<h1 class="text-3xl font-bold text-yellow-400 mb-6">管理员控制台</h1>
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
                    $stmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM work_logs WHERE project_id = ? AND status = 'pending'");
                    $stmt->bind_param("i", $project['id']);
                    $stmt->execute();
                    $pending_count = $stmt->get_result()->fetch_assoc()['pending_count'];
                    $stmt->close();
                    
                    echo '<li class="p-4 flex justify-between items-center hover:bg-gray-700/50">';
                    echo '<span>' . htmlspecialchars($project['name']) . '</span>';
                    echo '<div>';
                    if ($pending_count > 0) {
                        echo '<span class="mr-4 inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-red-800 text-red-200">' . $pending_count . '条待审核</span>';
                    }
                    echo '<a href="view_project.php?id=' . $project['id'] . '" class="bg-blue-500 hover:bg-blue-400 text-white font-bold py-1 px-3 rounded text-sm transition duration-300">查看/审核</a>';
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