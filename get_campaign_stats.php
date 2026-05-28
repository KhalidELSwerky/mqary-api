<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

if (isset($_GET['property_id'])) {
    $property_id = intval($_GET['property_id']);

    // جلب بيانات الحملة النشطة أو آخر حملة مكتملة لهذا العقار - تم إضافة الحقول الجديدة للاستعلام
    $sql = "SELECT id, total_budget, spent_amount, remaining_budget, refunded_amount, target_reach, current_reach, status, start_date, clicks_count 
            FROM ad_campaigns 
            WHERE property_id = $property_id 
            ORDER BY id DESC LIMIT 1";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $campaign_id = $data['id'];
        
        // حساب النسبة المئوية للاستهلاك
        $consumption_percentage = ($data['total_budget'] > 0) 
            ? ($data['spent_amount'] / $data['total_budget']) * 100 
            : 0;

        // --- الحسابات الاحترافية الجديدة (International Standards) ---
        
        $clicks = intval($data['clicks_count']);
        $reach = intval($data['current_reach']);
        $spent = floatval($data['spent_amount']);

        // 1. حساب نسبة النقر للظهور (CTR %)
        // المعادلة العالمية: (عدد النقرات / عدد الوصول) * 100
        $ctr = ($reach > 0) ? ($clicks / $reach) * 100 : 0;

        // 2. حساب متوسط تكلفة النقرة الفعلية (Avg. CPC)
        // المعادلة العالمية: (إجمالي المبلغ المنفق / عدد النقرات)
        $avg_cpc = ($clicks > 0) ? ($spent / $clicks) : 0;

        // --- جلب توزيع المنصات بدقة من جدول الأحداث (للميزة رقم 1 في التطبيق) ---
        $platform_sql = "SELECT 
                            SUM(CASE WHEN platform = 'android' THEN 1 ELSE 0 END) as android_count,
                            SUM(CASE WHEN platform = 'ios' THEN 1 ELSE 0 END) as ios_count
                         FROM campaign_events 
                         WHERE campaign_id = $campaign_id";
        
        $platform_result = $conn->query($platform_sql);
        $platforms = $platform_result ? $platform_result->fetch_assoc() : ['android_count' => 0, 'ios_count' => 0];

        // --- الجزء الجديد: جلب توزيع الجمهور (الأعمار والنوع) لمحاكاة فيسبوك ---
        
        // 1. حساب نسبة النوع (ذكور وإناث)
        $gender_sql = "SELECT 
                        ROUND(SUM(CASE WHEN u.gender = 'male' THEN 1 ELSE 0 END) * 100 / COUNT(ce.id), 1) as men_percent,
                        ROUND(SUM(CASE WHEN u.gender = 'female' THEN 1 ELSE 0 END) * 100 / COUNT(ce.id), 1) as women_percent
                       FROM campaign_events ce
                       JOIN users u ON ce.user_id = u.id
                       WHERE ce.campaign_id = $campaign_id";
        $gender_res = $conn->query($gender_sql)->fetch_assoc();

        // 2. حساب توزيع الفئات العمرية والنوع لكل فئة
        $age_ranges = [
            ['min' => 18, 'max' => 24, 'label' => '18-24'],
            ['min' => 25, 'max' => 34, 'label' => '25-34'],
            ['min' => 35, 'max' => 44, 'label' => '35-44'],
            ['min' => 45, 'max' => 54, 'label' => '45-54'],
            ['min' => 55, 'max' => 64, 'label' => '55-64'],
            ['min' => 65, 'max' => 100, 'label' => '65+'],
        ];

        $age_demographics = [];
        $total_events = ($reach > 0) ? $reach : 1;

        foreach ($age_ranges as $range) {
            $min = $range['min'];
            $max = $range['max'];
            $label = $range['label'];

            $age_sql = "SELECT 
                            SUM(CASE WHEN u.gender = 'male' THEN 1 ELSE 0 END) as male_count,
                            SUM(CASE WHEN u.gender = 'female' THEN 1 ELSE 0 END) as female_count
                        FROM campaign_events ce
                        JOIN users u ON ce.user_id = u.id
                        WHERE ce.campaign_id = $campaign_id 
                        AND TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN $min AND $max";
            
            $age_res = $conn->query($age_sql)->fetch_assoc();
            
            $age_demographics[] = [
                "range" => $label,
                "men" => round(($age_res['male_count'] / $total_events) * 100, 1),
                "women" => round(($age_res['female_count'] / $total_events) * 100, 1)
            ];
        }

        // إرسال البيانات النهائية المتوافقة مع تحديثات Flutter الأخيرة
        echo json_encode([
            "status" => "success",
            "data" => [
                "total_budget" => floatval($data['total_budget']),
                "spent_amount" => floatval($data['spent_amount']),
                "remaining_budget" => floatval($data['remaining_budget']), // تم التعديل لجلب القيمة من العمود الجديد مباشرة
                "refunded_amount" => floatval($data['refunded_amount']),   // تم إضافة الحقل الجديد للمخرجات
                "target_reach" => intval($data['target_reach']),
                "current_reach" => intval($data['current_reach']),
                "consumption_percent" => round($consumption_percentage, 2),
                "campaign_status" => $data['status'],
                "start_date" => $data['start_date'],
                "clicks_count" => $clicks,
                "ctr" => round($ctr, 2),
                "avg_cpc" => round($avg_cpc, 2),
                // ربط بيانات المنصات بالحقول التي يطلبها التطبيق حالياً
                "android_clicks" => intval($platforms['android_count']),
                "ios_clicks" => intval($platforms['ios_count']),
                // البيانات الجديدة للديموغرافية
                "men_percent" => floatval($gender_res['men_percent'] ?? 0),
                "women_percent" => floatval($gender_res['women_percent'] ?? 0),
                "age_demographics" => $age_demographics
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "لا توجد حملة إعلانية لهذا العقار"
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(["status" => "error", "message" => "property_id is required"]);
}

$conn->close();
?>