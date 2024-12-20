<?php
// admin/get_stats.php
require_once '../config/database.php';

$conn = connectDB();

try {
    // Basic statistics
    $stats = [
        'totalUsers' => $conn->query("SELECT COUNT(*) FROM mb_users")->fetch_row()[0],
        'totalThreads' => $conn->query("SELECT COUNT(*) FROM mb_forum_threads")->fetch_row()[0],
        'totalResources' => $conn->query("SELECT COUNT(*) FROM mb_resources")->fetch_row()[0],
        
        // Active users in last 24 hours
        'activeUsers' => $conn->query("
            SELECT COUNT(DISTINCT user_id) 
            FROM mb_forum_threads 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ")->fetch_row()[0],
        
        // 30-day statistics
        'newUsers30d' => $conn->query("
            SELECT COUNT(*) 
            FROM mb_users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch_row()[0],
        
        'activeUsers30d' => $conn->query("
            SELECT COUNT(DISTINCT user_id) 
            FROM mb_forum_threads 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch_row()[0]
    ];
    
    echo json_encode(['success' => true, 'stats' => $stats]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();