<?php
require_once '../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $threadId = $data['threadId'] ?? null;
    
    if (!$threadId) {
        throw new Exception('Thread ID is required');
    }
    
    $conn = connectDB();
    $stmt = $conn->prepare("DELETE FROM mb_forum_threads WHERE id = ?");
    $stmt->bind_param('i', $threadId);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
