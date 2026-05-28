<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if (isset($_GET['p1']) && isset($_GET['p2'])) {
    $p1 = intval($_GET['p1']);
    $p2 = intval($_GET['p2']);
    
    // الرابط اللي هيبعته المستخدم
    $share_link = "https://sha2tak.app/compare?p1=$p1&p2=$p2";
    
    echo json_encode(["status" => "success", "link" => $share_link], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["status" => "error", "message" => "بيانات ناقصة"]);
}
?>