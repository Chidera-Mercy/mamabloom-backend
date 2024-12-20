<?php
require_once '../config/database.php';

try {
    $conn = connectDB();

    // Get featured resources with their categories
    // Limiting to 6 resources for the dashboard
    $query = "
        SELECT 
            r.id as resource_id,
            r.title,
            r.type,
            r.thumbnail,
            r.view_count,
            r.is_featured,
            r.created_at,
            rc.name as category_name,
            u.username as author_name
        FROM mb_resources r
        LEFT JOIN mb_resource_categories rc ON r.category_id = rc.id
        LEFT JOIN mb_users u ON r.author_id = u.id
        WHERE r.is_featured = 1
        ORDER BY r.view_count DESC, r.created_at DESC
        LIMIT 6
    ";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $featured_resources = [];
    while ($row = $result->fetch_assoc()) {
        // Format the resource data
        $featured_resources[] = [
            'resource_id' => $row['resource_id'],
            'title' => $row['title'],
            'type' => $row['type'],
            'thumbnail' => $row['thumbnail'],
            'view_count' => $row['view_count'],
            'created_at' => $row['created_at'],
            'category_name' => $row['category_name'],
            'author_name' => $row['author_name']
        ];
    }

    echo json_encode([
        'success' => true,
        'featured_resources' => $featured_resources
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching resources: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 