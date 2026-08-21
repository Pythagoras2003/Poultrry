<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'add') {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $delivery_instructions = trim($_POST['delivery_instructions'] ?? '');
    $customer = get_row($pdo, 'SELECT * FROM customers WHERE id = ?', [$customer_id]);
    $product = get_row($pdo, 'SELECT * FROM products WHERE id = ?', [$product_id]);
    if (!$customer || !$product) {
        flash('Valid customer and product are required.', 'error');
    } elseif ($product['stock'] < $quantity) {
        flash('Not enough stock for this product.', 'error');
    } else {
        $total = $product['price'] * $quantity;
        $stmt = $pdo->prepare('INSERT INTO orders (customer_id, product_id, quantity, total, status, delivery_instructions, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$customer_id, $product_id, $quantity, $total, 'confirmed', $delivery_instructions ?: null, date('Y-m-d H:i:s')]);
        $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$quantity, $product_id]);
        flash('Order recorded and inventory updated.');
        header('Location: index.php?page=orders');
        exit;
    }
}
$products = get_rows($pdo, 'SELECT * FROM products ORDER BY category, name');

if (!empty($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    // Customer: show only their orders
    $custId = $_SESSION['customer_id'] ?? 0;
    $orders = get_rows($pdo, 'SELECT o.*, c.name AS customer_name, p.name AS product_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id LEFT JOIN products p ON o.product_id = p.id WHERE o.customer_id = ? ORDER BY o.created_at DESC', [$custId]);
    $customers = [];
} else {
    $customers = get_rows($pdo, 'SELECT * FROM customers ORDER BY created_at DESC');
    $orders = get_rows($pdo, 'SELECT o.*, c.name AS customer_name, p.name AS product_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id LEFT JOIN products p ON o.product_id = p.id ORDER BY o.created_at DESC');
}
?>
<div class="grid">
    <?php if (empty($_SESSION['role']) || $_SESSION['role'] !== 'customer'): ?>
    <div class="card small-card">
        <h2>New Order</h2>
        <form action="index.php?page=orders&action=add" method="post">
            <label>Customer</label>
            <select name="customer_id" required>
                <option value="">Select customer</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo h($customer['id']); ?>"><?php echo h($customer['name'] . ' (' . $customer['phone'] . ')'); ?></option>
                <?php endforeach; ?>
            </select>
            <label>Product</label>
            <select name="product_id" required>
                <option value="">Select a product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo h($product['id']); ?>"><?php echo h($product['name'] . ' - E' . number_format($product['price'], 2) . ' (' . $product['stock'] . ' in stock)'); ?></option>
                <?php endforeach; ?>
            </select>
            <label>Quantity</label>
            <input type="number" name="quantity" value="1" min="1" required>
            <label>Delivery instructions</label>
            <textarea name="delivery_instructions" placeholder="e.g. Deliver to gate B, call on arrival"></textarea>
            <button type="submit">Place order</button>
        </form>
    </div>
    <?php else: ?>
    <div class="card small-card">
        <h2>Your Orders</h2>
        <p class="hint">You can place new orders from the Public page or by contacting the farm.</p>
    </div>
    <?php endif; ?>
    <div class="card small-card">
        <h2>Orders list</h2>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Customer</th><th>Product</th><th>Qty</th><th>Total</th><th>Delivery</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo h($order['customer_name']); ?></td>
                        <td><?php echo h($order['product_name']); ?></td>
                        <td><?php echo h($order['quantity']); ?></td>
                        <td>E<?php echo number_format($order['total'], 2); ?></td>
                        <td><?php echo h($order['delivery_instructions'] ?? ''); ?></td>
                        <td><?php echo h($order['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
