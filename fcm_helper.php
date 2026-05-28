<?php

/**
 * دالة جلب Access Token من جوجل باستخدام ملف الـ JSON
 */
function getGoogleAccessToken() {
    $file_path = 'firebase-adminsdk.json'; // تأكد من وجود الملف في نفس المجلد
    if (!file_exists($file_path)) {
        return "Error: JSON file not found";
    }

    $key = json_decode(file_get_contents($file_path), true);

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'iss' => $key['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $key['private_key'], 'SHA256');
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));

    $response = curl_exec($ch);
    $data = json_decode($response, true);
    return $data['access_token'];
}

/**
 * دالة إرسال الإشعار الأساسية
 */
function sendFCMNotification($userToken, $title, $body, $extraData = []) {
    $projectId = 'mqary-c764e'; // الـ ID الخاص بك
    $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";
    
    $accessToken = getGoogleAccessToken(); 

    // بناء جسم الرسالة الأساسي
    $message = [
        'message' => [
            'token' => $userToken,
            'notification' => [
                'title' => $title,
                'body' => $body
            ]
        ]
    ];

    // إضافة البيانات الإضافية فقط إذا كانت موجودة (لحل مشكلة الـ Map)
    if (!empty($extraData) && is_array($extraData)) {
        $message['message']['data'] = array_map('strval', $extraData);
    }

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>