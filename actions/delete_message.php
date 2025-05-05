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

// Get message ID
$messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;

if ($messageId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de message invalide']);
    exit;
}

// Get the message
$message = fetchRow(
    "SELECT m.*, c.id as conversation_id, c.last_message_id 
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

// Check if user is the sender of the message
if ($message['sender_id'] != $currentUser['id']) {
    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez supprimer que vos propres messages']);
    exit;
}

// Delete the message
$deleted = executeQuery(
    "DELETE FROM messages WHERE id = ?",
    [$messageId],
    "i"
);

if (!$deleted) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du message']);
    exit;
}

// If this was the last message in the conversation, update the last_message_id
if ($message['last_message_id'] == $messageId) {
    // Find the new last message
    $newLastMessage = fetchRow(
        "SELECT id FROM messages 
        WHERE conversation_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1",
        [$message['conversation_id']],
        "i"
    );
    
    $newLastMessageId = $newLastMessage ? $newLastMessage['id'] : null;
    
    // Update the conversation
    executeQuery(
        "UPDATE conversations SET last_message_id = ? WHERE id = ?",
        [$newLastMessageId, $message['conversation_id']],
        "ii"
    );
}

// Delete any reactions to this message
executeQuery(
    "DELETE FROM message_reactions WHERE message_id = ?",
    [$messageId],
    "i"
);

echo json_encode([
    'success' => true,
    'message' => 'Message supprimé avec succès'
]);