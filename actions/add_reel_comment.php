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
$reelId = isset($_POST['reel_id']) ? (int)$_POST['reel_id'] : 0;
$comment = trim($_POST['comment'] ?? '');

// Validate reel ID
if ($reelId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de reel invalide']);
    exit;
}

// Validate comment
if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas être vide']);
    exit;
}

// Get reel
$reel = fetchRow("SELECT * FROM reels WHERE id = ?", [$reelId], "i");

if (!$reel) {
    echo json_encode(['success' => false, 'message' => 'Reel introuvable']);
    exit;
}

// Create comment
$commentData = [
    'reel_id' => $reelId,
    'user_id' => $userId,
    'content' => $comment,
    'created_at' => date('Y-m-d H:i:s')
];

$commentId = insert('reel_comments', $commentData);

if (!$commentId) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du commentaire']);
    exit;
}

// Create notification for reel owner (if not the current user)
if ($reel['user_id'] !== $userId) {
    createNotification(
        $reel['user_id'],
        'reel_comment',
        $user['name'] . ' a commenté votre reel',
        $userId
    );
}

// Get comment count
$commentCount = fetchRow(
    "SELECT COUNT(*) as count FROM reel_comments WHERE reel_id = ?",
    [$reelId],
    "i"
)['count'];

// Return success response with comment data
echo json_encode([
    'success' => true,
    'comment' => [
        'id' => $commentId,
        'reel_id' => $reelId,
        'user_id' => $userId,
        'content' => $comment,
        'name' => $user['name'],
        'profile_pic' => $user['profile_pic'],
        'time' => date('H:i')
    ],
    'comment_count' => $commentCount
]);
exit;