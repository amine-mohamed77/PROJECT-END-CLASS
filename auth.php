<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db.php';

// Function to register a new user
function registerUser($name, $email, $password, $role, $university = null) {
    // Check if email already exists
    $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    
    if ($user) {
        return [
            'success' => false,
            'message' => 'Email already registered'
        ];
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare user data
    $userData = [
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword,
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Add university if user is a student
    if ($role === 'student' && $university) {
        $userData['university'] = $university;
    }
    
    // Insert user into database
    $userId = insert('users', $userData);
    
    if ($userId) {
        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Registration successful'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Registration failed'
        ];
    }
}

// Function to login a user
function loginUser($email, $password) {
    // Get user by email
    $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        // Update last login time
        update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'message' => 'Login successful'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return fetchOne("SELECT id, name, email, role, profile_image, university, bio, rating FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// Function to logout user
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return true;
}

// Function to update user profile
function updateUserProfile($userId, $data) {
    // Check if user exists
    $user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    // Update user data
    $updated = update('users', $data, 'id = ?', [$userId]);
    
    if ($updated !== false) {
        return [
            'success' => true,
            'message' => 'Profile updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update profile'
        ];
    }
}

// Function to handle password reset request
function requestPasswordReset($email) {
    // Check if email exists
    $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Email not found'
        ];
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store token in database
    $data = [
        'reset_token' => $token,
        'reset_expires' => $expires
    ];
    
    $updated = update('users', $data, 'id = ?', [$user['id']]);
    
    if ($updated !== false) {
        // In a real application, you would send an email with the reset link
        // For this example, we'll just return the token
        return [
            'success' => true,
            'token' => $token,
            'message' => 'Password reset link has been sent to your email'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to process password reset request'
        ];
    }
}

// Function to reset password using token
function resetPassword($token, $newPassword) {
    // Find user with this token
    $user = fetchOne("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()", [$token]);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid or expired reset token'
        ];
    }
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password and clear reset token
    $data = [
        'password' => $hashedPassword,
        'reset_token' => null,
        'reset_expires' => null
    ];
    
    $updated = update('users', $data, 'id = ?', [$user['id']]);
    
    if ($updated !== false) {
        return [
            'success' => true,
            'message' => 'Password has been reset successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to reset password'
        ];
    }
}
?>