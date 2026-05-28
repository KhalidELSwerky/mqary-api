<?php
include 'db_connect.php';

// استقبال الإحداثيات من الـ Request (سواء من الأدمين أو عند رفع عقار جديد)
$lat = isset($_GET['lat']) ? $_GET['lat'] : null;
$lng = isset($_GET['lng']) ? $_GET['lng'] : null;
$radius = isset($_GET['radius']) ? $_GET['radius'] : 5000; // الافتراضي 5 كيلو

if (!$lat || !$lng) {
    die(json_encode(["status" => "error", "message" => "Missing coordinates (lat/lng)"]));
}

/**
 * جلب الخدمات من OpenStreetMap بناءً على الموقع المرسل
 */
// طلب البحث عن (مدارس، مستشفيات، صيدليات، مساجد، بنوك، مطاعم، سوبر ماركت)
$overpass_query = "[out:json];(
    node[\"amenity\"~\"school|hospital|pharmacy|mosque|bank|restaurant|cafe|marketplace|supermarket\"](around:$radius,$lat,$lng);
    way[\"amenity\"~\"school|hospital|pharmacy|mosque|bank|restaurant|cafe|marketplace|supermarket\"](around:$radius,$lat,$lng);
);out center;";

$overpass_url = "https://overpass-api.de/api/interpreter?data=" . urlencode($overpass_query);

// تنفيذ الطلب
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $overpass_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!isset($data['elements']) || empty($data['elements'])) {
    die(json_encode(["status" => "success", "message" => "No new services found in this range"]));
}

$added_count = 0;

foreach ($data['elements'] as $element) {
    // تحديد الإحداثيات سواء كان node أو way (center)
    $s_lat = isset($element['lat']) ? $element['lat'] : $element['center']['lat'];
    $s_lng = isset($element['lon']) ? $element['lon'] : $element['center']['lon'];
    
    $name = isset($element['tags']['name']) ? $conn->real_escape_string($element['tags']['name']) : 'خدمة عامة';
    $type = $element['tags']['amenity'];

    // تصنيف الفئات لجدول الـ services
    $category = 'leisure';
    if (in_array($type, ['school', 'university', 'kindergarten'])) $category = 'education';
    if (in_array($type, ['hospital', 'pharmacy', 'clinic', 'dentist'])) $category = 'health';
    if (in_array($type, ['bank', 'atm', 'marketplace', 'supermarket'])) $category = 'shopping';
    if (in_array($type, ['bus_station', 'fuel', 'parking'])) $category = 'transport';

    // التأكد من عدم تكرار الخدمة (بناءً على الاسم والإحداثيات المتقاربة جداً)
    $check = $conn->query("SELECT id FROM services WHERE 
                           (ABS(latitude - $s_lat) < 0.0001 AND ABS(longitude - $s_lng) < 0.0001) 
                           OR (service_name = '$name' AND service_type = '$category')");

    if ($check->num_rows == 0) {
        $insert = "INSERT INTO services (service_name, service_type, latitude, longitude) 
                   VALUES ('$name', '$category', $s_lat, $s_lng)";
        if ($conn->query($insert)) {
            $added_count++;
        }
    }
}

echo json_encode([
    "status" => "success",
    "message" => "Sync completed",
    "new_services_added" => $added_count,
    "total_found" => count($data['elements'])
]);