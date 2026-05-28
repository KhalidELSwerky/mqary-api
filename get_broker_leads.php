<?php
error_reporting(0); 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once 'db_config.php';

$broker_id = isset($_GET['broker_id']) ? intval($_GET['broker_id']) : 0;

if ($broker_id > 0) {
    try {
        // التعديل: استخدمنا LEFT JOIN للعقارات والحالات (Stories)
        // أضفنا شرط التحقق من أن الوسيط هو صاحب العقار OR صاحب الستوري
        $sql = "SELECT lr.*, 
                       lr.id as lead_id, 
                       p.title as property_title, 
                       p.price as price, 
                       s.caption as story_caption,
                       u.full_name as user_name, 
                       u.phone as phone,
                       lsr.buying_timeframe,
                       lsr.financing_type,
                       lsr.has_property_to_sell
                FROM leads_requests lr
                LEFT JOIN properties p ON lr.property_id = p.id
                LEFT JOIN stories s ON lr.story_id = s.id
                JOIN users u ON lr.user_id = u.id
                LEFT JOIN lead_survey_responses lsr ON lr.id = lsr.lead_id
                WHERE (p.broker_id = $broker_id OR s.user_id = $broker_id)
                ORDER BY lr.created_at DESC";

        $result = $conn->query($sql);
        $leads = [];

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $leads[] = $row;
            }
        }
        
        echo json_encode(["status" => "success", "data" => $leads]);
        
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Broker ID"]);
}
?>