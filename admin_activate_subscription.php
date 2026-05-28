<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db_config.php';

// استقبال البيانات بصيغة JSON
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id']) && isset($data['months'])) {
    $user_id = mysqli_real_escape_string($conn, $data['user_id']);
    $months = intval($data['months']);
    
    // 1. هنجيب آخر تاريخ انتهاء من جدول الاشتراكات نفسه بناءً على الـ subscriber_id
    // الـ subscriber_id هنا هو نفسه الـ user_id اللي جاي من التطبيق
    $check_query = "SELECT MAX(end_date) as last_date FROM subscriptions WHERE subscriber_id = '$user_id' AND payment_status = 'active'";
    $result = mysqli_query($conn, $check_query);
    $row = mysqli_fetch_assoc($result);
    
    $now = date('Y-m-d');

    // 2. تحديد بداية الاشتراك الجديد
    // لو عنده اشتراك لسه مخلصش، بنبدأ من تاريخ نهايته، لو مخلص بنبدأ من النهاردة
    if ($row && !empty($row['last_date']) && $row['last_date'] > $now) {
        $start_date = $row['last_date'];
    } else {
        $start_date = $now;
    }
                        
    // 3. حساب تاريخ الانتهاء الجديد
    $new_end_date = date('Y-m-d', strtotime("+$months months", strtotime($start_date)));

    // بدء المعاملة لضمان تحديث الجدولين سوا
    mysqli_begin_transaction($conn);

    try {
        // 4. تحديث جدول المطورين (developers)
        // بنحدث حالة subscription_status لـ 'active' بناءً على الـ user_id
        $update_dev = "UPDATE developers SET 
                        subscription_status = 'active'
                      WHERE user_id = '$user_id'";
        mysqli_query($conn, $update_dev);

        // 5. إضافة السجل في جدول الاشتراكات (subscriptions)
        // الربط بالـ subscriber_id اللي هو الـ user_id
        $insert_sub = "INSERT INTO subscriptions (subscriber_id, plan_type, start_date, end_date, payment_status) 
                       VALUES ('$user_id', 'developer_pack', '$start_date', '$new_end_date', 'active')";
        mysqli_query($conn, $insert_sub);

        mysqli_commit($conn);

        echo json_encode([
            "status" => "success", 
            "message" => "تم تفعيل الاشتراك بنجاح حتى $new_end_date",
            "new_expiry" => $new_end_date
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode([
            "status" => "error", 
            "message" => "خطأ في قاعدة البيانات: " . $e->getMessage()
        ]);
    }

} else {
    echo json_encode([
        "status" => "error", 
        "message" => "بيانات ناقصة (user_id أو months)"
    ]);
}
?>