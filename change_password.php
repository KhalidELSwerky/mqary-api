<?php
header('Content-Type: application/json');
include 'db_config.php'; // ملف الاتصال بقاعدة البيانات

// استقبال البيانات من تطبيق فلاتر
$user_id = $_POST['user_id'];
$old_password = $_POST['old_password'];
$new_password = $_POST['new_password'];

// التأكد من وصول جميع البيانات المطلوبة
if (!$user_id || !$old_password || !$new_password) {
    echo json_encode(["status" => "error", "message" => "جميع الحقول مطلوبة"]);
    exit;
}

// 1. جلب كلمة المرور الحالية من قاعدة البيانات للتحقق منها
$sql = "SELECT password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $hashed_password = $user['password'];

    // 2. التحقق هل كلمة المرور القديمة صحيحة؟
    // ملاحظة: استخدم password_verify إذا كانت الكلمة مشفرة بـ BCRYPT، 
    // أو قارنها مباشرة إذا كانت غير مشفرة (ولكن يفضل التشفير دائماً)
    if (password_verify($old_password, $hashed_password) || $old_password === $hashed_password) {
        
        // 3. تشفير كلمة المرور الجديدة
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // 4. تحديث كلمة المرور في قاعدة البيانات
        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "تم تغيير كلمة المرور بنجاح"]);
        } else {
            echo json_encode(["status" => "error", "message" => "فشل تحديث البيانات في السيرفر"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "كلمة المرور القديمة غير صحيحة"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "المستخدم غير موجود"]);
}

$stmt->close();
$conn->close();
?>