<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

error_reporting(0);
ini_set('display_errors', 0);

// --- دالة خوارزمية "العظمة": تحويل حروف الكيبورد الإنجليزي لعربي تلقائياً ---
function mapEnglishToArabic($input) {
    $map = [
        'q' => 'ض', 'w' => 'ص', 'e' => 'ث', 'r' => 'ق', 't' => 'ف', 'y' => 'غ', 'u' => 'ع', 'i' => 'ه', 'o' => 'خ', 'p' => 'ح', '[' => 'ج', ']' => 'د',
        'a' => 'ش', 's' => 'س', 'd' => 'ي', 'f' => 'ب', 'g' => 'ل', 'h' => 'ا', 'j' => 'ت', 'k' => 'ن', 'l' => 'م', ';' => 'ك', '\'' => 'ط',
        'z' => 'ئ', 'x' => 'ء', 'c' => 'ؤ', 'v' => 'ر', 'b' => 'لا', 'n' => 'ى', 'm' => 'ة', ',' => 'و', '.' => 'ز', '/' => 'ظ', ' ' => ' '
    ];
    
    $output = "";
    $input_lower = mb_strtolower($input, 'UTF-8');
    // تفكيك النص للتعامل مع الحروف متعددة البايت (UTF-8)
    for ($i = 0; $i < mb_strlen($input_lower, 'UTF-8'); $i++) {
        $char = mb_substr($input_lower, $i, 1, 'UTF-8');
        $output .= isset($map[$char]) ? $map[$char] : $char;
    }
    return $output;
}

$gov = isset($_GET['governorate']) ? $_GET['governorate'] : null;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
// جلب نص البحث العام وعمل trim فوراً لحل مشكلة المسافات (المسطرة)
$search_query = isset($_GET['search']) ? trim($_GET['search']) : null;

// التعديل هنا: جلب الحقول الصريحة من الجدول بما فيها الإحداثيات الجديدة
// تم إضافة p.is_sold لضمان وصول الحالة للتطبيق
$sql = "SELECT p.*, GROUP_CONCAT(pi.image_url) as all_images, 
               ac.id AS campaign_id,
               u.is_verified,
               u.full_name AS broker_name, 
               u.profile_image AS broker_photo, 
               CASE WHEN ac.status = 'active' THEN 1 ELSE 0 END as is_promoted,
               (SELECT COUNT(*) FROM property_votes WHERE property_id = p.id) as total_votes
        FROM properties p 
        LEFT JOIN property_images pi ON p.id = pi.property_id 
        LEFT JOIN ad_campaigns ac ON p.id = ac.property_id AND ac.status = 'active'
        LEFT JOIN users u ON p.broker_id = u.id 
        WHERE 1=1";

// --- التعديل المطلوب: جعل فلترة العقارات المباعة ديناميكية لضمان ظهور الصور عند الطلب ---
$is_sold_filter = isset($_GET['is_sold']) ? intval($_GET['is_sold']) : 0;
$sql .= " AND p.is_sold = $is_sold_filter";

// إضافة فلترة المحافظة (القديمة)
if ($gov) {
    $sql .= " AND p.governorate = '" . $conn->real_escape_string($gov) . "'";
}

// إضافة فلترة السعر (القديمة)
if ($max_price) {
    $sql .= " AND p.price <= $max_price";
}

// --- تطبيق خوارزمية البحث الذكي (الجمدان) ---
// التعديل: التأكد من أن searchQuery ليس نال وليس نصاً فارغاً إطلاقاً لضمان جلب الكل عند المسح
if (!empty($search_query)) {
    $q1 = $conn->real_escape_string($search_query); // النص الأصلي (مثلاً Khalid أو rkh)
    $mapped = mapEnglishToArabic($search_query);
    $q2 = $conn->real_escape_string($mapped);       // النص المحول (مثلاً نشفمهي أو قنا)

    $sql .= " AND (
        p.title LIKE '%$q1%' OR p.description LIKE '%$q1%' OR p.city LIKE '%$q1%' OR p.governorate LIKE '%$q1%' OR p.category LIKE '%$q1%'
        OR 
        p.title LIKE '%$q2%' OR p.description LIKE '%$q2%' OR p.city LIKE '%$q2%' OR p.governorate LIKE '%$q2%' OR p.category LIKE '%$q2%'
    )";
}

