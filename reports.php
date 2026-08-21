<?php
$weekly_sales = get_rows($pdo, "SELECT DATE(created_at) AS day, COUNT(*) AS orders, IFNULL(SUM(total), 0) AS revenue FROM orders WHERE created_at >= DATE('now','-6 days') GROUP BY DATE(created_at) ORDER BY day DESC");
$growth_notes = get_rows($pdo, 'SELECT * FROM broiler_batches ORDER BY created_at DESC LIMIT 5');
$egg_inventory = get_row($pdo, "SELECT IFNULL(SUM(stock),0) AS total_egg_stock FROM products WHERE category = 'eggs'");
?>
<div class="grid">
    <div class="card small-card">
        <h2>Weekly sales</h2>
        <table>
            <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php foreach ($weekly_sales as $row): ?>
                <tr>
                    <td><?php echo h($row['day']); ?></td>
                    <td><?php echo h($row['orders']); ?></td>
                    <td>E<?php echo number_format($row['revenue'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card small-card">
        <h2>Egg inventory</h2>
        <p><strong>Total egg stock:</strong> <?php echo h($egg_inventory['total_egg_stock']); ?></p>
        <p>Track how many crates and singles are still available for sale.</p>
    </div>
</div>
<div class="card">
    <h2>Broiler growth log</h2>
    <table>
        <thead><tr><th>Name</th><th>Start</th><th>Age</th><th>Pending</th><th>Sell date</th></tr></thead>
        <tbody>
        <?php foreach ($growth_notes as $note): ?>
            <tr>
                <td><?php echo h($note['name']); ?></td>
                <td><?php echo h($note['start_date']); ?></td>
                <td><?php echo h($note['age_weeks']); ?> weeks</td>
                <td><?php echo h($note['pending_count']); ?></td>
                <td><?php echo h($note['expected_sell_date']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
