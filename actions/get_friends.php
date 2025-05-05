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

// Get user's friends
$friends = fetchAll(
    "SELECT u.id, u.name, u.profile_pic, u.is_active
    FROM users u
    JOIN friendships f ON (u.id = f.friend_id OR u.id = f.user_id)
    WHERE ((f.user_id = ? AND f.friend_id = u.id) OR (f.friend_id = ? AND f.user_id = u.id))
    AND f.status = 'accepted'
    ORDER BY u.name ASC",
    [$userId, $userId],
    "ii"
);

// Process friends to ensure we only have the other user's info
$processedFriends = [];
foreach ($friends as $friend) {
    if ($friend['id'] != $userId) {
        $processedFriends[] = [
            'id' => $friend['id'],
            'name' => $friend['name'],
            'profile_pic' => $friend['profile_pic'],
            'is_active' => $friend['is_active'] ?? false
        ];
    }
}

// Return friends list
echo json_encode([
    'success' => true,
    'friends' => $processedFriends
]);
exit;