<?php
require_once '../config/database.php';

$conn = connectDB();

try {
    $result = $conn->query("
        SELECT t.id as id, 
        c.name as category_name,
        t.title as title, 
        t.content as content, 
        t.created_at as created_at, 
        u.username as username
        FROM mb_forum_threads t
        LEFT JOIN mb_forum_categories c ON t.category_id = c.id
        LEFT JOIN mb_users u ON t.user_id = u.id
        ORDER BY created_at DESC
    ");
    
    $threads = [];
    while ($row = $result->fetch_assoc()) {
        $threads[] = $row;
    }
    
    echo json_encode(['success' => true, 'threads' => $threads]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
