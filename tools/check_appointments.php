<?php
require_once __DIR__ . '/../config/db.php';
try {
    $stmt = $pdo->query('SELECT COUNT(*) AS c FROM appointments');
    $row = $stmt->fetch();
    echo "COUNT: " . ($row['c'] ?? 0) . PHP_EOL;

    $rows = $pdo->query('SELECT appointment_code, fullname, phone, status, price, created_at FROM appointments ORDER BY created_at DESC LIMIT 5')->fetchAll();
    if ($rows) {
        foreach ($rows as $r) {
            echo implode(' | ', [$r['appointment_code'], $r['fullname'], $r['phone'], $r['status'], $r['price'], $r['created_at']]) . PHP_EOL;
        }
    } else {
        echo "No recent rows." . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
