<?php
include_once 'db_config.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(array("status" => "error", "message" => "Missing user_id"));
    exit;
}

$user_id = mysqli_real_escape_string($conn, $_GET['user_id']);

// جلب كل الحالات الخاصة بهذا المستخدم في آخر 24 ساعة
$sql = "SELECT media_url, media_type, caption, created_at 
        FROM stories 
        WHERE user_id = '$user_id' 
        AND created_at > NOW() - INTERVAL 1 DAY 
        ORDER BY created_at ASC"; // ASC عشان يبدأ من القديم للجديد

$result = mysqli_query($conn, $sql);
$stories = array();

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $stories[] = $row;
    }
    echo json_encode(array("status" => "success", "data" => $stories));
} else {
    echo json_encode(array("status" => "error", "message" => mysqli_error($conn)));
}

mysqli_close($conn);
?>