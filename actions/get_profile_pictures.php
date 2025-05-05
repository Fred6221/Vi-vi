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
$currentUserId = $_SESSION['user_id'];
$currentUser = getUserById($currentUserId);

if (!$currentUser) {
    // Invalid user ID in session
    echo json_encode(['success' => false, 'message' => 'Utilisateur invalide']);
    exit;
}

// Get target user ID (default to current user if not specified)
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentUserId;

// Check if the user has permission to view this user's profile pictures
// Only allow viewing other users' profile pictures if they're friends or the profile is public
if ($userId !== $currentUserId) {
    $targetUser = getUserById($userId);
    
    if (!$targetUser) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
        exit;
    }
    
    // Check friendship status
    $friendship = fetchRow(
        "SELECT * FROM friendships WHERE 
        (user_id = ? AND friend_id = ?) OR 
        (user_id = ? AND friend_id = ?)", 
        [$currentUserId, $userId, $userId, $currentUserId], 
        "iiii"
    );
    
    $isFriend = $friendship && $friendship['status'] === 'accepted';
    
    // Get user settings
    $settings = fetchRow("SELECT * FROM user_settings WHERE user_id = ?", [$userId], "i");
    
    // Check if user can view profile
    $canViewProfile = true;
    if ($settings && $settings['privacy_profile'] !== 'public') {
        if ($settings['privacy_profile'] === 'private' || 
            ($settings['privacy_profile'] === 'friends' && !$isFriend)) {
            $canViewProfile = false;
        }
    }
    
    if (!$canViewProfile) {
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de voir les photos de profil de cet utilisateur']);
        exit;
    }
}

// Get profile pictures
$profilePictures = fetchAll(
    "SELECT * FROM profile_pictures 
    WHERE user_id = ? AND is_deleted = 0
    ORDER BY upload_date DESC",
    [$userId],
    "i"
);

// Format the results
$formattedPictures = [];
foreach ($profilePictures as $picture) {
    $formattedPictures[] = [
        'id' => $picture['id'],
        'filename' => $picture['filename'],
        'url' => 'assets/images/profile/' . $picture['filename'],
        'upload_date' => formatDate($picture['upload_date']),
        'is_active' => (bool)$picture['is_active'],
        'can_delete' => $userId === $currentUserId // Only the owner can delete their profile pictures
    ];
}

// Return the results
echo json_encode([
    'success' => true,
    'user_id' => $userId,
    'is_current_user' => $userId === $currentUserId,
    'profile_pictures' => $formattedPictures
]);
exit;