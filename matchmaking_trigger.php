<?php
// matchmaking_trigger.php [cite: 2026-01-29]
ob_start();


// دالة توليد Access Token من جوجل [cite: 2026-01-29]
function getGoogleAccessToken($jsonFilePath) {
    if (!file_exists($jsonFilePath)) return null;
    
    $json = json_decode(file_get_contents($jsonFilePath), true);
    $now = time();
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $payload = json_encode([
        'iss' => $json['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    // التوقيع باستخدام OpenSSL [cite: 2026-03-03]
    if (!openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $json['private_key'], 'sha256')) return null;
    
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// دالة فحص المطابقات وإرسال الإشعارات [cite: 2026-01-29]
function checkAndNotifyMatches($conn, $property_id, $city, $price, $category, $governorate) { 
    $sql = "SELECT ui.user_id, u.fcm_token, u.full_name 
            FROM user_interests ui
            JOIN users u ON ui.user_id = u.id
            WHERE ui.city = ? AND ui.category = ? AND ui.max_price >= ? AND ui.governorate = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssds", $city, $category, $price, $governorate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $accessToken = getGoogleAccessToken('firebase-adminsdk.json');
        if ($accessToken) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['fcm_token'])) {
                    sendV1Notification($accessToken, $row['fcm_token'], $row['full_name'], $property_id, $city);
                }
            }
        }
    }
    $stmt->close();
}

// دالة إرسال الإشعار عبر بروتوكول V1 (صامتة تماماً) [cite: 2026-01-29]
function sendV1Notification($accessToken, $fcmToken, $userName, $propId, $city) {
    $projectId = "mqary-c764e"; // معرف المشروع الخاص بك [cite: 2026-01-29]
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    $message = [
        "message" => [
            "token" => $fcmToken,
            "notification" => [
                "title" => "يا $userName ، صيدتك وصلت! 🎯",
                "body" => "لقينا العقار اللي بتدور عليه في $city. متاح الآن للمعاينة."
            ],
            "data" => [
                "property_id" => (string)$propId,
                "click_action" => "FLUTTER_NOTIFICATION_CLICK"
            ]
        ]
    ];

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_exec($ch); // التنفيذ بصمت [cite: 2026-03-03]
    curl_close($ch);
}
?>