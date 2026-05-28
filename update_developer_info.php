<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;
    
    if (empty($user_id)) {
        echo json_encode(["status" => "error", "message" => "User ID missing"]);
        exit();
    }

    try {
        // التحقق أولاً: هل للمطور سجل في جدول developers؟ باستخدام mysqli
        $checkSql = "SELECT id FROM developers WHERE user_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $exists = $checkResult->fetch_assoc();

        // 1. معالجة البيانات النصية (إذا وجدت)
        if (isset($_POST['full_name'])) {
            $name = $_POST['full_name']; // سيتم تخزينه في عمود name
            $phones = $_POST['phone'] ?? ''; // سيتم تخزينه في عمود phones
            $whatsapp = $_POST['whatsapp'] ?? '';
            $bio = $_POST['bio'] ?? '';
            // الخانات الجديدة
            $location_url = $_POST['location_url'] ?? '';
            $website = $_POST['website'] ?? '';
            $facebook = $_POST['facebook'] ?? '';
            $instagram = $_POST['instagram'] ?? '';
            $youtube_video = $_POST['youtube_video'] ?? '';

            if ($exists) {
                // تحديث البيانات الموجودة باستخدام mysqli مع الخانات الجديدة
                $sql = "UPDATE developers SET 
                        name = ?, 
                        phones = ?, 
                        whatsapp = ?, 
                        bio = ?,
                        location_url = ?,
                        website = ?,
                        facebook = ?,
                        instagram = ?,
                        youtube_video = ?
                        WHERE user_id = ?";
                
                $stmt = $conn->prepare($sql);
                // sssssssssi تعني 9 نصوص و 1 رقم صحيح (user_id)
                $stmt->bind_param("sssssssssi", 
                    $name, 
                    $phones, 
                    $whatsapp, 
                    $bio, 
                    $location_url, 
                    $website, 
                    $facebook, 
                    $instagram, 
                    $youtube_video, 
                    $user_id
                );
                $stmt->execute();
            } else {
                // إنشاء سجل جديد للمطور لأول مرة باستخدام mysqli مع الخانات الجديدة
                $sql = "INSERT INTO developers (user_id, name, phones, whatsapp, bio, location_url, website, facebook, instagram, youtube_video) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                // isssssssss تعني رقم صحيح و 9 نصوص
                $stmt->bind_param("isssssssss", 
                    $user_id, 
                    $name, 
                    $phones, 
                    $whatsapp, 
                    $bio, 
                    $location_url, 
                    $website, 
                    $facebook, 
                    $instagram, 
                    $youtube_video
                );
                $stmt->execute();
            }
        }

        // 2. معالجة رفع الصور (اللوجو)
        if (isset($_FILES['image']) && isset($_POST['type'])) {
            $type = $_POST['type']; // 'profile' تعني اللوجو الخاص بالمطور
            $target_dir = "uploads/developer_profile/"; // نفس المسار العام لسهولة العرض
            
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            // تسمية الصورة باسم فريد للمطور
            $file_name = "dev_logo_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // التحديث في عمود logo بجدول developers باستخدام mysqli
                if ($exists) {
                    $sqlImg = "UPDATE developers SET logo = ? WHERE user_id = ?";
                    $stmtImg = $conn->prepare($sqlImg);
                    $stmtImg->bind_param("si", $file_name, $user_id);
                    $stmtImg->execute();
                } else {
                    // لو رفع صورة قبل ما يملأ البيانات النصية
                    $sqlImg = "INSERT INTO developers (user_id, logo, name) VALUES (?, ?, 'شركة جديدة')";
                    $stmtImg = $conn->prepare($sqlImg);
                    $stmtImg->bind_param("is", $user_id, $file_name);
                    $stmtImg->execute();
                }
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => "Developer profile updated successfully"
        ]);

    } catch (Exception $e) {
        // استخدام Exception عامة لأننا بنستخدم mysqli
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "POST method required"]);
}
?>