<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'db_config.php';

// منع ظهور أي أخطاء نصية تفسد الـ JSON
error_reporting(0);
ini_set('display_errors', 0);

$data = json_decode(file_get_contents("php://input"), true);

// التحقق من القيم الأساسية المطلوبة فقط
if (isset($data['full_name'], $data['phone'], $data['password'])) {
    
    $full_name = $conn->real_escape_string($data['full_name']);
    $phone = $conn->real_escape_string($data['phone']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // استخدام المشغل الثلاثي للتحقق من وجود بقية القيم أو وضعها كـ NULL
    $gov = isset($data['governorate']) ? $conn->real_escape_string($data['governorate']) : null;
    $city = isset($data['city']) ? $conn->real_escape_string($data['city']) : null;
    // التعديل: استقبال تاريخ الميلاد والنوع بدلاً من العمر
    $birth_date = isset($data['birth_date']) ? $conn->real_escape_string($data['birth_date']) : null;
    $gender = isset($data['gender']) ? $conn->real_escape_string($data['gender']) : 'male';
    $salary = isset($data['approx_salary']) ? floatval($data['approx_salary']) : 0.0;
    $role = isset($data['role']) ? $conn->real_escape_string($data['role']) : 'user';
    // التعديل: استقبال توكن الإشعارات
    $fcm_token = isset($data['fcm_token']) ? $conn->real_escape_string($data['fcm_token']) : null;

    // استعلام الإدخال المعدل ليشمل birth_date و gender بدلاً من age
    $sql = "INSERT INTO users (full_name, phone, password, governorate, city, birth_date, gender, approx_salary, role, fcm_token) 
            VALUES ('$full_name', '$phone', '$password', " . ($gov ? "'$gov'" : "NULL") . ", " . ($city ? "'$city'" : "NULL") . ", " . ($birth_date ? "'$birth_date'" : "NULL") . ", '$gender', $salary, '$role', " . ($fcm_token ? "'$fcm_token'" : "NULL") . ")";

    if ($conn->query($sql) === TRUE) {
        // أهم سطر: إرجاع الـ ID الجديد لكي يستخدمه الـ Flutter في الحفظ
        echo json_encode([
            "status" => "success", 
            "message" => "User registered successfully",
            "user_id" => $conn->insert_id 
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات غير مكتملة (الاسم، الهاتف، وكلمة المرور مطلوبة)"]);
}
































// header("Access-Control-Allow-Origin: *");
// header("Content-Type: application/json; charset=UTF-8");
// require_once 'db_config.php';

// $data = json_decode(file_get_contents("php://input"), true);

// if (isset($data['full_name'], $data['phone'], $data['password'])) {
//     $full_name = $conn->real_escape_string($data['full_name']);
//     $phone = $conn->real_escape_string($data['phone']);
//     $password = password_hash($data['password'], PASSWORD_DEFAULT);
//     $gov = $conn->real_escape_string($data['governorate']);
//     $city = $conn->real_escape_string($data['city']);
//     $age = intval($data['age']);
//     $salary = floatval($data['approx_salary']);
//     $role = $conn->real_escape_string($data['role']);

//     $sql = "INSERT INTO users (full_name, phone, password, governorate, city, age, approx_salary, role) 
//             VALUES ('$full_name', '$phone', '$password', '$gov', '$city', $age, $salary, '$role')";

//     if ($conn->query($sql) === TRUE) {
//         echo json_encode(["status" => "success", "message" => "User registered successfully"]);
//     } else {
//         echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
//     }
// } else {
//     echo json_encode(["status" => "error", "message" => "Incomplete data"]);
// }
?>