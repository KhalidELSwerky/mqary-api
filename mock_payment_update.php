<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

// استلام البيانات من تطبيق فلاتر [cite: 2026-01-29]
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id'], $data['amount'])) {
    $user_id = intval($data['user_id']);
    $amount = floatval($data['amount']);
    
    // بيانات Paymob اللي جبناها من الصور
    $api_key = "CCCDFE8C0AD5AEE03FFDC68CB1EA3BD1"; 
    $integration_id = "5554027"; 
 
    try {
        // 1. الحصول على Auth Token [cite: 2026-02-25]
        $auth_req = curl_post("https://accept.paymob.com/api/auth/tokens", ["api_key" => $api_key]);
        if (!isset($auth_req['token'])) throw new Exception("Failed to get Auth Token");
        $token = $auth_req['token'];

        // 2. تسجيل الطلب في Paymob (Order Registration)
        $order_data = [
            "auth_token" => $token,
            "delivery_needed" => "false",
            "amount_cents" => $amount * 100, // المبلغ بالقرش
            "currency" => "EGP",
            "items" => []
        ];
        $order_req = curl_post("https://accept.paymob.com/api/ecommerce/orders", $order_data);
        $order_id = $order_req['id'];

        // 3. الحصول على Payment Key (اللي بيفتح صفحة الدفع) [cite: 2026-02-25]
        $payment_data = [
            "auth_token" => $token,
            "amount_cents" => $amount * 100,
            "expiration" => 3600,
            "order_id" => $order_id,
            "billing_data" => [
                "first_name" => "Broker", 
                "last_name" => "User", 
                "email" => "test@test.com", 
                "phone_number" => "01000000000",
                "apartment" => "NA", "floor" => "NA", "street" => "NA", "building" => "NA", 
                "shipping_method" => "NA", "postal_code" => "NA", "city" => "NA", "country" => "NA", "state" => "NA"
            ],
            "currency" => "EGP",
            "integration_id" => $integration_id
        ];
        $payment_key_req = curl_post("https://accept.paymob.com/api/accept/payment_keys", $payment_data);

        // نبعت التوكن للفلاتر عشان يفتح الـ WebView [cite: 2026-02-25]
        echo json_encode([
            "status" => "success", 
            "payment_token" => $payment_key_req['token']
        ]);

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}

// دالة الـ CURL لإرسال البيانات [cite: 2026-02-25]
function curl_post($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>
