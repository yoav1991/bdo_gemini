<?php
require_once '../templates/header.php';
check_login('../login.php');
check_admin('../index.php');

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id <= 0) {
    header("Location: index.php");
    exit;
}

// 获取项目信息
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo "项目不存在。";
    exit;
}
$project = $result->fetch_assoc();
$stmt->close();

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $conn->prepare("UPDATE projects SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $project_id);
        if ($stmt->execute()) {
            $success = "项目更新成功!";
            // 重新获取更新后的项目信息
            $project['name'] = $name;
            $project['description'] = $description;
        } else {
            $error = "更新失败，请稍后再试。";
        }
        $stmt->close();
    } else {
        $error = "项目名称不能为空。";
    }
}
?>

<div class="max-w-lg mx-auto bg-gray-800 p-8 rounded-lg shadow-xl">
    <h1 class="text-3xl font-bold text-center text-yellow-400 mb-6">编辑项目</h1>
    
    <?php if ($success): ?>
        <div class="bg-green-500 text-white p-3 rounded mb-4"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-500 text-white p-3 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="edit_project.php?id=<?php echo $project_id; ?>" method="post">
        <div class="mb-4">
            <label for="name" class="block text-gray-300 mb-2">项目名称</label>
            <input type="text" name="name" id="name" 
                   value="<?php echo htmlspecialchars($project['name']); ?>"
                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <div class="mb-6">
            <label for="description" class="block text-gray-300 mb-2">项目描述 (可选)</label>
            <textarea name="description" id="description" rows="4" 
                      class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500"><?php echo htmlspecialchars($project['description']); ?></textarea>
        </div>
        
        <div class="bg-gray-700 p-4 rounded-lg mb-6">
            <h3 class="text-lg font-semibold text-yellow-300 mb-2">项目信息</h3>
            <p class="text-gray-400 text-sm">创建时间：<?php echo date('Y年m月d日 H:i', strtotime($project['created_at'])); ?></p>
            <?php
            // 获取项目统计信息
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as user_count, COUNT(*) as log_count FROM work_logs_new WHERE project_id = ?");
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            ?>
            <p class="text-gray-400 text-sm">参与人数：<?php echo $stats['user_count']; ?> 人</p>
            <p class="text-gray-400 text-sm">日志记录：<?php echo $stats['log_count']; ?> 条</p>
        </div>
        
        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg transition duration-300">
            更新项目
        </button>
    </form>
    
    <div class="mt-4 text-center">
        <a href="index.php" class="text-gray-400 hover:text-yellow-400">&larr; 返回管理后台</a>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>