<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db_config.php';
require_once 'fcm_helper.php'; // استدعاء المساعد لضمان إرسال إشعارات حية

// استقبال البيانات المرسلة من التطبيق (Admin Panel)
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id']) && isset($data['status'])) {
    $user_id = mysqli_real_escape_string($conn, $data['user_id']);
    $status = mysqli_real_escape_string($conn, $data['status']); // القيمة المتوقعة: 'eligible' أو 'rejected'
    
    // استقبال سبب الرفض لو موجود
    $reason = isset($data['reason']) ? mysqli_real_escape_string($conn, $data['reason']) : '';

    // استقبال معرف الأدمن المبعوث من التطبيق
    $admin_id = isset($data['admin_id']) ? mysqli_real_escape_string($conn, $data['admin_id']) : '0';

    // 1. تحديث الحالة الإدارية في جدول المطورين (developers)
    if ($status == 'rejected') {
        $query = "UPDATE developers SET admin_verified_status = '$status' WHERE user_id = '$user_id'";
    } else {
        $query = "UPDATE developers SET admin_verified_status = '$status' WHERE user_id = '$user_id'";
    }

    if (mysqli_query($conn, $query)) {
        
        // 2. تحديث حالة الطلبات في جدول المستندات (verification_requests) ليصبح 'approved' أو 'rejected'
        $sub_status = ($status == 'eligible') ? 'approved' : 'rejected';
        $query_docs = "UPDATE verification_requests SET admin_approval = '$sub_status' , admin_notes= '$reason' WHERE user_id = '$user_id'";
        mysqli_query($conn, $query_docs);

        // --- [إضافة: تسجيل العملية في الجدول الجديد verification_logs] ---
        $query_log = "INSERT INTO verification_logs (user_id, admin_id, status, reason) 
                      VALUES ('$user_id', '$admin_id', '$sub_status', '$reason')";
        mysqli_query($conn, $query_log);
        // --- [نهاية الإضافة] ---

        // 3. بناء نص الإشعار بناءً على الحالة وسبب الرفض
        if ($status == 'eligible') {
            $msg_title = "موافقة إدارية ✅";
            $msg_body = "تمت الموافقة على أوراقك إدارياً، يمكنك الآن تفعيل الاشتراك لتوثيق حسابك بالعلامة الزرقاء.";
        } else {
            $msg_title = "تحديث طلب التوثيق ❌";
            $msg_body = "للأسف تم رفض أوراق التوثيق. السبب: " . ($reason ?: "البيانات غير مكتملة") . ". برجاء مراجعة المستندات وإعادة المحاولة.";
        }

        // 4. إرسال إشعار للمستخدم في قاعدة البيانات (للسجل الداخلي في التطبيق)
        $insert_notification = "INSERT INTO notifications (user_id, title, message, type) 
                                VALUES ('$user_id', '$msg_title', '$msg_body', 'system')";
        mysqli_query($conn, $insert_notification);

        // --- [بدء منطق الإشعار الحي - Live Notification] ---
        // جلب الـ FCM Token الخاص بالمستخدم من جدول users
        $token_query = "SELECT fcm_token FROM users WHERE id = '$user_id' LIMIT 1";
        $token_res = mysqli_query($conn, $token_query);
        if ($token_res && mysqli_num_rows($token_res) > 0) {
            $user_info = mysqli_fetch_assoc($token_res);
            $user_token = $user_info['fcm_token'];

            if (!empty($user_token)) {
                // استخدام المساعد لإرسال الإشعار فوراً
                @sendFCMNotification($user_token, $msg_title, $msg_body, ["type" => "verification_update"]);
            }
        }
        // --- [نهاية منطق الإشعار الحي] ---
        
        echo json_encode([
            "status" => "success", 
            "message" => "Admin verification status updated to $status and live notification sent",
            "reason_sent" => $reason,
            "log_recorded" => true
        ]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "Database error: " . mysqli_error($conn)
        ]);
    }
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Missing user_id or status"
    ]);
}
?>