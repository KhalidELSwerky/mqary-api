<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'db_config.php';

// استقبال البيانات بصيغة JSON
$json = file_get_contents("php://input");
$data = json_decode($json);

if (!empty($data->property_id)) {
    // التأكد من صحة المعرفات وتحويلها لأرقام
    $property_id = intval($data->property_id);
    // لو الـ user_id مش موجود أو 0 بنخليه NULL في قاعدة البيانات
    $user_id     = (!empty($data->user_id) && $data->user_id != 0) ? intval($data->user_id) : "NULL";
    $type        = !empty($data->interaction_type) ? $conn->real_escape_string($data->interaction_type) : 'view';

    // جلب بيانات العقار الأساسية
    $prop_info = $conn->query("SELECT category_id, location_id, price FROM properties WHERE id = $property_id LIMIT 1");
    
    if ($prop_info && $prop_info->num_rows > 0) {
        $prop = $prop_info->fetch_assoc();
        $cat_id = intval($prop['category_id']);
        $loc_id = intval($prop['location_id']);
        $price  = floatval($prop['price']);

        // التعديل هنا: الـ $user_id لو قيمته NULL مش بيتحط بين علامات تنصيص
        // باقي القيم النصية زي الـ $type لازم تتحط بين ''
        $sql = "INSERT INTO user_property_interactions (user_id, property_id, category_id, location_id, price_range, interaction_type) 
                VALUES ($user_id, $property_id, $cat_id, $loc_id, $price, '$type')";

        if ($conn->query($sql)) {
            echo json_encode([
                "status" => "success", 
                "message" => "Interaction logged",
                "debug_info" => ["user" => $user_id, "property" => $property_id]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "SQL Error: " . $conn->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Property ID $property_id not found in database"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Incomplete data: property_id is required"]);
}

$conn->close();
?>