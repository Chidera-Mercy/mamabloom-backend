<?php
require_once '../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['userId'] ?? null;
    
    if (!$userId) {
        throw new Exception('User ID is required');
    }
    
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM mb_users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();