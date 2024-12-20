<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['thread_id']) || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

try {
    $conn = connectDB();

    // Check if like already exists
    $stmt = $conn->prepare("SELECT id FROM mb_forum_likes WHERE user_id = ? AND thread_id = ?");
    $stmt->bind_param("ii", $data['user_id'], $data['thread_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unlike: Remove existing like
        $stmt = $conn->prepare("DELETE FROM mb_forum_likes WHERE user_id = ? AND thread_id = ?");
        $stmt->bind_param("ii", $data['user_id'], $data['thread_id']);
        $action = 'unliked';
    } else {
        // Like: Add new like
        $stmt = $conn->prepare("INSERT INTO mb_forum_likes (user_id, thread_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $data['user_id'], $data['thread_id']);
        $action = 'liked';
    }

    if ($stmt->execute()) {
        // Get updated like count
        $stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM mb_forum_likes WHERE thread_id = ?");
        $stmt->bind_param("i", $data['thread_id']);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $like_count = $count_result->fetch_assoc()['like_count'];

        echo json_encode([
            'success' => true,
            'message' => "Thread $action successfully",
            'action' => $action,
            'likes_count' => $like_count
        ]);
    } else {
        throw new Exception("Failed to toggle like: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 