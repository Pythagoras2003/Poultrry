<?php
// Admin contact for order notifications (update as needed)
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', 'admin@example.com');
function init_db() {
    $dbDir = __DIR__ . '/data';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    $dbFile = $dbDir . '/farm.db';
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY,
        username TEXT UNIQUE,
        password TEXT
    )");
    // Ensure users table has role and customer_id columns
    $userCols = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('role', $userCols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN role TEXT');
        $pdo->exec("UPDATE users SET role = 'admin' WHERE role IS NULL");
    }
    if (!in_array('customer_id', $userCols, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN customer_id INTEGER');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY,
        name TEXT,
        phone TEXT,
        source TEXT,
        notes TEXT,
        created_at TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY,
        name TEXT UNIQUE,
        description TEXT,
        price REAL,
        unit TEXT,
        category TEXT,
        stock INTEGER,
        created_at TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY,
        customer_id INTEGER,
        product_id INTEGER,
        quantity INTEGER,
        total REAL,
        status TEXT,
        delivery_instructions TEXT,
        pickup_date TEXT,
        delivery_date TEXT,
        created_at TEXT,
        FOREIGN KEY(customer_id) REFERENCES customers(id),
        FOREIGN KEY(product_id) REFERENCES products(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS broiler_batches (
        id INTEGER PRIMARY KEY,
        name TEXT,
        start_date TEXT,
        age_weeks INTEGER,
        pending_count INTEGER,
        expected_sell_date TEXT,
        notes TEXT,
        created_at TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS egg_collections (
        id INTEGER PRIMARY KEY,
        collected_at TEXT,
        crate_30 INTEGER,
        crate_60 INTEGER,
        singles INTEGER,
        notes TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY,
        title TEXT,
        message TEXT,
        created_at TEXT
    )");

    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('farm1234', PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)')->execute(['admin', $password]);
    }

    $columns = $pdo->query('PRAGMA table_info(products)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('created_at', $columns, true)) {
        $pdo->exec('ALTER TABLE products ADD COLUMN created_at TEXT');
    }

    // Ensure orders table has pickup_date and delivery_date columns (safe for existing DBs)
    $orderCols = $pdo->query('PRAGMA table_info(orders)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('pickup_date', $orderCols, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN pickup_date TEXT');
    }
    if (!in_array('delivery_date', $orderCols, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN delivery_date TEXT');
    }
    if (!in_array('delivery_instructions', $orderCols, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN delivery_instructions TEXT');
    }

    $stmt = $pdo->query('SELECT COUNT(*) FROM products');
    if ($stmt->fetchColumn() == 0) {
        $products = [
            ['Broiler bird', 'Live broiler ready to sell', 100.00, 'bird', 'broiler', 0],
            ['Egg crate 30', '30-pack eggs', 70.00, 'crate', 'eggs', 0],
            ['Egg crate 60', '60-pack eggs', 120.00, 'crate', 'eggs', 0],
            ['Single egg', 'Individual egg', 3.00, 'egg', 'eggs', 0],
        ];
        $insert = $pdo->prepare('INSERT INTO products (name, description, price, unit, category, stock, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($products as $product) {
            $insert->execute([
                $product[0],
                $product[1],
                $product[2],
                $product[3],
                $product[4],
                $product[5],
                date('Y-m-d H:i:s'),
            ]);
        }
    }

    // Ensure broiler products use E100 price across existing DB
    try {
        $pdo->prepare('UPDATE products SET price = ? WHERE category = ?')->execute([100.00, 'broiler']);
    } catch (Exception $e) {
        // ignore
    }

    // Set stock to zero for all existing products so admin fills inventory
    try {
        $pdo->prepare('UPDATE products SET stock = 0')->execute();
    } catch (Exception $e) {
        // ignore
    }

    // Clean up any stray product entries accidentally added (e.g., names used for testing)
    // Remove product named 'Mayibongwe Khumalo' if present.
    try {
        $pdo->prepare('DELETE FROM products WHERE name = ?')->execute(['Mayibongwe Khumalo']);
    } catch (Exception $e) {
        // ignore
    }

    return $pdo;
}

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flash($message, $type = 'success') {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function show_flash() {
    if (empty($_SESSION['flash'])) {
        return;
    }
    foreach ($_SESSION['flash'] as $flash) {
        $class = $flash['type'] === 'error' ? 'alert' : 'success';
        echo '<div class="' . $class . '">' . h($flash['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

function get_rows($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_row($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_product_by_name($pdo, $name) {
    return get_row($pdo, 'SELECT * FROM products WHERE name = ?', [$name]);
}

function ensure_single_egg_stock($pdo, $needed) {
    $single = get_product_by_name($pdo, 'Single egg');
    if (!$single) {
        return false;
    }
    if ($single['stock'] >= $needed) {
        return true;
    }

    $needed_more = $needed - $single['stock'];
    $crate30 = get_product_by_name($pdo, 'Egg crate 30');
    if ($crate30 && $crate30['stock'] > 0) {
        $use_crates = min($crate30['stock'], (int)ceil($needed_more / 30));
        $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$use_crates, $crate30['id']]);
        $pdo->prepare('UPDATE products SET stock = stock + ? WHERE name = ?')->execute([$use_crates * 30, 'Single egg']);
        $single = get_product_by_name($pdo, 'Single egg');
        if ($single['stock'] >= $needed) {
            return true;
        }
        $needed_more = $needed - $single['stock'];
    }

    $crate60 = get_product_by_name($pdo, 'Egg crate 60');
    if ($crate60 && $crate60['stock'] > 0) {
        $use_crates = min($crate60['stock'], (int)ceil($needed_more / 60));
        $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$use_crates, $crate60['id']]);
        $pdo->prepare('UPDATE products SET stock = stock + ? WHERE name = ?')->execute([$use_crates * 60, 'Single egg']);
        $single = get_product_by_name($pdo, 'Single egg');
        return $single['stock'] >= $needed;
    }

    return false;
}

function update_egg_collection_stock($pdo, $crate_30, $crate_60, $singles) {
    $single = get_product_by_name($pdo, 'Single egg');
    if (!$single) {
        return ['crate_30' => $crate_30, 'crate_60' => $crate_60, 'singles' => $singles];
    }
    $total_singles = $single['stock'] + $singles;
    $new_crates_from_singles = intdiv($total_singles, 30);
    $remainder_singles = $total_singles % 30;

    if ($new_crates_from_singles > 0) {
        $pdo->prepare('UPDATE products SET stock = stock + ? WHERE name = ?')->execute([$new_crates_from_singles, 'Egg crate 30']);
    }

    $pdo->prepare('UPDATE products SET stock = ? WHERE name = ?')->execute([$remainder_singles, 'Single egg']);

    if ($crate_30 > 0) {
        $pdo->prepare('UPDATE products SET stock = stock + ? WHERE name = ?')->execute([$crate_30, 'Egg crate 30']);
    }
    if ($crate_60 > 0) {
        $pdo->prepare('UPDATE products SET stock = stock + ? WHERE name = ?')->execute([$crate_60, 'Egg crate 60']);
    }

    return ['crate_30' => $crate_30 + $new_crates_from_singles, 'crate_60' => $crate_60, 'singles' => $remainder_singles];
}

function require_login() {
    if (empty($_SESSION['user'])) {
        header('Location: index.php?page=login');
        exit;
    }
}

function is_admin() {
    return !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_customer() {
    return !empty($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function build_menu_item(string $page, string $label): string {
    $current = basename($_GET['page'] ?? 'dashboard');
    $class = $current === $page ? 'active' : '';
    return '<a class="' . $class . '" href="index.php?page=' . $page . '">' . h($label) . '</a>';
}
