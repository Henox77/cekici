<?php


if (!function_exists('notification_log_path')) {
    function notification_log_path() {
        return __DIR__ . '/../logs/sms_logs.txt';
    }
}

if (!function_exists('build_tracking_link')) {
    function build_tracking_link($code) {
        $code = urlencode((string)$code);
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = rtrim(dirname((isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/')), '/');
        $url = $scheme . '://' . $host . $base . '/sorgula.php?code=' . $code;
        return $url;
    }
}

if (!function_exists('append_notification_log')) {
    function append_notification_log($channel, $target, $message, $status = 'queued') {
        $logDir = dirname(notification_log_path());
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $line = sprintf(
            "[%s] [%s] [%s] %s => %s%s",
            date('Y-m-d H:i:s'),
            strtoupper($channel),
            strtoupper($status),
            $target,
            $message,
            PHP_EOL
        );

        file_put_contents(notification_log_path(), $line, FILE_APPEND | LOCK_EX);
        return true;
    }
}

if (!function_exists('sendSMS')) {
    function sendSMS($phone, $message) {
        $phone = trim((string)$phone);
        $message = trim((string)$message);

        
        
        $provider = 'mock';

        if ($provider === 'mock') {
            return append_notification_log('sms', $phone, $message, 'queued');
        }

        $payload = [
            'to' => $phone,
            'message' => $message,
        ];

        $ch = curl_init('https://api.example.com/sms/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            append_notification_log('sms', $phone, $message, 'failed');
            return false;
        }

        append_notification_log('sms', $phone, $message, 'sent');
        return true;
    }
}

if (!function_exists('sendWhatsApp')) {
    function sendWhatsApp($phone, $message) {
        $phone = trim((string)$phone);
        $message = trim((string)$message);

        $provider = 'mock';

        if ($provider === 'mock') {
            return append_notification_log('whatsapp', $phone, $message, 'queued');
        }

        $payload = [
            'to' => $phone,
            'message' => $message,
        ];

        $ch = curl_init('https://api.example.com/whatsapp/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            append_notification_log('whatsapp', $phone, $message, 'failed');
            return false;
        }

        append_notification_log('whatsapp', $phone, $message, 'sent');
        return true;
    }
}