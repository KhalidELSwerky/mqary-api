<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id'])) {
    $uid = intval($data['user_id']);
    $is_comm = intval($data['is_committed']);
    $amt = floatval($data['amount_saved']);

    $sql = "INSERT INTO commitment_tracker (user_id, month_date, is_committed, amount_saved) 
            VALUES ($uid, CURDATE(), $is_comm, $amt)";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Commitment Updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
?>