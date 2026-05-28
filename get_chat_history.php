<?php
header("Content-Type: application/json");
include 'db_config.php'; // تأكد من وجود ملف الاتصال بقاعدة البيانات

$user_id = $_GET['user_id'];

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID is required"]);
    exit;
}

// جلب المحادثات مرتبة من الأحدث للأقدم
$sql = "SELECT id, title, created_at FROM ai_conversations WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode(["status" => "success", "data" => $history]);
?>