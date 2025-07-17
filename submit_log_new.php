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
    $work_date = $_POST['work_date'];
    $user_id = $_SESSION['user_id'];
    $item_data = $_POST['items'] ?? [];
    
    // 验证是否有杂物数据
    if (empty($item_data)) {
        $error = "请至少选择一种杂物并填写数量。";
    } else {
        // 开始事务
        $conn->begin_transaction();
        
        try {
            // 插入主日志记录
            $stmt = $conn->prepare("INSERT INTO work_logs_new (user_id, project_id, work_date) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $project_id, $work_date);
            $stmt->execute();
            $log_id = $conn->insert_id;
            $stmt->close();
            
            // 插入杂物详情
            $total_amount_sum = 0;
            foreach ($item_data as $item_id => $quantity) {
                if ($quantity > 0) {
                    // 获取杂物单价
                    $price_stmt = $conn->prepare("SELECT price FROM items WHERE id = ?");
                    $price_stmt->bind_param("i", $item_id);
                    $price_stmt->execute();
                    $price_result = $price_stmt->get_result()->fetch_assoc();
                    $item_price = $price_result['price'];
                    $price_stmt->close();
                    
                    $total_amount = $item_price * $quantity;
                    $total_amount_sum += $total_amount;
                    
                    // 插入杂物记录
                    $item_stmt = $conn->prepare("INSERT INTO work_log_items (log_id, item_id, quantity, total_amount) VALUES (?, ?, ?, ?)");
                    $item_stmt->bind_param("iiid", $log_id, $item_id, $quantity, $total_amount);
                    $item_stmt->execute();
                    $item_stmt->close();
                }
            }
            
            // 处理多图片上传
            $uploaded_files = 0;
            if (isset($_FILES['work_images'])) {
                $upload_dir = "uploads/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['work_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['work_images']['error'][$key] == 0) {
                        $file_name = $_FILES['work_images']['name'][$key];
                        $file_size = $_FILES['work_images']['size'][$key];
                        $file_tmp = $_FILES['work_images']['tmp_name'][$key];
                        $file_type = $_FILES['work_images']['type'][$key];
                        
                        // 验证文件
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!in_array($file_type, $allowed_types)) {
                            throw new Exception("不支持的文件类型: " . $file_name);
                        }
                        
                        if ($file_size > 5000000) { // 5MB
                            throw new Exception("文件过大: " . $file_name);
                        }
                        
                        // 生成唯一文件名
                        $unique_name = $user_id . '_' . $log_id . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $file_name);
                        $target_file = $upload_dir . $unique_name;
                        
                        if (move_uploaded_file($file_tmp, $target_file)) {
                            // 保存图片记录
                            $img_stmt = $conn->prepare("INSERT INTO work_log_images (log_id, image_path) VALUES (?, ?)");
                            $img_stmt->bind_param("is", $log_id, $unique_name);
                            $img_stmt->execute();
                            $img_stmt->close();
                            $uploaded_files++;
                        }
                    }
                }
            }
            
            if ($uploaded_files == 0) {
                throw new Exception("请至少上传一张截图。");
            }
            
            // 提交事务
            $conn->commit();
            $success = "工作日志提交成功！共记录 " . count(array_filter($item_data, function($q) { return $q > 0; })) . " 种杂物，上传 " . $uploaded_files . " 张截图。";
            
        } catch (Exception $e) {
            // 回滚事务
            $conn->rollback();
            $error = "提交失败: " . $e->getMessage();
        }
    }
}
?>

