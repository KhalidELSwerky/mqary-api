<?php
// ملف عرض نجاح العملية للمستخدم داخل التطبيق
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم الدفع</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f4f4f4; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .icon { font-size: 50px; color: #4CAF50; }
        h2 { color: #1A237E; }
        p { color: #666; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h2>تمت العملية بنجاح!</h2>
        <p>تم استلام بيانات الدفع، يمكنك الآن العودة للتطبيق وتحديث رصيدك.</p>
        <p>سيتم إغلاق هذه الصفحة تلقائياً...</p>
    </div>

    <script>
        // كود بسيط عشان نرجع المستخدم للتطبيق أو نغلق الصفحة بعد 3 ثواني
        setTimeout(function() {
            // لو بتستخدم مكتبة WebView ممكن نبعت إشارة هنا لإغلاق الصفحة
            console.log("Payment Successful - Closing WebView");
        }, 3000);
    </script>
</body>
</html>