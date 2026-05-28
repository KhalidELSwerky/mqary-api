<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

include "db_config.php";

if (isset($_GET['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['user_id']);

    // جلب آخر 20 عملية مالية للمستخدم
    $query = "SELECT amount, type, description, created_at 
              FROM transactions 
              WHERE user_id = '$user_id' 
              ORDER BY created_at DESC 
              LIMIT 20";

    $result = $conn->query($query);
    $transactions = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // تحويل البيانات لتنسيق سهل للقراءة في التطبيق
            $transactions[] = [
                "amount" => (float)$row['amount'],
                "type" => $row['type'],
                "description" => $row['description'],
                "date" => date("d-m-Y H:i", strtotime($row['created_at']))
            ];
        }
        
        echo json_encode([
            "status" => "success",
            "data" => $transactions
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "data" => [],
            "message" => "لا توجد عمليات سابقة"
        ]);
    }

} else {
    echo json_encode([
        "status" => "error",
        "message" => "user_id is required"
    ]);
}

$conn->close();
?>