<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db_config.php';

// استعلام احترافي بيجيب بيانات المستخدم وحالة المطور (لو موجود) وتفاصيل اشتراكه
$query = "SELECT 
            u.id, 
            u.full_name as name, 
            u.phone, 
            u.role, 
            u.created_at,
            d.name as company_name, 
            d.is_verified,
            s.end_date as expiry_date,
            (CASE WHEN s.end_date >= CURDATE() THEN 'active' ELSE 'expired' END) as sub_status
          FROM users u
          LEFT JOIN developers d ON u.id = d.user_id
          LEFT JOIN subscriptions s ON u.id = s.subscriber_id
          ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $query);

if ($result) {
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    echo json_encode([
        "status" => "success",
        "data" => $users
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}
?>