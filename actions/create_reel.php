<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Get current user
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

if (!$user) {
    // Invalid user ID in session
    echo json_encode(['success' => false, 'message' => 'Utilisateur invalide']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Get form data
$description = trim($_POST['description'] ?? '');
$privacy = isset($_POST['privacy']) && in_array($_POST['privacy'], ['public', 'friends', 'private']) ? $_POST['privacy'] : 'friends';

// Validate video file
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Veuillez sélectionner une vidéo']);
    exit;
}

// Check file type
$allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
if (!in_array($_FILES['video']['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Seuls les fichiers vidéo sont acceptés.']);
    exit;
}

// Upload video file
$uploadDir = '../assets/videos/reels/';
$videoFileName = time() . '_' . basename($_FILES['video']['name']);
$videoFilePath = $uploadDir . $videoFileName;

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Move uploaded file
if (!move_uploaded_file($_FILES['video']['tmp_name'], $videoFilePath)) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de la vidéo']);
    exit;
}

// Generate thumbnail (this would typically use a library like FFmpeg)
// For simplicity, we'll just use a placeholder
$thumbnailFileName = null;
$thumbnailUrl = null;

// In a real implementation, you would use FFmpeg to generate a thumbnail
// For example:
// $thumbnailFileName = time() . '_thumbnail.jpg';
// $thumbnailFilePath = '../assets/images/thumbnails/' . $thumbnailFileName;
// exec("ffmpeg -i $videoFilePath -ss 00:00:01 -frames:v 1 $thumbnailFilePath");
// $thumbnailUrl = 'assets/images/thumbnails/' . $thumbnailFileName;

// Create reel
$reelData = [
    'user_id' => $userId,
    'video_url' => 'assets/videos/reels/' . $videoFileName,
    'thumbnail_url' => $thumbnailUrl,
    'description' => $description,
    'privacy' => $privacy,
    'view_count' => 0,
    'like_count' => 0,
    'created_at' => date('Y-m-d H:i:s')
];

$reelId = insert('reels', $reelData);

if (!$reelId) {
    // Delete uploaded file if insert fails
    unlink($videoFilePath);
    if ($thumbnailFileName) {
        unlink('../assets/images/thumbnails/' . $thumbnailFileName);
    }
    
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du reel']);
    exit;
}

// Return success response
echo json_encode([
    'success' => true,
    'reel_id' => $reelId,
    'message' => 'Reel créé avec succès'
]);
exit;