<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once '../config/database.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$user_id = filter_var($_GET['user_id'], FILTER_SANITIZE_NUMBER_INT);

try {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id, username, email, role, first_name, last_name, bio, location, profile_picture_url, created_at 
                           FROM mb_users 
                           WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
        // Remove sensitive information
        unset($profile['password_hash']);
        
        echo json_encode([
            'success' => true,
            'profile' => $profile
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Profile not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}

$stmt->close();
$conn->close();