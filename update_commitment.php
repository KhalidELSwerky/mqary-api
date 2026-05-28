<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';
// استدعاء ملف الـ FCM الذي أعددته مسبقاً (تأكد من المسار)
require_once 'fcm_helper.php'; 

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id'])) {
    $uid = intval($data['user_id']);
    $is_comm = intval($data['is_committed']);
    $amt = floatval($data['amount_saved']);

    $sql = "INSERT INTO commitment_tracker (user_id, month_date, is_committed, amount_saved) 
            VALUES ($uid, CURDATE(), $is_comm, $amt)";

    if ($conn->query($sql) === TRUE) {
        
        // --- إضافة منطق الإشعارات الجديد ---
        
        // 1. جلب التوكن الخاص بالمستخدم من جدول users
        $token_sql = "SELECT fcm_token FROM users WHERE id = $uid LIMIT 1";
        $token_res = $conn->query($token_sql);
        
        if ($token_res && $token_res->num_rows > 0) {
            $user_data = $token_res->fetch_assoc();
            $user_token = $user_data['fcm_token'];

            if (!empty($user_token)) {
                // 2. إرسال الإشعار التحفيزي
                $title = "عاش يا بطل! ✅";
                $body = "تم تسجيل التزامك بمبلغ " . number_format($amt) . " ج.م بنجاح. خطوة جديدة نحو بيت أحلامك!";
                
                // استدعاء دالة الإرسال التي اختبرتها أنت مسبقاً
                sendFCMNotification($user_token, $title, $body);
            }
        }
        
        // ----------------------------------

        echo json_encode(["status" => "success", "message" => "Commitment Updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Data"]);
}

$conn->close();
?>