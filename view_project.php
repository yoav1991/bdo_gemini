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
$logs_stmt = $conn->prepare(
    "SELECT wl.*, i.name as item_name
     FROM work_logs wl
     JOIN items i ON wl.item_id = i.id
     WHERE wl.user_id = ? AND wl.project_id = ?
     ORDER BY wl.submitted_at DESC"
);
$logs_stmt->bind_param("ii", $user_id, $project_id);
$logs_stmt->execute();
$logs_result = $logs_stmt->get_result();

// 计算总收入
$total_income_approved = 0;
$stmt_income = $conn->prepare("SELECT SUM(total_amount) as total FROM work_logs WHERE user_id = ? AND project_id = ? AND status = 'approved'");
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
        <a href="submit_log.php?project_id=<?php echo $project_id; ?>" class="bg-green-500 hover:bg-green-400 text-green-900 font-bold py-3 px-6 rounded-lg transition duration-300">提交新的工作日志</a>
    </div>
</div>

<h2 class="text-2xl font-bold text-yellow-300 mb-4">我的提交记录</h2>
<div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-700">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">日期</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">杂物</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">数量</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">截图</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">金额 (银币)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">状态</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php if ($logs_result->num_rows > 0): ?>
                <?php while($log = $logs_result->fetch_assoc()): ?>
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($log['submitted_at']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($log['item_name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo number_format($log['quantity']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="/uploads/<?php echo htmlspecialchars($log['image_path']); ?>" target="_blank" class="text-blue-400 hover:underline">查看图片</a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-yellow-400"><?php echo format_silver($log['total_amount']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($log['status'] == 'approved'): ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-800 text-green-200">已审核</span>
                        <?php else: ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-800 text-yellow-200">待审核</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-gray-400">你还没有提交任何记录。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'templates/footer.php'; ?>