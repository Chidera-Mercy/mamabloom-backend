<?php
require_once '../config/database.php';

$conn = connectDB();

try {
    $result = $conn->query("
        SELECT id, username, email, role, first_name, last_name,
               location, created_at
        FROM mb_users 
        ORDER BY created_at DESC
    ");
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
