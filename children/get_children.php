<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

    if (!$user_id) {
        throw new Exception('User ID is required');
    }

    // Get basic child information needed for cards
    $query = "
        SELECT 
            id,
            name,
            date_of_birth,
            gender,
            profile_picture_url,
            weight,
            height
        FROM mb_children 
        WHERE user_id = ?
        ORDER BY date_of_birth DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception($conn->error);
    }

    $children = [];
    while ($row = $result->fetch_assoc()) {
        $children[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'date_of_birth' => $row['date_of_birth'],
            'gender' => $row['gender'],
            'profile_picture' => $row['profile_picture_url'], // This should return "uploads/children/filename"
            'weight' => $row['weight'],
            'height' => $row['height']
        ];
    }

    echo json_encode([
        'success' => true,
        'children' => $children
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching children: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 