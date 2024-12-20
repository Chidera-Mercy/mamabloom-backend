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

// Validate required fields
if (!isset($data['thread_id']) || !isset($data['user_id']) || !isset($data['content'])) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Validate content length
if (strlen($data['content']) < 1) {
    echo json_encode(['success' => false, 'message' => 'Reply content cannot be empty']);
    exit;
}

try {
    $conn = connectDB();

    // Verify thread exists
    $stmt = $conn->prepare("SELECT id FROM mb_forum_threads WHERE id = ?");
    $stmt->bind_param("i", $data['thread_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Thread not found");
    }
    $stmt->close();

    // Verify user exists
    $stmt = $conn->prepare("SELECT id FROM mb_users WHERE id = ?");
    $stmt->bind_param("i", $data['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Invalid user");
    }
    $stmt->close();

    // Insert reply
    $stmt = $conn->prepare("INSERT INTO mb_forum_replies (thread_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", 
        $data['thread_id'],
        $data['user_id'],
        $data['content']
    );

    if ($stmt->execute()) {
        $reply_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Reply posted successfully',
            'reply_id' => $reply_id
        ]);
    } else {
        throw new Exception("Failed to post reply: " . $stmt->error);
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