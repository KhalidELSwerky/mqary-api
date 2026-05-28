<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once 'db_config.php';

// استقبال البيانات المرسلة من Flutter
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['property_id'])) {
    $property_id = intval($data['property_id']);
    // سنستخدم session_id لتمييز المصوت (يمكنك تطويره لاحقاً لربطه بالـ User ID)
    $voter_session = $conn->real_escape_string($data['voter_session_id'] ?? 'guest');

    // منع التكرار: التأكد أن هذا المستخدم لم يصوت لهذا العقار من قبل في هذه الجلسة
    $check_sql = "SELECT id FROM property_votes WHERE property_id = $property_id AND voter_session_id = '$voter_session'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        echo json_encode(["status" => "exists", "message" => "لقد قمت بالتصويت بالفعل"], JSON_UNESCAPED_UNICODE);
    } else {
        $sql = "INSERT INTO property_votes (property_id, voter_session_id) VALUES ($property_id, '$voter_session')";
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "تم تسجيل صوتك بنجاح"], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["status" => "error", "message" => "فشل تسجيل الصوت"], JSON_UNESCAPED_UNICODE);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات غير مكتملة"], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>