<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once 'db_config.php'; 

$suggestions = array();

$sql = "SELECT suggestion_text FROM ai_suggestions WHERE is_active = 1 ORDER BY RAND() LIMIT 3";
$result = mysqli_query($conn, $sql);

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $suggestions[] = $row['suggestion_text'];
        }
        
        // إرسال الاستجابة بنجاح
        echo json_encode(array(
            "status" => "success",
            "data" => $suggestions
        ));
    } else {
        // في حال كان الجدول فارغاً، نرسل اقتراحات افتراضية بدلاً من مصفوفة فارغة
        echo json_encode(array(
            "status" => "success",
            "data" => array("أرخص شقق للبيع", "عقارات بالتقسيط", "أسعار اليوم", "أفضل استثمار")
        ));
    }
} else {
    // في حال حدوث خطأ في الاستعلام
    echo json_encode(array(
        "status" => "error",
        "message" => mysqli_error($conn)
    ));
}
mysqli_close($conn);
?>