<?php
header('Content-Type: application/json');
include_once 'db_config.php';

// --- الجزء المضاف لدعم القراءة بالطريقتين (JSON Body و Form Data) ---
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); 
if (is_array($input)) {
    $_POST = array_merge($_POST, $input);
}
// -------------------------------------------------------------------

if (isset($_POST['story_id']) && isset($_POST['viewer_id'])) {
    $story_id = mysqli_real_escape_string($conn, $_POST['story_id']);
    $viewer_id = mysqli_real_escape_string($conn, $_POST['viewer_id']);

    // إدخال المشاهدة (استخدام INSERT IGNORE لتجنب الخطأ لو المشاهدة موجودة)
    $sql = "INSERT IGNORE INTO story_views (story_id, viewer_id) VALUES ('$story_id', '$viewer_id')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => mysqli_error($conn)));
    }
}
mysqli_close($conn);
?>