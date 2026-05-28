<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

$stats = [];

// 1. إجمالي المستخدمين حسب الأدوار
$user_sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$user_res = $conn->query($user_sql);
while($row = $user_res->fetch_assoc()) {
    $stats['users_by_role'][$row['role']] = $row['count'];
}

// 2. إجمالي الخطط المالية الفعالة
$plan_count = $conn->query("SELECT COUNT(*) as total FROM financial_plans")->fetch_assoc();
$stats['total_financial_plans'] = $plan_count['total'];

// 3. إجمالي طلبات التواصل (التي تحقق منها أرباح Leads)
$leads_count = $conn->query("SELECT COUNT(*) as total FROM leads_requests")->fetch_assoc();
$stats['total_leads_generated'] = $leads_count['total'];

// 4. أكثر المناطق (المحافظات) طلباً للتحليل التسويقي
$geo_sql = "SELECT governorate, COUNT(*) as interest_count FROM users GROUP BY governorate ORDER BY interest_count DESC LIMIT 5";
$geo_res = $conn->query($geo_sql);
$stats['top_regions'] = [];
while($row = $geo_res->fetch_assoc()) {
    $stats['top_regions'][] = $row;
}

// 5. إجمالي العقارات المعروضة
$prop_count = $conn->query("SELECT COUNT(*) as total FROM properties")->fetch_assoc();
$stats['total_properties'] = $prop_count['total'];

echo json_encode(["status" => "success", "admin_dashboard" => $stats]);

$conn->close();
?>