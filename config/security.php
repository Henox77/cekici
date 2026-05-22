<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function check_auth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        
        $script_path = $_SERVER['SCRIPT_NAME'];
        if (strpos($script_path, '/admin/') !== false && strpos($script_path, '/admin/index.php') === false) {
            header('Location: index.php'); 
        } else {
            header('Location: admin/index.php'); 
        }
        exit;
    }
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function normalize_booking_text($text) {
    $text = trim((string)$text);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return $text;
}

function booking_hash($text) {
    $normalized = normalize_booking_text($text);
    $hash = 5381;
    $length = strlen($normalized);

    for ($i = 0; $i < $length; $i++) {
        $hash = ((($hash << 5) + $hash) + ord($normalized[$i])) & 0xFFFFFFFF;
    }

    return $hash;
}

function calculate_mock_distance($pickup, $dropoff) {
    $seed = booking_hash($pickup . '|' . $dropoff);
    return round(6 + (($seed % 4200) / 100), 1);
}

function calculate_mock_coordinates($pickup, $dropoff) {
    $pickupSeed = booking_hash($pickup . '|pickup');
    $dropoffSeed = booking_hash($dropoff . '|dropoff');

    $pickupLatitude = 40.850000 + (($pickupSeed % 7000) / 100000);
    $pickupLongitude = 29.000000 + ((int)(($pickupSeed / 7000) % 7000) / 100000);
    $dropoffLatitude = 40.860000 + (($dropoffSeed % 7000) / 100000);
    $dropoffLongitude = 29.010000 + ((int)(($dropoffSeed / 7000) % 7000) / 100000);

    return [
        'pickup_latitude' => round($pickupLatitude, 6),
        'pickup_longitude' => round($pickupLongitude, 6),
        'dropoff_latitude' => round($dropoffLatitude, 6),
        'dropoff_longitude' => round($dropoffLongitude, 6),
    ];
}

function calculate_booking_price($base_price, $distance_km) {
    
    return round(((float)$distance_km) * 40, 2);
}

function generate_booking_code() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';

    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return 'REZ-' . $code;
}
