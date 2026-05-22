<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'randevulu_cekici');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
        PDO::ATTR_EMULATE_PREPARES   => false,                  
    ];
    
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    
    $appointmentColumns = [
        'price' => "ALTER TABLE appointments ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER phone",
        'distance_km' => "ALTER TABLE appointments ADD COLUMN distance_km DECIMAL(6,2) NOT NULL DEFAULT 0 AFTER price",
        'pickup_latitude' => "ALTER TABLE appointments ADD COLUMN pickup_latitude DECIMAL(10,6) NULL AFTER distance_km",
        'pickup_longitude' => "ALTER TABLE appointments ADD COLUMN pickup_longitude DECIMAL(10,6) NULL AFTER pickup_latitude",
        'dropoff_latitude' => "ALTER TABLE appointments ADD COLUMN dropoff_latitude DECIMAL(10,6) NULL AFTER pickup_longitude",
        'dropoff_longitude' => "ALTER TABLE appointments ADD COLUMN dropoff_longitude DECIMAL(10,6) NULL AFTER dropoff_latitude",
        
    ];

    try {
        $columnStmt = $pdo->query("SHOW COLUMNS FROM appointments");
        $existingColumns = $columnStmt->fetchAll(PDO::FETCH_COLUMN, 0);

        foreach ($appointmentColumns as $columnName => $alterSql) {
            if (!in_array($columnName, $existingColumns, true)) {
                $pdo->exec($alterSql);
            }
        }

        $pdo->exec("ALTER TABLE appointments MODIFY status ENUM('pending', 'on_way', 'completed', 'canceled') NOT NULL DEFAULT 'pending'");
    } catch (PDOException $schemaException) {
        error_log('Schema sync skipped: ' . $schemaException->getMessage());
    }
    
} catch (PDOException $e) {
    
    error_log($e->getMessage());
    die("<div style='background-color: #0f172a; color: #f87171; padding: 20px; font-family: sans-serif; text-align: center; border-radius: 8px; margin: 50px auto; max-width: 600px; border: 1px solid #ef4444;'>
            <h3 style='margin-top:0;'>Veritabanı Bağlantı Hatası</h3>
            <p>Sistem şu anda veritabanına bağlanamıyor. Lütfen yapılandırmayı kontrol edin.</p>
         </div>");
}
