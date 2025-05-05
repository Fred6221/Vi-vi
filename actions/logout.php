<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Create logout activity log
    $activityData = [
        'user_id' => $userId,
        'activity_type' => 'logout',
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    insert('user_activities', $activityData);
    
    // Clear remember me token if exists
    if (isset($_COOKIE['remember_token'])) {
        list($cookieUserId, $token) = explode(':', $_COOKIE['remember_token']);
        
        if ($cookieUserId == $userId) {
            // Delete token from database
            delete('remember_tokens', 'user_id = ? AND token = ?', [$userId, password_hash($token, PASSWORD_DEFAULT)]);
            
            // Clear cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }
    
    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to login page
header('Location: ../login.php');
exit;