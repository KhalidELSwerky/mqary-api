<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

error_reporting(0);
ini_set('display_errors', 0);

// استلام معرف المستخدم
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

if ($user_id) {
    // الاستعلام كما هو
    $sql = "SELECT 
                l.status as lead_status, 
                l.created_at as request_date, 
                p.*,
                GROUP_CONCAT(pi.image_url) as property_images
            FROM leads_requests l 
            JOIN properties p ON l.property_id = p.id 
            LEFT JOIN property_images pi ON p.id = pi.property_id
            WHERE l.user_id = $user_id 
            GROUP BY l.id
            ORDER BY l.id DESC";

    $result = $conn->query($sql);
    $my_leads = [];

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $row['id'] = intval($row['id']);
            $row['price'] = floatval($row['price']);
            $row['images'] = $row['property_images'] ? explode(',', $row['property_images']) : [];
            unset($row['property_images']);
            $my_leads[] = $row;
        }
    }
    // التعديل هنا: نغلف المصفوفة بكلمة status و data
    echo json_encode([
        "status" => "success",
        "data" => $my_leads
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Missing user_id",
        "data" => []
    ]);
}

$conn->close();
?>