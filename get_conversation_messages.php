<?php
header("Content-Type: application/json");
include 'db_config.php';

$conv_id = $_GET['conversation_id'];

if (!$conv_id) {
    echo json_encode(["status" => "error", "message" => "Conversation ID is required"]);
    exit;
}

$sql = "SELECT message_text as text, is_user, properties_json as properties FROM ai_messages WHERE conversation_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $conv_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    // تحويل JSON العقارات مرة أخرى لـ Array
    $row['is_user'] = (bool)$row['is_user'];
    $row['properties'] = json_decode($row['properties'], true);
    $messages[] = $row;
}

echo json_encode(["status" => "success", "data" => $messages]);
?>