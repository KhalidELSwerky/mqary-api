<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $type = $_POST['type']; // 'profile' أو 'cover'
    
    $target_dir = "uploads/profiles/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $new_file_name = $type . "_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_file_name;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // تحديث المسار في قاعدة البيانات
        $column = ($type == 'profile') ? 'profile_image' : 'cover_image';
        
        // تم تعديل هذا السطر لتخزين المسار النسبي فقط ليتوافق مع معالجة Flutter للرابط
        $db_path = "profiles/" . $new_file_name; 
        
        $sql = "UPDATE users SET $column = '$db_path' WHERE id = $user_id";
        
        if ($conn->query($sql)) {
            // نرسل الرابط الكامل في الاستجابة فقط للعرض في التطبيق بعد الرفع مباشرة
            $full_url = "http://192.168.1.6/sha2tak_api/uploads/" . $db_path;
            echo json_encode(["status" => "success", "url" => $full_url]);
        } else {
            echo json_encode(["status" => "error", "message" => "خطأ في تحديث القاعدة"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "فشل رفع الملف"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات غير مكتملة"]);
}
$conn->close();
?>