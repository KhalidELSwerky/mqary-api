<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// استدعاء نفس الملفات اللي إنت شغال بيها
require_once 'db_config.php';
require_once 'fcm_helper.php'; 

// استقبال البيانات بنفس الطريقة (json decode)
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['story_id']) && isset($data['user_id'])) {
    
    $story_id = intval($data['story_id']);
    $user_id  = intval($data['user_id']); // الشخص اللي عمل القلب

    // 1. التحقق أولاً إذا كان المستخدم قد وضع إعجاب سابقاً (Toggle Logic)
    $check_sql = "SELECT id FROM story_likes WHERE story_id = $story_id AND user_id = $user_id";
    $check_res = $conn->query($check_sql);

    if ($check_res && $check_res->num_rows > 0) {
        // العميل وضع قلب سابقاً -> نقوم بحذفه (Unlike)
        $delete_sql = "DELETE FROM story_likes WHERE story_id = $story_id AND user_id = $user_id";
        
        if ($conn->query($delete_sql)) {
            echo json_encode([
                "status" => "success", 
                "action" => "removed", 
                "message" => "تم حذف الإعجاب بنجاح"
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    } else {
        // العميل لم يضع قلب -> نقوم بتسجيل الإعجاب الجديد (Like)
        $sql_like = "INSERT INTO story_likes (story_id, user_id) VALUES ($story_id, $user_id)";

        if ($conn->query($sql_like)) {
            
            // 2. جلب بيانات العميل (اللي عمل لايك) وتوكن المعلن (صاحب الستوري)
            $sql_data = "SELECT 
                            u_liker.full_name AS liker_name, 
                            u_owner.fcm_token AS owner_token,
                            u_owner.id AS owner_id
                         FROM stories s
                         JOIN users u_liker ON u_liker.id = $user_id
                         JOIN users u_owner ON u_owner.id = s.user_id
                         WHERE s.id = $story_id";
            
            $res_data = $conn->query($sql_data);
            
            if ($res_data && $res_data->num_rows > 0) {
                $row = $res_data->fetch_assoc();
                
                $owner_token = $row['owner_token'];
                $owner_id    = $row['owner_id'];
                $liker_name  = $row['liker_name'];

                if (!empty($owner_token)) {
                    $msg_title = "إعجاب جديد! ❤️";
                    $msg_body = "العميل $liker_name أعجب بحالتك الآن.";
                    
                    // 3. إرسال الإشعار باستخدام الدالة المعتمدة عندك في fcm_helper
                    sendFCMNotification($owner_token, $msg_title, $msg_body, ["story_id" => $story_id]);

                    // 4. تسجيل في جدول notifications بنفس الأعمدة والمنطق بتاعك
                    $conn->query("INSERT INTO notifications (user_id, title, message, type) 
                                  VALUES ($owner_id, '$msg_title', '$msg_body', 'system')");
                }
            }

            echo json_encode([
                "status" => "success", 
                "action" => "added", 
                "message" => "تم تسجيل الإعجاب وإرسال الإشعار للمعلن"
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات ناقصة"]);
}
?>