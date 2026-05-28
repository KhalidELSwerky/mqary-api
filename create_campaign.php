<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
include "db_config.php";

if (isset($_POST['property_id']) && isset($_POST['user_id']) && isset($_POST['budget'])) {
    
    // استقبال البيانات الأساسية
    $property_id = intval($_POST['property_id']);
    $user_id = intval($_POST['user_id']);
    $budget = floatval($_POST['budget']);
    
    // استقبال البيانات الجديدة (الديناميكية)
    $cpm_applied = isset($_POST['cpm_applied']) ? floatval($_POST['cpm_applied']) : 30.00;
    $cpc_applied = isset($_POST['cpc_applied']) ? floatval($_POST['cpc_applied']) : 2.00;
    $campaign_type = isset($_POST['campaign_type']) ? mysqli_real_escape_string($conn, $_POST['campaign_type']) : 'CPM';
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 3;

    // حساب الوصول المتوقع وتاريخ الانتهاء
    $target_reach = ($budget / $cpm_applied) * 1000;
    $end_date = date('Y-m-d H:i:s', strtotime("+$duration days"));

    // التحقق من وجود حملة نشطة حالياً لهذا العقار
    $check_active_query = "SELECT id FROM ad_campaigns WHERE property_id = '$property_id' AND user_id = '$user_id' AND status = 'active'";
    $active_result = $conn->query($check_active_query);

    if ($active_result && $active_result->num_rows > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "يوجد بالفعل حملة إعلانية نشطة لهذا العقار. لا يمكنك بدء حملة جديدة حتى تنتهي الحملة الحالية."
        ]);
    } else {
        // 1. التأكد من الرصيد من جدول user_stats
        $stats_query = "SELECT wallet_balance FROM user_stats WHERE user_id = '$user_id'";
        $stats_result = $conn->query($stats_query);

        if ($stats_result && $stats_result->num_rows > 0) {
            $stats_row = $stats_result->fetch_assoc();
            $current_balance = floatval($stats_row['wallet_balance']);

            // 2. التحقق من كفاية الرصيد
            if ($current_balance >= $budget) {
                
                // أ - خصم المبلغ من جدول user_stats
                $conn->query("UPDATE user_stats SET wallet_balance = wallet_balance - $budget WHERE user_id = '$user_id'");

                // ب - تسجيل عملية الخصم في جدول transactions (ليظهر في سجل العمليات)
                $description = "ترويج العقار رقم #$property_id ($campaign_type - $duration يوم)";
                $conn->query("INSERT INTO transactions (user_id, amount, type, description) 
                              VALUES ('$user_id', '$budget', 'withdraw', '$description')");

                // ج - إنشاء الحملة في جدول ad_campaigns مع الحقول الجديدة
                // تم إضافة عمود remaining_budget وضبط قيمته لتكون مساوية لـ budget عند البداية
                $sql_campaign = "INSERT INTO ad_campaigns 
                                 (property_id, user_id, total_budget, remaining_budget, cpm, cpc, target_reach, campaign_type, duration_days, end_date, status) 
                                 VALUES 
                                 ('$property_id', '$user_id', '$budget', '$budget', '$cpm_applied', '$cpc_applied', '$target_reach', '$campaign_type', '$duration', '$end_date', 'active')";

                if ($conn->query($sql_campaign)) {
                    echo json_encode([
                        "status" => "success", 
                        "message" => "تم خصم الرصيد وبدء الترويج بنجاح",
                        "new_balance" => ($current_balance - $budget),
                        "expected_reach" => round($target_reach)
                    ]);
                } else {
                    // في حالة الفشل نرجع الرصيد (Rollback بسيط)
                    $conn->query("UPDATE user_stats SET wallet_balance = wallet_balance + $budget WHERE user_id = '$user_id'");
                    echo json_encode(["status" => "error", "message" => "خطأ في إنشاء الحملة: " . $conn->error]);
                }
            } else {
                echo json_encode([
                    "status" => "error", 
                    "message" => "رصيدك غير كافٍ. الرصيد الحالي: $current_balance"
                ]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "لم يتم العثور على محفظة لهذا المستخدم"]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات غير مكتملة"]);
}
$conn->close();
?>