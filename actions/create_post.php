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
    $content = trim($_POST['content'] ?? '');
    
    // Validate content
    if (empty($content)) {
        $_SESSION['post_error'] = 'Le contenu de la publication ne peut pas être vide';
        header('Location: ../index.php');
        exit;
    }
    
    // Handle image upload if present
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = '../assets/images/posts/';
        $image = uploadImage($_FILES['image'], $uploadDir);
        
        if (!$image) {
            $_SESSION['post_error'] = 'Erreur lors du téléchargement de l\'image. Formats acceptés: JPG, PNG, GIF';
            header('Location: ../index.php');
            exit;
        }
    }
    
    // Create post
    $postId = createPost($userId, $content, $image);
    
    if ($postId) {
        // Get user's friends to notify them
        $friends = getUserFriends($userId);
        
        // Create notifications for friends
        foreach ($friends as $friend) {
            createNotification(
                $friend['id'],
                'post',
                $user['name'] . ' a publié quelque chose',
                $userId,
                $postId
            );
        }
        
        // Set success message
        $_SESSION['post_success'] = 'Votre publication a été créée avec succès';
    } else {
        // Post creation failed
        $_SESSION['post_error'] = 'Une erreur est survenue lors de la création de la publication';
    }
    
    // Redirect back to index page
    header('Location: ../index.php');
    exit;
} else {
    // If not a POST request, redirect to index page
    header('Location: ../index.php');
    exit;
}