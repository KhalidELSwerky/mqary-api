<?php
// save_user_interest.php
header("Content-Type: application/json");
include 'db_config.php'; // اتصال قاعدة البيانات الحقيقي [cite: 2026-01-29]

$data = json_decode(file_get_contents("php://input"), true);

// بنعتمد الآن على user_id فقط كمعرف أساسي [cite: 2026-01-29]
if (isset($data['user_id'])) {
    $user_id     = $data['user_id'];
    $governorate = $data['governorate'] ?? null;
    $city        = $data['city'] ?? null;
    $max_price   = $data['max_price'] ?? 0;
    $category    = $data['category'] ?? 'شقة';
    $min_rooms   = $data['min_rooms'] ?? 0;

    // إدخال البيانات لجدول الاهتمامات
    $sql = "INSERT INTO user_interests (user_id, governorate, city, max_price, category, min_rooms) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssdi", $user_id, $governorate, $city, $max_price, $category, $min_rooms);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success", 
            "message" => "تم صيد اهتمام المستخدم بنجاح وسيتم الربط مع التوكن المسجل في حسابه"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "خطأ في تنفيذ الطلب: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "user_id missing"]);
}
?>