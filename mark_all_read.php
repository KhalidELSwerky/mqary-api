<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db_config.php'; // اسم ملفك المعتمد

// استقبال البيانات من نوع POST JSON
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;

if ($user_id) {
    // تحديث كل إشعارات المستخدم لتصبح مقروءة
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id' AND is_read = 0";
    
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => "All marked as read"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User ID is missing"]);
}
?>