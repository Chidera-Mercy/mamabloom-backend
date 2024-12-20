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
if (!isset($data['user_id']) || !isset($data['category_id']) || 
    !isset($data['title']) || !isset($data['content'])) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Validate data
if (strlen($data['title']) < 5 || strlen($data['title']) > 255) {
    echo json_encode(['success' => false, 'message' => 'Title must be between 5 and 255 characters']);
    exit;
}

if (strlen($data['content']) < 20) {
    echo json_encode(['success' => false, 'message' => 'Content must be at least 20 characters']);
    exit;
}

try {
    $conn = connectDB();

    // First verify that the category exists
    $stmt = $conn->prepare("SELECT id FROM mb_forum_categories WHERE id = ?");
    $stmt->bind_param("i", $data['category_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Invalid category");
    }
    $stmt->close();

    // Verify that the user exists
    $stmt = $conn->prepare("SELECT id FROM mb_users WHERE id = ?");
    $stmt->bind_param("i", $data['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Invalid user");
    }
    $stmt->close();

    // Insert the thread
    $stmt = $conn->prepare("INSERT INTO mb_forum_threads (user_id, category_id, title, content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", 
        $data['user_id'],
        $data['category_id'],
        $data['title'],
        $data['content']
    );

    if ($stmt->execute()) {
        $thread_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Thread created successfully',
            'thread_id' => $thread_id
        ]);
    } else {
        throw new Exception("Failed to create thread: " . $stmt->error);
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