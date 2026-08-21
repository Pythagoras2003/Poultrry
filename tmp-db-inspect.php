<?php
$pdo = new PDO('sqlite:data/farm.db');
$tables = ['products', 'customers', 'orders', 'broiler_batches', 'egg_collections', 'notifications'];
foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo "$t: $count\n";
    $stmt = $pdo->query("SELECT * FROM $t LIMIT 20");
    if ($stmt) {
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            echo json_encode($row) . "\n";
        }
    }
    echo "---\n";
}
