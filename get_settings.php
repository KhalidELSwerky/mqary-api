<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// إعدادات الاتصال بقاعدة البيانات
include "db_config.php";

// التحقق من الاتصال

// ضبط الترميز للعربية
mysqli_set_charset($conn, "utf8");

// الاستعلام لجلب الإعدادات
$sql = "SELECT cpm, cpc FROM app_settings LIMIT 1";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $settings = mysqli_fetch_assoc($result);
    echo json_encode([
        "status" => "success",
        "settings" => [
            "cpm" => $settings['cpm'],
            "cpc" => $settings['cpc']
        ]
    ]);
} else {
    // قيم افتراضية في حالة عدم وجود بيانات في الجدول
    echo json_encode([
        "status" => "success",
        "settings" => [
            "cpm" => "30.00",
            "cpc" => "2.00"
        ]
    ]);
}

// إغلاق الاتصال
mysqli_close($conn);
?>