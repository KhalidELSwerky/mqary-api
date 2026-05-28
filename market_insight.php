<?php
header("Content-Type: application/json");
include 'db_config.php'; // تأكد من اسم ملف الاتصال بقاعدة البيانات عندك

// $city = 'قنا';
$city = $_GET['city'] ?? '';

if (empty($city)) {
    echo json_encode([
        "status" => "error",
        "message" => "City name is required"
    ]);
    exit();
}

// 1. حساب متوسط سعر المتر في هذه المدينة
$queryAvg = "SELECT AVG(price / net_area) as avg_meter FROM properties WHERE governorate = '$city'";
$resAvg = mysqli_query($conn, $queryAvg);
$rowAvg = mysqli_fetch_assoc($resAvg);
$avgMeter = round($rowAvg['avg_meter'] ?? 0, 0); // أضفت ?? 0 لتجنب null لو المدينة فارغة

// 2. حساب عدد الوحدات المتاحة
$queryCount = "SELECT COUNT(*) as total_units FROM properties WHERE governorate = '$city'";
$resCount = mysqli_query($conn, $queryCount);
$rowCount = mysqli_fetch_assoc($resCount);
$totalUnits = $rowCount['total_units'] ?? 0;

// 3. تحليل بسيط للحالة
$status = "stable";
// استخدمت {$city} لضمان عدم حدوث Undefined variable بسبب الفواصل العربية
$insight_ar = "الأسعار في {$city} مستقرة حالياً مع توفر خيارات متنوعة.";
$insight_en = "Prices in {$city} are stable with various options available.";

if ($avgMeter > 25000) {
    $status = "premium";
    $insight_ar = "منطقة استثمارية من الفئة الممتازة، متوسط سعر المتر {$avgMeter} ج.م.";
    $insight_en = "Premium investment area, avg price per meter is {$avgMeter} EGP.";
} elseif ($totalUnits < 5) {
    $status = "high_demand";
    $insight_ar = "طلب مرتفع جداً في {$city}، الوحدات المتاحة محدودة.";
    $insight_en = "High demand in {$city}, available units are limited.";
}

echo json_encode([
    "status" => "success",
    "data" => [
        "city" => $city,
        "avg_meter" => $avgMeter,
        "total_units" => $totalUnits,
        "market_status" => $status,
        "insight_ar" => $insight_ar,
        "insight_en" => $insight_en
    ]
], JSON_UNESCAPED_UNICODE); // أضفت هذا الخيار لظهور العربي بشكل مقروء في الـ JSON