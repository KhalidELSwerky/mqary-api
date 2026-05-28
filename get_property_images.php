<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

if ($property_id > 0) {
    $result = $conn->query("SELECT image_url FROM property_images WHERE property_id = $property_id");
    $images = [];
    while ($row = $result->fetch_assoc()) {
        // نرسل الرابط الكامل للصورة على السيرفر
        $images[] = "http://192.168.1.6/SHA2TAK_API/uploads/properties/" . $row['image_url'];
    }
    echo json_encode(["status" => "success", "images" => $images]);
} else {
    echo json_encode(["status" => "error", "message" => "ID عقار غير صحيح"]);
}
?>