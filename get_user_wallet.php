<?php
include "db_config.php";

$user_id = $_GET['user_id'];

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID missing"]);
    exit;
}

// الاستعلام من جدول user_stats بناءً على الصورة التالتة
$stmt = $conn->prepare("SELECT wallet_balance FROM user_stats WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "status" => "success",
        "balance" => (float)$row['wallet_balance']
    ]);
} else {
    // لو ملوش سجل في stats بنفترض رصيده صفر
    echo json_encode(["status" => "success", "balance" => 0.00]);
}
?>