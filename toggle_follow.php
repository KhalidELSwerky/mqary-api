<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';
require_once 'fcm_helper.php'; // استدعاء ملف دالة الإرسال التي أرسلتها [cite: 2026-02-25]

// قراءة الـ JSON المبعوث من فلاتر
$data = json_decode(file_get_contents("php://input"), true);

$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$broker_id = isset($data['broker_id']) ? intval($data['broker_id']) : 0;

if ($user_id > 0 && $broker_id > 0) {
    // التحقق من حالة المتابعة
    $check = $conn->query("SELECT id FROM follows WHERE user_id = $user_id AND broker_id = $broker_id");

    if ($check && $check->num_rows > 0) {
        // إلغاء المتابعة
        $sql = "DELETE FROM follows WHERE user_id = $user_id AND broker_id = $broker_id";
        $action = "unfollowed";
    } else {
        // إضافة متابعة
        $sql = "INSERT INTO follows (user_id, broker_id) VALUES ($user_id, $broker_id)";
        $action = "followed";
        
        // --- تطوير نظام الإشعارات عند المتابعة [cite: 2026-01-29] ---
        // جلب اسم الشخص الذي قام بالمتابعة [cite: 2026-01-29]
        $user_res = $conn->query("SELECT full_name FROM users WHERE id = $user_id");
        $user_info = $user_res ? $user_res->fetch_assoc() : null;
        $follower_name = $user_info['full_name'] ?? 'مستخدم جديد';

        // جلب توكن الفايربيز الخاص بالبروكير لإرسال الإشعار له [cite: 2026-01-29]
        $broker_res = $conn->query("SELECT fcm_token FROM users WHERE id = $broker_id");
        $broker_info = $broker_res ? $broker_res->fetch_assoc() : null;
        
        if ($broker_info && !empty($broker_info['fcm_token'])) {
            $msg_title = "متابع جديد! 👤";
            $msg_body = "قام $follower_name بمتابعتك الآن.";
            
            // 1. تسجيل الإشعار في قاعدة البيانات ليظهر داخل التطبيق [cite: 2026-01-29]
            $notif_sql = "INSERT INTO notifications (user_id, title, message, type) 
                         VALUES ($broker_id, '$msg_title', '$msg_body', 'follow')";
            $conn->query($notif_sql);
            
            // 2. إرسال Push Notification فوري باستخدام دالتك [cite: 2026-02-25]
            sendFCMNotification($broker_info['fcm_token'], $msg_title, $msg_body, [
                "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                "type" => "follow",
                "user_id" => (string)$user_id
            ]);
        }
    }

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "action" => $action]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing IDs"]);
}
$conn->close();
?>