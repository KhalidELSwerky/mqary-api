<?php
header('Content-Type: application/json');
include_once 'db_config.php';

// --- بداية منطق التنظيف الذاتي لحماية مساحة السيرفر ---
// جلب أسماء الملفات التي انتهت صلاحيتها (أقدم من 24 ساعة) قبل حذف سجلاتها
$cleanup_query = "SELECT media_url FROM stories WHERE created_at < NOW() - INTERVAL 1 DAY";
$cleanup_result = mysqli_query($conn, $cleanup_query);

if ($cleanup_result) {
    while ($old_story = mysqli_fetch_assoc($cleanup_result)) {
        $file_to_delete = "uploads/stories/" . $old_story['media_url'];
        // حذف الملف الفيزيائي من المجلد
        if (file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }
    }
    // حذف السجلات نهائياً من قاعدة البيانات لتخفيف الجداول
    mysqli_query($conn, "DELETE FROM stories WHERE created_at < NOW() - INTERVAL 1 DAY");
}
// --- نهاية منطق التنظيف الذاتي ---

// التأكد من وجود user_id
if (!isset($_GET['user_id'])) {
    echo json_encode(array("status" => "error", "message" => "Missing user_id"));
    exit;
}

$user_id = mysqli_real_escape_string($conn, $_GET['user_id']);

/**
 * الاستعلام الاحترافي:
 * تم إضافة حقل is_liked_by_me للتأكد من حالة الإعجاب الحالية للعميل
 */
$sql = "SELECT 
            u.id, 
            u.full_name, 
            u.profile_image,
            (
                SELECT CONCAT('[', GROUP_CONCAT(
                    JSON_OBJECT(
                        'id', s.id,
                        'media_url', s.media_url,
                        'media_type', s.media_type,
                        'caption', s.caption,
                        'created_at', s.created_at,
                        'views_count', (SELECT COUNT(*) FROM story_views WHERE story_id = s.id),
                        'is_viewed_by_me', IF((SELECT COUNT(*) FROM story_views WHERE story_id = s.id AND viewer_id = '$user_id') > 0, 1, 0),
                        'is_liked_by_me', IF((SELECT COUNT(*) FROM story_likes WHERE story_id = s.id AND user_id = '$user_id') > 0, 1, 0)
                    )
                ), ']')
                FROM stories s 
                WHERE s.user_id = u.id 
                AND s.created_at > NOW() - INTERVAL 1 DAY
            ) as stories
        FROM users u
        WHERE u.id = '$user_id' OR u.id IN (SELECT broker_id FROM follows WHERE user_id = '$user_id')
        GROUP BY u.id
        HAVING stories IS NOT NULL
        ORDER BY (u.id = '$user_id') DESC";

$result = mysqli_query($conn, $sql);
$data_output = array();

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        // تحويل نص الـ JSON اللي جاي من القاعدة إلى Array حقيقي في PHP
        $row['stories'] = json_decode($row['stories']);
        $data_output[] = $row;
    }
    echo json_encode(array("status" => "success", "data" => $data_output));
} else {
    echo json_encode(array("status" => "error", "message" => mysqli_error($conn)));
}

mysqli_close($conn);

?>