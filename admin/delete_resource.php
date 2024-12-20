<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $resourceId = $data['resourceId'] ?? null;
    
    if (!$resourceId) {
        throw new Exception('Resource ID is required');
    }
    
    $conn = connectDB();
    
    // Delete the resource
    $stmt = $conn->prepare("DELETE FROM mb_resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
