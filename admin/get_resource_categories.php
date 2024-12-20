<?php
require_once '../config/database.php';

$conn = connectDB();

try {
    $result = $conn->query("
        SELECT id, name, description, slug, created_at 
        FROM mb_resource_categories 
        ORDER BY name
    ");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    echo json_encode(['success' => true, 'categories' => $categories]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
