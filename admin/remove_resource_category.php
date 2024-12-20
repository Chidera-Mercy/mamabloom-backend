<?php
require_once '../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $categoryId = $data['categoryId'] ?? null;
    
    if (!$categoryId) {
        throw new Exception('Category ID is required');
    }
    
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM mb_resource_categories WHERE id = ?");
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
