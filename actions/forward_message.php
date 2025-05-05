<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

$currentUser = getUserById($_SESSION['user_id']);

if (!$currentUser) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur invalide']);
    exit;
}

// Get message ID and target conversation ID
$messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
$conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;

if ($messageId <= 0 || $conversationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Get the original message
$message = fetchRow(
    "SELECT m.*, c.user1_id, c.user2_id 
    FROM messages m
    JOIN conversations c ON m.conversation_id = c.id
    WHERE m.id = ?",
    [$messageId],
    "i"
);

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Message introuvable']);
    exit;
}

// Check if user has access to the original message
if ($message['user1_id'] != $currentUser['id'] && $message['user2_id'] != $currentUser['id']) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé au message']);
    exit;
}

// Get the target conversation
$conversation = fetchRow(
    "SELECT * FROM conversations WHERE id = ?",
    [$conversationId],
    "i"
);

if (!$conversation) {
    echo json_encode(['success' => false, 'message' => 'Conversation introuvable']);
    exit;
}

// Check if user has access to the target conversation
if ($conversation['user1_id'] != $currentUser['id'] && $conversation['user2_id'] != $currentUser['id']) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé à la conversation']);
    exit;
}

// Determine the receiver ID
$receiverId = ($conversation['user1_id'] == $currentUser['id']) ? $conversation['user2_id'] : $conversation['user1_id'];

// Create the forwarded message
$forwardedMessage = [
    'conversation_id' => $conversationId,
    'sender_id' => $currentUser['id'],
    'receiver_id' => $receiverId,
    'content' => $message['content'],
    'message_type' => $message['message_type'],
    'media_url' => $message['media_url'],
    'is_read' => 0,
    'created_at' => date('Y-m-d H:i:s')
];

// Insert the forwarded message
$newMessageId = insert('messages', $forwardedMessage);

if (!$newMessageId) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du transfert du message']);
    exit;
}

// Update the conversation's last message ID and timestamp
executeQuery(
    "UPDATE conversations SET last_message_id = ?, updated_at = ? WHERE id = ?",
    [$newMessageId, date('Y-m-d H:i:s'), $conversationId],
    "isi"
);

// Create notification for the receiver
$notification = [
    'user_id' => $receiverId,
    'type' => 'message',
    'reference_id' => $newMessageId,
    'message' => $currentUser['name'] . ' vous a envoyé un message',
    'is_read' => 0,
    'created_at' => date('Y-m-d H:i:s')
];

insert('notifications', $notification);

echo json_encode([
    'success' => true,
    'message' => 'Message transféré avec succès',
    'message_id' => $newMessageId
]);