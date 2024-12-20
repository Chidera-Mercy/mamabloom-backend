<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Check if child_id is provided
if (!isset($data['child_id'])) {
    echo json_encode(['success' => false, 'message' => 'Child ID is required']);
    exit;
}

$conn = connectDB();

// First get the child's profile picture to delete it if exists
$stmt = $conn->prepare("SELECT profile_picture_url FROM mb_children WHERE id = ?");
$stmt->bind_param("i", $data['child_id']);
$stmt->execute();
$result = $stmt->get_result();
$child = $result->fetch_assoc();
$stmt->close();

// Delete the child record
$stmt = $conn->prepare("DELETE FROM mb_children WHERE id = ?");
$stmt->bind_param("i", $data['child_id']);

if ($stmt->execute()) {
    // If deletion was successful and there was a profile picture, delete it
    if ($child && $child['profile_picture_url']) {
        $file_path = dirname(__DIR__) . $child['profile_picture_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Child deleted successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete child'
    ]);
}

$stmt->close();
$conn->close();
?> 