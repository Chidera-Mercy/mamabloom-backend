<?php
require_once '../config/database.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'resource' => null
);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    // Get resource ID from query parameters
    $resource_id = isset($_GET['resource_id']) ? intval($_GET['resource_id']) : 0;
    if ($resource_id <= 0) {
        throw new Exception('Invalid resource ID');
    }

    // Get database connection
    $conn = connectDB();

    // Update view count
    $update_query = "UPDATE mb_resources SET view_count = view_count + 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('i', $resource_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Fetch resource with author name
    $query = "SELECT r.*, u.first_name, u.last_name, 
              CONCAT(u.first_name, ' ', u.last_name) as author_name,
              c.name as category_name
              FROM mb_resources r
              LEFT JOIN mb_users u ON r.author_id = u.id
              LEFT JOIN mb_resource_categories c ON r.category_id = c.id
              WHERE r.id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param('i', $resource_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to fetch resource: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $resource = $result->fetch_assoc();

    if (!$resource) {
        throw new Exception('Resource not found');
    }

    // Successfully found the resource
    $response['success'] = true;
    $response['resource'] = $resource;

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>