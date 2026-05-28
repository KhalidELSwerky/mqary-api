<?php
header("Content-Type: application/json; charset=UTF-8");

// إعدادات قاعدة البيانات
include "db_config.php";

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // استعلام جلب العمليات
    $sql = "SELECT amount, type, description, created_at 
            FROM transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        // ربط المعاملات (s تعني string)
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        $transactions = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $transactions[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "data" => $transactions
        ]);
        
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to prepare statement"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Missing user_id"
    ]);
}

mysqli_close($conn);
?>