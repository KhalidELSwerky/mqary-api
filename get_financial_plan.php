<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    
    // جلب البيانات الفعلية فقط من جدول financial_plans
    $sql = "SELECT monthly_income, target_property_price, savings_amount, expenses, down_payment_target, plan_duration_months, status 
            FROM financial_plans 
            WHERE user_id = $user_id LIMIT 1";
            
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $plan = $result->fetch_assoc();
        // إرسال البيانات الحقيقية فقط
        echo json_encode(["status" => "success", "data" => $plan]);
    } else {
        // في حال عدم وجود خطة سابقة، نرسل خطأ ولا نضع أي قيم افتراضية
        echo json_encode(["status" => "error", "message" => "No previous plan found for this user"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "user_id is required"]);
}

$conn->close();
?>