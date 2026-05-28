<?php
// إعدادات الرأس للاستجابة بصيغة JSON ودعم اللغة العربية
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// تضمين ملف الاتصال بقاعدة البيانات الخاص بك (تأكد من المسار الصحيح للملف لديك)
include_once "db_config.php"; 

// التحقق من إرسال معرف المطور
if (isset($_GET['developer_id']) && !empty($_GET['developer_id'])) {
    
    $developer_id = intval($_GET['developer_id']);

    try {
        // استعلام لجلب التقييمات مع اسم المستخدم الموثق الذي قام بالتقييم
        // تم استخدام DATE_FORMAT لتنسيق التاريخ بشكل مقروء ومناسب للواجهة
        $query = "SELECT r.id, r.rating, r.comment, 
                         DATE_FORMAT(r.created_at, '%Y-%m-%d') as created_at, 
                         u.full_name 
                  FROM developer_reviews r
                  INNER JOIN users u ON r.user_id = u.id
                  WHERE r.developer_id = ?
                  ORDER BY r.id DESC";

        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            // ربط المعاملات بنظام MySQLi (i تعني integer)
            $stmt->bind_param("i", $developer_id);
            $stmt->execute();
            
            // جلب النتيجة وتخزينها في مصفوفة
            $result = $stmt->get_result();
            $reviews = [];
            
            while ($row = $result->fetch_assoc()) {
                $reviews[] = $row;
            }
            
            $stmt->close();

            // إرجاع النتيجة بنجاح للتطبيق متوافقة مع الـ FutureBuilder
            echo json_encode([
                "status" => "success",
                "data" => $reviews
            ], JSON_UNESCAPED_UNICODE);
            
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "فشل في تجهيز الاستعلام البرمجي"
            ], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        // في حالة حدوث خطأ في السيرفر أو الاستعلام
        echo json_encode([
            "status" => "error",
            "message" => "خطأ في السيرفر: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }

} else {
    // في حالة عدم إرسال المعرف المطلق في الرابط
    echo json_encode([
        "status" => "error",
        "message" => "معرف المطور (developer_id) مطلوب"
    ], JSON_UNESCAPED_UNICODE);
}
?>