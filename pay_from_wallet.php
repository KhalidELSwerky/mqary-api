<?php
include "db_config.php";

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'];
$amount = 500.00; 

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "بيانات ناقصة"]);
    exit;
}

$conn->begin_transaction();

try {
    // 1. التأكد من الرصيد
    $stmt = $conn->prepare("SELECT wallet_balance FROM user_stats WHERE user_id = ? FOR UPDATE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows == 0) throw new Exception("المحفظة غير موجودة");
    
    $balance = $res->fetch_assoc()['wallet_balance'];

    if ($balance < $amount) {
        throw new Exception("رصيدك الحالي ($balance ج.م) لا يكفي");
    }

    // 2. خصم المبلغ
    $update_wallet = $conn->prepare("UPDATE user_stats SET wallet_balance = wallet_balance - ? WHERE user_id = ?");
    $update_wallet->bind_param("di", $amount, $user_id);
    $update_wallet->execute();

    // 3. تسجيل العملية في transactions
    $desc = "تجديد اشتراك التوثيق الشهري";
    $type = "payment"; 
    $insert_log = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description, created_at) VALUES (?, ?, ?, ?, NOW())");
    $insert_log->bind_param("idss", $user_id, $amount, $type, $desc);
    $insert_log->execute();

    // 4. إدارة جدول الاشتراكات (subscriptions)
    $check_sub = $conn->prepare("SELECT id, end_date FROM subscriptions WHERE subscriber_id = ? LIMIT 1");
    $check_sub->bind_param("i", $user_id);
    $check_sub->execute();
    $sub_res = $check_sub->get_result();

    $new_end_date = ""; // تعريف المتغير بشكل عام

    if ($sub_res->num_rows > 0) {
        // العميل عنده اشتراك فعلاً -> هنعمل تجديد (Update)
        $sub_data = $sub_res->fetch_assoc();
        $old_end_date = $sub_data['end_date'];
        $current_date = date('Y-m-d');

        $start_from = ($old_end_date > $current_date) ? $old_end_date : $current_date;
        $new_end_date = date('Y-m-d', strtotime($start_from . ' + 1 month'));

        $update_sub = $conn->prepare("UPDATE subscriptions SET start_date = ?, end_date = ?, payment_status = 'active', plan_type = 'developer_pack' WHERE subscriber_id = ?");
        $update_sub->bind_param("ssi", $current_date, $new_end_date, $user_id);
        $update_sub->execute();
    } else {
        // عميل جديد -> هنضيف سطر جديد (Insert)
        $start_date = date('Y-m-d');
        $new_end_date = date('Y-m-d', strtotime($start_date . ' + 1 month')); // تم توحيد الاسم هنا
        $plan_type = 'developer_pack';
        $status = 'active';

        $insert_sub = $conn->prepare("INSERT INTO subscriptions (subscriber_id, plan_type, start_date, end_date, payment_status) VALUES (?, ?, ?, ?, ?)");
        $insert_sub->bind_param("issss", $user_id, $plan_type, $start_date, $new_end_date, $status);
        $insert_sub->execute();
    }

   
    $conn->commit();
    echo json_encode(["status" => "success", "message" => "تم تجديد الاشتراك بنجاح حتى $new_end_date"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>