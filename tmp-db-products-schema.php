<?php
$pdo = new PDO('sqlite:data/farm.db');
$stmt = $pdo->query('PRAGMA table_info(products)');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo $col['cid'] . '|' . $col['name'] . '|' . $col['type'] . '|' . $col['notnull'] . '|' . $col['dflt_value'] . '|' . $col['pk'] . "\n";
}