<div class="max-w-4xl mx-auto bg-gray-800 p-8 rounded-lg shadow-xl">
    <h1 class="text-3xl font-bold text-center text-yellow-400 mb-6">提交工作日志</h1>
    <?php if ($error): ?><div class="bg-red-500 text-white p-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="bg-green-500 text-white p-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>

    <form action="submit_log_new.php?project_id=<?php echo $project_id; ?>" method="post" enctype="multipart/form-data" x-data="{ 
        items: {},
        addItem(itemId, itemName, itemPrice) {
            if (!this.items[itemId]) {
                this.items[itemId] = { name: itemName, price: itemPrice, quantity: 0 };
            }
        },
        removeItem(itemId) {
            delete this.items[itemId];
        },
        calculateTotal() {
            let total = 0;
            for (let itemId in this.items) {
                total += this.items[itemId].price * this.items[itemId].quantity;
            }
            return total;
        }
    }">
        <div class="mb-4">
            <label for="work_date" class="block text-gray-300 mb-2">工作日期</label>
            <input type="date" name="work_date" id="work_date" value="<?php echo date('Y-m-d'); ?>" 
                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        
        <!-- 杂物选择部分 -->
        <div class="mb-6">
            <label class="block text-gray-300 mb-2">选择杂物种类</label>
            <div class="bg-gray-700 p-4 rounded-lg">
                <select id="item_selector" class="w-full px-4 py-2 bg-gray-600 border border-gray-500 rounded-lg focus:outline-none focus:border-yellow-500 mb-4">
                    <option value="">-- 选择要添加的杂物 --</option>
                    <?php 
                    $items->data_seek(0);
                    while($item = $items->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $item['id']; ?>" 
                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                data-price="<?php echo $item['price']; ?>">
                            <?php echo htmlspecialchars($item['name']); ?> (<?php echo format_silver($item['price']); ?>/个)
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <button type="button" @click="
                    const select = document.getElementById('item_selector');
                    if (select.value) {
                        const option = select.selectedOptions[0];
                        addItem(select.value, option.dataset.name, parseInt(option.dataset.price));
                        select.value = '';
                    }
                " class="bg-blue-500 hover:bg-blue-400 text-white font-bold py-2 px-4 rounded">
                    添加杂物
                </button>
                
                <!-- 已选择的杂物列表 -->
                <div class="mt-4 space-y-2">
                    <template x-for="(item, itemId) in items" :key="itemId">
                        <div class="flex items-center justify-between bg-gray-600 p-3 rounded">
                            <div class="flex-1">
                                <span x-text="item.name" class="text-white font-medium"></span>
                                <span class="text-gray-300 text-sm ml-2">(<span x-text="item.price"></span> 银币/个)</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="text-gray-300 text-sm">数量:</label>
                                <input type="number" :name="'items[' + itemId + ']'" x-model.number="item.quantity" 
                                       min="0" class="w-20 px-2 py-1 bg-gray-700 border border-gray-500 rounded text-white">
                                <span class="text-yellow-400 text-sm w-24 text-right" x-text="format_silver(item.price * item.quantity)"></span>
                                <button type="button" @click="removeItem(itemId)" 
                                        class="text-red-400 hover:text-red-300 ml-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    
                    <!-- 总计 -->
                    <div x-show="Object.keys(items).length > 0" class="mt-4 pt-4 border-t border-gray-500">
                        <div class="text-right">
                            <span class="text-gray-300">预计总收入: </span>
                            <span class="text-2xl font-bold text-yellow-400" x-text="format_silver(calculateTotal())"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 图片上传部分 -->
        <div class="mb-6">
            <label for="work_images" class="block text-gray-300 mb-2">上传工作截图 (可多选)</label>
            <input type="file" name="work_images[]" id="work_images" multiple accept="image/*"
                   class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 
                          file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100" required>
            <p class="text-sm text-gray-400 mt-1">支持 JPG, PNG, GIF 格式，每张最大 5MB</p>
        </div>
        
        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg transition duration-300">
            提交记录
        </button>
    </form>
    
    <div class="mt-4 text-center">
        <a href="view_project.php?id=<?php echo $project_id; ?>" class="text-gray-400 hover:text-yellow-400">&larr; 返回项目详情</a>
    </div>
</div>

<script>
// 格式化银币显示
function format_silver(amount) {
    if (amount >= 100000000) {
        return (amount / 100000000).toFixed(4).replace(/\.?0+$/, '') + '亿';
    }
    if (amount >= 10000) {
        return (amount / 10000).toFixed(4).replace(/\.?0+$/, '') + '万';
    }
    return amount.toLocaleString();
}
</script>

<?php require_once 'templates/footer.php'; ?>