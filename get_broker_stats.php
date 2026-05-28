<?php
// منع ظهور الأخطاء كـ HTML لضمان استلام التطبيق لـ JSON فقط
error_reporting(0);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once 'db_config.php';

$broker_id = isset($_GET['broker_id']) ? intval($_GET['broker_id']) : 0;

if ($broker_id > 0) {
    try {
        // 1. عدد عقارات الوسيط
        $res1 = $conn->query("SELECT COUNT(*) as total FROM properties WHERE broker_id = $broker_id");
        $total_properties = ($res1) ? $res1->fetch_assoc()['total'] : 0;

        // 2. إجمالي طلبات التواصل - تعديل اسم الجدول إلى leads_requests
        $res2 = $conn->query("SELECT COUNT(*) as total FROM leads_requests l JOIN properties p ON l.property_id = p.id WHERE p.broker_id = $broker_id");
        $total_leads = ($res2) ? $res2->fetch_assoc()['total'] : 0;

        // 3. الطلبات بانتظار الرد - تعديل اسم الجدول إلى leads_requests
        $res3 = $conn->query("SELECT COUNT(*) as total FROM leads_requests l JOIN properties p ON l.property_id = p.id WHERE p.broker_id = $broker_id AND l.status = 'pending'");
        $pending_leads = ($res3) ? $res3->fetch_assoc()['total'] : 0;

        echo json_encode([
            "status" => "success",
            "stats" => [
                "total_properties" => (int)$total_properties,
                "total_leads" => (int)$total_leads,
                "pending_leads" => (int)$pending_leads
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Broker ID"]);
}
?>