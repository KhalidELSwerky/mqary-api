<?php
// إظهار أي خطأ يحصل في السيرفر فوراً
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'db_config.php';

// تم إضافة التحقق من viewer_id ليتوافق مع جدول ad_logs
if (isset($_POST['property_id']) && isset($_POST['viewer_id'])) {
    $property_id = intval($_POST['property_id']);
    $viewer_id = intval($_POST['viewer_id']);
    $current_time = date('Y-m-d H:i:s'); // التوقيت الحالي للسيرفر
    $view_date = date('Y-m-d'); // التاريخ الحالي للمقارنة في ad_logs

    // بيانات إضافية للتحليل (International Stats)
    $platform = isset($_POST['platform']) ? $_POST['platform'] : 'android';
    $region = isset($_POST['region']) ? $_POST['region'] : 'Unknown';

    // 1. زيادة عدد المشاهدات العام للعقار (دائماً تزيد)
    $conn->query("UPDATE properties SET views = views + 1 WHERE id = $property_id");

    // 2. التحقق من وجود حملة إعلانية نشطة (مع فحص التاريخ والميزانية)
    $campaign_query = $conn->query("SELECT id, cpm, total_budget, spent_amount, end_date FROM ad_campaigns 
                                    WHERE property_id = $property_id 
                                    AND status = 'active' 
                                    LIMIT 1");

    if ($campaign_query && $campaign_query->num_rows > 0) {
        $campaign = $campaign_query->fetch_assoc();
        $campaign_id = $campaign['id'];
        $end_date = $campaign['end_date'];

        // أ - فحص هل انتهى وقت الحملة؟
        if ($current_time > $end_date) {
            $conn->query("UPDATE ad_campaigns SET status = 'completed' WHERE id = $campaign_id");
            echo json_encode(["status" => "info", "message" => "Campaign ended by time"]);
            exit();
        }

        // --- الجزء الجديد: التحقق من تكرار المشاهدة في نفس اليوم لمنع استنزاف الميزانية ---
        $check_log = $conn->query("SELECT id FROM ad_logs 
                                   WHERE campaign_id = $campaign_id 
                                   AND viewer_id = $viewer_id 
                                   AND view_date = '$view_date'");

        if ($check_log->num_rows == 0) {
            // تسجيل المشاهدة في السجل أولاً
            $conn->query("INSERT INTO ad_logs (campaign_id, viewer_id, view_date) VALUES ($campaign_id, $viewer_id, '$view_date')");

            // ب - حساب تكلفة المشاهدة الواحدة (CPM / 1000)
            $cost_per_view = $campaign['cpm'] / 1000;

            // ج - تحديث الحملة: زيادة الوصول الفعلي + زيادة المبلغ المصروف
            $update_campaign = "UPDATE ad_campaigns SET 
                                current_reach = current_reach + 1, 
                                spent_amount = spent_amount + $cost_per_view 
                                WHERE id = $campaign_id";
            
            if ($conn->query($update_campaign)) {

                // تسجيل تفاصيل المشاهدة في جدول الأحداث (الجديد) للرسم البياني والتحليل
                $log_event_sql = "INSERT INTO campaign_events (campaign_id, property_id, user_id, event_type, platform, region) 
                                  VALUES ($campaign_id, $property_id, $viewer_id, 'view', '$platform', '$region')";
                $conn->query($log_event_sql);

                // د - فحص هل الميزانية خلصت بعد الخصم الأخير؟
                if (($campaign['spent_amount'] + $cost_per_view) >= $campaign['total_budget']) {
                    $conn->query("UPDATE ad_campaigns SET status = 'completed' WHERE id = $campaign_id");
                }
            }
            echo json_encode(["status" => "success", "message" => "Unique view recorded and budget deducted"]);
        } else {
            echo json_encode(["status" => "success", "message" => "View counted, but no budget deducted (Already viewed today)"]);
        }
    } else {
        echo json_encode(["status" => "success", "message" => "View updated"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing property_id or viewer_id"]);
}

$conn->close();
?>