<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

// إيقاف الأخطاء عشان متبوظش الـ JSON
error_reporting(0);
ini_set('display_errors', 0);

if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    $response = [];

    // 1. جلب بيانات الخطة المالية
    $plan_sql = "SELECT * FROM financial_plans WHERE user_id = $user_id LIMIT 1";
    $plan_res = $conn->query($plan_sql);
    
    if ($plan_res && $plan_res->num_rows > 0) {
        $plan = $plan_res->fetch_assoc();
        $response['plan'] = $plan;

        // 2. حساب نسبة الإنجاز
        $commit_sql = "SELECT COUNT(*) as committed_months FROM commitment_tracker WHERE user_id = $user_id AND is_committed = 1";
        $commit_res = $conn->query($commit_sql);
        $total_committed = ($commit_res) ? $commit_res->fetch_assoc()['committed_months'] : 0;
        
        // تأمين الحساب عشان لو المدة صفر ميعملش Division by zero error
        $duration = intval($plan['plan_duration_months']);
        $percentage = ($duration > 0) ? ($total_committed / $duration) * 100 : 0;

        $response['progress'] = [
            "total_months" => $duration,
            "completed_months" => intval($total_committed),
            "percentage" => round($percentage, 1)
        ];
    } else {
        // الزتونة هنا: لو مفيش خطة، بنبعت بيانات "صفرية" بدل الـ null عشان الفلاتر ميهنجش
        $response['plan'] = [
            "target_property_price" => "0",
            "savings_amount" => "0",
            "governorate" => "غير محدد"
        ];
        $response['progress'] = [
            "total_months" => 0,
            "completed_months" => 0,
            "percentage" => 0
        ];
        $response['message'] = "لم يتم إنشاء خطة بعد";
    }

    echo json_encode(["status" => "success", "data" => $response]);
} else {
    echo json_encode(["status" => "error", "message" => "user_id is required"]);
}

$conn->close();
?>