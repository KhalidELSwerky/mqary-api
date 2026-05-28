<?php
header('Content-Type: application/json');
include 'db_config.php';

$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User ID is required"]);
    exit;
}

// 1. تحديث الاسم إذا تم إرساله
if (isset($_POST['full_name'])) {
    $full_name = $_POST['full_name'];
    $sql = "UPDATE users SET full_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $full_name, $user_id);
    $stmt->execute();
}

// 2. تحديث الصورة إذا تم رفع ملف
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $target_dir = "uploads/profiles/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_info = pathinfo($_FILES["image"]["name"]);
    $file_extension = strtolower($file_info['extension']);
    
    // --- ميزة أمان الملفات ---
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode(["status" => "error", "message" => "نوع الملف غير مدعوم. مسموح فقط بـ JPG, PNG, WEBP"]);
        exit;
    }

    $new_filename = "profile_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    // --- ميزة حذف الصورة القديمة ---
    // أولاً: نجيب اسم الصورة الحالية من قاعدة البيانات
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $get_stmt = $conn->prepare($query);
    $get_stmt->bind_param("i", $user_id);
    $get_stmt->execute();
    $result = $get_stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $old_image = $row['profile_image'];
        $old_path = $target_dir . $old_image;
        // لو الملف موجود فعلياً على السيرفر، امسحه
        if (!empty($old_image) && file_exists($old_path)) {
            unlink($old_path);
        }
    }

    // رفع الصورة الجديدة
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
        $update_stmt = $conn->prepare($sql);
        $update_stmt->bind_param("si", $new_filename, $user_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                "status" => "success", 
                "message" => "تم تحديث البيانات وحذف الصورة القديمة", 
                "image" => $new_filename
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "فشل تحديث قاعدة البيانات"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "فشل في نقل الملف المرفوع"]);
    }
} else {
    echo json_encode(["status" => "success", "message" => "تم تحديث الاسم بنجاح"]);
}
?>