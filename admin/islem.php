<?php

header('Content-Type: application/json; charset=utf-8');

require_once '../config/db.php';
require_once '../config/security.php';
require_once '../config/notifications.php';

check_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

$action = isset($_POST['action']) ? clean_input($_POST['action']) : '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz randevu kimliği (ID).']);
    exit;
}

if ($action === 'change_status') {
    $status = isset($_POST['status']) ? clean_input($_POST['status']) : '';
    
    
    if (!in_array($status, ['pending', 'on_way', 'completed', 'canceled'])) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz durum değeri gönderildi.']);
        exit;
    }
    
    try {
        $appointmentStmt = $pdo->prepare("SELECT appointment_code, phone, fullname FROM appointments WHERE id = ?");
        $appointmentStmt->execute([$id]);
        $appointment = $appointmentStmt->fetch();

        
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $id]);
        
        if ($result) {
            $labels = [
                'pending' => 'Beklemede',
                'on_way' => 'Çekici Yolda',
                'completed' => 'Tamamlandı',
                'canceled' => 'İptal Edildi'
            ];

            if ($appointment) {
                $trackingLink = build_tracking_link($appointment['appointment_code']);

                if ($status === 'on_way') {
                    $message = 'Çekicimiz yola çıkmıştır. Canlı takip linki: ' . $trackingLink;
                    sendSMS($appointment['phone'], $message);
                    sendWhatsApp($appointment['phone'], $message);
                } elseif ($status === 'canceled') {
                    $message = 'Randevunuz iptal edilmiştir.';
                    sendSMS($appointment['phone'], $message);
                    sendWhatsApp($appointment['phone'], $message);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Randevu durumu başarıyla güncellendi: " . $labels[$status]
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'İşlem başarısız oldu (Güncelleme yapılamadı).']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("AJAX status change exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu.']);
        exit;
    }
    
} elseif ($action === 'delete') {
    try {
        
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Randevu kaydı sistemden tamamen kaldırıldı.'
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Silme işlemi gerçekleştirilemedi.']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("AJAX delete exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Veritabanı silme hatası oluştu.']);
        exit;
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Tanımsız işlem türü.']);
    exit;
}
