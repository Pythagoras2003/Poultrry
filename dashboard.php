<?php
$orders = get_rows($pdo, 'SELECT o.*, c.name AS customer_name, p.name AS product_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id LEFT JOIN products p ON o.product_id = p.id ORDER BY o.created_at DESC LIMIT 12');
$customers = get_rows($pdo, 'SELECT * FROM customers ORDER BY created_at DESC LIMIT 8');
$egg_collections = get_rows($pdo, 'SELECT * FROM egg_collections ORDER BY collected_at DESC LIMIT 8');
$notifications = get_rows($pdo, 'SELECT * FROM notifications ORDER BY created_at DESC LIMIT 8');
$batch = get_row($pdo, 'SELECT * FROM broiler_batches ORDER BY created_at DESC LIMIT 1');
$analytics = get_row($pdo, 'SELECT COUNT(*) AS total_orders, IFNULL(SUM(total), 0) AS revenue FROM orders');
$today = get_row($pdo, 'SELECT COUNT(*) AS todays_orders, IFNULL(SUM(total), 0) AS todays_revenue FROM orders WHERE DATE(created_at) = DATE(?)', [date('Y-m-d')]);
$egg_totals = get_row($pdo, 'SELECT IFNULL(SUM(crate_30),0) AS crate_30, IFNULL(SUM(crate_60),0) AS crate_60, IFNULL(SUM(singles),0) AS singles FROM egg_collections');
$stock_total = get_row($pdo, "SELECT IFNULL(SUM(stock),0) AS total_stock FROM products WHERE category = 'eggs'");
$low_stock = get_rows($pdo, "SELECT name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC");
$products = get_rows($pdo, "SELECT * FROM products WHERE category = 'eggs' ORDER BY name");
?>
<div class="grid stats-grid">
    <div class="card stat-card total fade-in">
        <h3>Total orders</h3>
        <span><?php echo h($analytics['total_orders']); ?></span>
        <div class="stat-icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4" stroke="#0f172a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>
    <div class="card stat-card revenue fade-in">
        <h3>Revenue</h3>
        <span>E<?php echo number_format($analytics['revenue'], 2); ?></span>
        <div class="stat-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 1v22M17 5H7a4 4 0 100 8h8a4 4 0 110 8H7" stroke="#0f172a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>
    <div class="card stat-card today fade-in">
        <h3>Today</h3>
        <span><?php echo h($today['todays_orders']); ?> orders</span>
        <div class="stat-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2" stroke="#0f172a" stroke-width="1.6"/><path d="M16 2v4M8 2v4M3 10h18" stroke="#0f172a" stroke-width="1.6" stroke-linecap="round"/></svg>
        </div>
    </div>
    <div class="card stat-card eggs fade-in">
        <h3>Egg stock</h3>
        <span><?php echo h($stock_total['total_stock']); ?> units</span>
        <div class="stat-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2c2.8 0 5 2.7 5 6s-2.2 8-5 10-5-4.7-5-8 2.2-8 5-8z" stroke="#0f172a" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>
</div>

<div class="grid">
    <div class="card alert-card">
        <div class="section-heading">
            <h2>Inventory alerts</h2>
        </div>
        <?php if (!empty($low_stock)): ?>
            <ul class="item-list">
                <?php foreach ($low_stock as $item): ?>
                    <li><?php echo h($item['name']); ?> stock is low: <?php echo h($item['stock']); ?> left</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No low stock items detected. Your inventory is healthy.</p>
        <?php endif; ?>
    </div>
    <div class="card quick-sale">
        <h2>Quick Walk-in Egg Sale</h2>
        <form action="eggs.php" method="post">
            <label>Product
                <select name="product_id">
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo h($p['id']); ?>"><?php echo h($p['name']); ?> (Stock: <?php echo h($p['stock']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="form-row">
                <div>
                    <label>Quantity sold
                        <input type="number" name="quantity" value="1" min="1">
                    </label>
                </div>
                <div>
                    <label>Unit Price
                        <input type="text" name="unit_price" class="price" value="">
                    </label>
                </div>
            </div>
            <button class="button record-btn" type="submit">Record Sale</button>
        </form>
    </div>
</div>
<div class="grid">
    <div class="card">
        <h2>Broiler batch</h2>
        <?php if ($batch): ?>
            <p><strong>Name:</strong> <?php echo h($batch['name']); ?></p>
            <p><strong>Start date:</strong> <?php echo h($batch['start_date']); ?></p>
            <p><strong>Age:</strong> <?php echo h($batch['age_weeks']); ?> weeks</p>
            <p><strong>Pending:</strong> <?php echo h($batch['pending_count']); ?></p>
            <p><strong>Sell date:</strong> <?php echo h($batch['expected_sell_date']); ?></p>
        <?php else: ?>
            <p>No active broiler batch yet.</p>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2>Latest notifications</h2>
        <ul class="item-list">
            <?php foreach ($notifications as $note): ?>
                <li><strong><?php echo h($note['title']); ?>:</strong> <?php echo h($note['message']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h2>Recent orders</h2>
        <table>
            <thead><tr><th>Customer</th><th>Product</th><th>Qty</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo h($order['customer_name']); ?></td>
                    <td><?php echo h($order['product_name']); ?></td>
                    <td><?php echo h($order['quantity']); ?></td>
                    <td>K<?php echo number_format($order['total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2>Recent egg collections</h2>
        <table>
            <thead><tr><th>Date</th><th>30s</th><th>60s</th><th>Singles</th></tr></thead>
            <tbody>
            <?php foreach ($egg_collections as $row): ?>
                <tr>
                    <td><?php echo h($row['collected_at']); ?></td>
                    <td><?php echo h($row['crate_30']); ?></td>
                    <td><?php echo h($row['crate_60']); ?></td>
                    <td><?php echo h($row['singles']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Recent customers</h2>
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
