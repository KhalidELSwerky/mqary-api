<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

// إخفاء الأخطاء لضمان استلام التطبيق لـ JSON نظيف فقط
error_reporting(0);
ini_set('display_errors', 0);

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    // 1. جلب بيانات الخطة من جدول financial_plans
    $sql_plan = "SELECT * FROM financial_plans WHERE user_id = $user_id LIMIT 1";
    $result_plan = $conn->query($sql_plan);

    if ($result_plan && $result_plan->num_rows > 0) {
        $plan = $result_plan->fetch_assoc();
        
        // 2. جلب مجموع المدخرات - تم تعديل اسم العمود إلى amount_saved بناءً على بياناتك
        $sql_commit = "SELECT SUM(amount_saved) as actual_saved FROM commitment_tracker WHERE user_id = $user_id AND is_committed = 1";
        $res_commit = $conn->query($sql_commit);
        
        $total_saved = 0;
        if ($res_commit) {
            $row_commit = $res_commit->fetch_assoc();
            $total_saved = $row_commit['actual_saved'] ?? 0;
        }

        // 3. إرسال البيانات النهائية للتطبيق
        echo json_encode([
            "status" => "success",
            "data" => [
                "plan" => [
                    "target_property_price" => (float)$plan['target_property_price'],
                    "savings_amount" => (float)$plan['savings_amount'],
                    "governorate" => "قنا" // قيمة افتراضية لعدم وجود العمود في الجدول
                ],
                "progress" => [
                    "total_saved_amount" => (float)$total_saved,
                    "total_months" => (int)$plan['plan_duration_months']
                ]
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "no_plan"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "no_id"]);
}
?>