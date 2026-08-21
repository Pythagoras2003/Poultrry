<?php
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    // Customer can update their own profile
    $custId = $_SESSION['customer_id'] ?? 0;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($name === '' || $phone === '') {
            flash('Name and phone are required.', 'error');
        } else {
            $pdo->prepare('UPDATE customers SET name = ?, phone = ?, notes = ? WHERE id = ?')->execute([$name, $phone, $notes, $custId]);
            flash('Profile updated.');
            header('Location: index.php?page=customers');
            exit;
        }
    }
    $customer = get_row($pdo, 'SELECT * FROM customers WHERE id = ?', [$custId]);
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'add') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $source = trim($_POST['source'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($name === '' || $phone === '') {
            flash('Customer name and phone are required.', 'error');
        } else {
            $stmt = $pdo->prepare('INSERT INTO customers (name, phone, source, notes, created_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $phone, $source, $notes, date('Y-m-d H:i:s')]);
            flash('Customer added successfully.');
            header('Location: index.php?page=customers');
            exit;
        }
    }
    $customers = get_rows($pdo, 'SELECT * FROM customers ORDER BY created_at DESC');
}
?>
<div class="card">
    <h2>Customers</h2>
    <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
        <div class="card small-card">
            <h3>Your Profile</h3>
            <?php show_flash(); ?>
            <form action="index.php?page=customers&action=update_profile" method="post">
                <label>Name</label>
                <input type="text" name="name" value="<?php echo h($customer['name'] ?? ''); ?>" required>
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo h($customer['phone'] ?? ''); ?>" required>
                <label>Notes</label>
                <textarea name="notes"><?php echo h($customer['notes'] ?? ''); ?></textarea>
                <button type="submit">Update profile</button>
            </form>
        </div>
    <?php else: ?>
        <div class="grid">
            <div class="card small-card">
                <h3>New customer</h3>
                <form action="index.php?page=customers&action=add" method="post">
                    <label>Name</label>
                    <input type="text" name="name" required>
                    <label>Phone</label>
                    <input type="text" name="phone" required>
                    <label>Source</label>
                    <input type="text" name="source" placeholder="WhatsApp, call, walk-in">
                    <label>Notes</label>
                    <textarea name="notes"></textarea>
                    <button type="submit">Add customer</button>
                </form>
            </div>
            <div class="card small-card">
                <h3>Customer list</h3>
                <table>
                    <thead><tr><th>Name</th><th>Phone</th><th>Source</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo h($customer['name']); ?></td>
                            <td><?php echo h($customer['phone']); ?></td>
                            <td><?php echo h($customer['source']); ?></td>
                            <td><?php echo h($customer['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
