<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['phone'], $data['password'])) {
    $phone = $conn->real_escape_string($data['phone']);
    $password = $data['password'];
    // التعديل: استقبال توكن الإشعارات إذا كان مرسلاً من التطبيق
    $fcm_token = isset($data['fcm_token']) ? $conn->real_escape_string($data['fcm_token']) : null;

    $sql = "SELECT * FROM users WHERE phone = '$phone'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            
            // التعديل: تحديث التوكن في قاعدة البيانات فور التأكد من صحة الحساب
            if ($fcm_token) {
                $user_id = $user['id'];
                $conn->query("UPDATE users SET fcm_token = '$fcm_token' WHERE id = '$user_id'");
                $user['fcm_token'] = $fcm_token; // إضافته لبيانات المستخدم العائدة للتطبيق
            }

            unset($user['password']); // إزالة الباسورد للأمان
            echo json_encode(["status" => "success", "user" => $user]);
        } else {
            echo json_encode(["status" => "error", "message" => "Wrong password"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }
}
?>