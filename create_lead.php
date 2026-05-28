<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once 'db_config.php';
require_once 'fcm_helper.php';
 
// استقبال البيانات من تطبيق Flutter
$data = json_decode(file_get_contents("php://input"), true);

// التحقق من وجود user_id و (إما property_id أو story_id)
if (isset($data['user_id']) && (isset($data['property_id']) || isset($data['story_id']))) {
    
    $user_id = intval($data['user_id']);
    $property_id = isset($data['property_id']) && $data['property_id'] != 0 ? intval($data['property_id']) : null;
    $story_id = isset($data['story_id']) && $data['story_id'] != 0 ? intval($data['story_id']) : null;

    // التأكد من عدم تكرار الطلب لنفس الحالة أو نفس العقار من نفس المستخدم (تم التعديل ليفحص الحالة المعلقة فقط)
    if ($story_id) {
        $stmt = $conn->prepare("SELECT id FROM leads_requests WHERE user_id = ? AND story_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $user_id, $story_id);
    } else {
        $stmt = $conn->prepare("SELECT id FROM leads_requests WHERE user_id = ? AND property_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $user_id, $property_id);
    }
    
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode([
            "status" => "error", 
            "message" => "لقد قمت بإرسال هذا الطلب بالفعل"
        ]);
        $stmt->close();
    } else {
        $stmt->close();

        // إدراج الطلب الجديد (بناءً على المصدر)
        $insert_stmt = $conn->prepare("INSERT INTO leads_requests (user_id, property_id, story_id, status, commission_paid) VALUES (?, ?, ?, 'pending', 0)");
        $insert_stmt->bind_param("iii", $user_id, $property_id, $story_id);

        if ($insert_stmt->execute()) {
            $new_lead_id = $conn->insert_id;

            // إدراج بيانات الاستبيان (إن وجدت)
            if (isset($data['is_qualified']) && $data['is_qualified'] == 1) {
                $timeframe = $data['buying_timeframe'] ?? null;
                $financing = $data['financing_type'] ?? null;
                $has_property = isset($data['has_property_to_sell']) ? intval($data['has_property_to_sell']) : 0;

                $survey_stmt = $conn->prepare("INSERT INTO lead_survey_responses (lead_id, buying_timeframe, financing_type, has_property_to_sell) VALUES (?, ?, ?, ?)");
                $survey_stmt->bind_param("issi", $new_lead_id, $timeframe, $financing, $has_property);
                $survey_stmt->execute();
                $survey_stmt->close();
            }
            
            // --- [ تطوير الإشعارات ] ---
            
            // 1. جلب اسم المشتري
            $buyer_res = $conn->query("SELECT full_name FROM users WHERE id = $user_id");
            $buyer_row = $buyer_res->fetch_assoc();
            $buyer_name = $buyer_row['full_name'] ?? "عميل مهتم";

            $broker_id = 0;
            $fcm_token = "";
            $item_title = "";

            if ($story_id) {
                // جلب بيانات صاحب الستوري (البروكير)
                $info = $conn->query("SELECT s.caption, s.user_id as broker_id, u.fcm_token 
                                     FROM stories s 
                                     JOIN users u ON s.user_id = u.id 
                                     WHERE s.id = $story_id LIMIT 1")->fetch_assoc();
                $item_title = "الحالة: " . ($info['caption'] ?? "عرض خاص");
                $broker_id = $info['broker_id'] ?? 0;
                $fcm_token = $info['fcm_token'] ?? "";
            } else {
                // جلب بيانات صاحب العقار
                $info = $conn->query("SELECT p.title, p.broker_id, u.fcm_token 
                                     FROM properties p 
                                     JOIN users u ON p.broker_id = u.id 
                                     WHERE p.id = $property_id LIMIT 1")->fetch_assoc();
                $item_title = "العقار: " . ($info['title'] ?? "");
                $broker_id = $info['broker_id'] ?? 0;
                $fcm_token = $info['fcm_token'] ?? "";
            }

            if ($broker_id > 0 && !empty($fcm_token)) {
                $msg_title = "طلب تواصل جديد! 📞";
                $msg_body = "العميل $buyer_name مهتم بـ $item_title";
                
                sendFCMNotification($fcm_token, $msg_title, $msg_body, [
                    "type" => "lead_request",
                    "story_id" => (string)$story_id,
                    "property_id" => (string)$property_id,
                    "buyer_id" => (string)$user_id
                ]);

                $conn->query("INSERT INTO notifications (user_id, title, message, type) 
                             VALUES ($broker_id, '$msg_title', '$msg_body', 'lead')");
            }

            echo json_encode([
                "status" => "success", 
                "message" => "تم تسجيل طلبك بنجاح"
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "خطأ في قاعدة البيانات"]);
        }
        $insert_stmt->close();
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات غير مكتملة"]);
}

$conn->close();
?>