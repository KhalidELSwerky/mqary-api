<?php
include "db_config.php";

if (isset($_POST['campaign_id'])) {
    $campaign_id = intval($_POST['campaign_id']);

    // 1. تحديث عدد المشاهدات الحالية في جدول الحملات
    $update_view = "UPDATE ad_campaigns 
                    SET current_reach = current_reach + 1 
                    WHERE id = '$campaign_id' AND status = 'active'";
    
    if ($conn->query($update_view)) {
        
        // 2. التحقق هل وصلت الحملة لهدفها؟
        $check_status = "SELECT current_reach, target_reach FROM ad_campaigns WHERE id = '$campaign_id'";
        $result = $conn->query($check_status);
        $row = $result->fetch_assoc();

        if ($row['current_reach'] >= $row['target_reach']) {
            // تحويل الحالة لمكتملة لإيقاف ظهور الإعلان
            $conn->query("UPDATE ad_campaigns SET status = 'completed' WHERE id = '$campaign_id'");
        }

        echo json_encode(["status" => "success", "message" => "Impression tracked"]);
    }
}
$conn->close();
?>