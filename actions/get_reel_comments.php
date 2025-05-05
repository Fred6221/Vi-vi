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

// Check if request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Get reel ID
$reelId = isset($_GET['reel_id']) ? (int)$_GET['reel_id'] : 0;

// Validate reel ID
if ($reelId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de reel invalide']);
    exit;
}

// Get reel
$reel = fetchRow("SELECT * FROM reels WHERE id = ?", [$reelId], "i");

if (!$reel) {
    echo json_encode(['success' => false, 'message' => 'Reel introuvable']);
    exit;
}

// Get comments
$comments = fetchAll(
    "SELECT c.*, u.name, u.profile_pic
    FROM reel_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.reel_id = ?
    ORDER BY c.created_at ASC",
    [$reelId],
    "i"
);

// Format comments for response
$formattedComments = [];
foreach ($comments as $comment) {
    $formattedComments[] = [
        'id' => $comment['id'],
        'user_id' => $comment['user_id'],
        'content' => $comment['content'],
        'name' => $comment['name'],
        'profile_pic' => $comment['profile_pic'],
        'time' => date('H:i', strtotime($comment['created_at'])),
        'timestamp' => $comment['created_at']
    ];
}

// Return success response with comments
echo json_encode([
    'success' => true,
    'comments' => $formattedComments,
    'count' => count($formattedComments)
]);
exit;