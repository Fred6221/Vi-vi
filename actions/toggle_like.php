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

// Get post ID
$postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

// Validate post ID
if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de publication invalide']);
    exit;
}

// Get post to check if it exists and get owner
$post = fetchRow("SELECT * FROM posts WHERE id = ?", [$postId], "i");

if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Publication introuvable']);
    exit;
}

// Toggle like (using the new reaction system)
$previousReaction = getUserReaction($postId, $userId);
$success = toggleLike($postId, $userId); // This now calls addReaction with 'like' type

// Get updated reaction counts
$reactionCounts = getReactionCounts($postId);
$likeCount = $reactionCounts['total']; // For backward compatibility

// Determine if the post is now liked
$currentReaction = getUserReaction($postId, $userId);
$liked = $currentReaction === 'like';

// If the user liked the post and is not the post owner, create notification
if ($liked && !$previousReaction && $post['user_id'] != $userId) {
    createNotification(
        $post['user_id'],
        'like',
        $user['name'] . ' a aimé votre publication',
        $userId,
        $postId
    );
}

// Return success response with updated like status and count
// This maintains the same response format for backward compatibility
echo json_encode([
    'success' => true,
    'liked' => $liked,
    'count' => $likeCount
]);
exit;