<?php
$pdo = new PDO('sqlite:data/farm.db');
$tables = ['products', 'customers', 'orders', 'broiler_batches', 'egg_collections', 'notifications'];
foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo "$t: $count\n";
}
