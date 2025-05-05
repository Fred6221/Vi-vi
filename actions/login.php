<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate credentials
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Veuillez entrer votre email et votre mot de passe';
        header('Location: ../index.php');
        exit;
    }
    
    // Attempt to authenticate user
    $user = authenticateUser($email, $password);
    
    if ($user) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        
        // Set remember me cookie if requested
        if ($remember) {
            $token = generateRandomString(32);
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store token in database
            $tokenData = [
                'user_id' => $user['id'],
                'token' => password_hash($token, PASSWORD_DEFAULT),
                'expires_at' => date('Y-m-d H:i:s', $expiry),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            insert('remember_tokens', $tokenData);
            
            // Set cookie
            setcookie('remember_token', $user['id'] . ':' . $token, $expiry, '/', '', false, true);
        }
        
        // Create login activity log
        $activityData = [
            'user_id' => $user['id'],
            'activity_type' => 'login',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        insert('user_activities', $activityData);
        
        // Redirect to home page
        header('Location: ../index.php');
        exit;
    } else {
        // Invalid credentials
        $_SESSION['login_error'] = 'Email ou mot de passe incorrect';
        header('Location: ../index.php');
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header('Location: ../login.php');
    exit;
}