$sql .= " GROUP BY p.id ORDER BY is_promoted DESC, p.is_featured DESC, p.priority_level DESC, p.id DESC";

$result = $conn->query($sql);
$properties = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // تحويل الأنواع الأساسية
        $row['id'] = intval($row['id']);
        $row['broker_id'] = intval($row['broker_id'] ?? 0);
        $row['price'] = floatval($row['price']);
        $row['rooms'] = isset($row['rooms']) ? intval($row['rooms']) : 0;
        
        // --- التعديل النهائي للربط مع داتابيز حقيقية ---
        
        // 1. المساحة: نعطي الأولوية لـ area_sqm (التي بها 500) لتجاوز الـ 56 الموجودة في net_area
        $row['net_area'] = (isset($row['area_sqm']) && floatval($row['area_sqm']) > 0) 
                           ? floatval($row['area_sqm']) 
                           : (isset($row['net_area']) ? floatval($row['net_area']) : 0);
        
        // 2. الدور: نستخدم floor_number الصريح الموجود في قاعدة بياناتك
        $row['floor_level'] = isset($row['floor_number']) ? intval($row['floor_number']) : (isset($row['floor_level']) ? intval($row['floor_level']) : 0);
        
        // 3. التشطيب والوجهة
        $row['finishing_type'] = $row['finishing_type'] ?? 'غير محدد';
        $row['view_direction'] = $row['view_direction'] ?? 'غير محدد';
        
        // 4. حساب سعر المتر بناءً على المساحة الصحيحة (500) لضمان دقة المقارنة
        $calc_area = ($row['net_area'] > 0) ? $row['net_area'] : 1;
        $row['price_per_meter'] = round($row['price'] / $calc_area, 2);
        
        // 5. الإحداثيات الجغرافية الجديدة للبحث بالخريطة
        $row['latitude'] = isset($row['latitude']) ? floatval($row['latitude']) : null;
        $row['longitude'] = isset($row['longitude']) ? floatval($row['longitude']) : null;
        
        // --- التعديل الجوهري لضمان عمل الشرط في Flutter ---
        // يتم تحويل القيم إلى int (0 أو 1) بدلاً من bool ليتوافق مع شرط (data['has_elevator'] == 1)
        $row['has_elevator'] = isset($row['has_elevator']) ? intval($row['has_elevator']) : 0;
        $row['has_parking'] = isset($row['has_parking']) ? intval($row['has_parking']) : 0;
        $row['has_gas'] = isset($row['has_gas']) ? intval($row['has_gas']) : 0;
        $row['has_electricity'] = isset($row['has_electricity']) ? intval($row['has_electricity']) : 0;
        
        // --- إرسال حالة البيع صراحة للتطبيق ---
        $row['is_sold'] = isset($row['is_sold']) ? intval($row['is_sold']) : 0;

        $row['total_votes'] = intval($row['total_votes'] ?? 0);
        $row['is_verified'] = isset($row['is_verified']) ? intval($row['is_verified']) : 0;
        $row['broker_name'] = strip_tags($row['broker_name'] ?? 'معلن العقار');
        $row['broker_photo'] = $row['broker_photo'] ?? ''; 

        if (!empty($row['all_images'])) {
            $row['images'] = explode(',', $row['all_images']);
        } else {
            $row['images'] = [];
        }

        $row['is_featured'] = isset($row['is_featured']) ? intval($row['is_featured']) : 0;
        $row['is_promoted'] = intval($row['is_promoted'] ?? 0); 
        $row['campaign_id'] = isset($row['campaign_id']) ? intval($row['campaign_id']) : null;
        $row['title'] = strip_tags($row['title'] ?? '');
        
        unset($row['all_images']); 
        $properties[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $properties], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["status" => "error", "data" => [], "message" => "لا توجد عقارات"], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>