<?php
header('Content-Type: application/json');
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // التأكد من وصول المعرف ونوع الوثيقة والملف ورقم المستند (الرقم الضريبي أو البطاقة)
    if (!isset($_POST['user_id']) || !isset($_POST['document_type']) || !isset($_POST['document_number']) || !isset($_FILES['image'])) {
        echo json_encode(["status" => "error", "message" => "بيانات التوثيق ناقصة (المعرف، النوع، رقم المستند، أو الملف)"]);
        exit;
    }

    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $doc_type = mysqli_real_escape_string($conn, $_POST['document_type']); // id_card, commercial_registry, license
    $doc_number = mysqli_real_escape_string($conn, $_POST['document_number']); // رقم البطاقة أو السجل المبعوث من التطبيق
    
    // --- الذكاء المطلوب: تحديد نوع الطلب وحساب عدد المرات ---
    $count_sql = "SELECT COUNT(*) as total FROM `verification_requests` WHERE `user_id` = '$user_id'";
    $count_result = mysqli_query($conn, $count_sql);
    $count_row = mysqli_fetch_assoc($count_result);
    
    $attempts_count = $count_row['total'] + 1; // دي المرة رقم كام
    $request_type = ($count_row['total'] > 0) ? 'update_info' : 'new_verification';
    // -----------------------------------------------------

    // المجلد المخصص لصور التوثيق
    $target_dir = "uploads/verifications/";

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // تسمية الملف باسم فريد
    $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $new_file_name = "verify_" . $user_id . "_" . time() . "." . $extension;
    $target_file = $target_dir . $new_file_name;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        
        // التعديل هنا: إضافة attempts_count لجملة الـ INSERT لضمان التسجيل في قاعدة البيانات
        $sql = "INSERT INTO `verification_requests` 
                (`user_id`, `document_type`, `document_number`, `request_type`, `file_path`, `admin_approval`, `admin_notes`, `attempts_count`) 
                VALUES 
                ('$user_id', '$doc_type', '$doc_number', '$request_type', '$new_file_name', 'pending', '', '$attempts_count')";

        // تنفيذ الاستعلام
        if (mysqli_query($conn, $sql)) {
            echo json_encode([
                "status" => "success", 
                "message" => "تم رفع الوثيقة بنجاح",
                "file_path" => $new_file_name,
                "request_type" => $request_type, // يخبر التطبيق هل هو طلب جديد أم تحديث
                "attempt_number" => $attempts_count // يخبر التطبيق أن هذه هي المحاولة رقم X
            ]);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "فشل تحديث قاعدة البيانات: " . mysqli_error($conn)
            ]);
        }

    } else {
        echo json_encode(["status" => "error", "message" => "فشل رفع ملف الوثيقة للسيرفر"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}

mysqli_close($conn);
?>