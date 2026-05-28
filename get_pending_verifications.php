<?php
header("Content-Type: application/json; charset=UTF-8");
include 'db_config.php';

// استعلام لجلب بيانات المستخدمين الذين لديهم طلبات توثيق معلقة (Pending)
// بنعمل JOIN مع جدول المطورين عشان نجيب اسم المكتب أو المطور بالمرة
// التعديل: أضفنا الأعمدة الجديدة داخل الاستعلام واستخدمنا MAX لضمان جلب آخر قيمة لها عند التجميع
$query = "SELECT 
            vr.user_id, 
            d.name, 
            d.phones,
            GROUP_CONCAT(vr.document_type) as docs_types, 
            GROUP_CONCAT(vr.file_path) as docs_paths,
            MAX(vr.document_number) as document_number,
            MAX(vr.request_type) as request_type,
            MAX(vr.attempts_count) as attempts_count,
            MAX(vr.created_at) as request_date
          FROM verification_requests vr
          JOIN developers d ON vr.user_id = d.user_id
          WHERE vr.admin_approval = 'pending'
          GROUP BY vr.user_id
          ORDER BY request_date ASC";

$result = mysqli_query($conn, $query);

if ($result) {
    $requests = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // تحويل النصوص المجمعة (Comma separated) إلى مصفوفات عشان التعامل معاها في Flutter يكون أسهل
        $row['docs_types'] = explode(',', $row['docs_types']);
        $row['docs_paths'] = explode(',', $row['docs_paths']);
        
        // ضمان وجود قيم للأعمدة الجديدة حتى لو كانت فارغة في قاعدة البيانات لمنع مشاكل الـ Null في التطبيق
        $row['document_number'] = $row['document_number'] ?? "غير متوفر";
        $row['request_type'] = $row['request_type'] ?? "new_verification";
        $row['attempts_count'] = $row['attempts_count'] ?? "1";

        // بناء المسار الكامل للصور (تأكد من تعديل المسار حسب إعدادات السيرفر عندك)
        $full_paths = [];
        foreach ($row['docs_paths'] as $path) {
            $full_paths[] = "https://dulce-esophageal-votively.ngrok-free.dev/sha2tak_api/uploads/verifications/" . $path;
        }
        $row['full_docs_urls'] = $full_paths;
        
        $requests[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $requests
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}
?>