<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    $child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : null;

    if (!$child_id) {
        throw new Exception('Child ID is required');
    }

    // Get milestones with child verification
    $query = "
        SELECT 
            m.id,
            m.milestone_type,
            m.description,
            m.date_achieved,
            m.notes,
            m.created_at,
            c.user_id
        FROM mb_milestones m
        JOIN mb_children c ON m.child_id = c.id
        WHERE m.child_id = ?
        ORDER BY m.date_achieved DESC, m.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $child_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $milestones = [];
    while ($row = $result->fetch_assoc()) {
        $milestones[] = [
            'id' => $row['id'],
            'milestone_type' => $row['milestone_type'],
            'description' => $row['description'],
            'date_achieved' => $row['date_achieved'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'user_id' => $row['user_id'] // For frontend verification
        ];
    }

    echo json_encode([
        'success' => true,
        'milestones' => $milestones
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