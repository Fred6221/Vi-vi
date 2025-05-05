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
$currentUser = getUserById($userId);

if (!$currentUser) {
    // Invalid user ID in session
    echo json_encode(['success' => false, 'message' => 'Utilisateur invalide']);
    exit;
}

// Get requested user ID
$requestedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($requestedUserId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit;
}

// Get requested user
$user = getUserById($requestedUserId);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
    exit;
}

// Check if users are friends
$areFriends = areFriends($userId, $requestedUserId);

if (!$areFriends && $requestedUserId !== $userId) {
    echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas ami avec cet utilisateur']);
    exit;
}

// Return user info
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'profile_pic' => $user['profile_pic'],
        'is_active' => $user['is_active'] ?? false
    ]
]);
exit;

// Helper function to check if users are friends
function areFriends($user1Id, $user2Id) {
    $friendship = fetchRow(
        "SELECT * FROM friendships 
        WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) 
        AND status = 'accepted'",
        [$user1Id, $user2Id, $user2Id, $user1Id],
        "iiii"
    );
    
    return $friendship !== null;
}