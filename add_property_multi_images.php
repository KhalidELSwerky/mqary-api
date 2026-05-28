<?php

// 1. منع ظهور الأخطاء للمستخدم نهائياً وتحويلها لملف debug_log.txt
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
ini_set('error_log', 'debug_log.txt'); 
error_reporting(E_ALL);

// 2. تفعيل حاجز المخرجات (Output Buffering) لحبس أي تحذيرات مفاجئة
ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'db_config.php';
require_once 'fcm_helper.php'; // استدعاء المساعد

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['broker_id']) && isset($data['images'])) {
    
    // --- [نظام الرادار الذكي المطور] ---
    $title_to_check = $data['title'];
    $desc_to_check = $data['description'];
    $full_text = $title_to_check . " " . $desc_to_check;

    // أ- جلب الكلمات المحظورة من قاعدة البيانات
    $forbidden_words = [];
    $words_res = $conn->query("SELECT word FROM forbidden_words");
    if ($words_res) {
        while ($row = $words_res->fetch_assoc()) {
            $forbidden_words[] = $row['word'];
        }
    }

    // ب- رادار الكلمات المحظورة (ديناميكي)
    foreach ($forbidden_words as $word) {
        if (mb_strpos($full_text, $word) !== false) {
            ob_clean();
            echo json_encode([
                "status" => "error", 
                "message" => "مرفوض: تم اكتشاف كلمة غير مسموح بها وهى ($word). برجاء الالتزام بسياسة النشر."
            ]);
            exit;
        }
    }

    // ج- رادار أرقام الهواتف المتطور (يصطاد الأرقام حتى لو بينهم مسافات أو رموز)
    // النمط يبحث عن أي 7 أرقام أو أكثر يفصل بينهم مسافات أو نقاط أو شرط
    if (preg_match('/[0-9٠-٩][\s\-\.\_]*[0-9٠-٩][\s\-\.\_]*[0-9٠-٩]{7,}/u', $full_text, $matches)) {
        ob_clean();
        echo json_encode([
            "status" => "error", 
            "message" => "مرفوض: تم اكتشاف محاولة وضع رقم هاتف ($matches[0]) في الوصف. التواصل يجب أن يكون عبر التطبيق فقط."
        ]);
        exit;
    }
    // --- [نهاية نظام الرادار] ---

    $broker_id = intval($data['broker_id']);
    $title = $conn->real_escape_string($data['title']);
    $price = $data['price'];
    $gov = $conn->real_escape_string($data['governorate']); 
    $city = $conn->real_escape_string($data['city']);
    $phone = $conn->real_escape_string($data['phone']);
    $rooms = intval($data['rooms']);
    $baths = intval($data['bathrooms']);
    $area = intval($data['area_sqm']);
    
    $desc = $conn->real_escape_string($data['description']);
    
    $lat = isset($data['latitude']) ? $data['latitude'] : 0;
    $lng = isset($data['longitude']) ? $data['longitude'] : 0;
    $floor = isset($data['floor_number']) ? intval($data['floor_number']) : 0;
    $is_featured = isset($data['is_featured']) ? intval($data['is_featured']) : 0;
    $priority = isset($data['priority_level']) ? intval($data['priority_level']) : 0;
    $net_area = isset($data['net_area']) ? $data['net_area'] : null;
    $finishing = isset($data['finishing_type']) ? $conn->real_escape_string($data['finishing_type']) : null;
    $view = isset($data['view_direction']) ? $conn->real_escape_string($data['view_direction']) : null;
    $elevator = isset($data['has_elevator']) ? intval($data['has_elevator']) : 0;
    $parking = isset($data['has_parking']) ? intval($data['has_parking']) : 0;
    $category = isset($data['category']) ? $conn->real_escape_string($data['category']) : 'شقة';

    $sql = "INSERT INTO properties (
                broker_id, title, description, price, governorate, city, 
                phone, rooms, bathrooms, area_sqm, latitude, longitude, category ,
                floor_number, is_featured, priority_level, net_area, 
                finishing_type, view_direction, has_elevator, has_parking
            ) 
            VALUES (
                $broker_id, '$title', '$desc', '$price', '$gov', '$city', 
                '$phone', $rooms, $baths, $area, $lat, $lng, '$category' ,
                $floor, $is_featured, $priority, " . ($net_area !== null ? "'$net_area'" : "NULL") . ", 
                " . ($finishing !== null ? "'$finishing'" : "NULL") . ", " . ($view !== null ? "'$view'" : "NULL") . ", 
                $elevator, $parking
            )";

    if ($conn->query($sql)) {
        $property_id = $conn->insert_id;
        
        // --- [بدء عملية حساب مؤشر جودة الحياة - صامتة] ---
        if ($lat != 0 && $lng != 0) {
            try {
                calculateNeighborhoodScore($conn, $property_id, $lat, $lng);
            } catch (Exception $e) {
                error_log("Neighborhood Score Error: " . $e->getMessage());
            }
        }

        $check_vip = $conn->query("SELECT is_vip, full_name FROM users WHERE id = $broker_id");
        $user_data = $check_vip->fetch_assoc();
        $broker_name = $user_data['full_name'] ?? "عقارات";
        
        // --- [إشعار المستخدمين المهتمين بنفس المحافظة] ---
        if ($user_data && $user_data['is_vip'] == 1) {
            $notify_sql = "SELECT users.id, fcm_token FROM users 
                           JOIN financial_plans ON users.id = financial_plans.user_id 
                           WHERE users.governorate = '$gov' AND fcm_token IS NOT NULL AND users.role = 'user'";
            
            $notify_res = $conn->query($notify_sql);
            if ($notify_res && $notify_res->num_rows > 0) {
                $msg_title = "فرصة عقارية جديدة في $gov! 🏠";
                $msg_body = "تم إضافة: $title بسعر $price ج.م. ادخل شوف التفاصيل!";
                
                while ($row = $notify_res->fetch_assoc()) {
                    @sendFCMNotification($row['fcm_token'], $msg_title, $msg_body, ["property_id" => $property_id]);
                    $u_id = $row['id'];
                    $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($u_id, '$msg_title', '$msg_body', 'system')");
                }
            }
        }

        // --- [إرسال إشعار للمتابعين] ---
        $followers_sql = "SELECT u.id, u.fcm_token 
                          FROM follows f 
                          JOIN users u ON f.user_id = u.id 
                          WHERE f.broker_id = $broker_id AND u.fcm_token IS NOT NULL";
        
        $followers_res = $conn->query($followers_sql);
        if ($followers_res && $followers_res->num_rows > 0) {
            $f_title = "عقار جديد من $broker_name الذي تتابعه! 🔔";
            $f_body = "نشر $broker_name عقاراً جديداً: $title. تفقده الآن!";
            
            while ($f_row = $followers_res->fetch_assoc()) {
                @sendFCMNotification($f_row['fcm_token'], $f_title, $f_body, ["property_id" => $property_id]);
                $f_u_id = $f_row['id'];
                $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($f_u_id, '$f_title', '$f_body', 'system')");
            }
        }

        // --- [منطق المقارنة المدمج - Matchmaking] ---
        $match_sql = "SELECT u.id, u.fcm_token, u.full_name 
                      FROM user_interests ui
                      JOIN users u ON ui.user_id = u.id
                      WHERE ui.city = ? AND ui.category = ? AND ui.max_price >= ? AND ui.governorate = ?";

        if ($match_stmt = $conn->prepare($match_sql)) {
            $match_stmt->bind_param("ssds", $city, $category, $price, $gov);
            $match_stmt->execute();
            $match_result = $match_stmt->get_result();

            if ($match_result->num_rows > 0) {
                while ($match_row = $match_result->fetch_assoc()) {
                    if (!empty($match_row['fcm_token'])) {
                        $m_title = "يا " . $match_row['full_name'] . "، صيدتك وصلت! 🎯";
                        $m_body = "لقينا العقار اللي بتدور عليه في $city بسعر $price ج.م.";
                        @sendFCMNotification($match_row['fcm_token'], $m_title, $m_body, ["property_id" => $property_id]);
                        $m_u_id = $match_row['id'];
                        $conn->query("INSERT INTO notifications (user_id, title, message, type, property_id) VALUES ($m_u_id, '$m_title', '$m_body', 'system' , '$property_id')");
                    }
                }
            }
            $match_stmt->close();
        }

        // --- [رفع الصور والعلامة المائية] ---
        $images = $data['images']; 
        $upload_dir = "uploads/";
        $watermark_path = "assets/mqary_watermark.png"; 

        foreach ($images as $base64_string) {
            $unique_name = "IMG_" . time() . "_" . uniqid() . ".jpg";
            $file_path = $upload_dir . $unique_name;

            if (file_put_contents($file_path, base64_decode($base64_string))) {
                if (file_exists($watermark_path)) {
                    $image = @imagecreatefromjpeg($file_path);
                    $watermark = @imagecreatefrompng($watermark_path);
                    
                    if ($image && $watermark) {
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                        $img_w = imagesx($image);
                        $img_h = imagesy($image);
                        $wt_w = imagesx($watermark);
                        $wt_h = imagesy($watermark);

                        $new_wt_w = $img_w * 0.85; 
                        $new_wt_h = ($wt_h / $wt_w) * $new_wt_w;
                        $dest_x = ($img_w - $new_wt_w) / 2;
                        $dest_y = ($img_h - $new_wt_h) / 2;

                        imagecopyresampled($image, $watermark, $dest_x, $dest_y, 0, 0, $new_wt_w, $new_wt_h, $wt_w, $wt_h);
                        imagejpeg($image, $file_path, 90);
                        imagedestroy($image);
                        imagedestroy($watermark);
                    }
                }
                $conn->query("INSERT INTO property_images (property_id, image_url) VALUES ($property_id, '$unique_name')");
            }
        }

        // تنظيف أي مخرجات غريبة وإرسال الرد النظيف
        ob_clean();
        echo json_encode(["status" => "success", "message" => "تم رفع العقار وإخطار المتابعين والمهتمين وتسجيل الإشعارات وحساب جودة الحياة"]);
    } else {
        ob_clean();
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}

