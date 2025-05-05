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

// Get post ID and reaction type
$postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$reactionType = isset($_POST['reaction_type']) ? $_POST['reaction_type'] : 'like';

// Validate reaction type
$validReactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
if (!in_array($reactionType, $validReactions)) {
    echo json_encode(['success' => false, 'message' => 'Type de réaction invalide']);
    exit;
}

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

// Add reaction
$previousReaction = getUserReaction($postId, $userId);
$success = addReaction($postId, $userId, $reactionType);

// Get updated reaction counts
$reactionCounts = getReactionCounts($postId);

// Determine if the reaction was added or removed
$currentReaction = getUserReaction($postId, $userId);
$added = $currentReaction === $reactionType;
$removed = $previousReaction && !$currentReaction;
$changed = $previousReaction && $currentReaction && $previousReaction !== $currentReaction;

// If the user added a reaction and is not the post owner, create notification
if (($added || $changed) && $post['user_id'] != $userId) {
    // Get reaction emoji for notification message
    $reactionEmojis = [
        'like' => '👍',
        'love' => '❤️',
        'haha' => '😂',
        'wow' => '😮',
        'sad' => '😢',
        'angry' => '😡'
    ];
    
    $emoji = $reactionEmojis[$reactionType];
    
    createNotification(
        $post['user_id'],
        $reactionType,
        $user['name'] . ' a réagi ' . $emoji . ' à votre publication',
        $userId,
        $postId
    );
}

// Return success response with updated reaction status and counts
echo json_encode([
    'success' => true,
    'reaction' => $currentReaction,
    'added' => $added,
    'removed' => $removed,
    'changed' => $changed,
    'counts' => $reactionCounts
]);
exit;