<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once 'db_config.php';

if (isset($_POST['property_id'])) {
    $property_id = $_POST['property_id'];

    // بدء عملية Transaction لضمان تنفيذ كل شيء أو التراجع في حال الخطأ
    $conn->begin_transaction();

    try {
        // 1. جلب أسماء ملفات الصور من جدول property_images لحذفها من السيرفر
        $img_query = "SELECT image_url FROM property_images WHERE property_id = ?";
        $stmt_img = $conn->prepare($img_query);
        $stmt_img->bind_param("i", $property_id);
        $stmt_img->execute();
        $result_img = $stmt_img->get_result();

        while ($row = $result_img->fetch_assoc()) {
            $image_name = $row['image_url'];
            $full_path = "uploads/" . $image_name; 
            if (file_exists($full_path)) {
                unlink($full_path); // حذف الملف الفيزيائي
            }
        }
        $stmt_img->close();

        // 2. أ: حذف سجلات المشاهدات (logs) من جدول ad_logs المرتبطة بحملات هذا العقار
        // تم استخدام Subquery للوصول إلى الـ campaign_id المتعلق بالـ property_id
        $delete_logs = "DELETE FROM ad_logs WHERE campaign_id IN (SELECT id FROM ad_campaigns WHERE property_id = ?)";
        $stmt_logs = $conn->prepare($delete_logs);
        $stmt_logs->bind_param("i", $property_id);
        $stmt_logs->execute();
        $stmt_logs->close();

        // 2. ب: حذف كافة الحملات الإعلانية المرتبطة بهذا العقار من جدول ad_campaigns
        $delete_campaigns = "DELETE FROM ad_campaigns WHERE property_id = ?";
        $stmt_camp = $conn->prepare($delete_campaigns);
        $stmt_camp->bind_param("i", $property_id);
        $stmt_camp->execute();
        $stmt_camp->close();

        // 3. حذف العقار من جدول properties
        // ملاحظة: سيتم حذف سجلات الصور من جدول property_images تلقائياً بسبب ON DELETE CASCADE
        $delete_query = "DELETE FROM properties WHERE id = ?";
        $stmt_del = $conn->prepare($delete_query);
        $stmt_del->bind_param("i", $property_id);
        $stmt_del->execute();

        if ($stmt_del->affected_rows > 0) {
            $conn->commit(); // تنفيذ كل العمليات أعلاه بنجاح
            echo json_encode([
                "status" => "success",
                "message" => "Property, physical images, and all related campaigns/logs handled successfully"
            ]);
        } else {
            throw new Exception("Property not found");
        }

        $stmt_del->close();

    } catch (Exception $e) {
        $conn->rollback(); // التراجع عن كل شيء في حال حدوث خطأ
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Property ID is missing"
    ]);
}

$conn->close();
?>