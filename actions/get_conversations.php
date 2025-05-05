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

// Get all conversations
$conversations = fetchAll(
    "SELECT c.*, 
    u1.name as user1_name, u1.profile_pic as user1_pic,
    u2.name as user2_name, u2.profile_pic as user2_pic
    FROM conversations c
    JOIN users u1 ON c.user1_id = u1.id
    JOIN users u2 ON c.user2_id = u2.id
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY c.updated_at DESC",
    [$currentUser['id'], $currentUser['id']],
    "ii"
);

// Process conversations to get the other user's info
$processedConversations = [];
foreach ($conversations as $conv) {
    $otherUserId = ($conv['user1_id'] == $currentUser['id']) ? $conv['user2_id'] : $conv['user1_id'];
    $otherUserName = ($conv['user1_id'] == $currentUser['id']) ? $conv['user2_name'] : $conv['user1_name'];
    $otherUserPic = ($conv['user1_id'] == $currentUser['id']) ? $conv['user2_pic'] : $conv['user1_pic'];
    
    $processedConversations[] = [
        'id' => $conv['id'],
        'other_user_id' => $otherUserId,
        'other_user_name' => $otherUserName,
        'other_user_pic' => $otherUserPic,
        'updated_at' => $conv['updated_at']
    ];
}

echo json_encode([
    'success' => true,
    'conversations' => $processedConversations
]);