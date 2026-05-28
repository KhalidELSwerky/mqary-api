<?php
// 1. تفعيل إظهار الأخطاء للاختبار
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'db_config.php';

// المفتاح الخاص بك الذي يعمل حالياً
$gemini_api_key = "AIzaSyCwQ7x1_QqboWyGg22YBfdmVPJO4z9e6EI";

// --- [تعديل: استقبال البيانات من Flutter أو المتصفح GET/POST]
$input_raw = file_get_contents("php://input");
$input_data = json_decode($input_raw, true);

// يحاول القراءة من JSON أولاً (Flutter)، وإذا لم يجدها يقرأ من الـ URL (المتصفح)
$user_id = isset($input_data['user_id']) ? intval($input_data['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
$user_query = isset($input_data['message']) ? $input_data['message'] : (isset($_GET['message']) ? $_GET['message'] : "");

// --- [إضافة: استقبال معرف المحادثة للربط بالجداول] --- [cite: 2026-03-04]
$conv_id = isset($input_data['conversation_id']) ? intval($input_data['conversation_id']) : 0;

if ($user_id > 0 && !empty($user_query)) {

    // --- [خطوة: إدارة المحادثة في قاعدة البيانات] --- [cite: 2026-03-04]
    // 1. إذا لم يوجد ID محادثة، ننشئ محادثة جديدة
    if ($conv_id <= 0) {
        $title = mb_substr($user_query, 0, 40, 'utf-8') . "...";
        $stmt_conv = $conn->prepare("INSERT INTO ai_conversations (user_id, title) VALUES (?, ?)");
        $stmt_conv->bind_param("is", $user_id, $title);
        $stmt_conv->execute();
        $conv_id = $stmt_conv->insert_id;
        $stmt_conv->close();
    }

    // 2. حفظ رسالة المستخدم الحالية في جدول الرسائل
    $stmt_msg = $conn->prepare("INSERT INTO ai_messages (conversation_id, message_text, is_user) VALUES (?, ?, 1)");
    $stmt_msg->bind_param("is", $conv_id, $user_query);
    $stmt_msg->execute();
    $stmt_msg->close();

    $user_name = "يا هندسة"; // قيمة افتراضية في حال عدم العثور على الاسم
    $sql_user = "SELECT full_name FROM users WHERE id = $user_id LIMIT 1";
    $res_user = $conn->query($sql_user);
    if ($res_user && $res_user->num_rows > 0) {
        $user_row = $res_user->fetch_assoc();
        $user_name = $user_row['full_name'];
    }

    // 1. جلب بيانات الخطة المالية للمستخدم
    $sql_plan = "SELECT * FROM financial_plans WHERE user_id = $user_id LIMIT 1";
    $res_plan = $conn->query($sql_plan);

    if (!$res_plan) {
        die(json_encode(["status" => "error", "message" => "خطأ في جدول الخطة: " . $conn->error]));
    }

    $plan_data = $res_plan->fetch_assoc();

    if ($plan_data) {
        
        // --- [تعديل: حساب إجمالي المدخرات الفعلية من جدول الالتزامات] --- [cite: 2026-02-28]
        $total_actual_savings = 0;
        $sql_total_savings = "SELECT SUM(amount_saved) as total_sum FROM commitment_tracker WHERE user_id = $user_id AND is_committed = 1";
        $res_savings = $conn->query($sql_total_savings);
        if ($res_savings) {
            $savings_row = $res_savings->fetch_assoc();
            $total_actual_savings = $savings_row['total_sum'] ?? 0;
        }

        $target_price = $plan_data['target_property_price'] ?? 0; // السعر المستهدف
        $monthly_plan = $plan_data['savings_amount'] ?? 0; // الالتزام الشهري المخطط له

        // 2. جلب العقارات المقترحة بناءً على السعر المستهدف
        $sql_props = "SELECT * FROM properties 
                      ORDER BY ABS(price - $target_price) ASC LIMIT 8";
        $res_props = $conn->query($sql_props);

        $suggested_list = "";
        $properties_details = [];

        if ($res_props && $res_props->num_rows > 0) {
            while ($p = $res_props->fetch_assoc()) {
                $prop_id = $p['id'];

                $sql_images = "SELECT image_url FROM property_images WHERE property_id = $prop_id";
                $res_images = $conn->query($sql_images);
                $images = [];
                while ($img = $res_images->fetch_assoc()) {
                    $images[] = $img['image_url'];
                }

                $p['images'] = $images;
                $p['images_json'] = $images;
                $properties_details[] = $p;

                $lat = $p['latitude'] ?? 'غير متوفر';
                $lng = $p['longitude'] ?? 'غير متوفر';
$suggested_list .= "- العقار رقم [ID:{$p['id']}]: {$p['title']} في {$p['city']} - {$p['governorate']} بسعر {$p['price']} ج.م. (الإحداثيات الجغرافية: Lat: $lat, Lng: $lng)\n";            }
        } else {
            $suggested_list = "لا توجد عقارات متاحة حالياً.";
        }

        // --- [تحديث الـ Context بمهمة "الصيد الذكي" والبيانات الحقيقية] --- [cite: 2026-01-29]
        $context = "أنت 'مستشار مقري الذكي'. تعليماتك الصارمة:

1. الهوية: صممك العبقري المبرمج خالد جمال (أبو الحبيب).  أنت ابن بلد مصري أصيل.

2. الذكاء المالي الفعلي: 
   - العميل محوش فعلياً مبلغ: $total_actual_savings ج.م.
   - هدفه يشتري عقار بسعر $target_price ج.م.
   - بيوفر شهرياً مبلغ $monthly_plan ج.م.
   - مهمتك: قارن بين مدخراته الفعلية ($total_actual_savings) وهدفه. لو المبلغ لسه بعيد، شجعه يلتزم بالـ $monthly_plan بتاعته.

4. الرد على العقارات: يجب أن تنهي وصف أي عقار ترشحه بعبارة: 'تقدر تشوف تفاصيل أكتر وتتفرج عليه من هنا [ID:رقم_العقار]' حرفياً.

5. مهمة صيد الطلبات (هام جداً): إذا طلب المستخدم عقاراً بمواصفات معينة (مثل: شقة في قنا بمليون جنيه) ولم تجدها في قائمة العقارات المتاحة ($suggested_list)، قل له أنك سجلت طلبه وستنبهه فور توفره، ويجب أن تدرج في نهاية ردك هذا الكود تماماً: 
[[INTENT:{\"governorate\":\"اسم_المحافظة\",\"city\":\"اسم_المدينة\",\"max_price\":السعر_الرقمي,\"category\":\"نوع_العقار\"}]]

6. اللهجة: عامية مصرية جدعة.

اسم العميل: $user_name

قائمة العقارات المرشحة:
$suggested_list";
        // 4. طلب Gemini API (بدون تغيير الرابط أو الإصدار كما طلبت)
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $gemini_api_key;
        $payload = [
            "contents" => [
                ["parts" => [["text" => $context . "\n\nسؤال المستخدم: " . $user_query]]]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "topP" => 0.8,
                "topK" => 40
            ]
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $response_data = json_decode($response, true);
        
        // --- [صيد الأخطاء في طلب الـ API] ---
        if (curl_errno($ch)) {
            die(json_encode(["status" => "error", "message" => "Curl Error: " . curl_error($ch)]));
        }
        curl_close($ch);

        if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_message = $response_data['candidates'][0]['content']['parts'][0]['text'];

            // --- [مرحلة التنفيذ: صيد وحفظ اهتمام المستخدم] ---
            if (preg_match('/\[\[INTENT:(.*?)\]\]/', $ai_message, $matches)) {
                $intent_json = $matches[1];
                $intent_data = json_decode($intent_json, true);
                
                if ($intent_data) {
                    $gov = $intent_data['governorate'] ?? null;
                    $city = $intent_data['city'] ?? null;
                    $price = $intent_data['max_price'] ?? 0;
                    $cat = $intent_data['category'] ?? 'شقة';

                    // إدخال البيانات في جدول الاهتمامات الحقيقي [cite: 2026-01-29]
                    $stmt_hunt = $conn->prepare("INSERT INTO user_interests (user_id, governorate, city, max_price, category) VALUES (?, ?, ?, ?, ?)");
                    $stmt_hunt->bind_param("isssd", $user_id, $gov, $city, $price, $cat);
                    $stmt_hunt->execute();
                    $stmt_hunt->close();

                    // تنظيف الرسالة من كود الـ JSON قبل عرضها للمستخدم [cite: 2026-01-11]
                    $ai_message = str_replace($matches[0], "", $ai_message);
                }
            }

            // --- [إضافة: حفظ رد الـ AI في قاعدة البيانات] --- [cite: 2026-03-04]
            $props_json = json_encode($properties_details, JSON_UNESCAPED_UNICODE);
            $stmt_reply = $conn->prepare("INSERT INTO ai_messages (conversation_id, message_text, is_user, properties_json) VALUES (?, ?, 0, ?)");
            $stmt_reply->bind_param("iss", $conv_id, $ai_message, $props_json);
            $stmt_reply->execute();
            $stmt_reply->close();

            echo json_encode([
                "status" => "success",
                "conversation_id" => $conv_id, // إرجاع الـ ID لـ Flutter للاستمرار عليه
                "reply" => trim($ai_message),
                "properties_details" => $properties_details
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // في حال وجود خطأ في الـ API نفسه (مثل مفتاح منتهي الصلاحية)
            $error_info = isset($response_data['error']['message']) ? $response_data['error']['message'] : "Unknown API Error";
            echo json_encode([
                "status" => "error", 
                "reply" => "يا هندسة السيرفر مهيس شوية. السبب: " . $error_info
            ]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "لم يتم العثور على خطة ماليّة لليوزر ده ($user_id)"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "بيانات غير مكتملة. ابعت الـ user_id والـ message"]);
}
$conn->close();
?>