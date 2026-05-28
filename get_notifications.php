<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

// $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$user_id = 27;

if ($user_id > 0) {
    // تعديل الشرط لجلب إشعارات المستخدم الخاص + الإشعارات العامة (user_id = 0)
    $sql = "SELECT id, title, message, is_read, type, property_id ,
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as time 
            FROM notifications 
            WHERE user_id = $user_id OR user_id = 0
            ORDER BY created_at DESC";
            
    $result = $conn->query($sql);
    $notifications = [];

    if ($result) {
        while($row = $result->fetch_assoc()) {
            // تحويل الـ property_id فقط إلى int كما طلبت
            if (isset($row['property_id'])) {
                $row['property_id'] = (int)$row['property_id'];
            }
            $notifications[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $notifications]);
    } else {
        echo json_encode(["status" => "error", "message" => "خطأ في الاستعلام"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ID المستخدم مفقود"]);
}
?>