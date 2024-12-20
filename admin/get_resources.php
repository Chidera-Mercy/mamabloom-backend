<?php
require_once '../config/database.php';

$conn = connectDB();

try {
    $result = $conn->query("
        SELECT 
            r.id, r.title, r.type, r.external_url, r.description,
            r.thumbnail, r.view_count, r.is_featured, r.created_at,
            rc.name as category_name
        FROM mb_resources r
        JOIN mb_resource_categories rc ON r.category_id = rc.id
        ORDER BY r.created_at DESC
    ");
    
    $resources = [];
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
    
    echo json_encode(['success' => true, 'resources' => $resources]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();