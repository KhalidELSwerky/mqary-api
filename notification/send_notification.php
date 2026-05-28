<?php
// دالة إرسال الإشعار عبر Firebase Cloud Messaging (FCM)
function sendFCMNotification($target_token, $title, $body) {
    // عنوان الخدمة في Firebase
    $url = 'https://fcm.googleapis.com/fcm/send';
    
    // ملاحظة: هذا المفتاح تحصل عليه من إعدادات مشروعك في Firebase (Cloud Messaging -> Server Key)
    $server_key = 'AAAA...Your_Server_Key_Here...'; 

    // محتوى الإشعار
    $notification = [
        'title' on' => 'FLUTTER_NOTIFICATION_CLICK'
    ];

    // هيكل الطلب
    $fields = [
        'to' => $ta=> $title,
        'body' => $body,
        'sound' => 'default',
        'badge' => '1',
        'click_actirget_token,
        'notification' => $notification,
        'priority' => 'high'
    ];

    $headers = [
        'Authorization: key=' . $server_key,
        'Content-Type: application/json'
    ];

    // إرسال الطلب باستخدام cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>