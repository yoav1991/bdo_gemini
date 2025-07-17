<?php
require_once 'templates/header.php';
check_login('login.php');

$projects = $conn->query("SELECT * FROM projects ORDER BY created_at DESC");
?>

<h1 class="text-3xl font-bold text-yellow-400 mb-6">当前代练项目</h1>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if ($projects->num_rows > 0): ?>
        <?php while($project = $projects->fetch_assoc()): ?>
            <div class="bg-gray-800 rounded-lg shadow-xl p-6 hover:shadow-2xl transition-shadow duration-300">
                <h2 class="text-2xl font-bold text-yellow-300 mb-2"><?php echo htmlspecialchars($project['name']); ?></h2>
                <p class="text-gray-400 mb-4"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                <div class="mt-4">
                    <a href="view_project.php?id=<?php echo $project['id']; ?>" class="inline-block bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded transition duration-300">查看详情 & 提交日志</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-gray-400">当前没有进行中的项目。</p>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>