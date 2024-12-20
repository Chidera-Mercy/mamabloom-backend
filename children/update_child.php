<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    // Get POST data
    $child_id = $_POST['child_id'] ?? null;
    $name = $_POST['name'] ?? null;
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $relationship_to_child = $_POST['relationship_to_child'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $height = $_POST['height'] ?? null;
    $head_circumference = $_POST['head_circumference'] ?? null;
    $blood_group = $_POST['blood_group'] ?? null;
    $rh_factor = $_POST['rh_factor'] ?? null;

    // Validate required fields
    if (!$child_id || !$name || !$date_of_birth || !$gender || !$relationship_to_child) {
        throw new Exception('Required fields are missing');
    }

    // Handle profile picture upload
    $profile_picture_url = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $file = $_FILES['profile_picture'];

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPEG, PNG and GIF are allowed.');
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        // Generate filename and set paths
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;

        // Use absolute path for directory operations
        $upload_dir = dirname(__DIR__) . '/upload/children/';
        $upload_path = $upload_dir . $filename;

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                error_log("Failed to create directory: " . $upload_dir);
                throw new Exception('Failed to create upload directory');
            }
        }

        // Ensure directory is writable
        if (!is_writable($upload_dir)) {
            error_log("Upload directory is not writable: " . $upload_dir);
            throw new Exception('Upload directory is not writable');
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            error_log("Failed to move uploaded file to: " . $upload_path);
            throw new Exception('Failed to save profile picture');
        }

        // Set the relative URL for database storage
        $profile_picture_url = 'upload/children/' . $filename;

        // Delete old profile picture if exists
        $stmt = $conn->prepare("SELECT profile_picture_url FROM mb_children WHERE id = ?");
        $stmt->bind_param("i", $child_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_profile = $result->fetch_assoc();
        
        if ($old_profile && $old_profile['profile_picture_url']) {
            $old_file = dirname(__DIR__) . '/' . $old_profile['profile_picture_url'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
    }

    // Update child record
    $query = "
        UPDATE mb_children 
        SET 
            name = ?,
            date_of_birth = ?,
            gender = ?,
            relationship_to_child = ?,
            weight = ?,
            height = ?,
            head_circumference = ?,
            blood_group = ?,
            rh_factor = ?
    ";

    // Add profile picture to update if provided
    if ($profile_picture_url) {
        $query .= ", profile_picture_url = ?";
    }

    $query .= " WHERE id = ?";

    $stmt = $conn->prepare($query);

    if ($profile_picture_url) {
        $stmt->bind_param(
            "ssssdddsssi",
            $name,
            $date_of_birth,
            $gender,
            $relationship_to_child,
            $weight,
            $height,
            $head_circumference,
            $blood_group,
            $rh_factor,
            $profile_picture_url,
            $child_id
        );
    } else {
        $stmt->bind_param(
            "ssssdddssi",
            $name,
            $date_of_birth,
            $gender,
            $relationship_to_child,
            $weight,
            $height,
            $head_circumference,
            $blood_group,
            $rh_factor,
            $child_id
        );
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Child updated successfully',
            'child' => [
                'id' => $child_id,
                'name' => $name,
                'date_of_birth' => $date_of_birth,
                'gender' => $gender,
                'relationship_to_child' => $relationship_to_child,
                'profile_picture' => $profile_picture_url,
                'weight' => $weight,
                'height' => $height,
                'head_circumference' => $head_circumference,
                'blood_group' => $blood_group,
                'rh_factor' => $rh_factor
            ]
        ]);
    } else {
        throw new Exception('Failed to update child');
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