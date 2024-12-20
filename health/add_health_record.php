<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['child_id', 'record_type', 'date_recorded', 'details', 'doctor_name'];
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

    // Insert health record
    $query = "
        INSERT INTO mb_health_records (
            child_id,
            record_type,
            date_recorded,
            details,
            doctor_name,
            next_appointment
        ) VALUES (?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "isssss",
        $data['child_id'],
        $data['record_type'],
        $data['date_recorded'],
        $data['details'],
        $data['doctor_name'],
        $data['next_appointment']
    );

    if ($stmt->execute()) {
        $record_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Health record added successfully',
            'record' => [
                'record_id' => $record_id,
                'record_type' => $data['record_type'],
                'date_recorded' => $data['date_recorded'],
                'details' => $data['details'],
                'doctor_name' => $data['doctor_name'],
                'next_appointment' => $data['next_appointment']
            ]
        ]);
    } else {
        throw new Exception('Failed to add health record');
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