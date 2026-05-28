<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db_config.php'; 

// تأكد من ضبط الترميز للعربي
mysqli_set_charset($conn, "utf8mb4");

// استعلام لجلب المطورين المشتركين فقط مع ترتيب المميزين أولاً
$query = "SELECT * FROM developers WHERE subscription_status = 'active' ORDER BY is_featured DESC, created_at DESC";
$result = mysqli_query($conn, $query);

$developers = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // 1. معالجة التليفونات: نتحقق أولاً هل هي مصفوفة أم نص عادي
        $phones_raw = json_decode($row['phones'], true);
        if (!is_array($phones_raw)) {
            // لو الداتا مش JSON (زي الرقم اللي طلع في الخطأ)، حطها في مصفوفة يدوياً
            $phones_raw = $row['phones'] ? [$row['phones']] : [];
        }
        $row['phones'] = array_values(array_map(function($item) {
            return (string)$item;
        }, $phones_raw));
        
        // 2. معالجة صور المعرض: نفس الفحص لضمان عدم تكرار الخطأ
        $portfolio_raw = json_decode($row['portfolio_images'], true);
        if (!is_array($portfolio_raw)) {
            $portfolio_raw = $row['portfolio_images'] ? [$row['portfolio_images']] : [];
        }
        $row['portfolio_images'] = array_values(array_map(function($item) {
            return (string)$item;
        }, $portfolio_raw));

        // التأكد من جلب الخانات الجديدة وضمان قيمها الافتراضية
        $row['location_url'] = $row['location_url'] ?? "";
        $row['website'] = $row['website'] ?? "";
        $row['facebook'] = $row['facebook'] ?? "";
        $row['instagram'] = $row['instagram'] ?? "";
        $row['youtube_video'] = $row['youtube_video'] ?? "";

        // تحويل الأنواع لتطابق الـ Model في Flutter
        $row['id'] = (int)$row['id'];
        $row['user_id'] = (int)$row['user_id']; 
        $row['is_verified'] = (int)$row['is_verified'];
        $row['is_featured'] = (int)$row['is_featured'];
        $row['views_count'] = (int)$row['views_count'];
        $row['clicks_count'] = (int)$row['clicks_count'];

        $developers[] = $row;
    }
    echo json_encode([
        "status" => "success", 
        "data" => $developers
    ], JSON_UNESCAPED_UNICODE); 
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Failed to fetch data: " . mysqli_error($conn)
    ]);
}
?>