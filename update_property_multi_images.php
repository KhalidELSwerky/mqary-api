<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once 'db_config.php';

// استقبال البيانات
$data = json_decode(file_get_contents("php://input"), true);

if (
    !empty($data['property_id']) &&
    !empty($data['title'])
) {
    $property_id = $data['property_id'];
    $title = $conn->real_escape_string($data['title']);
    $price = $data['price'];
    $phone = $data['phone'];
    $governorate = $data['governorate'];
    $city = $data['city'];
    $rooms = $data['rooms'];
    $bathrooms = $data['bathrooms'];
    $area_sqm = $data['area_sqm'];
    $description = $conn->real_escape_string($data['description']);
    $floor_number = $data['floor_number'];
    // استقبال حالة البيع من الداتا المرسلة
    $is_sold = isset($data['is_sold']) ? intval($data['is_sold']) : 0;

    // 1. تحديث البيانات النصية في جدول properties بما في ذلك حالة البيع
    $sql = "UPDATE properties SET 
            title = '$title', 
            price = '$price', 
            phone = '$phone', 
            governorate = '$governorate', 
            city = '$city', 
            rooms = '$rooms', 
            bathrooms = '$bathrooms', 
            area_sqm = '$area_sqm', 
            description = '$description', 
            floor_number = '$floor_number',
            is_sold = '$is_sold' 
            WHERE id = '$property_id'";

    if ($conn->query($sql) === TRUE) {
        
        // 2. معالجة الصور القديمة (المزامنة مع جدول property_images)
        // لاحظ تغيير image_path إلى image_url ليتطابق مع جدولك
        $res_old = $conn->query("SELECT image_url FROM property_images WHERE property_id = '$property_id'");
        $existing_images = [];
        while($row = $res_old->fetch_assoc()){
            $existing_images[] = $row['image_url'];
        }

        // الصور التي قرر المستخدم الاحتفاظ بها من التطبيق
        $keep_images_raw = isset($data['old_images']) ? $data['old_images'] : [];
        $keep_images = [];

        // تنظيف البيانات المستلمة للتأكد أنها أسماء ملفات فقط وليست روابط كاملة
        foreach ($keep_images_raw as $path) {
            $keep_images[] = basename($path);
        }

        // حذف الصور المستبعدة
        foreach ($existing_images as $img_file) {
            if (!in_array($img_file, $keep_images)) {
                // حذف الملف الفيزيائي من المجلد
                if (file_exists("uploads/" . $img_file)) {
                    @unlink("uploads/" . $img_file);
                }
                // حذف السجل من قاعدة البيانات - تعديل لاسم العمود image_url
                $conn->query("DELETE FROM property_images WHERE property_id = '$property_id' AND image_url = '$img_file'");
            }
        }

        // 3. رفع الصور الجديدة (إن وُجدت)
        if (!empty($data['new_images']) && is_array($data['new_images'])) {
            foreach ($data['new_images'] as $base64_image) {
                $image_name = "prop_" . time() . "_" . rand(1000, 9999) . ".jpg";
                $decoded_image = base64_decode($base64_image);
                
                if (file_put_contents("uploads/" . $image_name, $decoded_image)) {
                    // إدراج في جدول الصور بالعمود الصحيح image_url
                    $conn->query("INSERT INTO property_images (property_id, image_url) VALUES ('$property_id', '$image_name')");
                }
            }
        }

        echo json_encode(array("status" => "success", "message" => "تم تحديث البيانات والصور بنجاح"));
    } else {
        echo json_encode(array("status" => "error", "message" => "فشل تحديث الجدول: " . $conn->error));
    }
} else {
    echo json_encode(array("status" => "error", "message" => "بيانات ناقصة"));
}

$conn->close();
?>

