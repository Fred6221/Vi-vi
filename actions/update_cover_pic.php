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

// Check if form was submitted with a file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Define allowed file types and max file size
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB (larger for cover photos)
    
    // Get file info
    $fileName = $_FILES['cover_pic']['name'];
    $fileSize = $_FILES['cover_pic']['size'];
    $fileTmp = $_FILES['cover_pic']['tmp_name'];
    $fileType = $_FILES['cover_pic']['type'];
    
    // Check file type
    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['error'] = 'Type de fichier non autorisé. Veuillez télécharger une image (JPG, PNG, GIF).';
        header('Location: ../profile.php');
        exit;
    }
    
    // Check file size
    if ($fileSize > $maxFileSize) {
        $_SESSION['error'] = 'La taille du fichier est trop grande. Maximum 10MB.';
        header('Location: ../profile.php');
        exit;
    }
    
    // Create directory if it doesn't exist
    $uploadDir = '../assets/images/covers/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = 'cover_' . $userId . '_' . time() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFileName;
    
    // Upload file
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        // Delete old cover photo if exists
        if ($user['cover_pic'] && file_exists($uploadDir . $user['cover_pic'])) {
            unlink($uploadDir . $user['cover_pic']);
        }
        
        // Update user profile in database
        $success = updateUserProfile($userId, ['cover_pic' => $newFileName]);
        
        if ($success) {
            $_SESSION['success'] = 'Photo de couverture mise à jour avec succès.';
        } else {
            $_SESSION['error'] = 'Erreur lors de la mise à jour de la photo de couverture dans la base de données.';
        }
    } else {
        $_SESSION['error'] = 'Erreur lors du téléchargement de la photo de couverture.';
    }
} else {
    $_SESSION['error'] = 'Aucune image sélectionnée.';
}

// Redirect back to profile page
header('Location: ../profile.php');
exit;