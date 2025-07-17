<?php
require_once '../templates/header.php';
check_login('../login.php');
check_admin('../index.php');

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    header("Location: index.php"); exit;
}

// 获取项目信息
$project = $conn->query("SELECT * FROM projects WHERE id = $project_id")->fetch_assoc();
if (!$project) { echo "项目不存在。"; exit; }

// 获取所有该项目的日志 (按用户分组)
$sql = "SELECT 
    wl.id as log_id,
    wl.user_id,
    wl.work_date,
    wl.status,
    wl.created_at,
    u.username,
    GROUP_CONCAT(CONCAT(i.name, ':', wli.quantity, ':', wli.total_amount) SEPARATOR '|') as items_detail,
    SUM(wli.total_amount) as total_amount
FROM work_logs_new wl
JOIN users u ON wl.user_id = u.id
JOIN work_log_items wli ON wl.id = wli.log_id
JOIN items i ON wli.item_id = i.id
WHERE wl.project_id = ?
GROUP BY wl.id
ORDER BY u.username, wl.work_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

$logs_by_user = [];
while ($row = $result->fetch_assoc()) {
    // 获取该日志的所有图片
    $img_stmt = $conn->prepare("SELECT image_path FROM work_log_images WHERE log_id = ?");
    $img_stmt->bind_param("i", $row['log_id']);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    $row['images'] = [];
    while ($img = $img_result->fetch_assoc()) {
        $row['images'][] = $img['image_path'];
    }
    $img_stmt->close();
    
    // 解析杂物详情
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
    
    $logs_by_user[$row['username']][] = $row;
}
?>

<!-- 添加图片预览样式 -->
<style>
.image-preview {
    position: relative;
    display: inline-block;
}

.image-preview img {
    transition: transform 0.2s ease;
}

.image-preview:hover img {
    transform: scale(1.05);
}

.image-tooltip {
    display: none;
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    z-index: 50;
    margin-bottom: 10px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    border-radius: 8px;
    overflow: hidden;
}

.image-preview:hover .image-tooltip {
    display: block;
}
</style>

<h1 class="text-3xl font-bold text-yellow-400 mb-4">审核项目: <?php echo htmlspecialchars($project['name']); ?></h1>

<div class="space-y-8">
<?php if (empty($logs_by_user)): ?>
    <p class="text-gray-400 bg-gray-800 p-4 rounded-lg">该项目还没有任何提交记录。</p>
<?php else: ?>
    <?php foreach ($logs_by_user as $username => $logs): ?>
        <?php
            // 计算每个用户的总收入
            $total_income = 0;
            $approved_income = 0;
            foreach ($logs as $log) {
                $total_income += $log['total_amount'];
                if ($log['status'] == 'approved') {
                    $approved_income += $log['total_amount'];
                }
            }
        ?>
        <div class="bg-gray-800 rounded-lg shadow-xl" x-data="{ open: true }">
            <div @click="open = !open" class="p-4 flex justify-between items-center cursor-pointer hover:bg-gray-700/50">
                <h2 class="text-2xl font-semibold text-white"><?php echo htmlspecialchars($username); ?></h2>
                <div class="flex items-center">
                    <span class="text-lg text-gray-300 mr-4">已审核: <span class="font-bold text-green-400"><?php echo format_silver($approved_income); ?></span></span>
                    <span class="text-lg text-gray-300">预计总计: <span class="font-bold text-yellow-400"><?php echo format_silver($total_income); ?></span></span>
                    <button class="ml-4 text-gray-400 hover:text-white">
                        <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                    </button>
                </div>
            </div>
            <div x-show="open" class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">日期</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">杂物明细</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">截图</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">总金额</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">状态/操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-700/50">
                            <td class="px-4 py-3 whitespace-nowrap"><?php echo htmlspecialchars($log['work_date']); ?></td>
                            <td class="px-4 py-3">
                                <div class="space-y-1">
                                    <?php foreach ($log['items'] as $item): ?>
                                        <div class="text-sm">
                                            <span class="text-gray-300"><?php echo htmlspecialchars($item['name']); ?></span>
                                            <span class="text-gray-400"> × <?php echo number_format($item['quantity']); ?></span>
                                            <span class="text-yellow-400"> = <?php echo format_silver($item['amount']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>