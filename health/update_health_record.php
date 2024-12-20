<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['record_id']) || !isset($data['record_type']) || 
    !isset($data['date_recorded']) || !isset($data['details']) || 
    !isset($data['doctor_name'])) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

try {
    $conn = connectDB();

    $query = "UPDATE mb_health_records SET 
        record_type = ?,
        date_recorded = ?,
        details = ?,
        doctor_name = ?,
        next_appointment = ?
        WHERE id = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    // Handle null next_appointment
    $next_appointment = empty($data['next_appointment']) ? null : $data['next_appointment'];

    $stmt->bind_param(
        "sssssi",
        $data['record_type'],
        $data['date_recorded'],
        $data['details'],
        $data['doctor_name'],
        $next_appointment,
        $data['record_id']
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Health record updated successfully',
            'record' => [
                'record_id' => $data['record_id'],
                'record_type' => $data['record_type'],
                'date_recorded' => $data['date_recorded'],
                'details' => $data['details'],
                'doctor_name' => $data['doctor_name'],
                'next_appointment' => $next_appointment
            ]
        ]);
    } else {
        throw new Exception("Failed to update health record: " . $stmt->error);
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