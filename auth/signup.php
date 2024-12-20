<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['username', 'email', 'password', 'firstName', 'lastName'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

$conn = connectDB();

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM mb_users WHERE email = ?");
$stmt->bind_param("s", $data['email']);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}
$stmt->close();

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM mb_users WHERE username = ?");
$stmt->bind_param("s", $data['username']);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit;
}
$stmt->close();

// Handle profile image upload
$profile_picture_url = null;
if (!empty($data['profileImage'])) {
    // Extract the base64 data
    $image_parts = explode(";base64,", $data['profileImage']);
    
    // Verify it's actually an image
    if (count($image_parts) !== 2) {
        echo json_encode(['success' => false, 'message' => 'Invalid image format']);
        exit;
    }
    
    // Get image extension
    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1];
    
    // Decode base64
    $image_base64 = base64_decode($image_parts[1]);
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $image_type;
    
    // Set upload path (using absolute path)
    $upload_dir = dirname(__DIR__) . '/upload/profiles/';
    $upload_path = $upload_dir . $filename;
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            error_log("Failed to create directory: " . $upload_dir);
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }
    
    // Save the file
    if (file_put_contents($upload_path, $image_base64)) {
        // Store the relative path in the database
        $profile_picture_url = 'upload/profiles/' . $filename;
    } else {
        error_log("Failed to save image: " . $upload_path);
        echo json_encode(['success' => false, 'message' => 'Failed to save profile image']);
        exit;
    }
}

// Hash password
$password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO mb_users (username, email, password_hash, first_name, last_name, role, profile_picture_url) VALUES (?, ?, ?, ?, ?, 'user', ?)");
$stmt->bind_param("ssssss", 
    $data['username'],
    $data['email'],
    $password_hash,
    $data['firstName'],
    $data['lastName'],
    $profile_picture_url
);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user' => [
            'user_id' => $user_id,
            'username' => $data['username'],
            'email' => $data['email'],
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'role' => 'user',
            'profile_picture_url' => $profile_picture_url
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>