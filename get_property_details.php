<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'db_config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // التعديل: استلام الحقول بأسماء توافق كود الـ Flutter [cite: 2026-01-29]
    // area_sqm -> net_area | floor_number -> floor_level
    $sql = "SELECT p.*, 
            p.area_sqm AS net_area, 
            p.floor_number AS floor_level,
            (SELECT COUNT(*) FROM property_votes WHERE property_id = p.id) as total_votes 
            FROM properties p WHERE p.id = $id";
            
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // حساب سعر المتر بناءً على المساحة الحقيقية [cite: 2026-01-29]
        $row['price_per_meter'] = ($row['net_area'] > 0) ? round($row['price'] / $row['net_area'], 2) : 0;
        
        // جلب الصور وضمان أنها تذهب كـ Array [cite: 2026-01-29]
        $img_sql = "SELECT image_url FROM property_images WHERE property_id = $id";
        $img_res = $conn->query($img_sql);
        $images = [];
        while($img = $img_res->fetch_assoc()) { 
            $images[] = $img['image_url']; 
        }
        $row['images'] = $images;

        echo json_encode(["status" => "success", "data" => $row], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["status" => "error", "message" => "العقار غير موجود"]);
    }
}
$conn->close();
?>