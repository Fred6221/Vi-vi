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

// Get conversation or group ID
$conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

// Validate IDs
if ($conversationId <= 0 && $groupId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de conversation ou de groupe invalide']);
    exit;
}

// Initialize variables
$isGroup = false;

// Handle one-to-one conversation
if ($conversationId > 0) {
    // Get conversation
    $conversation = fetchRow("SELECT * FROM conversations WHERE id = ?", [$conversationId], "i");

    if (!$conversation) {
        echo json_encode(['success' => false, 'message' => 'Conversation introuvable']);
        exit;
    }

    // Check if user is part of the conversation
    if ($conversation['user1_id'] !== $userId && $conversation['user2_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à accéder à cette conversation']);
        exit;
    }
}
// Handle group conversation
else if ($groupId > 0) {
    $isGroup = true;
    
    // Get group
    $group = fetchRow("SELECT * FROM group_conversations WHERE id = ?", [$groupId], "i");

    if (!$group) {
        echo json_encode(['success' => false, 'message' => 'Groupe introuvable']);
        exit;
    }

    // Check if user is a member of the group
    $isMember = fetchRow(
        "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?",
        [$groupId, $userId],
        "ii"
    );

    if (!$isMember) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas membre de ce groupe']);
        exit;
    }
}

// Get last message timestamp from query string (if provided)
$lastTimestamp = isset($_GET['last_timestamp']) ? $_GET['last_timestamp'] : null;

// Get new messages
$params = [];
$types = "";
$sql = "SELECT m.*, u.name, u.profile_pic 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE ";

if ($isGroup) {
    $sql .= "m.group_id = ?";
    $params[] = $groupId;
    $types .= "i";
} else {
    $sql .= "m.conversation_id = ? AND m.receiver_id = ? AND m.is_read = 0";
    $params = [$conversationId, $userId];
    $types = "ii";
}

if ($lastTimestamp) {
    $sql .= " AND m.created_at > ?";
    $params[] = $lastTimestamp;
    $types .= "s";
}

$sql .= " ORDER BY m.created_at ASC";
$newMessages = fetchAll($sql, $params, $types);

// Format messages for response
$formattedMessages = [];
foreach ($newMessages as $message) {
    $formattedMessage = [
        'id' => $message['id'],
        'sender_id' => $message['sender_id'],
        'content' => $message['content'],
        'message_type' => $message['message_type'] ?? 'text',
        'media_url' => $message['media_url'] ?? null,
        'time' => date('H:i', strtotime($message['created_at'])),
        'timestamp' => $message['created_at'],
        'sender_name' => $message['name'],
        'sender_pic' => $message['profile_pic']
    ];
    
    $formattedMessages[] = $formattedMessage;
}

// Mark messages as read
if (!empty($newMessages) && !$isGroup) {
    // For one-to-one conversations, mark messages as read
    executeQuery(
        "UPDATE messages 
        SET is_read = 1 
        WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0",
        [$conversationId, $userId],
        "ii"
    );
} else if (!empty($newMessages) && $isGroup) {
    // For group conversations, mark messages as read for this user
    // This would require a separate table to track read status per user in groups
    // For now, we'll just update the is_read flag for all messages
    // In a real implementation, you would use a message_read_status table
    executeQuery(
        "UPDATE messages 
        SET is_read = 1 
        WHERE group_id = ? AND sender_id != ?",
        [$groupId, $userId],
        "ii"
    );
}

// Return success response with new messages
echo json_encode([
    'success' => true,
    'messages' => $formattedMessages,
    'count' => count($formattedMessages)
]);
exit;