<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/auth.php');

// Get user profile data
function getUserProfile($user_id) {
    global $conn;
    
    $user_id = (int)$user_id;
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

// Update user profile
function updateUserProfile($user_id, $data) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    // Validate and sanitize inputs
    $first_name = trim($data['first_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');
    $city = trim($data['city'] ?? '');
    $state = trim($data['state'] ?? '');
    $zip_code = trim($data['zip_code'] ?? '');
    $country = trim($data['country'] ?? '');
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Check if email is taken by another user
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return ['success' => false, 'message' => 'Email already taken by another user'];
    }
    
    // Update profile
    $sql = "UPDATE users SET 
            first_name = ?,
            last_name = ?,
            email = ?,
            phone = ?,
            address = ?,
            city = ?,
            state = ?,
            zip_code = ?,
            country = ?
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssssi", 
        $first_name, $last_name, $email, $phone, 
        $address, $city, $state, $zip_code, $country, $user_id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        // Update session email if changed
        $_SESSION['email'] = $email;
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
}

// Upload profile image
function uploadProfileImage($user_id, $file) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    // Validate file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB'];
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF allowed'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
    $upload_dir = __DIR__ . '/../assets/uploads/profiles/';
    $upload_path = $upload_dir . $filename;
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Get old profile image to delete
    $old_image = getUserProfile($user_id)['profile_image'] ?? null;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Update database
        $db_path = '../assets/uploads/profiles/' . $filename;
        $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $db_path, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Delete old image if exists
            if ($old_image && file_exists(__DIR__ . '/../' . $old_image)) {
                unlink(__DIR__ . '/../' . $old_image);
            }
            
            return ['success' => true, 'message' => 'Profile picture updated successfully', 'image_path' => $db_path];
        } else {
            return ['success' => false, 'message' => 'Failed to save image to database'];
        }
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

// Remove profile image
function removeProfileImage($user_id) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    // Get current image
    $user = getUserProfile($user_id);
    $image_path = $user['profile_image'] ?? null;
    
    if (!$image_path) {
        return ['success' => false, 'message' => 'No profile picture to remove'];
    }
    
    // Delete from database
    $sql = "UPDATE users SET profile_image = NULL WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Delete file
        $full_path = __DIR__ . '/../' . $image_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
        
        return ['success' => true, 'message' => 'Profile picture removed'];
    } else {
        return ['success' => false, 'message' => 'Failed to remove profile picture'];
    }
}

// Change password
function changePassword($user_id, $current_password, $new_password, $confirm_password) {
    global $conn;
    
    $user_id = (int)$user_id;
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    if ($new_password !== $confirm_password) {
        return ['success' => false, 'message' => 'New passwords do not match'];
    }
    
    if (strlen($new_password) < 6) {
        return ['success' => false, 'message' => 'New password must be at least 6 characters'];
    }
    
    // Get current password hash
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Hash new password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $new_hash, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        return ['success' => true, 'message' => 'Password changed successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to change password'];
    }
}
?>