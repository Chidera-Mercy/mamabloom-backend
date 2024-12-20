<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['thread_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thread ID is required']);
    exit;
}

try {
    $conn = connectDB();
    
    // Get thread details with user and category info
    $query = "SELECT 
        t.id as thread_id,
        t.title,
        t.content,
        t.created_at,
        u.id as user_id,
        u.username,
        u.profile_picture_url as profile_picture,
        c.id as category_id,
        c.name as category_name,
        (SELECT COUNT(*) FROM mb_forum_likes WHERE thread_id = t.id) as likes_count,
        EXISTS(SELECT 1 FROM mb_forum_likes WHERE thread_id = t.id AND user_id = ?) as is_liked
        FROM mb_forum_threads t
        LEFT JOIN mb_users u ON t.user_id = u.id
        LEFT JOIN mb_forum_categories c ON t.category_id = c.id
        WHERE t.id = ?";

    $stmt = $conn->prepare($query);
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
    $stmt->bind_param("ii", $user_id, $_GET['thread_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Thread not found");
    }

    $thread = $result->fetch_assoc();
    $stmt->close();

    // Get replies with user info
    $query = "SELECT 
        r.id as reply_id,
        r.content,
        r.created_at,
        r.updated_at,
        u.id as user_id,
        u.username,
        u.profile_picture_url as profile_picture
        FROM mb_forum_replies r
        LEFT JOIN mb_users u ON r.user_id = u.id
        WHERE r.thread_id = ?
        ORDER BY r.created_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_GET['thread_id']);
    $stmt->execute();
    $replies_result = $stmt->get_result();
    
    $replies = [];
    while ($reply = $replies_result->fetch_assoc()) {
        $replies[] = $reply;
    }

    echo json_encode([
        'success' => true,
        'thread' => $thread,
        'replies' => $replies
    ]);

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