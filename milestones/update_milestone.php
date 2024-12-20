<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['milestone_id', 'milestone_type', 'description', 'date_achieved', 'user_id'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    // Verify milestone belongs to user's child (security check)
    $verify_query = "
        SELECT m.id 
        FROM mb_milestones m
        JOIN mb_children c ON m.child_id = c.id
        WHERE m.id = ? AND c.user_id = ?
    ";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $data['milestone_id'], $data['user_id']);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        throw new Exception('Milestone not found or access denied');
    }

    // Update milestone
    $query = "
        UPDATE mb_milestones 
        SET 
            milestone_type = ?,
            description = ?,
            date_achieved = ?,
            notes = ?
        WHERE id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssssi",
        $data['milestone_type'],
        $data['description'],
        $data['date_achieved'],
        $data['notes'],
        $data['milestone_id']
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Milestone updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update milestone');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($verify_stmt)) {
        $verify_stmt->close();
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 