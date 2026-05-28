<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['user_id']) && isset($data['fcm_token'])) {
    $user_id = intval($data['user_id']);
    $fcm_token = $conn->real_escape_string($data['fcm_token']);

    $sql = "UPDATE users SET fcm_token = '$fcm_token' WHERE id = $user_id";

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => "تم تحديث التوكن"]);
    } else {
        echo json_encode(["status" => "error", "message" => "فشل التحديث"]);
    }
}
?>