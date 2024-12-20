<?php
require_once '../config/database.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'resources' => array()
);

try {
    // Get database connection
    $conn = connectDB();
    
    // Get query parameters
    $category = isset($_GET['category']) ? intval($_GET['category']) : null;
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : null;
    
    // Base query with joins to get author and category names
    $query = "
        SELECT 
            r.id as resource_id,
            r.title,
            r.type,
            r.content_json,
            r.external_url,
            r.thumbnail,
            r.view_count,
            r.is_featured,
            r.created_at,
            rc.name as category_name,
            rc.id as category_id,
            CONCAT(u.first_name, ' ', u.last_name) as author_name,
            u.id as author_id
        FROM mb_resources r
        LEFT JOIN mb_resource_categories rc ON r.category_id = rc.id
        LEFT JOIN mb_users u ON r.author_id = u.id
        WHERE 1=1
    ";
    
    // Add category filter if specified
    if ($category) {
        $query .= " AND r.category_id = $category";
    }
    
    // Add search filter if specified
    if ($search) {
        $query .= " AND (
            r.title LIKE '%$search%' 
            OR rc.name LIKE '%$search%'
            OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%'
        )";
    }
    
    // Order by featured first, then by creation date
    $query .= " ORDER BY r.is_featured DESC, r.created_at DESC";
    
    // Execute query
    $result = $conn->query($query);
    
    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    // Fetch all resources
    $resources = array();
    while ($row = $result->fetch_assoc()) {
        // Clean up the thumbnail URL if it exists
        if ($row['thumbnail']) {
            // Remove any leading slash to ensure consistent path format
            $row['thumbnail'] = ltrim($row['thumbnail'], '/');
        }
        
        // Format dates
        $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        
        // Convert is_featured to boolean
        $row['is_featured'] = (bool)$row['is_featured'];
        
        // Convert view_count to integer
        $row['view_count'] = intval($row['view_count']);
        
        $resources[] = $row;
    }
    
    // Set success response
    $response['success'] = true;
    $response['resources'] = $resources;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
} finally {
    // Close the connection
    if (isset($conn)) {
        $conn->close();
    }
}

// Send response
echo json_encode($response);
?>