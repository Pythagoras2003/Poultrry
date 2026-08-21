<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = get_row($pdo, 'SELECT * FROM users WHERE username = ?', [$username]);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'] ?? 'admin';
        $_SESSION['customer_id'] = !empty($user['customer_id']) ? $user['customer_id'] : null;
        // Redirect based on role
        if (isset($user['role']) && $user['role'] === 'customer') {
            header('Location: index.php?page=orders');
        } else {
            header('Location: index.php?page=dashboard');
        }
        exit;
    }
    flash('Invalid username or password.', 'error');
    header('Location: index.php?page=login');
    exit;
}
?>
<div class="login-box card">
    <h1>Login to Farm System</h1>
    <?php show_flash(); ?>
    <form action="index.php?page=login" method="post">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Sign In</button>
    </form>
    <p class="hint">Default admin credentials: <strong>admin</strong> / <strong>farm1234</strong></p>
</div>
