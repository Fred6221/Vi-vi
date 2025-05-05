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

// Get friend ID
$friendId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// Validate friend ID
if ($friendId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID d\'utilisateur invalide']);
    exit;
}

// Check if friend exists
$friend = getUserById($friendId);

if (!$friend) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
    exit;
}

// Check if friend request exists
$friendRequest = fetchRow(
    "SELECT * FROM friendships WHERE user_id = ? AND friend_id = ? AND status = 'pending'", 
    [$friendId, $userId], 
    "ii"
);

if (!$friendRequest) {
    echo json_encode(['success' => false, 'message' => 'Aucune demande d\'ami en attente de cet utilisateur']);
    exit;
}

// Accept friend request
$success = acceptFriendRequest($userId, $friendId);

if ($success) {
    // Create notification for sender
    createNotification(
        $friendId,
        'friend_accepted',
        $user['name'] . ' a accepté votre demande d\'ami',
        $userId
    );
    
    // Return success response
    echo json_encode(['success' => true, 'message' => 'Demande d\'ami acceptée avec succès']);
} else {
    // Return error response
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'acceptation de la demande d\'ami']);
}
exit;