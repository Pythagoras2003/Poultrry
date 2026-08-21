<?php
// Handle submission without performing PHP header redirects (prevent headers already sent)
$auto_redirect = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || $phone === '' || $username === '' || $password === '') {
        flash('All fields are required.', 'error');
    } elseif (strlen($password) < 6) {
        flash('Password must be at least 6 characters long.', 'error');
    } else {
        // check username uniqueness
        $exists = get_row($pdo, 'SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);
        if ($exists) {
            flash('That username is already taken. Please choose another.', 'error');
        } else {
            // create customer
            $stmt = $pdo->prepare('INSERT INTO customers (name, phone, source, notes, created_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $phone, 'self-register', '', date('Y-m-d H:i:s')]);
            $customer_id = $pdo->lastInsertId();
            // create user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, role, customer_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $hash, 'customer', $customer_id]);
            // Auto-login newly registered user
            $_SESSION['user'] = $username;
            $_SESSION['role'] = 'customer';
            $_SESSION['customer_id'] = $customer_id;
            flash('Account created and you are now logged in.', 'success');
            // perform client-side redirect after page renders
            $auto_redirect = 'index.php?page=orders';
        }
    }
}
?>
<div class="login-box card">
    <h1>Create an account</h1>
    <?php show_flash(); ?>
    <form action="index.php?page=register" method="post">
        <label>Full name</label>
        <input type="text" name="name" required>
        <label>Phone</label>
        <input type="text" name="phone" required>
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Create account</button>
    </form>
    <p class="hint">After registering you will be able to view your orders and profile.</p>
</div>
<?php if (!empty($auto_redirect)): ?>
    <script>window.location.href = '<?php echo h($auto_redirect); ?>';</script>
<?php endif; ?>
