<?php
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
        name TEXT,
        description TEXT,
        price REAL,
        unit TEXT,
        category TEXT,
        stock INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY,
        customer_id INTEGER,
        product_id INTEGER,
        quantity INTEGER,
        total REAL,
        status TEXT,
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

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users');
    if ($stmt->execute() && $stmt->fetchColumn() == 0) {
        $password = password_hash('farm1234', PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)')->execute(['admin', $password]);
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products');
    if ($stmt->execute() && $stmt->fetchColumn() == 0) {
        $products = [
            ['Broiler bird', 'Live broiler ready to sell', 90.00, 'bird', 'broiler', 10],
            ['Egg crate 30', '30-pack eggs', 70.00, 'crate', 'eggs', 50],
            ['Egg crate 60', '60-pack eggs', 120.00, 'crate', 'eggs', 30],
            ['Single egg', 'Individual egg', 3.00, 'egg', 'eggs', 200],
        ];
        $insert = $pdo->prepare('INSERT INTO products (name, description, price, unit, category, stock) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($products as $product) {
            $insert->execute($product);
        }
    }

    return $pdo;
}
