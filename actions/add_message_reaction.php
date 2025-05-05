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

// Get message ID and reaction type
$messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
$reactionType = isset($_POST['reaction_type']) ? $_POST['reaction_type'] : 'like';

// Validate reaction type
$validReactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
if (!in_array($reactionType, $validReactions)) {
    echo json_encode(['success' => false, 'message' => 'Type de réaction invalide']);
    exit;
}

// Validate message ID
if ($messageId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de message invalide']);
    exit;
}

// Get message to check if it exists
$message = fetchRow("SELECT * FROM messages WHERE id = ?", [$messageId], "i");

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Message introuvable']);
    exit;
}

// Check if user already has a reaction to this message
$existingReaction = fetchRow(
    "SELECT * FROM message_reactions WHERE message_id = ? AND user_id = ?", 
    [$messageId, $userId], 
    "ii"
);

// Add, update, or remove reaction
if ($existingReaction) {
    // If same reaction type, remove it (toggle off)
    if ($existingReaction['reaction_type'] === $reactionType) {
        $success = delete('message_reactions', 'message_id = ? AND user_id = ?', [$messageId, $userId]);
        $currentReaction = null;
        $added = false;
        $removed = true;
        $changed = false;
    } else {
        // Update to new reaction type
        $success = update(
            'message_reactions',
            ['reaction_type' => $reactionType, 'created_at' => date('Y-m-d H:i:s')],
            'message_id = ? AND user_id = ?',
            [$messageId, $userId]
        );
        $currentReaction = $reactionType;
        $added = false;
        $removed = false;
        $changed = true;
    }
} else {
    // Add new reaction
    $reactionData = [
        'message_id' => $messageId,
        'user_id' => $userId,
        'reaction_type' => $reactionType,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $success = insert('message_reactions', $reactionData) ? true : false;
    $currentReaction = $reactionType;
    $added = true;
    $removed = false;
    $changed = false;
}

// Get updated reaction counts
$reactionCounts = [];
$sql = "SELECT reaction_type, COUNT(*) as count 
        FROM message_reactions 
        WHERE message_id = ? 
        GROUP BY reaction_type";

$results = fetchAll($sql, [$messageId], "i");

// Initialize counts for all reaction types
$counts = [
    'like' => 0,
    'love' => 0,
    'haha' => 0,
    'wow' => 0,
    'sad' => 0,
    'angry' => 0,
    'total' => 0
];

// Update counts from results
foreach ($results as $result) {
    $counts[$result['reaction_type']] = (int)$result['count'];
    $counts['total'] += (int)$result['count'];
}

// If the message sender is not the current user, create notification
if ($added || $changed) {
    if ($message['sender_id'] != $userId) {
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
            $message['sender_id'],
            'message_' . $reactionType,
            $user['name'] . ' a réagi ' . $emoji . ' à votre message',
            $userId,
            $messageId
        );
    }
}

// Return success response with updated reaction status and counts
echo json_encode([
    'success' => true,
    'reaction' => $currentReaction,
    'added' => $added,
    'removed' => $removed,
    'changed' => $changed,
    'counts' => $counts
]);
exit;