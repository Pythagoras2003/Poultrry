<?php
// Export CSV (admin only)
if (isset($_GET['action']) && $_GET['action'] === 'export_csv' && is_admin()) {
    $rows = get_rows($pdo, 'SELECT id, name, price, stock, category FROM products ORDER BY category, name');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventory.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','name','price','stock','category']);
    foreach ($rows as $r) fputcsv($out, [$r['id'], $r['name'], $r['price'], $r['stock'], $r['category']]);
    fclose($out);
    exit;
}

// Handle admin update/delete/create/import actions (supports AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') || (!empty($_POST['ajax']) && $_POST['ajax'] == '1');
    // Create new product
    if (($_POST['action'] ?? '') === 'create') {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? 'eggs');
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO products (name, price, stock, category, created_at) VALUES (?, ?, ?, ?, datetime("now"))');
            $stmt->execute([$name, $price, $stock, $category]);
            $id = $pdo->lastInsertId();
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => true, 'message' => 'Product created', 'id' => $id, 'name' => $name, 'price' => $price, 'stock' => $stock, 'category' => $category]); exit; }
            flash('Product added to inventory.', 'success'); header('Location: index.php?page=inventory'); exit;
        }
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Invalid input']); exit; }
    }
    // Import CSV
    if (($_POST['action'] ?? '') === 'import_csv') {
        if (!empty($_FILES['csvfile']) && $_FILES['csvfile']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['csvfile']['tmp_name'];
            $handle = fopen($tmp, 'r');
            $headers = fgetcsv($handle);
            $count = 0;
            while (($row = fgetcsv($handle)) !== false) {
                // Expecting columns: id,name,price,stock,category (id optional)
                $data = array_combine($headers, $row);
                $name = trim($data['name'] ?? '');
                if ($name === '') continue;
                $price = floatval($data['price'] ?? 0);
                $stock = intval($data['stock'] ?? 0);
                $category = trim($data['category'] ?? 'eggs');
                if (!empty($data['id'])) {
                    // update if exists
                    $pdo->prepare('UPDATE products SET name = ?, price = ?, stock = ?, category = ? WHERE id = ?')->execute([$name, $price, $stock, $category, intval($data['id'])]);
                } else {
                    $pdo->prepare('INSERT INTO products (name, price, stock, category, created_at) VALUES (?, ?, ?, ?, datetime("now"))')->execute([$name, $price, $stock, $category]);
                }
                $count++;
            }
            fclose($handle);
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => true, 'message' => 'Imported', 'count' => $count]); exit; }
            flash('Imported ' . $count . ' products.', 'success'); header('Location: index.php?page=inventory'); exit;
        }
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'No file uploaded']); exit; }
    }
    if (($_POST['action'] ?? '') === 'update') {
        $id = intval($_POST['product_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        if ($id > 0 && $name !== '') {
            $pdo->prepare('UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?')->execute([$name, $price, $stock, $id]);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Product updated', 'id' => $id, 'name' => $name, 'price' => $price, 'stock' => $stock]);
                exit;
            }
            flash('Product updated.', 'success');
            header('Location: index.php?page=inventory');
            exit;
        }
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Invalid input']); exit; }
    }
    if (($_POST['action'] ?? '') === 'delete') {
        $id = intval($_POST['product_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Product deleted', 'id' => $id]);
                exit;
            }
            flash('Product removed from inventory.', 'success');
            header('Location: index.php?page=inventory');
            exit;
        }
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Invalid product id']); exit; }
    }
}

$egg_stock = get_row($pdo, "SELECT IFNULL(SUM(stock),0) AS total_egg_stock FROM products WHERE category = 'eggs'");
$broiler_stock = get_row($pdo, "SELECT IFNULL(SUM(stock),0) AS total_broiler_stock FROM products WHERE category = 'broiler'");
$breakdown = get_rows($pdo, 'SELECT id, name, stock, price FROM products WHERE category IN ("eggs","broiler") ORDER BY category, name');
?>
<div class="grid stats-grid">
    <div class="card stat-card">
        <h3>Total broilers</h3>
        <span><?php echo h($broiler_stock['total_broiler_stock']); ?></span>
    </div>
    <div class="card stat-card">
        <h3>Total eggs</h3>
        <span><?php echo h($egg_stock['total_egg_stock']); ?></span>
    </div>
</div>
<div class="card">
    <h2>Inventory breakdown</h2>
    <table>
        <thead><tr><th>Item</th><th>Stock</th><th>Price</th><th>Value</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($breakdown as $item): ?>
            <tr data-product-id="<?php echo h($item['id']); ?>">
                <td class="p-name"><?php echo h($item['name']); ?></td>
                <td class="p-stock"><?php echo h($item['stock']); ?></td>
                <td class="p-price">E<?php echo number_format($item['price'], 2); ?></td>
                <td class="p-value">E<?php echo number_format($item['stock'] * $item['price'], 2); ?></td>
                <td>
                    <?php if (is_admin()): ?>
                        <button class="button" data-inv-edit-id="<?php echo h($item['id']); ?>" data-inv-name="<?php echo h($item['name']); ?>" data-inv-price="<?php echo number_format($item['price'], 2, '.', ''); ?>" data-inv-stock="<?php echo h($item['stock']); ?>">Edit</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="grid">
    <div class="card">
        <h2>Fast actions</h2>
        <div class="action-grid">
            <a class="button" href="index.php?page=eggs">Record eggs</a>
            <a class="button" href="index.php?page=orders">New order</a>
            <a class="button" href="index.php?page=broilers">Update batch</a>
            <?php if (is_admin()): ?>
                <button class="button" id="addProductBtn">Add product</button>
                <a class="button" href="index.php?page=inventory&action=export_csv">Export CSV</a>
                <button class="button" id="importCsvBtn">Import CSV</button>
                <form id="csvImportForm" action="index.php?page=inventory" method="post" enctype="multipart/form-data" style="display:none;">
                    <input type="hidden" name="action" value="import_csv">
                    <input type="file" name="csvfile" id="csvFileInput" accept=".csv">
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
