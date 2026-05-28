<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$broker_id = isset($data['broker_id']) ? intval($data['broker_id']) : 0;

if ($user_id > 0 && $broker_id > 0) {
    // منع تكرار الطلب في نفس اليوم
    $check = $conn->query("SELECT id FROM broker_leads WHERE user_id = $user_id AND broker_id = $broker_id AND DATE(created_at) = CURDATE()");

    if ($check && $check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Already sent today"]);
    } else {
        $sql = "INSERT INTO broker_leads (user_id, broker_id, status) VALUES ($user_id, $broker_id, 'pending')";
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Lead created"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error"]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing IDs"]);
}
$conn->close();
?>