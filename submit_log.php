<?php
require_once 'templates/header.php';
check_login('login.php');

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($project_id <= 0) {
    header("Location: index.php");
    exit;
}

$items = $conn->query("SELECT * FROM items ORDER BY name ASC");
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];
    $submitted_at = $_POST['submitted_at'];
    $user_id = $_SESSION['user_id'];

    // 文件上传处理
    $target_dir = "uploads/";
    $image_name = time() . '_' . basename($_FILES["work_image"]["name"]);
    $target_file = $target_dir . $image_name;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // 检查文件是否为真实图片
    $check = getimagesize($_FILES["work_image"]["tmp_name"]);
    if($check === false) {
        $error = "文件不是一个有效的图片。";
        $uploadOk = 0;
    }
    // 限制文件大小 (例如 5MB)
    if ($_FILES["work_image"]["size"] > 5000000) {
        $error = "抱歉, 您的图片文件过大。";
        $uploadOk = 0;
    }
    // 允许特定格式
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $error = "抱歉, 只允许上传 JPG, JPEG, PNG & GIF 格式的图片。";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        // 错误信息已设置
    } else {
        if (move_uploaded_file($_FILES["work_image"]["tmp_name"], $target_file)) {
            // 获取杂物单价
            $item_stmt = $conn->prepare("SELECT price FROM items WHERE id = ?");
            $item_stmt->bind_param("i", $item_id);
            $item_stmt->execute();
            $item_result = $item_stmt->get_result()->fetch_assoc();
            $item_price = $item_result['price'];

            $total_amount = $item_price * $quantity;

            // 插入数据库
            $insert_stmt = $conn->prepare("INSERT INTO work_logs (user_id, project_id, item_id, quantity, image_path, total_amount, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("iiiisis", $user_id, $project_id, $item_id, $quantity, $image_name, $total_amount, $submitted_at);

            if ($insert_stmt->execute()) {
                $success = "工作日志提交成功! 等待管理员审核。";
            } else {
                $error = "数据库插入失败: " . $conn->error;
            }
        } else {
            $error = "抱歉, 上传图片时发生错误。";
        }
    }
}
?>
<div class="max-w-lg mx-auto bg-gray-800 p-8 rounded-lg shadow-xl">
    <h1 class="text-3xl font-bold text-center text-yellow-400 mb-6">提交工作日志</h1>
    <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>

    <form action="submit_log.php?project_id=<?php echo $project_id; ?>" method="post" enctype="multipart/form-data">
        <div class="mb-4">
            <label for="submitted_at" class="block text-gray-300 mb-2">工作日期</label>
            <input type="date" name="submitted_at" id="submitted_at" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <div class="mb-4">
            <label for="item_id" class="block text-gray-300 mb-2">杂物种类</label>
            <select name="item_id" id="item_id" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
                <option value="">-- 请选择杂物 --</option>
                <?php while($item = $items->fetch_assoc()): ?>
                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?> (<?php echo format_silver($item['price']); ?>/个)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-4">
            <label for="quantity" class="block text-gray-300 mb-2">打到数量</label>
            <input type="number" name="quantity" id="quantity" min="1" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <div class="mb-6">
            <label for="work_image" class="block text-gray-300 mb-2">上传工作图片 (截图)</label>
            <input type="file" name="work_image" id="work_image" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100" required>
        </div>
        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg transition duration-300">提交记录</button>
    </form>
    <div class="mt-4 text-center">
        <a href="view_project.php?id=<?php echo $project_id; ?>" class="text-gray-400 hover:text-yellow-400">&larr; 返回项目详情</a>
    </div>
</div>
<?php require_once 'templates/footer.php'; ?>