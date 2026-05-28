<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include "db_config.php";

// 1. نظام الـ Logger لمراقبة العمليات (Debug)
function write_log($message)
{
    $log_file = "debug_log.txt";
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// 2. استقبال البيانات بصيغة JSON
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

write_log("Raw Input: " . $json_data);

if (isset($data['campaign_id']) && isset($data['viewer_id'])) {
    $campaign_id = intval($data['campaign_id']);
    $viewer_id = intval($data['viewer_id']);
    $current_time = date('Y-m-d H:i:s');

    // محاولة تسجيل المشاهدة في جدول ad_logs لضمان عدم التكرار
    $log_query = "INSERT IGNORE INTO ad_logs (campaign_id, viewer_id) 
                  VALUES ('$campaign_id', '$viewer_id')";

    if ($conn->query($log_query)) {
        if ($conn->affected_rows > 0) {
            
            // جلب بيانات الحملة الحالية للتأكد من الوقت والميزانية
            $check_sql = "SELECT user_id, total_budget, remaining_budget, target_reach, current_reach, end_date FROM ad_campaigns WHERE id = '$campaign_id' AND status = 'active'";
            $res = $conn->query($check_sql);
            
            if ($res && $res->num_rows > 0) {
                $campaign = $res->fetch_assoc();
                $owner_id = $campaign['user_id'];
                $remaining = floatval($campaign['remaining_budget']);
                $cost_per_view = floatval($campaign['total_budget'] / $campaign['target_reach']);

                // أ - فحص انتهاء وقت الحملة عند الظهور (Lazy Refund)
                if ($current_time > $campaign['end_date']) {
                    if ($remaining > 0) {
                        // 1. إرجاع المبلغ المتبقي للمحفظة
                        $conn->query("UPDATE user_stats SET wallet_balance = wallet_balance + $remaining WHERE user_id = $owner_id");
                        
                        // 2. تسجيل العملية في جدول transactions
                        $desc = "استرداد ميزانية متبقية للحملة رقم #$campaign_id (انتهى وقت الحملة عند الظهور)";
                        $conn->query("INSERT INTO transactions (user_id, amount, type, description) VALUES ($owner_id, $remaining, 'deposit', '$desc')");
                        
                        // 3. تحديث الحملة للإغلاق وتصفير المتبقي
                        $conn->query("UPDATE ad_campaigns SET status = 'completed', remaining_budget = 0, refunded_amount = $remaining, is_refunded = 1 WHERE id = '$campaign_id'");
                    } else {
                        $conn->query("UPDATE ad_campaigns SET status = 'completed' WHERE id = '$campaign_id'");
                    }
                    echo json_encode(["status" => "error", "message" => "Campaign expired"]);
                    exit();
                }

                // ب - تحديث العداد والمبلغ المصروف (في حال الحملة مستمرة)
                $update_campaign = "UPDATE ad_campaigns 
                                    SET current_reach = current_reach + 1,
                                        spent_amount = spent_amount + (total_budget / target_reach),
                                        remaining_budget = remaining_budget - (total_budget / target_reach)
                                    WHERE id = '$campaign_id' AND status = 'active'";
                
                if ($conn->query($update_campaign)) {
                    
                    // ج - فحص الميزانية أو الوصول بعد التحديث للإغلاق التلقائي
                    $after_update = $conn->query("SELECT current_reach, target_reach, remaining_budget FROM ad_campaigns WHERE id = '$campaign_id'");
                    $row = $after_update->fetch_assoc();

                    if ($row['current_reach'] >= $row['target_reach'] || $row['remaining_budget'] <= 0) {
                        $conn->query("UPDATE ad_campaigns SET status = 'completed', remaining_budget = 0 WHERE id = '$campaign_id'");
                    }

                    write_log("SUCCESS: Impression & Spent Amount updated for Campaign: $campaign_id");
                    echo json_encode(["status" => "success", "message" => "Impression and cost counted"]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Campaign not active"]);
            }
        } else {
            // المستخدم شاهد الإعلان مسبقاً
            write_log("IGNORED: Duplicate view for Campaign: $campaign_id by User: $viewer_id");
            echo json_encode(["status" => "ignored", "message" => "View already recorded"]);
        }
    } else {
        write_log("DB ERROR: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Database error"]);
    }
} else {
    write_log("ERROR: Invalid Data Received or Missing IDs");
    echo json_encode(["status" => "error", "message" => "Missing data"]);
}

$conn->close();
?>