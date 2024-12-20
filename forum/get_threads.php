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

try {
    $conn = connectDB();
    
    // Base query with joins and counts
    $query = "SELECT 
        t.id as thread_id,
        t.title,
        t.content,
        t.created_at,
        u.id as user_id,
        u.username,
        u.profile_picture_url,
        c.id as category_id,
        c.name as category_name,
        COUNT(DISTINCT r.id) as reply_count,
        COUNT(DISTINCT l.id) as likes_count
        FROM mb_forum_threads t
        LEFT JOIN mb_users u ON t.user_id = u.id
        LEFT JOIN mb_forum_categories c ON t.category_id = c.id
        LEFT JOIN mb_forum_replies r ON t.id = r.thread_id
        LEFT JOIN mb_forum_likes l ON t.id = l.thread_id";

    $whereConditions = [];
    $params = [];
    $types = "";

    // Add category filter if specified
    if (isset($_GET['category']) && $_GET['category'] !== 'all') {
        $whereConditions[] = "t.category_id = ?";
        $params[] = $_GET['category'];
        $types .= "i";
    }

    // Add search filter if specified
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchTerm = "%" . $_GET['search'] . "%";
        $whereConditions[] = "(t.title LIKE ? OR t.content LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    // Add WHERE clause if conditions exist
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }

    // Add GROUP BY and ORDER BY
    $query .= " GROUP BY t.id ORDER BY t.created_at DESC";

    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $threads = [];

    while ($row = $result->fetch_assoc()) {
        // Format the thread data
        $threads[] = [
            'thread_id' => $row['thread_id'],
            'title' => $row['title'],
            'content' => $row['content'],
            'created_at' => $row['created_at'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'profile_picture' => $row['profile_picture_url'],
            'category_id' => $row['category_id'],
            'category_name' => $row['category_name'],
            'reply_count' => (int)$row['reply_count'],
            'likes_count' => (int)$row['likes_count']
        ];
    }

    echo json_encode([
        'success' => true,
        'threads' => $threads
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
