<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $milestone_id = $data['milestone_id'] ?? null;

    if (!$milestone_id) {
        throw new Exception('Milestone ID is required');
    }

    // Delete milestone with security check
    $query = "
        DELETE m FROM mb_milestones m
        JOIN mb_children c ON m.child_id = c.id
        WHERE m.id = ? AND c.user_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $milestone_id, $data['user_id']);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Milestone deleted successfully'
            ]);
        } else {
            throw new Exception('Milestone not found or access denied');
        }
    } else {
        throw new Exception('Failed to delete milestone');
    }

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