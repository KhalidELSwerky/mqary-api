<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include "db_config.php";
// ضبط الترميز للعربية
$conn->set_charset("utf8");

// التأكد من وجود البيانات المطلوبة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $media_type = $_POST['media_type'] ?? 'image';
    $caption = $_POST['caption'] ?? '';

    if ($user_id && isset($_FILES['media'])) {
        $target_dir = "uploads/stories/";
        
        // إنشاء المجلد لو مش موجود
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES["media"]["name"], PATHINFO_EXTENSION);
        $file_name = uniqid() . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["media"]["tmp_name"], $target_file)) {
            
            // تجهيز الاستعلام (لحماية البيانات من SQL Injection)
            $query = "INSERT INTO stories (user_id, media_url, media_type, caption, created_at) 
                      VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isss", $user_id, $file_name, $media_type, $caption);

            if ($stmt->execute()) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Story uploaded successfully",
                    "data" => [
                        "media_url" => $file_name,
                        "created_at" => date("Y-m-d H:i:s")
                    ]
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to save story: " . $stmt->error]);
            }
            
            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to upload file"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Incomplete data (user_id or media missing)"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}

$conn->close();
?>