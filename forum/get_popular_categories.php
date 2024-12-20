<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    // Get categories with thread count, ordered by thread count
    $query = "
        SELECT 
            c.id as category_id,
            c.name,
            c.description,
            c.icon,
            COUNT(t.id) as thread_count,
            SUM(t.reply_count) as total_replies
        FROM mb_forum_categories c
        LEFT JOIN mb_forum_threads t ON c.id = t.category_id
        GROUP BY c.id
        HAVING thread_count > 0
        ORDER BY thread_count DESC, total_replies DESC
        LIMIT 5
    ";

    $result = $conn->query($query);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'category_id' => $row['category_id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'icon' => $row['icon'],
            'thread_count' => $row['thread_count'],
            'total_replies' => $row['total_replies']
        ];
    }

    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 