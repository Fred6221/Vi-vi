<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get current user
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

if (!$user) {
    // Invalid user ID in session
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $password = $_POST['password'] ?? '';
    $confirmDelete = isset($_POST['confirm_delete']);
    
    // Validate input
    if (empty($password)) {
        $_SESSION['error_message'] = 'Veuillez entrer votre mot de passe';
        header('Location: ../settings.php#account');
        exit;
    }
    
    if (!$confirmDelete) {
        $_SESSION['error_message'] = 'Vous devez confirmer la suppression de votre compte';
        header('Location: ../settings.php#account');
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        $_SESSION['error_message'] = 'Mot de passe incorrect';
        header('Location: ../settings.php#account');
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete user's data
        
        // Delete notifications
        executeQuery("DELETE FROM notifications WHERE user_id = ? OR from_user_id = ?", [$userId, $userId], "ii");
        
        // Delete messages
        executeQuery("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?", [$userId, $userId], "ii");
        
        // Delete conversations
        executeQuery("DELETE FROM conversations WHERE user1_id = ? OR user2_id = ?", [$userId, $userId], "ii");
        
        // Delete likes
        executeQuery("DELETE FROM likes WHERE user_id = ?", [$userId], "i");
        
        // Delete comments
        executeQuery("DELETE FROM comments WHERE user_id = ?", [$userId], "i");
        
        // Delete posts
        executeQuery("DELETE FROM posts WHERE user_id = ?", [$userId], "i");
        
        // Delete friendships
        executeQuery("DELETE FROM friendships WHERE user_id = ? OR friend_id = ?", [$userId, $userId], "ii");
        
        // Delete remember tokens
        executeQuery("DELETE FROM remember_tokens WHERE user_id = ?", [$userId], "i");
        
        // Delete user activities
        executeQuery("DELETE FROM user_activities WHERE user_id = ?", [$userId], "i");
        
        // Delete user settings
        executeQuery("DELETE FROM user_settings WHERE user_id = ?", [$userId], "i");
        
        // Finally, delete the user
        executeQuery("DELETE FROM users WHERE id = ?", [$userId], "i");
        
        // Commit transaction
        $conn->commit();
        
        // Clear cookies
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Redirect to home page with success message
        session_start();
        $_SESSION['success_message'] = 'Votre compte a été supprimé avec succès';
        header('Location: ../index.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $_SESSION['error_message'] = 'Une erreur est survenue lors de la suppression de votre compte: ' . $e->getMessage();
        header('Location: ../settings.php#account');
        exit;
    }
} else {
    // If not a POST request, redirect to settings page
    header('Location: ../settings.php');
    exit;
}