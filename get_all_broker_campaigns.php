<?php
header('Content-Type: application/json');
require_once 'db_config.php'; // تأكد من اسم ملف الاتصال عندك

$broker_id = $_GET['broker_id'];

// استعلام يجيب تفاصيل الحملة مع اسم العقار من جدول العقارات
$sql = "SELECT 
            c.id as campaign_id, 
            c.property_id, 
            c.total_budget, 
            c.status, 
            p.title as property_title 
        FROM ad_campaigns c 
        JOIN properties p ON c.property_id = p.id 
        WHERE p.broker_id = '$broker_id' 
        ORDER BY c.id DESC";

$result = $conn->query($sql);
$campaigns = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $campaigns[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $campaigns]);
} else {
    echo json_encode(["status" => "error", "message" => "لا توجد حملات حالياً", "data" => []]);
}

$conn->close();
?>