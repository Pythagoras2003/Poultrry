<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_GET['action'] ?? '') === 'collect') {
        $crate_30 = max(0, intval($_POST['crate_30'] ?? 0));
        $crate_60 = max(0, intval($_POST['crate_60'] ?? 0));
        $singles = max(0, intval($_POST['singles'] ?? 0));
        $notes = trim($_POST['notes'] ?? '');
        if ($crate_30 === 0 && $crate_60 === 0 && $singles === 0) {
            flash('Enter at least one egg count to record collection.', 'error');
        } else {
            $result = update_egg_collection_stock($pdo, $crate_30, $crate_60, $singles);
            $stmt = $pdo->prepare('INSERT INTO egg_collections (collected_at, crate_30, crate_60, singles, notes) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([date('Y-m-d H:i:s'), $result['crate_30'], $result['crate_60'], $result['singles'], $notes]);
            flash('Egg collection recorded and inventory updated.');
        }
        header('Location: index.php?page=eggs');
        exit;
    }

    if (($_GET['action'] ?? '') === 'sell') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        $unit_price = max(0, floatval($_POST['unit_price'] ?? 0));
        $notes = trim($_POST['notes'] ?? '');
        $product = get_row($pdo, 'SELECT * FROM products WHERE id = ?', [$product_id]);

        if (!$product) {
            flash('Select a valid product to sell.', 'error');
        } elseif ($unit_price <= 0) {
            flash('Enter a valid unit price.', 'error');
        } else {
            if ($product['name'] === 'Single egg') {
                if (!ensure_single_egg_stock($pdo, $quantity)) {
                    flash('Not enough single eggs available to complete the sale.', 'error');
                    header('Location: index.php?page=eggs');
                    exit;
                }
            } elseif ($product['stock'] < $quantity) {
                flash('Not enough stock available for this product.', 'error');
                header('Location: index.php?page=eggs');
                exit;
            }

            $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$quantity, $product_id]);
            $total = $unit_price * $quantity;
            $stmt = $pdo->prepare('INSERT INTO orders (customer_id, product_id, quantity, total, status, created_at) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([null, $product_id, $quantity, $total, 'walk-in', date('Y-m-d H:i:s')]);
            flash('Walk-in egg sale recorded. Inventory updated.');
        }
        header('Location: index.php?page=eggs');
        exit;
    }
}
$egg_collections = get_rows($pdo, 'SELECT * FROM egg_collections ORDER BY collected_at DESC');
$walkin_sales = get_rows($pdo, 'SELECT o.*, p.name AS product_name FROM orders o LEFT JOIN products p ON o.product_id = p.id WHERE o.status = ? ORDER BY o.created_at DESC LIMIT 20', ['walk-in']);
$products = get_rows($pdo, 'SELECT * FROM products WHERE name IN ("Egg crate 30", "Egg crate 60", "Single egg")');
$egg_totals = [
    'crate_30' => 0,
    'crate_60' => 0,
    'singles' => 0,
];
foreach ($products as $item) {
    if ($item['name'] === 'Egg crate 30') {
        $egg_totals['crate_30'] = $item['stock'];
    }
    if ($item['name'] === 'Egg crate 60') {
        $egg_totals['crate_60'] = $item['stock'];
    }
    if ($item['name'] === 'Single egg') {
        $egg_totals['singles'] = $item['stock'];
    }
}
?>
<div class="grid stats-grid">
    <div class="card stat-card">
        <h3>30-crates</h3>
        <span><?php echo h($egg_totals['crate_30']); ?></span>
    </div>
    <div class="card stat-card">
        <h3>60-crates</h3>
        <span><?php echo h($egg_totals['crate_60']); ?></span>
    </div>
    <div class="card stat-card">
        <h3>Singles</h3>
        <span><?php echo h($egg_totals['singles']); ?></span>
    </div>
</div>
<div class="grid">
    <div class="card small-card">
        <h2>Record egg collection</h2>
        <form action="index.php?page=eggs&action=collect" method="post">
            <label>New 30-pack crates collected</label>
            <input type="number" name="crate_30" value="0" min="0">
            <label>New 60-pack crates collected</label>
            <input type="number" name="crate_60" value="0" min="0">
            <label>New single eggs collected</label>
            <input type="number" name="singles" value="0" min="0">
            <label>Notes</label>
            <textarea name="notes"></textarea>
            <button type="submit">Record collection</button>
        </form>
    </div>
    <div class="card small-card">
        <h2>Quick walk-in egg sale</h2>
        <form action="index.php?page=eggs&action=sell" method="post">
            <label>Product</label>
            <select name="product_id" required>
                <option value="">Select</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo h($product['id']); ?>"><?php echo h($product['name'] . ' (Stock: ' . $product['stock'] . ')'); ?></option>
                <?php endforeach; ?>
            </select>
            <label>Quantity sold</label>
            <input type="number" name="quantity" value="1" min="1" required>
            <label>Unit price</label>
            <input type="number" step="0.01" name="unit_price" value="0.00" min="0" required>
            <label>Notes</label>
            <textarea name="notes" placeholder="e.g. walk-in sale details"></textarea>
            <button type="submit">Record sale</button>
        </form>
    </div>
</div>
<div class="grid">
    <div class="card">
        <h2>Egg collection history</h2>
        <table>
            <thead><tr><th>Date</th><th>30s</th><th>60s</th><th>Singles</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($egg_collections as $row): ?>
                <tr>
                    <td><?php echo h($row['collected_at']); ?></td>
                    <td><?php echo h($row['crate_30']); ?></td>
                    <td><?php echo h($row['crate_60']); ?></td>
                    <td><?php echo h($row['singles']); ?></td>
                    <td><?php echo h($row['notes']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Recent walk-in sales</h2>
        <table>
            <thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($walkin_sales as $sale): ?>
                <tr>
                    <td><?php echo h($sale['created_at']); ?></td>
                    <td><?php echo h($sale['product_name']); ?></td>
                    <td><?php echo h($sale['quantity']); ?></td>
                    <td>E<?php echo number_format($sale['total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
