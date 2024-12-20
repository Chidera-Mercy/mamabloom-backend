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

// Check if record_id is provided
if (!isset($data['record_id'])) {
    echo json_encode(['success' => false, 'message' => 'Record ID is required']);
    exit;
}

try {
    $conn = connectDB();

    // Delete the record
    $stmt = $conn->prepare("DELETE FROM mb_health_records WHERE id = ?");
    $stmt->bind_param("i", $data['record_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Health record deleted successfully'
        ]);
    } else {
        throw new Exception("Failed to delete health record: " . $stmt->error);
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