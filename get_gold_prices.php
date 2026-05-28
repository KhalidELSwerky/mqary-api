<?php
header('Content-Type: application/json; charset=utf-8');

// ربط قاعدة البيانات
include "db_config.php";

// دالة جلب البيانات مع محاكاة متصفح حقيقي وهيدرز إضافية لتخطي الحماية
function get_isagha_direct($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        CURLOPT_HTTPHEADER     => [
            "Accept: application/json, text/plain, */*",
            "X-Requested-With: XMLHttpRequest", // بنقوله إننا بنطلب بيانات مش صفحة
            "Referer: https://market.isagha.com/prices"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// الرابط ده هو الـ API المخفي اللي بيغذي الموقع بالأسعار الحقيقية
$apiUrl = "https://market.isagha.com/api/prices"; 
$jsonData = get_isagha_direct($apiUrl);
$data = json_decode($jsonData, true);

$prices = [];

// سحب الأسعار من الـ JSON اللي راجع مباشرة (أضمن وأدق طريق)
if (isset($data['prices'])) {
    $prices['gold_24'] = (double)$data['prices']['24'];
    $prices['gold_21'] = (double)$data['prices']['21'];
    $prices['gold_18'] = (double)$data['prices']['18'];
} else {
    // محاولة أخيرة لو الـ API اتغير، بنسحب من الـ HTML بـ Pattern مطور
    $html = get_isagha_direct("https://market.isagha.com/prices");
    preg_match_all('/"price":\s*(\d+(?:\.\d+)?)/', $html, $matches);
    if (!empty($matches[1])) {
        $prices['gold_24'] = (double)$matches[1][0];
        $prices['gold_21'] = (double)$matches[1][1];
        $prices['gold_18'] = (double)$matches[1][2];
    }
}

// التأكد إن السعر منطقي (أكبر من 100 ج.م) عشان نتجنب "عبط" الأرقام الوهمية
if (!empty($prices['gold_21']) && $prices['gold_21'] > 100) {
    $prices['gold_coin'] = $prices['gold_21'] * 8; 

    // --- التخزين في الداتابيز للشارت ---
    $check = $conn->query("SELECT id FROM gold_history WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO gold_history (price_24, price_21, price_18) VALUES (?, ?, ?)");
        $stmt->bind_param("ddd", $prices['gold_24'], $prices['gold_21'], $prices['gold_18']);
        $stmt->execute();
    }

    // جلب التاريخ لآخر 24 سجل
    $history = [];
    $res = $conn->query("SELECT * FROM gold_history ORDER BY created_at DESC LIMIT 24");
    while($row = $res->fetch_assoc()) { $history[] = $row; }

    $output = [
        "status" => "success",
        "source" => "iSagha Direct API",
        "data" => $prices,
        "history" => array_reverse($history),
        "last_update" => date("Y-m-d H:i:s")
    ];

    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta charset="UTF-8">
            <title>أسعار الذهب - مقري</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #0f172a; color: white; text-align: center; padding-top: 50px; }
                .card { background: #1e293b; display: inline-block; padding: 40px; border-radius: 24px; border: 2px solid #d4af37; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
                h1 { color: #d4af37; font-size: 24px; margin-bottom: 5px; }
                .main-price { font-size: 48px; font-weight: 800; margin: 20px 0; color: #fff; }
                table { width: 100%; margin-top: 25px; border-top: 1px solid #334155; }
                td { padding: 15px 10px; border-bottom: 1px solid #334155; font-size: 18px; }
                .label { color: #94a3b8; text-align: right; }
                .val { color: #facc15; font-weight: bold; text-align: left; }
                .footer { font-size: 12px; color: #64748b; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>سعر الذهب عيار 21 الآن</h1>
                <div class="main-price"><?php echo number_format($prices['gold_21']); ?> <small style="font-size: 18px;">ج.م</small></div>
                <table>
                    <tr><td class="label">عيار 24:</td><td class="val"><?php echo number_format($prices['gold_24']); ?> ج.م</td></tr>
                    <tr><td class="label">عيار 18:</td><td class="val"><?php echo number_format($prices['gold_18']); ?> ج.م</td></tr>
                    <tr><td class="label">الجنيه الذهب:</td><td class="val"><?php echo number_format($prices['gold_coin']); ?> ج.م</td></tr>
                </table>
                <div class="footer">آخر تحديث من آي صاغة: <?php echo $output['last_update']; ?></div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    echo json_encode($output);
} else {
    echo json_encode(["status" => "error", "message" => "فشل في جلب الأسعار الحقيقية، جاري الفحص"]);
}