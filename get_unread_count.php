<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db_config.php'; 

if (isset($_GET['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['user_id']); // تأمين البيانات

    // الاستعلام
    $sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = '$user_id' AND is_read = 0";
    $result = $conn->query($sql);

    if ($result) {
        $row = $result->fetch_assoc();
        $count = (int)$row['unread_count'];
        
        echo json_encode([
            "status" => "success",
            "count" => $count
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Query failed"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User ID missing"]);
}
?>