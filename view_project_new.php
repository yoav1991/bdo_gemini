<?php
require_once 'templates/header.php';
check_login('login.php');

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    header("Location: index.php");
    exit;
}

// 获取项目信息
$stmt_project = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt_project->bind_param("i", $project_id);
$stmt_project->execute();
$project_result = $stmt_project->get_result();
if ($project_result->num_rows === 0) {
    echo "项目不存在。";
    exit;
}
$project = $project_result->fetch_assoc();

// 获取该用户在此项目下的日志
$user_id = $_SESSION['user_id'];
$logs_sql = "SELECT 
    wl.id as log_id,
    wl.work_date,
    wl.status,
    wl.created_at,
    GROUP_CONCAT(CONCAT(i.name, ':', wli.quantity, ':', wli.total_amount) SEPARATOR '|') as items_detail,
    SUM(wli.total_amount) as total_amount
FROM work_logs_new wl
JOIN work_log_items wli ON wl.id = wli.log_id
JOIN items i ON wli.item_id = i.id
WHERE wl.user_id = ? AND wl.project_id = ?
GROUP BY wl.id
ORDER BY wl.work_date DESC";

$logs_stmt = $conn->prepare($logs_sql);
$logs_stmt->bind_param("ii", $user_id, $project_id);
$logs_stmt->execute();
$logs_result = $logs_stmt->get_result();

// 处理日志数据
$logs_data = [];
while ($row = $logs_result->fetch_assoc()) {
    // 获取图片
    $img_stmt = $conn->prepare("SELECT image_path FROM work_log_images WHERE log_id = ?");
    $img_stmt->bind_param("i", $row['log_id']);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    $row['images'] = [];
    while ($img = $img_result->fetch_assoc()) {
        $row['images'][] = $img['image_path'];
    }
    
    // 解析杂物
    $row['items'] = [];
    if ($row['items_detail']) {
        $items = explode('|', $row['items_detail']);
        foreach ($items as $item) {
            list($name, $quantity, $amount) = explode(':', $item);
            $row['items'][] = [
                'name' => $name,
                'quantity' => $quantity,
                'amount' => $amount
            ];
        }
    }
    
    $logs_data[] = $row;
}

// 计算总收入
$total_income_approved = 0;
$stmt_income = $conn->prepare("
    SELECT SUM(wli.total_amount) as total 
    FROM work_logs_new wl
    JOIN work_log_items wli ON wl.id = wli.log_id
    WHERE wl.user_id = ? AND wl.project_id = ? AND wl.status = 'approved'
");
$stmt_income->bind_param("ii", $user_id, $project_id);
$stmt_income->execute();
$income_result = $stmt_income->get_result()->fetch_assoc();
$total_income_approved = $income_result['total'] ?? 0;
?>

<h1 class="text-3xl font-bold text-yellow-400 mb-2">项目: <?php echo htmlspecialchars($project['name']); ?></h1>
<p class="text-gray-400 mb-6"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>

<div class="bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-semibold text-white">我的已审核总收入</h2>
            <p class="text-4xl font-bold text-green-400 mt-2"><?php echo format_silver($total_income_approved); ?> 银币</p>
        </div>
        <a href="submit_log_new.php?project_id=<?php echo $project_id; ?>" 
           class="bg-green-500 hover:bg-green-400 text-green-900 font-bold py-3 px-6 rounded-lg transition duration-300">
            提交新的工作日志
        </a>
    </div>
</div>

<h2 class="text-2xl font-bold text-yellow-300 mb-4">我的提交记录</h2>
<div class="space-y-4">
    <?php if (count($logs_data) > 0): ?>
        <?php foreach ($logs_data as $log): ?>
        <div class="bg-gray-800 rounded-lg shadow-xl p-6 hover:shadow-2xl transition-shadow duration-300">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-white">
                        <?php echo date('Y年m月d日', strtotime($log['work_date'])); ?>
                    </h3>
                    <p class="text-sm text-gray-400">提交时间: <?php echo $log['created_at']; ?></p>
                </div>
                <div class="text-right">
                    <?php if ($log['status'] == 'approved'): ?>
                        <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-green-800 text-green-200">
                            已审核
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-yellow-800 text-yellow-200">
                            待审核
                        </span>
                    <?php endif; ?>
                    <p class="text-2xl font-bold text-yellow-400 mt-2"><?php echo format_silver($log['total_amount']); ?></p>
                </div>
            </div>
            
            <!-- 杂物明细 -->
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-300 mb-2">杂物明细:</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <?php foreach ($log['items'] as $item): ?>
                        <div class="bg-gray-700 rounded p-2 text-sm">
                            <span class="text-gray-300"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="text-gray-400"> × <?php echo number_format($item['quantity']); ?></span>
                            <span class="text-yellow-400 float-right"><?php echo format_silver($item['amount']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 截图 -->
            <div>
                <h4 class="text-sm font-medium text-gray-300 mb-2">工作截图:</h4>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($log['images'] as $image): ?>
                        <a href="/uploads/<?php echo htmlspecialchars($image); ?>" target="_blank"
                           class="block hover:opacity-80 transition-opacity">
                            <img src="/uploads/<?php echo htmlspecialchars($image); ?>" 
                                 alt="截图" 
                                 class="w-24 h-24 object-cover rounded border-2 border-gray-600 hover:border-yellow-400">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-gray-800 rounded-lg p-6 text-center text-gray-400">
            你还没有提交任何记录。
        </div>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>