/**
 * وظيفة حساب جودة الحياة باستخدام Overpass API (OSM)
 */
function calculateNeighborhoodScore($conn, $property_id, $lat, $lng) {
    $categories_res = $conn->query("SELECT * FROM service_categories");
    $scores = ['Essential' => 0, 'Education' => 0, 'Transport' => 0, 'Leisure' => 0, 'Shopping' => 0];
    $raw_elements = [];
    $total_possible_weight = 0;
    $total_earned_weight = 0;

    while ($cat = $categories_res->fetch_assoc()) {
        $tags = explode(',', $cat['osm_tags']);
        $cat_name = $cat['category_name'];
        $weight = $cat['weight'];
        $total_possible_weight += ($weight * 5);

        foreach ($tags as $tag) {
            list($key, $value) = explode('=', $tag);
            $query = "[out:json];node(around:1500,$lat,$lng)[\"$key\"=\"$value\"];out body;";
            $url = "https://overpass-api.de/api/interpreter?data=" . urlencode($query);
            
            $ctx = stream_context_create(['http' => ['timeout' => 15]]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response) {
                $osm_data = json_decode($response, true);
                if (isset($osm_data['elements']) && count($osm_data['elements']) > 0) {
                    $count = count($osm_data['elements']);
                    $points = min($count, 5); 
                    $scores[$cat_name] = ($points / 5) * 10;
                    $total_earned_weight += ($points * $weight);
                    $raw_elements[$cat_name] = array_slice($osm_data['elements'], 0, 3);
                }
            }
        }
    }

    $final_score = ($total_possible_weight > 0) ? ($total_earned_weight / $total_possible_weight) * 100 : 0;
    $json_raw = $conn->real_escape_string(json_encode($raw_elements));

    $conn->query("INSERT INTO property_quality_scores 
        (property_id, total_score, education_score, health_score, transport_score, leisure_score, raw_data_json) 
        VALUES ($property_id, $final_score, " . $scores['Education'] . ", " . $scores['Essential'] . ", 
        " . $scores['Transport'] . ", " . $scores['Leisure'] . ", '$json_raw')");
}
 
ob_end_flush();
?>


