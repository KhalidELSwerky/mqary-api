<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// الاتصال بقاعدة البيانات باستخدام mysqli
include_once 'db_config.php'; 

// التأكد من أن الاتصال يستخدم mysqli (لو db_config بيستخدم pdo لازم تغيره لـ mysqli)
// $conn = mysqli_connect("localhost", "username", "password", "database");

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Missing user_id"]);
    exit();
}

// تنظيف المدخلات لمنع SQL Injection
$user_id = mysqli_real_escape_string($conn, $user_id);

try {
    // الاستعلام عن بيانات المطور بناءً على user_id
    $sql = "SELECT * FROM developers WHERE user_id = '$user_id' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $developer = mysqli_fetch_assoc($result);

        if ($developer) {
            // نجاح الجلب وإرسال البيانات
            echo json_encode([
                "status" => "success", 
                "data" => $developer
            ]);
        } else {
            // لا يوجد سجل لهذا المستخدم في جدول المطورين
            echo json_encode([
                "status" => "empty", 
                "message" => "No developer record found"
            ]);
        }
    } else {
        throw new Exception(mysqli_error($conn));
    }

} catch (Exception $e) {
    echo json_encode([
        "status" => "error", 
        "message" => $e->getMessage()
    ]);
}

// إغلاق الاتصال
mysqli_close($conn);
?>