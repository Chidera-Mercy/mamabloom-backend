<?php

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['user_id', 'name', 'dateOfBirth', 'gender', 'relationship'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit;
    }
}

$conn = connectDB();

// Handle profile picture upload
$profile_picture_url = null;
if (!empty($data['photo'])) {
    // Extract the base64 data
    $image_parts = explode(";base64,", $data['photo']);

    // Verify it's actually an image
    if (count($image_parts) !== 2) {
        echo json_encode(['success' => false, 'message' => 'Invalid image format']);
        exit;
    }

    // Get image extension
    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1];

    // Decode base64
    $image_base64 = base64_decode($image_parts[1]);

    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $image_type;

    // Set upload path (using absolute path)
    $upload_dir = dirname(__DIR__) . '/upload/children/';
    $upload_path = $upload_dir . $filename;

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            error_log("Failed to create directory: " . $upload_dir);
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }
    
    // Save the file
    if (file_put_contents($upload_path, $image_base64)) {
        // Store the relative path in the database
        $profile_picture_url = 'upload/children/' . $filename;
    } else {
        error_log("Failed to save image: " . $upload_path);
        echo json_encode(['success' => false, 'message' => 'Failed to save profile image']);
        exit;
    }
}

// Insert new child
$stmt = $conn->prepare("INSERT INTO mb_children 
    (user_id, name, date_of_birth, gender, relationship_to_child, profile_picture_url, weight, height, head_circumference, blood_group, rh_factor) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssssddss", 
    $data['user_id'],              
    $data['name'],                 
    $data['dateOfBirth'],          
    $data['gender'],                
    $data['relationship'],          
    $profile_picture_url,           
    $data['weight'],                
    $data['height'],                
    $data['headCircumference'],     
    $data['bloodGroup'],            
    $data['rhFactor']               
);

if ($stmt->execute()) {
    $child_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Child added successfully',
        'child' => [
            'id' => $child_id,
            'name' => $data['name'],
            'date_of_birth' => $data['dateOfBirth'],
            'gender' => $data['gender'],
            'relationship_to_child' => $data['relationship'],
            'profile_picture_url' => $profile_picture_url,
            'weight' => $data['weight'] ?: null,
            'height' => $data['height'] ?: null,
            'head_circumference' => $data['headCircumference'] ?: null,
            'blood_group' => $data['bloodGroup'] ?: null,
            'rh_factor' => $data['rhFactor'] ?: null
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add child: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>