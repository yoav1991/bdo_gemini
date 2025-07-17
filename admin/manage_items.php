<?php
require_once '../templates/header.php';
check_login('../login.php');
check_admin('../index.php');

// 处理添加杂物
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $name = trim($_POST['name']);
    $price = (int)$_POST['price'];
    if (!empty($name) && $price > 0) {
        $stmt = $conn->prepare("INSERT INTO items (name, price) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $price);
        $stmt->execute();
        $stmt->close();
    }
}
// 处理删除杂物
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    // 注意: 在生产环境中, 你可能不想真的删除, 而是标记为已删除
    // 并且要检查该杂物是否被日志引用
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_items.php");
    exit;
}

$items = $conn->query("SELECT * FROM items ORDER BY created_at DESC");
?>
<h1 class="text-3xl font-bold text-yellow-400 mb-6">管理杂物和价格</h1>

<div class="bg-gray-800 p-6 rounded-lg shadow-xl mb-8">
    <h2 class="text-2xl font-bold text-yellow-300 mb-4">添加新杂物</h2>
    <form action="manage_items.php" method="post" class="flex items-end gap-4">
        <div class="flex-grow">
            <label for="name" class="block text-gray-300 mb-2">杂物名称</label>
            <input type="text" name="name" id="name" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <div class="flex-grow">
            <label for="price" class="block text-gray-300 mb-2">杂物单价 (银币)</label>
            <input type="number" name="price" id="price" min="1" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <button type="submit" name="add_item" class="bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg transition duration-300">添加</button>
    </form>
</div>

<div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-700">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">名称</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">单价</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php if ($items->num_rows > 0): ?>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-yellow-400"><?php echo format_silver($item['price']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="manage_items.php?delete_id=<?php echo $item['id']; ?>" onclick="return confirm('确定要删除这个杂物吗?');" class="text-red-500 hover:text-red-400">删除</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                 <tr><td colspan="3" class="text-center py-4 text-gray-400">没有杂物数据。</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../templates/footer.php'; ?>