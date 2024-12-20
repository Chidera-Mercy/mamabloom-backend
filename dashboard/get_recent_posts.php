<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

    // Get recent forum threads with category, author, and reply count
    $query = "
        SELECT 
            t.id,
            t.title,
            t.content,
            t.created_at,
            c.name as category_name,
            c.slug as category_slug,
            u.username as author_name,
            u.profile_picture_url as author_image,
            COUNT(DISTINCT r.id) as reply_count,
            COUNT(DISTINCT l.id) as like_count
        FROM mb_forum_threads t
        LEFT JOIN mb_forum_categories c ON t.category_id = c.id
        LEFT JOIN mb_users u ON t.user_id = u.id
        LEFT JOIN mb_forum_replies r ON t.id = r.thread_id
        LEFT JOIN mb_forum_likes l ON t.id = l.thread_id
        GROUP BY t.id
        ORDER BY t.created_at DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception($conn->error);
    }

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        // Truncate content for preview
        $content_preview = strip_tags($row['content']);
        if (strlen($content_preview) > 150) {
            $content_preview = substr($content_preview, 0, 147) . '...';
        }

        $posts[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'content' => $content_preview,
            'created_at' => $row['created_at'],
            'category_name' => $row['category_name'],
            'category_slug' => $row['category_slug'],
            'author_name' => $row['author_name'],
            'author_image' => $row['author_image'],
            'reply_count' => (int)$row['reply_count'],
            'like_count' => (int)$row['like_count']
        ];
    }

    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching posts: ' . $e->getMessage()
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