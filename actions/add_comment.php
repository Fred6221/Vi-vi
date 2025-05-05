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
    $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $content = trim($_POST['content'] ?? '');
    $redirect = $_POST['redirect'] ?? '../index.php';
    
    // Validate post ID
    if ($postId <= 0) {
        $_SESSION['comment_error'] = 'ID de publication invalide';
        header('Location: ' . $redirect);
        exit;
    }
    
    // Validate content
    if (empty($content)) {
        $_SESSION['comment_error'] = 'Le commentaire ne peut pas être vide';
        header('Location: ' . $redirect);
        exit;
    }
    
    // Get post to check if it exists and get owner
    $post = fetchRow("SELECT * FROM posts WHERE id = ?", [$postId], "i");
    
    if (!$post) {
        $_SESSION['comment_error'] = 'Publication introuvable';
        header('Location: ' . $redirect);
        exit;
    }
    
    // Add comment
    $commentId = addComment($postId, $userId, $content);
    
    if ($commentId) {
        // Notify post owner if it's not the current user
        if ($post['user_id'] != $userId) {
            createNotification(
                $post['user_id'],
                'comment',
                $user['name'] . ' a commenté votre publication',
                $userId,
                $postId
            );
        }
        
        // Notify other commenters (except post owner and current user)
        $commenters = fetchAll(
            "SELECT DISTINCT user_id FROM comments WHERE post_id = ? AND user_id != ? AND user_id != ?",
            [$postId, $userId, $post['user_id']],
            "iii"
        );
        
        foreach ($commenters as $commenter) {
            createNotification(
                $commenter['user_id'],
                'comment',
                $user['name'] . ' a également commenté une publication',
                $userId,
                $postId
            );
        }
        
        // Set success message
        $_SESSION['comment_success'] = 'Votre commentaire a été ajouté avec succès';
    } else {
        // Comment creation failed
        $_SESSION['comment_error'] = 'Une erreur est survenue lors de l\'ajout du commentaire';
    }
    
    // Redirect back to the page
    header('Location: ' . $redirect);
    exit;
} else {
    // If not a POST request, redirect to index page
    header('Location: ../index.php');
    exit;
}