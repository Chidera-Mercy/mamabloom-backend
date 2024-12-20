<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    // Get user_id from request
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['user_id'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

    if (!$user_id) {
        throw new Exception('User ID is required');
    }

    // Get milestones for user's children with child's name and age
    $query = "
        SELECT 
            m.id,
            m.milestone_type,
            m.description,
            m.date_achieved,
            m.notes,
            c.name as child_name,
            c.date_of_birth,
            TIMESTAMPDIFF(MONTH, c.date_of_birth, m.date_achieved) as age_in_months
        FROM mb_milestones m
        JOIN mb_children c ON m.child_id = c.id
        WHERE c.user_id = ?
        ORDER BY m.date_achieved DESC, m.created_at DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception($conn->error);
    }

    $milestones = [];
    while ($row = $result->fetch_assoc()) {
        // Format age string
        $age_string = '';
        $months = $row['age_in_months'];
        if ($months >= 12) {
            $years = floor($months / 12);
            $remaining_months = $months % 12;
            $age_string = $years . ' year' . ($years > 1 ? 's' : '');
            if ($remaining_months > 0) {
                $age_string .= ' ' . $remaining_months . ' month' . ($remaining_months > 1 ? 's' : '');
            }
        } else {
            $age_string = $months . ' month' . ($months > 1 ? 's' : '');
        }

        $milestones[] = [
            'id' => $row['id'],
            'milestone_type' => $row['milestone_type'],
            'description' => $row['description'],
            'date_achieved' => $row['date_achieved'],
            'notes' => $row['notes'],
            'child_name' => $row['child_name'],
            'child_age' => $age_string
        ];
    }

    echo json_encode([
        'success' => true,
        'milestones' => $milestones
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching milestones: ' . $e->getMessage()
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