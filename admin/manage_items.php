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
        header("Location: manage_items.php?success=add");
        exit;
    }
}

// 处理更新杂物
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $item_id = (int)$_POST['item_id'];
    $name = trim($_POST['name']);
    $price = (int)$_POST['price'];
    if ($item_id > 0 && !empty($name) && $price > 0) {
        $stmt = $conn->prepare("UPDATE items SET name = ?, price = ? WHERE id = ?");
        $stmt->bind_param("sii", $name, $price, $item_id);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_items.php?success=update");
        exit;
    }
}

// 处理删除杂物
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // 检查是否有工作日志引用此杂物
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM work_log_items WHERE item_id = ?");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if ($check_result['count'] > 0) {
        header("Location: manage_items.php?error=in_use");
        exit;
    } else {
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_items.php?success=delete");
        exit;
    }
}

// 获取要编辑的杂物信息
$edit_item = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_item = $result->fetch_assoc();
    }
    $stmt->close();
}

$items = $conn->query("SELECT * FROM items ORDER BY created_at DESC");

// 显示消息
$message = '';
$message_type = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'add':
            $message = '杂物添加成功！';
            $message_type = 'success';
            break;
        case 'update':
            $message = '杂物更新成功！';
            $message_type = 'success';
            break;
        case 'delete':
            $message = '杂物删除成功！';
            $message_type = 'success';
            break;
    }
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'in_use') {
        $message = '无法删除：该杂物已被工作日志引用。';
        $message_type = 'error';
    }
}
?>

<h1 class="text-3xl font-bold text-yellow-400 mb-6">管理杂物和价格</h1>

<?php if ($message): ?>
    <div class="<?php echo $message_type == 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white p-3 rounded mb-4">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- 添加/编辑表单 -->
<div class="bg-gray-800 p-6 rounded-lg shadow-xl mb-8">
    <h2 class="text-2xl font-bold text-yellow-300 mb-4">
        <?php echo $edit_item ? '编辑杂物' : '添加新杂物'; ?>
    </h2>
    <form action="manage_items.php" method="post" class="flex items-end gap-4">
        <?php if ($edit_item): ?>
            <input type="hidden" name="item_id" value="<?php echo $edit_item['id']; ?>">
        <?php endif; ?>
        
        <div class="flex-grow">
            <label for="name" class="block text-gray-300 mb-2">杂物名称</label>
            <input type="text" name="name" id="name" 
                   value="<?php echo $edit_item ? htmlspecialchars($edit_item['name']) : ''; ?>"
                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <div class="flex-grow">
            <label for="price" class="block text-gray-300 mb-2">杂物单价 (银币)</label>
            <input type="number" name="price" id="price" min="1" 
                   value="<?php echo $edit_item ? $edit_item['price'] : ''; ?>"
                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <button type="submit" name="<?php echo $edit_item ? 'update_item' : 'add_item'; ?>" 
                class="bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg transition duration-300">
            <?php echo $edit_item ? '更新' : '添加'; ?>
        </button>
        <?php if ($edit_item): ?>
            <a href="manage_items.php" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                取消
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- 杂物列表 -->
<div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-700">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">名称</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">单价</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">创建时间</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php if ($items->num_rows > 0): ?>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-yellow-400"><?php echo format_silver($item['price']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-400 text-sm">
                        <?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="manage_items.php?edit_id=<?php echo $item['id']; ?>" 
                           class="text-blue-500 hover:text-blue-400 mr-3">编辑</a>
                        <a href="manage_items.php?delete_id=<?php echo $item['id']; ?>" 
                           onclick="return confirm('确定要删除这个杂物吗？如果已被日志引用将无法删除。');" 
                           class="text-red-500 hover:text-red-400">删除</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                 <tr><td colspan="4" class="text-center py-4 text-gray-400">没有杂物数据。</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-6 bg-gray-800 p-4 rounded-lg">
    <h3 class="text-lg font-semibold text-yellow-300 mb-2">使用说明：</h3>
    <ul class="list-disc list-inside text-gray-400 space-y-1">
        <li>添加新杂物：在上方表单中填写名称和单价，点击"添加"按钮</li>
        <li>编辑杂物：点击列表中的"编辑"链接，修改信息后点击"更新"按钮</li>
        <li>删除杂物：点击"删除"链接（注意：已被工作日志引用的杂物无法删除）</li>
        <li>杂物价格将用于计算代练人员的收入</li>
    </ul>
</div>

<div class="mt-4 text-center">
    <a href="index.php" class="text-gray-400 hover:text-yellow-400">&larr; 返回管理后台</a>
</div>

<?php require_once '../templates/footer.php'; ?>