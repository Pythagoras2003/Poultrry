<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'save') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = max(0, floatval($_POST['price'] ?? 0));
    $unit = trim($_POST['unit'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $stock = max(0, intval($_POST['stock'] ?? 0));
    if ($name === '' || $category === '') {
        flash('Product name and category are required.', 'error');
    } else {
        $pdo->prepare('INSERT INTO products (name, description, price, unit, category, stock, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$name, $description, $price, $unit, $category, $stock, date('Y-m-d H:i:s')]);
        flash('Product added successfully.');
        header('Location: index.php?page=products');
        exit;
    }
}
$products = get_rows($pdo, 'SELECT * FROM products ORDER BY category, name');
?>
<div class="grid">
    <div class="card small-card">
        <h2>Add product</h2>
        <form action="index.php?page=products&action=save" method="post">
            <label>Name</label>
            <input type="text" name="name" required>
            <label>Description</label>
            <input type="text" name="description">
            <label>Price</label>
            <input type="number" step="0.01" name="price" value="0.00" min="0" required>
            <label>Unit</label>
            <input type="text" name="unit" placeholder="bird, crate, egg">
            <label>Category</label>
            <input type="text" name="category" placeholder="broiler, eggs" required>
            <label>Stock</label>
            <input type="number" name="stock" value="0" min="0" required>
            <button type="submit">Save product</button>
        </form>
    </div>
    <div class="card small-card">
        <h2>Product catalog</h2>
        <table>
            <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Share</th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <?php $stockStatus = $product['stock'] > 20 ? 'Ready' : ($product['stock'] > 5 ? 'Low' : 'Almost gone'); ?>
                <?php $message = rawurlencode("Product update: {$product['name']} - E" . number_format($product['price'], 2) . " (Stock: {$product['stock']} - {$stockStatus}). Reply to book or order."); ?>
                <tr>
                    <td><?php echo h($product['name']); ?></td>
                    <td><?php echo h($product['category']); ?></td>
                    <td>E<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo h($product['stock']); ?></td>
                    <td><span class="status-label status-<?php echo strtolower(str_replace(' ', '-', $stockStatus)); ?>"><?php echo h($stockStatus); ?></span></td>
                    <td>
                        <a class="button whatsapp-button" href="https://wa.me/?text=<?php echo $message; ?>" target="_blank" rel="noopener">Share</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
