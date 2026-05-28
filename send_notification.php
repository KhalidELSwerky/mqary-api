<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include 'db_config.php';
// استدعاء ملف الـ v1 المساعد الذي تم إنشاؤه مسبقاً
require_once 'fcm_helper.php'; 

// --- [تم الاستغناء عن تعريف السيرفر كي القديم لأنه v1] ---

$input = json_decode(file_get_contents("php://input"), true);
    $user_id = isset($input['user_id']) ? $input['user_id'] : (isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0);
    $title   = isset($input['title'])   ? $input['title']   : (isset($_REQUEST['title'])   ? $_REQUEST['title']   : '');
    $message = isset($input['message']) ? $input['message'] : (isset($_REQUEST['message']) ? $_REQUEST['message'] : '');
    $type    = isset($input['type'])    ? $input['type']    : (isset($_REQUEST['type'])    ? $_REQUEST['type']    : 'system');

    if (!empty($title) && !empty($message)) {
    $title_esc = mysqli_real_escape_string($conn, $title);
    $message_esc = mysqli_real_escape_string($conn, $message);
    $type_esc = mysqli_real_escape_string($conn, $type);

    // 1. الحفظ في قاعدة البيانات (الأرشيف)
    if ($user_id == 0 || empty($user_id)) {
        $sql = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
                SELECT id, '$title_esc', '$message_esc', '$type_esc', 0, NOW() FROM users";
        $token_query = "SELECT fcm_token FROM users WHERE fcm_token IS NOT NULL";
    } else {
        $sql = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) 
                VALUES ('$user_id', '$title_esc', '$message_esc', '$type_esc', 0, NOW())";
        $token_query = "SELECT fcm_token FROM users WHERE id = '$user_id'";
    }

    if ($conn->query($sql)) {
        // 2. إرسال الإشعار للموبايل (Push Notification) عبر v1
        $tokens = [];
        $res = $conn->query($token_query);
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['fcm_token'])) $tokens[] = $row['fcm_token'];
        }

        if (!empty($tokens)) {
            // استدعاء الدالة الجديدة المتوافقة مع v1
            sendPushNotification($tokens, $title, $message);
        }

        echo json_encode(["status" => "success", "message" => "تم الحفظ والإرسال لـ " . count($tokens) . " جهاز"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}

// دالة إرسال الإشعارات عبر Firebase (محدثة لتعمل مع نظام v1 الجديد)
function sendPushNotification($registration_ids, $title, $body) {
    $responses = [];
    foreach ($registration_ids as $token) {
        // نستخدم الدالة الموجودة في fcm_helper.php لإرسال كل إشعار بنظام v1
        $responses[] = sendFCMNotification($token, $title, $body, ['type' => 'new_msg']);
    }
    return $responses;
}
?>