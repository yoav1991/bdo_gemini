<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>黑色沙漠代练工作日志</title>
    <link href="/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-900 text-gray-200 font-sans">
    <nav class="bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between">
                <div class="flex space-x-4">
                    <div>
                        <a href="/index.php" class="flex items-center py-5 px-2 text-gray-200 hover:text-yellow-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-1 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-bold">BDO代练日志</span>
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-1">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="py-5 px-3">欢迎, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="/admin/index.php" class="py-2 px-3 bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold rounded transition duration-300">管理后台</a>
                        <?php endif; ?>
                        <a href="/logout.php" class="py-2 px-3 bg-gray-700 hover:bg-red-600 text-gray-200 font-bold rounded transition duration-300">登出</a>
                    <?php else: ?>
                        <a href="/login.php" class="py-2 px-3 bg-gray-700 hover:bg-gray-600 text-gray-200 font-bold rounded transition duration-300">登录</a>
                        <a href="/register.php" class="py-2 px-3 bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold rounded transition duration-300">注册</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="container mx-auto px-4 py-8">