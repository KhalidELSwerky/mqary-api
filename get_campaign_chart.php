<?php
// إظهار أي خطأ يحصل في السيرفر فوراً
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'db_config.php';

if (isset($_GET['property_id'])) {
    $property_id = intval($_GET['property_id']);

    // 1. جلب ID آخر حملة لهذا العقار لضمان دقة البيانات المعروضة في المخطط
    $campaign_check = $conn->query("SELECT id FROM ad_campaigns WHERE property_id = $property_id ORDER BY id DESC LIMIT 1");
    
    if ($campaign_check && $campaign_check->num_rows > 0) {
        $campaign = $campaign_check->fetch_assoc();
        $campaign_id = $campaign['id'];

        // 2. استعلام لجلب عدد النقرات والوصول لكل يوم مع تنسيق التاريخ بشكل احترافي (رقم 3 في Flutter)
        // تم تعديل الاستعلام ليشمل SUM(CASE) لفرز الـ views والـ clicks بدلاً من COUNT المقتصر على الـ click فقط
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%d %b') as click_date_formatted, 
                    DATE(created_at) as raw_date, 
                    SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as daily_views,
                    SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as daily_clicks 
                FROM campaign_events 
                WHERE property_id = $property_id 
                AND campaign_id = $campaign_id
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at) 
                ORDER BY raw_date ASC";

        $result = $conn->query($sql);

        $chart_data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $chart_data[] = [
                    // إرسال التاريخ المنسق ليظهر مباشرة في كارت التفاصيل عند الضغط عليه في التطبيق
                    "date" => $row['click_date_formatted'],
                    "clicks" => intval($row['daily_clicks']),
                    "views" => intval($row['daily_views']) // إضافة عدد المشاهدات لكل تاريخ لمنع ظهور null
                ];
            }

            // في حال عدم وجود بيانات للنقرات في آخر 7 أيام، نرسل مصفوفة فارغة ليتعامل معها التطبيق
            echo json_encode([
                "status" => "success",
                "data" => $chart_data
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "status" => "error", 
                "message" => "فشل جلب بيانات المخطط البياني",
                "sql_error" => $conn->error
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => "لا توجد حملة إعلانية مسجلة لهذا العقار"
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "property_id is required"
    ]);
}

$conn->close();
?>