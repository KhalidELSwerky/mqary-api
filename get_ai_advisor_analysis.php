<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

// افترضنا إن عندك دالة بتكلم Gemini في ملف helper
// لو مش عندك، دي الطريقة المباشرة
function askGemini($prompt) {
    $apiKey = "AIzaSyCwQ7x1_QqboWyGg22YBfdmVPJO4z9e6EI"; // حط مفتاحك هنا
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['candidates'][0]['content']['parts'][0]['text'] ?? "عذراً، المستشار مشغول حالياً، حاول مرة أخرى.";
}

if (isset($_GET['property_id'])) {
    $p_id = intval($_GET['property_id']);
    
    // --- [إضافة: استقبال معرف المستخدم والمحادثة]
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $conv_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
    
    // جلب بيانات الجودة
    $sql = "SELECT * FROM property_quality_scores WHERE property_id = $p_id";
    $res = $conn->query($sql);
    
    if ($res->num_rows > 0) {
        $data = $res->fetch_assoc();
        $total = $data['total_score'];
        $raw_json = $data['raw_data_json']; // اللي فيه أسماء المرافق

        // بناء البرومبت (الرسالة للذكاء الاصطناعي)
        $prompt = "أنت مستشار عقاري خبير في مصر. حلل بيانات عقار تقييم منطقته هو $total/100. 
                   إليك المرافق القريبة منه بتنسيق JSON: $raw_json.
                   اكتب تقريراً قصيراً وجذاباً (بلهجة مصرية عامية مهذبة) يوضح للمشتري مميزات السكن هنا، 
                   وعيوب المنطقة إن وجدت (مثل الزحام أو نقص نوع معين من الخدمات). 
                   ركز على المسافات المذكورة في البيانات.";

        $analysis = askGemini($prompt);

        // --- [إضافة: حفظ السؤال والرد في قاعدة البيانات] ---
        if ($user_id > 0) {
            // 1. التأكد من وجود محادثة أو إنشاء واحدة لتحليل العقار
            if ($conv_id <= 0) {
                $title = "تحليل عقار رقم: " . $p_id;
                $stmt_conv = $conn->prepare("INSERT INTO ai_conversations (user_id, title) VALUES (?, ?)");
                $stmt_conv->bind_param("is", $user_id, $title);
                $stmt_conv->execute();
                $conv_id = $stmt_conv->insert_id;
                $stmt_conv->close();
            }

            // 2. حفظ طلب التحليل (كأنه سؤال من المستخدم)
            $user_msg_text = "ممكن تحلل لي جودة منطقة العقار رقم " . $p_id;
            $stmt_msg = $conn->prepare("INSERT INTO ai_messages (conversation_id, message_text, is_user) VALUES (?, ?, 1)");
            $stmt_msg->bind_param("is", $conv_id, $user_msg_text);
            $stmt_msg->execute();
            $stmt_msg->close();

            // 3. حفظ رد الذكاء الاصطناعي (التحليل)
            $stmt_reply = $conn->prepare("INSERT INTO ai_messages (conversation_id, message_text, is_user) VALUES (?, ?, 0)");
            $stmt_reply->bind_param("is", $conv_id, $analysis);
            $stmt_reply->execute();
            $stmt_reply->close();
        }

        echo json_encode([
            "status" => "success", 
            "conversation_id" => $conv_id,
            "analysis" => $analysis
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["status" => "error", "message" => "لا توجد بيانات جودة لهذا العقار"]);
    }
}
?>