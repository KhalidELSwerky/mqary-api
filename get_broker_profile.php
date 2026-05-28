<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

$broker_id = isset($_GET['broker_id']) ? intval($_GET['broker_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($broker_id > 0) {
    // جلب بيانات الوسيط
    $sql_user = "SELECT id, full_name, role, is_verified, profile_image, cover_image FROM users WHERE id = $broker_id LIMIT 1";
    $res_user = $conn->query($sql_user);
    
    if ($res_user && $res_user->num_rows > 0) {
        $userData = $res_user->fetch_assoc();
        
        // التعديل هنا: جلب كل الصور لكل عقار في حقل واحد اسمه images_json
        $sql_props = "SELECT p.*, 
                      (SELECT GROUP_CONCAT(pi.image_url) FROM property_images pi WHERE pi.property_id = p.id) as images_json 
                      FROM properties p 
                      WHERE p.broker_id = $broker_id 
                      ORDER BY p.id DESC";
        
        $res_props = $conn->query($sql_props);
        $properties = [];
        if ($res_props) {
            while($p = $res_props->fetch_assoc()) { 
                // --- التعديل المطلوب لضمان وصول الأنواع بشكل صحيح لـ Flutter ---
                $p['id'] = intval($p['id']);
                $p['rooms'] = intval($p['rooms']);
                $p['price'] = floatval($p['price']);
                if (isset($p['is_featured'])) {
                    $p['is_featured'] = intval($p['is_featured']);
                }
                // -----------------------------------------------------------

                // تحويل سلسلة الصور (img1.jpg,img2.jpg) إلى مصفوفة حقيقية
                if (!empty($p['images_json'])) {
                    $p['images_json'] = explode(',', $p['images_json']);
                    $p['main_image'] = $p['images_json'][0]; // أول صورة تبقى هي الصورة الرئيسية
                } else {
                    $p['images_json'] = [];
                    $p['main_image'] = "";
                }
                $properties[] = $p; 
            }
        }

        // جلب حالة المتابعة
        $is_following = ($conn->query("SELECT id FROM follows WHERE user_id = $user_id AND broker_id = $broker_id")->num_rows > 0);
        $total_followers = $conn->query("SELECT COUNT(id) as total FROM follows WHERE broker_id = $broker_id")->fetch_assoc()['total'];

        // --- جلب الحالات (Stories) مع حساب عدد المشاهدات من جدول story_views ---
        $sql_stories = "SELECT s.*, 
                        (SELECT COUNT(sv.id) FROM story_views sv WHERE sv.story_id = s.id) as views_count 
                        FROM stories s 
                        WHERE s.user_id = $broker_id 
                        AND s.created_at >= NOW() - INTERVAL 24 HOUR 
                        ORDER BY s.created_at DESC";
        $res_stories = $conn->query($sql_stories);
        $stories = [];
        if ($res_stories) {
            while($s = $res_stories->fetch_assoc()) {
                $s['id'] = intval($s['id']);
                $s['user_id'] = intval($s['user_id']);
                $s['views_count'] = intval($s['views_count'] ?? 0);
                $stories[] = $s;
            }
        }
        // ------------------------------------------

        echo json_encode([
            "status" => "success",
            "data" => [
                "full_name" => $userData['full_name'],
                "role" => $userData['role'],
                "is_verified" => intval($userData['is_verified']),
                "is_following" => $is_following,
                "followers_count" => intval($total_followers),
                "profile_image" => $userData['profile_image'],
                "cover_image" => $userData['cover_image'],
                "properties" => $properties,
                "stories" => $stories
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
}
?>