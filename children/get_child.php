<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    $child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : null;
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

    if (!$child_id || !$user_id) {
        throw new Exception('Child ID and User ID are required');
    }

    // Get child details with security check for user ownership
    $query = "
        SELECT 
            c.*,
            COUNT(DISTINCT m.id) as milestone_count,
            COUNT(DISTINCT h.id) as health_record_count
        FROM mb_children c
        LEFT JOIN mb_milestones m ON c.id = m.child_id
        LEFT JOIN mb_health_records h ON c.id = h.child_id
        WHERE c.id = ? AND c.user_id = ?
        GROUP BY c.id
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $child_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Child not found or access denied');
    }

    $child = $result->fetch_assoc();
    
    // Format the response
    $response = [
        'id' => $child['id'],
        'name' => $child['name'],
        'date_of_birth' => $child['date_of_birth'],
        'gender' => $child['gender'],
        'relationship_to_child' => $child['relationship_to_child'],
        'profile_picture' => $child['profile_picture_url'], // This will be like "uploads/children/filename"
        'weight' => $child['weight'],
        'height' => $child['height'],
        'head_circumference' => $child['head_circumference'],
        'blood_group' => $child['blood_group'],
        'rh_factor' => $child['rh_factor'],
        'milestone_count' => $child['milestone_count'],
        'health_record_count' => $child['health_record_count']
    ];

    echo json_encode([
        'success' => true,
        'child' => $response
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
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