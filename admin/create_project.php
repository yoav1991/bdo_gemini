<?php
require_once '../templates/header.php';
check_login('../login.php');
check_admin('../index.php');

$success = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO projects (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        if ($stmt->execute()) {
            $success = "项目 '" . htmlspecialchars($name) . "' 创建成功!";
        }
        $stmt->close();
    }
}
?>
<div class="max-w-lg mx-auto bg-gray-800 p-8 rounded-lg shadow-xl">
    <h1 class="text-3xl font-bold text-center text-yellow-400 mb-6">创建新项目</h1>
    <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>

    <form action="create_project.php" method="post">
        <div class="mb-4">
            <label for="name" class="block text-gray-300 mb-2">项目名称 (例如: 2025年7月项目)</label>
            <input type="text" name="name" id="name" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <div class="mb-6">
            <label for="description" class="block text-gray-300 mb-2">项目描述 (可选)</label>
            <textarea name="description" id="description" rows="4" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500"></textarea>
        </div>
        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg transition duration-300">创建项目</button>
    </form>
    <div class="mt-4 text-center">
        <a href="index.php" class="text-gray-400 hover:text-yellow-400">&larr; 返回管理后台</a>
    </div>
</div>
<?php require_once '../templates/footer.php'; ?>