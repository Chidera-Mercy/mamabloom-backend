<?php
require_once '../config/database.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get database connection
    $conn = connectDB();

    // Validate required fields
    $required_fields = ['title', 'type', 'category_id', 'author_id'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception($field . ' is required');
        }
    }

    // Get and sanitize basic resource data
    $title = $conn->real_escape_string($_POST['title']);
    $type = $conn->real_escape_string($_POST['type']);
    $category_id = intval($_POST['category_id']);
    $author_id = intval($_POST['author_id']);
    $is_featured = isset($_POST['is_featured']) ? intval($_POST['is_featured']) : 0;

    // Handle content based on resource type
    $content_json = '';
    $external_url = '';

    if ($type === 'article') {
        // For articles, create a simple delimited string instead of JSON
        $introduction = $conn->real_escape_string($_POST['introduction'] ?? '');
        $paragraphs = isset($_POST['paragraphs']) ? json_decode($_POST['paragraphs'], true) : [];
        $conclusion = $conn->real_escape_string($_POST['conclusion'] ?? '');
        
        // Validate paragraphs array
        if (!is_array($paragraphs)) {
            throw new Exception('Invalid paragraphs format');
        }
        
        // Escape each paragraph
        $escaped_paragraphs = array_map(function($p) use ($conn) {
            return $conn->real_escape_string($p);
        }, $paragraphs);
        
        // Create content string with custom delimiters
        $content_json = "INTRO:{$introduction}||PARA:" . implode('||PARA:', $escaped_paragraphs) . "||CONC:{$conclusion}";
    } else {
        // For video/podcast, store a simple description
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $external_url = $conn->real_escape_string($_POST['external_url'] ?? '');
        $content_json = "DESC:{$description}";
    }

    // Handle thumbnail upload
    $thumbnail_url = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['thumbnail']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPEG, PNG and GIF are allowed.');
        }

        // Validate file size (5MB max)
        if ($_FILES['thumbnail']['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }

        // Set up upload directory using absolute path
        $upload_dir = dirname(__DIR__) . '/upload/resources/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                error_log("Failed to create directory: " . $upload_dir);
                throw new Exception('Failed to create upload directory');
            }
        }

        // Ensure directory is writable
        if (!is_writable($upload_dir)) {
            error_log("Upload directory is not writable: " . $upload_dir);
            throw new Exception('Upload directory is not writable');
        }

        // Generate unique filename
        $file_extension = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_path)) {
            error_log("Failed to move uploaded file to: " . $target_path);
            throw new Exception('Failed to upload thumbnail');
        }

        // Set relative path for database storage
        $thumbnail_url = 'upload/resources/' . $filename;
    }

    // Prepare and execute SQL query
    $query = "INSERT INTO mb_resources (
        title, 
        type, 
        content_json, 
        external_url,
        category_id, 
        author_id,
        thumbnail,
        is_featured,
        created_at,
        updated_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
    )";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param(
        'ssssiisi',
        $title,
        $type,
        $content_json,
        $external_url,
        $category_id,
        $author_id,
        $thumbnail_url,
        $is_featured
    );

    if (!$stmt->execute()) {
        // If insert fails, delete uploaded thumbnail
        if (!empty($thumbnail_url)) {
            $thumbnail_path = dirname(__DIR__) . '/' . $thumbnail_url;
            if (file_exists($thumbnail_path)) {
                unlink($thumbnail_path);
            }
        }
        throw new Exception('Failed to create resource: ' . $stmt->error);
    }

    // Get the created resource data
    $resource_id = $stmt->insert_id;
    $select_stmt = $conn->prepare("SELECT * FROM mb_resources WHERE id = ?");
    $select_stmt->bind_param('i', $resource_id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    $resource_data = $result->fetch_assoc();

    $response['success'] = true;
    $response['message'] = 'Resource created successfully';
    $response['resource'] = $resource_data;

} catch (Exception $e) {
    error_log("Resource creation error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($select_stmt)) {
        $select_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>