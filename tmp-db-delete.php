<?php
$pdo = new PDO('sqlite:data/farm.db');
$pdo->exec('DELETE FROM customers WHERE name = "Nake Hlophe"');
$pdo->exec('DELETE FROM broiler_batches WHERE name = "Simgcinelwe Hlophe"');
$pdo->exec('DELETE FROM egg_collections WHERE id = 1');
$pdo->exec('DELETE FROM products WHERE id IN (1,2,3,4)');
echo "Seeded rows removed.\n";
