<?php

require_once '../config/database.php';

try {
    $conn = connectDB();
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['resourceId']) || !isset($data['isFeatured'])) {
        throw new Exception('Resource ID and featured status are required');
    }
    
    
    $stmt = $conn->prepare("
        UPDATE mb_resources 
        SET is_featured = :isFeatured 
        WHERE id = :resourceId
    ");
    
    $stmt->execute([
        'resourceId' => $data['resourceId'],
        'isFeatured' => $data['isFeatured'] 
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();