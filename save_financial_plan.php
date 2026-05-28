<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST"); // تحديد النوع POST
require_once 'db_config.php';

// استقبال البيانات المرسلة من التطبيق
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id'])) {
    $user_id = intval($data['user_id']);
    $income = $data['monthly_income'];
    $expenses = $data['expenses'];
    $savings = $data['savings_amount'];
    $target = $data['target_property_price'];
    $down_payment = $data['down_payment_target'];
    $duration = $data['plan_duration_months'];

    // جملة الاستعلام للحفظ (أو التحديث إذا كانت الخطة موجودة)
    $sql = "INSERT INTO financial_plans (user_id, monthly_income, expenses, savings_amount, target_property_price, down_payment_target, plan_duration_months) 
            VALUES ($user_id, $income, $expenses, $savings, $target, $down_payment, $duration)
            ON DUPLICATE KEY UPDATE 
            monthly_income=$income, expenses=$expenses, savings_amount=$savings, target_property_price=$target, plan_duration_months=$duration";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Plan saved successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
?>