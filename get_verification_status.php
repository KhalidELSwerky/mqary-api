<?php
header('Content-Type: application/json; charset=utf-8');
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET['user_id'])) {
        echo json_encode(["status" => "error", "message" => "user_id is required"]);
        exit;
    }

    $user_id = mysqli_real_escape_string($conn, $_GET['user_id']);

    // التعديل الجوهري: استعلام بيجيب بيانات التوثيق + تاريخ آخر اشتراك نشط
    $sql = "SELECT v.admin_approval, v.admin_notes, v.created_at, v.document_number, v.request_type,
                   (SELECT s.end_date FROM subscriptions s WHERE s.subscriber_id = '$user_id' AND s.payment_status = 'active' ORDER BY s.id DESC LIMIT 1) as sub_end_date
            FROM verification_requests v 
            WHERE v.user_id = '$user_id' 
            ORDER BY v.id DESC LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        
        echo json_encode([
            "status" => "success",
            "data" => [
                "verification_status" => $data['admin_approval'],
                "admin_notes" => $data['admin_notes'],
                "submitted_at" => $data['created_at'],
                "document_number" => $data['document_number'],
                "request_type" => $data['request_type'],
                // ده المفتاح اللي فلاتر مستنياه عشان تظهر الكارت
                "subscription_end_date" => $data['sub_end_date'] ?? "" 
            ]
        ]);
    } else {
        // لو مفيش توثيق، برضه بنحاول نشوف لو ليه اشتراك قديم
        $sub_check = mysqli_query($conn, "SELECT end_date FROM subscriptions WHERE subscriber_id = '$user_id' ORDER BY id DESC LIMIT 1");
        $sub_data = mysqli_fetch_assoc($sub_check);

        echo json_encode([
            "status" => "success", // رجعناها success عشان ميهيسش في فلاتر
            "data" => [
                "verification_status" => "unsubmitted",
                "admin_notes" => "",
                "subscription_end_date" => $sub_data['end_date'] ?? ""
            ]
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}

mysqli_close($conn);
?>