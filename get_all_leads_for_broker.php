<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

// جلب كل الطلبات مع بيانات المستخدم الذي طلب وبيانات العقار
$sql = "SELECT l.id as lead_id, l.status, l.created_at, 
               u.phone as user_phone, u.name as user_name,
               p.title as property_title, p.price
        FROM leads l
        JOIN users u ON l.user_id = u.id
        JOIN properties p ON l.property_id = p.id
        ORDER BY l.created_at DESC";

$result = $conn->query($sql);
$all_leads = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $all_leads[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $all_leads]);
} else {
    echo json_encode(["status" => "success", "data" => []]);
}
?>