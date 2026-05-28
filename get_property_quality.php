<?php
include 'db_config.php'; // اتصالك بقاعدة البيانات

$property_id = $_GET['id'];

// 1. جلب إحداثيات العقار (للحفاظ على نفس بنية الكود)
$sql = "SELECT latitude, longitude FROM properties WHERE id = $property_id";
$result = $conn->query($sql);
$property = $result->fetch_assoc();

$lat = $property['latitude'];
$lng = $property['longitude'];

// 2. دالة حساب المسافة (تم الإبقاء عليها للالتزام بهيكل الكود رغم الاعتماد على الجدول الجديد)
function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// 3. جلب البيانات من الجدول الجديد property_quality_scores بدلاً من جدول services المحذوف
$quality_sql = "SELECT * FROM property_quality_scores WHERE property_id = $property_id LIMIT 1";
$quality_res = $conn->query($quality_sql);

$scores = ['education' => 0, 'health' => 0, 'transport' => 0, 'leisure' => 0];
$total_score = 0;
$raw_data_json = null;

if($quality_res->num_rows > 0) {
    $row = $quality_res->fetch_assoc();
    $total_score = $row['total_score'];
    $scores['education'] = $row['education_score'];
    $scores['health'] = $row['health_score'];
    $scores['transport'] = $row['transport_score'];
    $scores['leisure'] = $row['leisure_score'];
    $raw_data_json = $row['raw_data_json'];
}

// 4. تأكد أن السكور لا يتعدى 10 (تم الإبقاء على المرحلة دي للالتزام بالقاعدة)
foreach($scores as $key => $val) {
    $scores[$key] = min(round($val, 1), 10);
}

// 5. إرسال النتيجة النهائية للـ Flutter شاملة الـ raw_data_json لرسم الخريطة
echo json_encode([
    "status" => "success",
    "data" => [
        "total_score" => $total_score,
        "sub_scores" => $scores,
        "raw_data_json" => $raw_data_json // مهم جداً لرسم الدبابيس في الـ NeighborhoodPage
    ]
]);
?>