<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    
    // 1. جلب بيانات الخطة
    $sql = "SELECT * FROM financial_plans WHERE user_id = $user_id LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $plan = $result->fetch_assoc();

        // 2. حساب عدد الشهور الملتزم بها من جدول الالتزامات (لو موجود)
        // لو الجدول لسه فاضي، النسبة هتكون 0%
        $sql_commit = "SELECT COUNT(*) as completed FROM monthly_commitments WHERE user_id = $user_id AND is_committed = 1";
        $res_commit = $conn->query($sql_commit);
        $completed_months = ($res_commit) ? $res_commit->fetch_assoc()['completed'] : 0;

        // 3. تجهيز الرد بالهيكل اللي الفلاتر مستنيه
        echo json_encode([
            "status" => "success",
            "data" => [
                "plan" => $plan, // بيانات الجدول اللي بعتهولي
                "progress" => [
                    "completed_months" => intval($completed_months),
                    "total_months" => intval($plan['plan_duration_months']),
                    "percentage" => ($plan['plan_duration_months'] > 0) 
                                    ? ($completed_months / $plan['plan_duration_months']) * 100 
                                    : 0
                ]
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No plan found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing user_id"]);
}
?>