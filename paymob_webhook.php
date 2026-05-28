<?php
header("Content-Type: application/json");
require_once 'db_config.php';
require_once 'fcm_helper.php'; // عشان نبعت إشعار للمستخدم أول ما الفلوس توصل

// 1. استلام البيانات من Paymob
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// 2. التأكد من نجاح العملية [cite: 2026-02-25]
if (isset($data['obj']['success']) && $data['obj']['success'] === true) {
    
    $amount = $data['obj']['amount_cents'] / 100; // تحويل من قرش لجنيه
    
    // 3. سحب الـ user_id (اللي بعتناه في الـ extra_description)
    $user_id = intval($data['obj']['order']['shipping_data']['extra_description']);
    $transaction_id = $data['obj']['id']; // رقم العملية في باي موب

    if ($user_id > 0) {
        $conn->begin_transaction();

        try {
            // تحديث رصيد المحفظة في جدول user_stats
            $sql_stats = "UPDATE user_stats SET wallet_balance = wallet_balance + ? WHERE user_id = ?";
            $stmt_stats = $conn->prepare($sql_stats);
            $stmt_stats->bind_param("di", $amount, $user_id);
            $stmt_stats->execute();

            // تسجيل العملية في جدول transactions
            $type = 'deposit';
            $description = "شحن محفظة عبر Paymob - رقم العملية: $transaction_id";
            $sql_trans = "INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)";
            $stmt_trans = $conn->prepare($sql_trans);
            $stmt_trans->bind_param("idss", $user_id, $amount, $type, $description);
            $stmt_trans->execute();

            $conn->commit();

            // 4. إرسال إشعار فوري للمستخدم بنجاح الشحن
            $user_res = $conn->query("SELECT fcm_token FROM users WHERE id = $user_id");
            $user_row = $user_res->fetch_assoc();
            if ($user_row && !empty($user_row['fcm_token'])) {
                sendFCMNotification(
                    $user_row['fcm_token'], 
                    "تم الإيداع بنجاح! ✅", 
                    "تم إضافة $amount ج.م إلى رصيدك."
                );
            }

            echo json_encode(["status" => "success"]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }
} else {
    echo json_encode(["status" => "ignored", "message" => "Transaction not successful"]);
}
?>