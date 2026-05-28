<?php
include 'db_config.php';

// دالة حساب المسافة (Haversine Formula)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// جلب العقارات التي لم يتم حساب السكور لها أو تحتاج تحديث
$properties = $conn->query("SELECT id, latitude, longitude FROM properties");

while ($prop = $properties->fetch_assoc()) {
    $p_id = $prop['id'];
    $p_lat = $prop['latitude'];
    $p_lng = $prop['longitude'];

    // جلب كل الخدمات القريبة في نطاق 3 كم
    $services = $conn->query("SELECT service_type, latitude, longitude FROM services");
    
    $scores = ['education' => 0, 'health' => 0, 'transport' => 0, 'leisure' => 0];
    
    while ($service = $services->fetch_assoc()) {
        $dist = calculateDistance($p_lat, $p_lng, $service['latitude'], $service['longitude']);
        
        if ($dist <= 3) {
            $weight = (3 - $dist) / 3; // كل ما كان أقرب كل ما الوزن زاد
            $type = $service['service_type'];
            if (isset($scores[$type])) {
                $scores[$type] += $weight * 2; // زيادة السكور
            }
        }
    }

    // تقييد الدرجات بحد أقصى 10
    foreach ($scores as $k => $v) $scores[$k] = min(round($v, 1), 10);
    
    $total_score = array_sum($scores) / 4 * 10; // المجموع من 100

    // تحديث جدول العقارات بالحقول الجديدة
    $update_sql = "UPDATE properties SET 
                   total_score = $total_score, 
                   education_score = {$scores['education']}, 
                   health_score = {$scores['health']}, 
                   transport_score = {$scores['transport']}, 
                   leisure_score = {$scores['leisure']} 
                   WHERE id = $p_id";
    
    $conn->query($update_sql);
}

echo "تم تحديث تقييمات جودة الحياة لكل العقارات بنجاح.";
?>