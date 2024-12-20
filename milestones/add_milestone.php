<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['child_id', 'milestone_type', 'description', 'date_achieved'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    // Verify child belongs to user (security check)
    $verify_query = "SELECT user_id FROM mb_children WHERE id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("i", $data['child_id']);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        throw new Exception('Child not found');
    }

    // Insert milestone
    $query = "
        INSERT INTO mb_milestones (
            child_id,
            milestone_type,
            description,
            date_achieved,
            notes,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "issss",
        $data['child_id'],
        $data['milestone_type'],
        $data['description'],
        $data['date_achieved'],
        $data['notes']
    );

    if ($stmt->execute()) {
        $milestone_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Milestone added successfully',
            'milestone' => [
                'id' => $milestone_id,
                'milestone_type' => $data['milestone_type'],
                'description' => $data['description'],
                'date_achieved' => $data['date_achieved'],
                'notes' => $data['notes']
            ]
        ]);
    } else {
        throw new Exception('Failed to add milestone');
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