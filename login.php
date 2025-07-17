<?php
require_once 'includes/db.php';
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $username, $hashed_password, $role);
        if ($stmt->fetch()) {
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                header("location: index.php");
                exit;
            } else {
                $error = '用户名或密码错误。';
            }
        }
    } else {
        $error = '用户名或密码错误。';
    }
    $stmt->close();
}
require_once 'templates/header.php';
?>
<div class="max-w-md mx-auto bg-gray-800 p-8 rounded-lg shadow-xl">
    <h1 class="text-3xl font-bold text-center text-yellow-400 mb-6">系统登录</h1>
    <?php if ($error): ?>
        <div class="bg-red-500 text-white p-3 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="login.php" method="post">
        <div class="mb-4">
            <label for="username" class="block text-gray-300 mb-2">用户名</label>
            <input type="text" name="username" id="username" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <div class="mb-6">
            <label for="password" class="block text-gray-300 mb-2">密码</label>
            <input type="password" name="password" id="password" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-yellow-500" required>
        </div>
        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg transition duration-300">登录</button>
    </form>
</div>
<?php require_once 'templates/footer.php'; ?>