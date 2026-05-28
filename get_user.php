<?php
header('Content-Type: application/json; charset=utf-8');
// تأكد من اسم ملف الاتصال الصحيح (db_connect.php أو db_config.php)
include 'db_config.php'; 

// التحقق من وصول معرف المستخدم
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User ID is required"]);
    exit;
}

$user_id = $_GET['user_id'];

// استعلام لجلب بيانات المستخدم مع آخر اشتراك له
// سحبنا u.profile_image و u.phone وغيرها من جدول المستخدمين
// سحبنا s.end_date و s.status من جدول الاشتراكات
$sql = "SELECT u.full_name, u.phone, u.profile_image, s.end_date as subscription_end_date, s.payment_status as sub_status
        FROM users u 
        LEFT JOIN subscriptions s ON u.id = s.subscriber_id 
        WHERE u.id = ? 
        ORDER BY s.id DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // معالجة حالة الصورة إذا كانت فارغة أو Null
    if (empty($user['profile_image']) || $user['profile_image'] == null) {
        $user['profile_image'] = "default.png";
    }
    
    // فحص حالة التوثيق بناءً على التاريخ + حالة الاشتراك (Active)
    $is_verified = 0;
    $current_date = date('Y-m-d');
    
    if (!empty($user['subscription_end_date'])) {
        // التحقق من أن التاريخ لم ينتهِ وأن الحالة نشطة 'active'
        // يمكنك تغيير 'active' لـ 1 لو كنت تستخدم أرقام في قاعدة البيانات
        if ($user['subscription_end_date'] >= $current_date && $user['sub_status'] == 'active') {
            $is_verified = 1;
        }
    }
    
    // إرجاع البيانات بنجاح لتطبيق الفلاتر
    echo json_encode([
        "status" => "success",
        "data" => [
            "full_name" => $user['full_name'],
            "phone" => $user['phone'],
            "profile_image" => $user['profile_image'],
            "subscription_end_date" => $user['subscription_end_date'],
            "is_verified" => $is_verified
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "User not found"]);
}

$stmt->close();
$conn->close();
?>