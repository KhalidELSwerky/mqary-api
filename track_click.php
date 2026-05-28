<?php
// إظهار أي خطأ يحصل في السيرفر فوراً
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// --- الجزء المضاف لدعم الطريقة الجديدة (JSON Body) ---
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); 
if (is_array($input)) {
    $_REQUEST = array_merge($_REQUEST, $input);
}
// ------------------------------------------------

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'db_config.php';

/**
 * دالة لرمي الخطأ بالتفصيل في المتصفح
 */
function stop_with_error($message, $conn = null) {
    $response = ["status" => "error", "message" => $message];
    if ($conn && $conn->error) {
        $response["sql_error"] = $conn->error;
    }
    die(json_encode($response));
}

// استخدام $_REQUEST عشان يقبل GET من المتصفح و POST من الأبلكيشن
if (isset($_REQUEST['property_id']) && isset($_REQUEST['user_id'])) {
    
    $property_id = intval($_REQUEST['property_id']);
    $user_id = intval($_REQUEST['user_id']);
    $current_time = date('Y-m-d H:i:s');

    // بيانات إضافية للتحليل (International Stats)
    $platform = isset($_REQUEST['platform']) ? $_REQUEST['platform'] : 'android';
    $region = isset($_REQUEST['region']) ? $_REQUEST['region'] : 'Unknown';

    // 1. التحقق من وجود طلبات معلقة (leads_requests) عشان م نكررر الخصم
    $check_sql = "SELECT id FROM leads_requests 
                  WHERE property_id = $property_id 
                  AND user_id = $user_id 
                  AND status = 'pending' LIMIT 1";
                  
    $check_request = $conn->query($check_sql);
    if (!$check_request) {
        stop_with_error("فشل الاستعلام في جدول leads_requests", $conn);
    }

    if ($check_request->num_rows > 0) {
        echo json_encode([
            "status" => "success", 
            "message" => "Lead already pending, no deduction made",
            "debited" => 0
        ]);
        exit();
    }

    // 2. جلب قيمة الـ CPC (تكلفة النقرة) من الإعدادات
    $settings_query = $conn->query("SELECT cpc FROM app_settings LIMIT 1");
    if (!$settings_query || $settings_query->num_rows == 0) {
        stop_with_error("لم يتم العثور على إعدادات الـ CPC في جدول app_settings");
    }
    $settings = $settings_query->fetch_assoc();
    $cpc_value = floatval($settings['cpc']);

    // 3. البحث عن الحملة النشطة (Active Campaign) لهذا العقار
    // تم جلب user_id الخاص بصاحب الحملة لضمان استرداد المال للمالك الصحيح
    $camp_sql = "SELECT id, user_id, total_budget, spent_amount, remaining_budget, end_date FROM ad_campaigns 
                 WHERE property_id = $property_id AND status = 'active' LIMIT 1";
                 
    $campaign_query = $conn->query($camp_sql);
    if (!$campaign_query) {
        stop_with_error("فشل الاستعلام في جدول ad_campaigns", $conn);
    }

    if ($campaign_query->num_rows > 0) {
        $campaign = $campaign_query->fetch_assoc();
        $campaign_id = $campaign['id'];
        $campaign_owner_id = $campaign['user_id'];
        $refund_amount = floatval($campaign['remaining_budget']);

        // أ - فحص انتهاء وقت الحملة + منطق الاسترداد التلقائي (Lazy Refund)
        if ($current_time > $campaign['end_date']) {
            
            if ($refund_amount > 0) {
                // 1. إرجاع المبلغ المتبقي لمحفظة صاحب الحملة في جدول user_stats
                $conn->query("UPDATE user_stats SET wallet_balance = wallet_balance + $refund_amount WHERE user_id = $campaign_owner_id");

                // 2. تسجيل العملية في جدول العمليات (transactions) ليظهر للعميل
                $description = "استرداد ميزانية متبقية للحملة رقم #$campaign_id (انتهى وقت الحملة)";
                $conn->query("INSERT INTO transactions (user_id, amount, type, description) 
                              VALUES ($campaign_owner_id, $refund_amount, 'deposit', '$description')");

                // 3. تحديث بيانات الحملة: إغلاق، تصفير المتبقي، وتسجيل المبلغ المسترد
                $conn->query("UPDATE ad_campaigns SET 
                              status = 'completed', 
                              remaining_budget = 0, 
                              refunded_amount = $refund_amount, 
                              is_refunded = 1 
                              WHERE id = $campaign_id");
            } else {
                // في حال انتهاء الوقت والميزانية مستهلكة بالكامل أصلاً
                $conn->query("UPDATE ad_campaigns SET status = 'completed' WHERE id = $campaign_id");
            }

            echo json_encode(["status" => "error", "message" => "Campaign expired and balance refunded"]);
            exit();
        }

        // ب - تحديث الميزانية (المنفق والمتبقي) وعدد النقرات الإجمالي (للحملة المستمرة)
        // تم إضافة تخصيم القيمة من remaining_budget وزيادتها في spent_amount
        $update_sql = "UPDATE ad_campaigns SET 
                       spent_amount = spent_amount + $cpc_value,
                       remaining_budget = remaining_budget - $cpc_value,
                       clicks_count = clicks_count + 1
                       WHERE id = $campaign_id";

        if ($conn->query($update_sql)) {

            // ج - تسجيل تفاصيل النقرة في جدول الأحداث (الجديد) للرسم البياني والتحليل
            $log_event_sql = "INSERT INTO campaign_events (campaign_id, property_id, user_id, event_type, platform, region) 
                              VALUES ($campaign_id, $property_id, $user_id, 'click', '$platform', '$region')";
            $conn->query($log_event_sql);
            
            // د - فحص الميزانية بعد التحديث مباشرة للإغلاق التلقائي (بناءً على الميزانية المتبقية)
            $check_budget = $conn->query("SELECT remaining_budget FROM ad_campaigns WHERE id = $campaign_id");
            $budget_data = $check_budget->fetch_assoc();

            if ($budget_data['remaining_budget'] <= 0) {
                // تصفير الميزانية المتبقية تماماً عند الوصول للحد الأقصى للإغلاق
                $conn->query("UPDATE ad_campaigns SET status = 'completed', remaining_budget = 0 WHERE id = $campaign_id");
            }

            // النتيجة النهائية الناجحة
            echo json_encode([
                "status" => "success", 
                "message" => "Contact click tracked successfully", 
                "debited" => $cpc_value,
                "property_id" => $property_id
            ]);
        } else {
            stop_with_error("فشل تحديث ميزانية الحملة", $conn);
        }
    } else {
        // لو مفيش حملة نشطة للعقار ده
        echo json_encode([
            "status" => "info", 
            "message" => "No active campaign found for this property",
            "property_id" => $property_id
        ]);
    }
} else {
    // لو البيانات ناقصة في الطلب
    echo json_encode([
        "status" => "error", 
        "message" => "Missing data: property_id and user_id are required"
    ]);
}

$conn->close();
?>