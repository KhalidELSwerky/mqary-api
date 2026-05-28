<?php
error_reporting(0);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'db_config.php';

// استلام البيانات المرسلة من Flutter (JSON)
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['lead_id']) && isset($data['status'])) {
    $lead_id = intval($data['lead_id']);
    $status = $conn->real_escape_string($data['status']);

    // تحديث حالة الطلب في جدول leads_requests
    $sql = "UPDATE leads_requests SET status = 'connected' WHERE id = $lead_id";

    if ($conn->query($sql)) {
        echo json_encode([
            "status" => "success",
            "message" => "تم تحديث حالة الطلب بنجاح"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "فشل التحديث في قاعدة البيانات: " . $conn->error
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "بيانات غير مكتملة، مطلوب lead_id و status"
    ]);
}
?>