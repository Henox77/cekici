<?php

header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';
require_once 'config/security.php';
require_once 'config/notifications.php';

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!validate_csrf_token($token)) {
    echo json_encode(['success' => false, 'message' => 'Güvenlik doğrulama hatası (CSRF Token Geçersiz). Lütfen sayfayı yenileyip tekrar deneyin.']);
    exit;
}

$brand_model = isset($_POST['brand_model']) ? clean_input($_POST['brand_model']) : '';
$plate       = isset($_POST['plate']) ? strtoupper(clean_input($_POST['plate'])) : '';
$issue_type  = isset($_POST['issue_type']) ? clean_input($_POST['issue_type']) : '';
$pickup      = isset($_POST['pickup_location']) ? clean_input($_POST['pickup_location']) : '';
$dropoff     = isset($_POST['dropoff_location']) ? clean_input($_POST['dropoff_location']) : '';
$app_date    = isset($_POST['appointment_date']) ? clean_input($_POST['appointment_date']) : '';
$app_time    = isset($_POST['appointment_time']) ? clean_input($_POST['appointment_time']) : '';
$fullname    = isset($_POST['fullname']) ? clean_input($_POST['fullname']) : '';
$phone       = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';

$pickup_latitude = isset($_POST['pickup_latitude']) ? trim($_POST['pickup_latitude']) : '';
$pickup_longitude = isset($_POST['pickup_longitude']) ? trim($_POST['pickup_longitude']) : '';
$dropoff_latitude = isset($_POST['dropoff_latitude']) ? trim($_POST['dropoff_latitude']) : '';
$dropoff_longitude = isset($_POST['dropoff_longitude']) ? trim($_POST['dropoff_longitude']) : '';

if (empty($brand_model) || empty($plate) || empty($issue_type) || empty($pickup) || empty($dropoff) || empty($app_date) || empty($app_time) || empty($fullname) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm zorunlu alanları eksiksiz doldurun.']);
    exit;
}

if (strtotime($app_date) < strtotime(date('Y-m-d'))) {
    echo json_encode(['success' => false, 'message' => 'Geçmiş bir tarihe randevu oluşturulamaz.']);
    exit;
}

try {
    
    $price_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'tow_base_price'");
    $price_stmt->execute();
    $base_price = (float) ($price_stmt->fetchColumn() ?: 500);

    
    $distance_km = null;
    $coordinates = null;

    $hasCoords = is_numeric($pickup_latitude) && is_numeric($pickup_longitude) && is_numeric($dropoff_latitude) && is_numeric($dropoff_longitude);
    if ($hasCoords) {
        
        $osrmUrl = sprintf('https://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s?overview=false&alternatives=false&steps=false',
            rawurlencode($pickup_longitude), rawurlencode($pickup_latitude), rawurlencode($dropoff_longitude), rawurlencode($dropoff_latitude)
        );

        $context = stream_context_create(['http' => ['header' => "User-Agent: yol-destek/1.0\r\n"]]);
        $json = @file_get_contents($osrmUrl, false, $context);
        if ($json) {
            $data = json_decode($json, true);
            if (!empty($data['routes'][0]['distance'])) {
                $distance_km = round($data['routes'][0]['distance'] / 1000, 1);
            }
        }

        
        $coordinates = [
            'pickup_latitude'  => (float) $pickup_latitude,
            'pickup_longitude' => (float) $pickup_longitude,
            'dropoff_latitude' => (float) $dropoff_latitude,
            'dropoff_longitude'=> (float) $dropoff_longitude,
        ];
    }

    
    if ($distance_km === null) {
        $distance_km = calculate_mock_distance($pickup, $dropoff);
    }
    if ($coordinates === null) {
        $coordinates = calculate_mock_coordinates($pickup, $dropoff);
    }

    $final_price = calculate_booking_price($base_price, $distance_km);

    
    $unique_code = generate_booking_code();
    while (true) {
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_code = ?");
        $stmt_check->execute([$unique_code]);
        if ((int) $stmt_check->fetchColumn() === 0) {
            break;
        }
        $unique_code = generate_booking_code();
    }

    
    $sql = "INSERT INTO appointments (
                appointment_code, brand_model, plate, issue_type, 
                pickup_location, dropoff_location, appointment_date, 
                appointment_time, fullname, phone, price, distance_km,
                pickup_latitude, pickup_longitude, dropoff_latitude, dropoff_longitude,
                status
            ) VALUES (
                :code, :brand, :plate, :issue, 
                :pickup, :dropoff, :app_date, 
                :app_time, :fullname, :phone, :price, :distance_km,
                :pickup_latitude, :pickup_longitude, :dropoff_latitude, :dropoff_longitude,
                'pending'
            )";
            
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':code'            => $unique_code,
        ':brand'           => $brand_model,
        ':plate'           => $plate,
        ':issue'           => $issue_type,
        ':pickup'          => $pickup,
        ':dropoff'         => $dropoff,
        ':app_date'        => $app_date,
        ':app_time'        => $app_time,
        ':fullname'        => $fullname,
        ':phone'           => $phone,
        ':price'           => $final_price,
        ':distance_km'     => $distance_km,
        ':pickup_latitude' => $coordinates['pickup_latitude'],
        ':pickup_longitude'=> $coordinates['pickup_longitude'],
        ':dropoff_latitude'=> $coordinates['dropoff_latitude'],
        ':dropoff_longitude'=> $coordinates['dropoff_longitude']
    ]);

    if ($result) {
        $tracking_link = build_tracking_link($unique_code);

        
        try {
            $logDir = __DIR__ . '/logs';
            if (!is_dir($logDir)) mkdir($logDir, 0777, true);
            $logLine = sprintf("[%s] BOOKED %s | %s | %s | %s | %s\n", date('Y-m-d H:i:s'), $unique_code, $fullname, $phone, $pickup, $dropoff);
            file_put_contents($logDir . '/bookings.log', $logLine, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            
        }

        $smsMessage = sprintf(
            "Randevunuz başarıyla alındı. Takip kodunuz: %s. Tutar: %s TL. Takip: %s",
            $unique_code,
            number_format($final_price, 2, ',', '.'),
            $tracking_link
        );

        $whatsappMessage = sprintf(
            "Randevunuz başarıyla alındı. Takip kodunuz: %s\nTahmini tutar: %s TL\nCanlı takip: %s",
            $unique_code,
            number_format($final_price, 2, ',', '.'),
            $tracking_link
        );

        sendSMS($phone, $smsMessage);
        sendWhatsApp($phone, $whatsappMessage);

        echo json_encode([
            'success' => true,
            'message' => 'Randevunuz başarıyla oluşturuldu.',
            'code'    => $unique_code,
            'price'   => $final_price,
            'distance_km' => $distance_km
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Sistem veri kaydederken hata oluştu.']);
        exit;
    }

} catch (PDOException $e) {
    
    error_log("Database insertion failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu. Lütfen teknik ekiple görüşün.']);
    exit;
}
