<?php
require_once '../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || empty($data['name'])) {
        throw new Exception('Category name is required');
    }
    
    $conn = connectDB();
    
    // Generate slug from name
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
    
    $stmt = $conn->prepare("
        INSERT INTO mb_resource_categories (name, description, slug) 
        VALUES (?, ?, ?)
    ");
    
    $description = $data['description'] ?? '';
    $stmt->bind_param('sss', $data['name'], $description, $slug);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();