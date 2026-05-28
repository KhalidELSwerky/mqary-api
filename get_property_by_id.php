<?php
require_once 'db_config.php';

$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($property_id > 0) {
    // جلب بيانات العقار الأساسية
    // تم التأكد من جلب عمود is_sold ليتوافق مع تحديثات صفحة التعديل في Flutter
    $sql = "SELECT * FROM properties WHERE id = $property_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $property = $result->fetch_assoc();

        // جلب الصور الخاصة بهذا الـ ID
        $img_sql = "SELECT image_url FROM property_images WHERE property_id = $property_id";
        $img_res = $conn->query($img_sql);
        $images = [];
        while($row = $img_res->fetch_assoc()) {
            $images[] = $row['image_url'];
        }
        $property['images'] = $images;

        // التعديل المطلوب ليتوافق مع Flutter:
        // وضع البيانات داخل مفتاح 'data' وإضافة حالة 'status'
        // تأكد أن قاعدة البيانات تعيد قيمة is_sold (0 أو 1)
        echo json_encode([
            "status" => "success",
            "data" => $property
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // تعديل رد الخطأ ليتوافق مع فحص الحالة في التطبيق
        echo json_encode([
            "status" => "error",
            "message" => "Property not found"
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid ID"
    ], JSON_UNESCAPED_UNICODE);
}
$conn->close();
?>