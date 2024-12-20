<?php
// Include configuration and database connection
require_once '../config/database.php';

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Get database connection
    $db = connectDB();
    
    // User statistics
    $userStats = $db->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_30d,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_7d
        FROM mb_users
    ");

    if (!$userStats) {
        throw new Exception("Error fetching user statistics: " . $db->error);
    }

    // Forum statistics
    $forumStats = $db->query("
        SELECT 
            c.name,
            COUNT(DISTINCT t.id) as thread_count,
            COUNT(DISTINCT r.id) as reply_count
        FROM mb_forum_categories c
        LEFT JOIN mb_forum_threads t ON c.id = t.category_id
        LEFT JOIN mb_forum_replies r ON t.id = r.thread_id
        GROUP BY c.id
        ORDER BY thread_count DESC
    ");

    if (!$forumStats) {
        throw new Exception("Error fetching forum statistics: " . $db->error);
    }

    // Resource statistics
    $resourceStats = $db->query("
        SELECT 
            r.title,
            r.view_count,
            r.type,
            rc.name as category_name
        FROM mb_resources r
        JOIN mb_resource_categories rc ON r.category_id = rc.id
        ORDER BY r.view_count DESC
        LIMIT 5
    ");

    if (!$resourceStats) {
        throw new Exception("Error fetching resource statistics: " . $db->error);
    }

    // User growth over time (last 12 months)
    $userGrowth = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_users
        FROM mb_users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");

    if (!$userGrowth) {
        throw new Exception("Error fetching user growth data: " . $db->error);
    }

    // Prepare and return response
    echo json_encode([
        'success' => true,
        'stats' => [
            'userStats' => $userStats->fetch_assoc(),
            'forumStats' => $forumStats->fetch_all(MYSQLI_ASSOC),
            'resourceStats' => $resourceStats->fetch_all(MYSQLI_ASSOC),
            'userGrowth' => $userGrowth->fetch_all(MYSQLI_ASSOC)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($db) && $db) {
        $db->close();
    }
}
?>