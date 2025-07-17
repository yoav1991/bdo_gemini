<?php
require_once 'includes/db.php';
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = '两次输入的密码不一致。';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少为6位。';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = '用户名已存在。';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
            $stmt->bind_param("ss", $username, $hashed_password);
            if ($stmt->execute()) {
                header("location: login.php");
                exit;
            } else {
                $error = '注册失败, 请稍后再试。';
            }
        }
        $stmt->close();
    }
}
require_once 'templates/header.php';
?>
<div class="max-w-md mx-auto bg-gray-800 p-8 rounded-lg shadow-xl">
    <h1 class="text-3xl font-bold text-center text-yellow-400 mb-6">代练注册</h1>
    <?php if ($error): ?>
        <div class="bg-red-500 text-white p-3 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="register.php" method="post">
        <div class="mb-4">
            <label for="username" class="block text-gray-300 mb-2">用户名</label>
            <input type="text" name="username" id="username" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <div class="mb-4">
            <label for="password" class="block text-gray-300 mb-2">密码</label>
            <input type="password" name="password" id="password" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <div class="mb-6">
            <label for="confirm_password" class="block text-gray-300 mb-2">确认密码</label>
            <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg transition duration-300">注册</button>
    </form>
</div>
<?php require_once 'templates/footer.php'; ?>