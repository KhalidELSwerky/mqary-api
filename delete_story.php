<?php
header("Content-Type: application/json");
include 'db_config.php'; // تأكد من اسم ملف الاتصال بقاعدة البيانات عندك

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // التعديل المطلوب لاستقبال بيانات JSON من Flutter
    $json_data = json_decode(file_get_contents("php://input"), true);
    $story_id = isset($_POST['story_id']) ? $_POST['story_id'] : (isset($json_data['story_id']) ? $json_data['story_id'] : null);
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : (isset($json_data['user_id']) ? $json_data['user_id'] : null);

    if (empty($story_id) || empty($user_id)) {
        echo json_encode(["status" => "error", "message" => "بيانات ناقصة"]);
        exit;
    }

    // 1. التحقق أولاً أن الحالة تخص المستخدم (أمان إضافي)
    $check_query = "SELECT media_url FROM stories WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $story_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_path = "uploads/stories/" . $row['media_url'];

        // 2. حذف السجل من قاعدة البيانات
        $delete_query = "DELETE FROM stories WHERE id = ?";
        $del_stmt = $conn->prepare($delete_query);
        $del_stmt->bind_param("s", $story_id);
        
        if ($del_stmt->execute()) {
            // 3. حذف الملف الفعلي من السيرفر لتوفير المساحة
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            echo json_encode(["status" => "success", "message" => "تم حذف الحالة بنجاح"]);
        } else {
            echo json_encode(["status" => "error", "message" => "فشل حذف السجل"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "لا تملك صلاحية حذف هذه الحالة"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "طريقة طلب غير صالحة"]);
}
?>