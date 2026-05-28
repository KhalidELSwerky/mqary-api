<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id'])) {
    $user_id = intval($data['user_id']);
    $income = floatval($data['monthly_income']);
    $exp = floatval($data['expenses']);
    $sav = floatval($data['savings_amount']);
    $target = floatval($data['target_property_price']);
    $down = floatval($data['down_payment_target']);
    $dur = intval($data['plan_duration_months']);

    $sql = "INSERT INTO financial_plans (user_id, monthly_income, expenses, savings_amount, target_property_price, down_payment_target, plan_duration_months) 
            VALUES ($user_id, $income, $exp, $sav, $target, $down, $dur)
            ON DUPLICATE KEY UPDATE monthly_income=$income, expenses=$exp, savings_amount=$sav, target_property_price=$target, plan_duration_months=$dur";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Plan Saved"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
?>