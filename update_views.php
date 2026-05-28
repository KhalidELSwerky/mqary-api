<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

// استدعاء ملف الاتصال الخاص بك
include "db_config.php";

// استلام المعرف (نستخدم broker_id ليكون متوافقاً مع Flutter)
if (isset($_POST['broker_id'])) {
    $broker_id = mysqli_real_escape_string($conn, $_POST['broker_id']);

    // أولاً: التحقق من وجود سجل لهذا المستخدم في جدول user_stats
    $check_query = "SELECT user_id FROM user_stats WHERE user_id = '$broker_id'";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        // إذا كان السجل موجوداً: قم بزيادة العداد
        $sql = "UPDATE user_stats SET profile_views = profile_views + 1 WHERE user_id = '$broker_id'";
    } else {
        // إذا كان السجل غير موجود (أول مشاهدة): قم بإنشاء السجل
        $sql = "INSERT INTO user_stats (user_id, profile_views) VALUES ('$broker_id', 1)";
    }
    
    if ($conn->query($sql)) {
        echo json_encode([
            "status" => "success", 
            "message" => "تم تسجيل المشاهدة بنجاح"
        ]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "فشل تحديث البيانات: " . $conn->error
        ]);
    }
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "لم يتم استلام معرف المعلن"
    ]);
}

$conn->close();
?>