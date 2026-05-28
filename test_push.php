<?php
require_once 'fcm_helper.php';

// ضع هنا توكن موبايلك الحقيقي (هاته من قاعدة البيانات من جدول users)
$testToken = "fqFaWvQwTJmpKYgfffp9_w:APA91bEJiA6o1nZjbFxaXFSsbU-bopjLYjRYk_LhKGzhWyp4UK6DY8kcWbaKO5RrqFdKB3zsMRRgeTwsFVX0f-PfDPeXwiL3EqsWwUAjRx4KwIoTGc88Now"; 

$title = "تجربة إشعار من المتصفح 🚀";
$body = "لو شفت الرسالة دي وأنت بره التطبيق، يبقى شغلك 10/10!";

$result = sendFCMNotification($testToken, $title, $body);

echo "<h1>تم إرسال الطلب لجوجل</h1>";
echo "<pre>الرد من السيرفر: " . htmlspecialchars($result) . "</pre>";
?>