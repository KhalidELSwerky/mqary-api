<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "real_estate_planner";

// الاتصال باستخدام MySQLi
$conn = new mysqli($host, $user, $pass, $db);

// ضبط الترميز لدعم اللغة العربية
$conn->set_charset("utf8mb4");

// التحقق من الاتصال
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection Failed: " . $conn->connect_error]));
}

// --- نظام الحماية المضاف لتأمين الـ APIs (مقري 2026) ---
// هذا المفتاح يجب أن يكون متطابقاً في كود الفلاتر (Flutter) وكود الـ PHP
define('APP_SECRET_KEY', 'Maqari_Secure_2026_Admin_Access_Token');

?>