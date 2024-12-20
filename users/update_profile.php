<?php
require_once '../config/database.php';

try {
    $conn = connectDB();
    
    // Get POST data
    $user_id = $_POST['user_id'] ?? null;
    $first_name = $_POST['first_name'] ?? null;
    $last_name = $_POST['last_name'] ?? null;
    $bio = $_POST['bio'] ?? null;
    $location = $_POST['location'] ?? null;

    // Validate required fields
    if (!$user_id || !$first_name || !$last_name) {
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
        $upload_dir = dirname(__DIR__) . '/upload/profiles/';
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
        $profile_picture_url = 'upload/profiles/' . $filename;
        
        // Delete old profile picture if exists
        $stmt = $conn->prepare("SELECT profile_picture_url FROM mb_users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
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

    // Update user record
    $query = "
        UPDATE mb_users 
        SET 
            first_name = ?,
            last_name = ?,
            bio = ?,
            location = ?
    ";

    // Add profile picture to update if provided
    if ($profile_picture_url) {
        $query .= ", profile_picture_url = ?";
    }

    $query .= " WHERE id = ?";

    $stmt = $conn->prepare($query);

    if ($profile_picture_url) {
        $stmt->bind_param(
            "sssssi",
            $first_name,
            $last_name,
            $bio,
            $location,
            $profile_picture_url,
            $user_id
        );
    } else {
        $stmt->bind_param(
            "ssssi",
            $first_name,
            $last_name,
            $bio,
            $location,
            $user_id
        );
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to update profile: ' . $conn->error);
    }

    // Fetch the updated user data
    $select_stmt = $conn->prepare("SELECT id, username, email, role, first_name, last_name, bio, location, profile_picture_url, created_at FROM mb_users WHERE id = ?");
    $select_stmt->bind_param("i", $user_id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    $user_data = $result->fetch_assoc();

    if (!$user_data) {
        throw new Exception('Failed to fetch updated user data');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'profile' => $user_data,
        'user' => [
            'id' => $user_data['id'],
            'username' => $user_data['username'],
            'email' => $user_data['email'],
            'role' => $user_data['role'],
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'profile_picture_url' => $user_data['profile_picture_url']
        ]
    ]);

} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($select_stmt)) {
        $select_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>