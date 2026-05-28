<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

// منع ظهور أي أخطاء جانبية في الـ JSON
error_reporting(0);
ini_set('display_errors', 0);

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    // جلب سعر الهدف من خطة المستخدم
    $sql_plan = "SELECT target_property_price FROM financial_plans WHERE user_id = $user_id LIMIT 1";
    $result_plan = $conn->query($sql_plan);

    if ($result_plan && $result_plan->num_rows > 0) {
        $plan = $result_plan->fetch_assoc();
        $target_price = $plan['target_property_price'];

        // الاستعلام الشامل لجلب بيانات العقار مع بيانات المعلن والصور من الجداول الحقيقية
        // تم إضافة شرط p.is_sold = 0 لاستثناء العقارات المباعة من الصفحة الرئيسية
        $sql_prop = "SELECT p.*, 
                    u.full_name as broker_name, 
                    u.profile_image as broker_photo, 
                    u.is_verified,
                    (SELECT GROUP_CONCAT(image_url) FROM property_images WHERE property_id = p.id) as all_images
                     FROM properties p 
                     LEFT JOIN users u ON p.broker_id = u.id 
                     WHERE p.is_sold = 0 
                     ORDER BY p.is_featured DESC, ABS(p.price - $target_price) ASC 
                     LIMIT 3";
        
        $result_prop = $conn->query($sql_prop);
        $properties = [];

        if ($result_prop && $result_prop->num_rows > 0) {
            while($row = $result_prop->fetch_assoc()) {
                
                // معالجة الصور القادمة من جدول property_images
                $images_array = [];
                if (!empty($row['all_images'])) {
                    $images_array = explode(',', $row['all_images']);
                }
                
                // تحديد الصدفة الأولى كصورة أساسية
                $display_image = (!empty($images_array)) ? $images_array[0] : "default_property.jpg";
                
                // --- معالجة البيانات التقنية بناءً على أسماء الأعمدة في داتابيز حقيقية [2026-02-26] ---
                
                // 1. المساحة: استخدام area_sqm كأولوية لتجاوز أي قيم فرعية
                $net_area = (isset($row['area_sqm']) && floatval($row['area_sqm']) > 0) 
                            ? floatval($row['area_sqm']) 
                            : (isset($row['net_area']) ? floatval($row['net_area']) : 0);
                
                // 2. الدور: استخدام floor_number الصريح من جدولك
                $floor_level = isset($row['floor_number']) ? intval($row['floor_number']) : (isset($row['floor_level']) ? intval($row['floor_level']) : 0);
                
                // 3. حساب سعر المتر بناءً على المساحة الحقيقية
                $calc_area = ($net_area > 0) ? $net_area : 1;
                $price_per_meter = round(floatval($row['price']) / $calc_area, 2);

                $properties[] = [
                    "id" => intval($row['id']),
                    "broker_id" => intval($row['broker_id']),
                    "broker_name" => strip_tags($row['broker_name'] ?? "معلن غير معروف"),
                    "broker_photo" => $row['broker_photo'] ?? "",
                    "is_verified" => (int)($row['is_verified'] ?? 0),
                    "title" => strip_tags($row['title']),
                    "description" => strip_tags($row['description'] ?? ""),
                    "price" => (float)$row['price'],
                    "rooms" => intval($row['rooms'] ?? 0),
                    "governorate" => $row['governorate'],
                    "city" => $row['city'],
                    "location" => $row['governorate'] . " - " . $row['city'],
                    "image_url" => $display_image, 
                    "images_json" => $images_array,
                    "is_featured" => (int)$row['is_featured'],
                    
                    // إرسال البيانات التقنية المطلوبة للتطبيق
                    "net_area" => $net_area,
                    "floor_level" => $floor_level,
                    "finishing_type" => $row['finishing_type'] ?? 'غير محدد',
                    "view_direction" => $row['view_direction'] ?? 'غير محدد',
                    "price_per_meter" => $price_per_meter,
                    "has_elevator" => isset($row['has_elevator']) ? (int)$row['has_elevator'] : 0,
                    "has_parking" => isset($row['has_parking']) ? (int)$row['has_parking'] : 0
                ];
            }
            echo json_encode(["status" => "success", "data" => $properties], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["status" => "error", "message" => "no_properties_found"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "plan_not_found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "invalid_user_id"]);
}
$conn->close();
?>