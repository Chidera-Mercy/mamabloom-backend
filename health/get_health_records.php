<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    $child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : null;

    if (!$child_id) {
        throw new Exception('Child ID is required');
    }

    // Get health records with child verification
    $query = "
        SELECT 
            h.id as record_id,
            h.record_type,
            h.date_recorded,
            h.details,
            h.doctor_name,
            h.next_appointment,
            c.user_id
        FROM mb_health_records h
        JOIN mb_children c ON h.child_id = c.id
        WHERE h.child_id = ?
        ORDER BY h.date_recorded DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $child_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = [
            'record_id' => $row['record_id'],
            'record_type' => $row['record_type'],
            'date_recorded' => $row['date_recorded'],
            'details' => $row['details'],
            'doctor_name' => $row['doctor_name'],
            'next_appointment' => $row['next_appointment'],
            'user_id' => $row['user_id'] // For frontend verification
        ];
    }

    echo json_encode([
        'success' => true,
        'records' => $records
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