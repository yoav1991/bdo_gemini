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
$sql = "SELECT wl.*, u.username, i.name as item_name
        FROM work_logs wl
        JOIN users u ON wl.user_id = u.id
        JOIN items i ON wl.item_id = i.id
        WHERE wl.project_id = ?
        ORDER BY u.username, wl.submitted_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

$logs_by_user = [];
while ($row = $result->fetch_assoc()) {
    $logs_by_user[$row['username']][] = $row;
}
?>

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
            <div @click="open = !open" class="p-4 flex justify-between items-center cursor-pointer">
                <h2 class="text-2xl font-semibold text-white"><?php echo htmlspecialchars($username); ?></h2>
                <div>
                    <span class="text-lg text-gray-300 mr-4">已审核收入: <span class="font-bold text-green-400"><?php echo format_silver($approved_income); ?></span></span>
                    <span class="text-lg text-gray-300">预计总收入: <span class="font-bold text-yellow-400"><?php echo format_silver($total_income); ?></span></span>
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
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">杂物</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">数量</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">截图</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">金额</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">状态/操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-700/50">
                            <td class="px-4 py-3 whitespace-nowrap"><?php echo htmlspecialchars($log['submitted_at']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?php echo htmlspecialchars($log['item_name']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?php echo number_format($log['quantity']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="/uploads/<?php echo htmlspecialchars($log['image_path']); ?>" target="_blank" class="text-blue-400 hover:underline">查看</a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-yellow-400"><?php echo format_silver($log['total_amount']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?php if ($log['status'] == 'pending'): ?>
                                    <a href="approve_log.php?id=<?php echo $log['id']; ?>&project_id=<?php echo $project_id; ?>" class="px-3 py-1 text-sm font-semibold rounded-full bg-green-600 hover:bg-green-500 text-white">通过审核</a>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-600 text-gray-200">已审核</span>
                                <?php endif; ?>
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