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

// Get reel ID
$reelId = isset($_POST['reel_id']) ? (int)$_POST['reel_id'] : 0;

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

// Check if user has already liked the reel
$existingLike = fetchRow(
    "SELECT * FROM reel_likes WHERE reel_id = ? AND user_id = ?",
    [$reelId, $userId],
    "ii"
);

// Toggle like
if ($existingLike) {
    // Unlike
    executeQuery(
        "DELETE FROM reel_likes WHERE reel_id = ? AND user_id = ?",
        [$reelId, $userId],
        "ii"
    );
    
    // Decrement like count
    executeQuery(
        "UPDATE reels SET like_count = like_count - 1 WHERE id = ? AND like_count > 0",
        [$reelId],
        "i"
    );
    
    $action = 'unliked';
} else {
    // Like
    $likeData = [
        'reel_id' => $reelId,
        'user_id' => $userId,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    insert('reel_likes', $likeData);
    
    // Increment like count
    executeQuery(
        "UPDATE reels SET like_count = like_count + 1 WHERE id = ?",
        [$reelId],
        "i"
    );
    
    $action = 'liked';
    
    // Create notification for reel owner (if not the current user)
    if ($reel['user_id'] !== $userId) {
        createNotification(
            $reel['user_id'],
            'reel_like',
            $user['name'] . ' a aimé votre reel',
            $userId
        );
    }
}

// Get updated like count
$updatedReel = fetchRow("SELECT like_count FROM reels WHERE id = ?", [$reelId], "i");
$likeCount = $updatedReel ? $updatedReel['like_count'] : 0;

// Return success response
echo json_encode([
    'success' => true,
    'action' => $action,
    'like_count' => $likeCount
]);
exit;