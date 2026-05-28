<?php
// إعدادات الرأس للتعامل مع التطبيق
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

include "db_config.php";

// التأكد من استلام معرف المستخدم
if (isset($_GET['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_GET['user_id']);

    // 1. استعلام جلب المحفظة والمشاهدات من الجدول الجديد user_stats
    $stats_query = "SELECT wallet_balance, profile_views FROM user_stats WHERE user_id = '$user_id'";
    $stats_result = $conn->query($stats_query);

    $wallet_balance = 0.00;
    $profile_views = 0;

    if ($stats_result->num_rows > 0) {
        $stats_row = $stats_result->fetch_assoc();
        $wallet_balance = $stats_row['wallet_balance'];
        $profile_views = $stats_row['profile_views'];
    } else {
        // إذا لم يوجد سجل، نقوم بإنشائه الآن لضمان استقرار التطبيق
        $conn->query("INSERT INTO user_stats (user_id, wallet_balance, profile_views) VALUES ('$user_id', 0.00, 0)");
    }

    // // 2. استعلام جلب عدد طلبات التواصل (Leads) من جدول leads
    // $leads_query = "SELECT COUNT(*) as total_leads FROM leads WHERE user_id = '$user_id'";
    // $leads_result = $conn->query($leads_query);
    // $total_leads = 0;

    // if ($leads_result->num_rows > 0) {
    //     $leads_row = $leads_result->fetch_assoc();
    //     $total_leads = (int)$leads_row['total_leads'];
    // }

    // 3. تجهيز الرد النهائي بصيغة JSON
    echo json_encode([
        "status" => "success",
        "data" => [
            "wallet_balance" => (float)$wallet_balance,
            "profile_views"  => (int)$profile_views,
            // "total_leads"    => $total_leads
        ]
    ]);

} else {
    echo json_encode([
        "status" => "error",
        "message" => "يجب إرسال user_id"
    ]);
}

$conn->close();
?>