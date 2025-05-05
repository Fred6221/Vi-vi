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

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Get form data
$groupName = trim($_POST['group_name'] ?? '');
$groupDescription = trim($_POST['group_description'] ?? '');
$selectedFriends = isset($_POST['friends']) ? $_POST['friends'] : [];

// Validate group name
if (empty($groupName)) {
    echo json_encode(['success' => false, 'message' => 'Le nom du groupe ne peut pas être vide']);
    exit;
}

// Validate selected friends
if (empty($selectedFriends) || !is_array($selectedFriends)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez sélectionner au moins un ami']);
    exit;
}

// Handle group image upload
$groupImage = null;
if (isset($_FILES['group_image']) && $_FILES['group_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../assets/images/groups/';
    $fileName = time() . '_' . basename($_FILES['group_image']['name']);
    $uploadFile = $uploadDir . $fileName;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($_FILES['group_image']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Seules les images sont acceptées.']);
        exit;
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['group_image']['tmp_name'], $uploadFile)) {
        $groupImage = 'assets/images/groups/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de l\'image']);
        exit;
    }
}

// Create group
$groupData = [
    'name' => $groupName,
    'creator_id' => $userId,
    'description' => $groupDescription,
    'image' => $groupImage,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$groupId = insert('group_conversations', $groupData);

if (!$groupId) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du groupe']);
    exit;
}

// Add creator as admin
$creatorMemberData = [
    'group_id' => $groupId,
    'user_id' => $userId,
    'role' => 'admin',
    'joined_at' => date('Y-m-d H:i:s')
];

insert('group_members', $creatorMemberData);

// Add selected friends as members
foreach ($selectedFriends as $friendId) {
    $friendId = (int)$friendId;
    
    // Verify friendship
    $areFriends = areFriends($userId, $friendId);
    
    if ($areFriends) {
        $memberData = [
            'group_id' => $groupId,
            'user_id' => $friendId,
            'role' => 'member',
            'joined_at' => date('Y-m-d H:i:s')
        ];
        
        insert('group_members', $memberData);
        
        // Create notification for the friend
        createNotification(
            $friendId,
            'group_invite',
            $currentUser['name'] . ' vous a ajouté au groupe "' . $groupName . '"',
            $userId
        );
    }
}

// Create welcome message
$welcomeMessageData = [
    'group_id' => $groupId,
    'sender_id' => $userId,
    'content' => 'Bienvenue dans le groupe "' . $groupName . '"!',
    'message_type' => 'text',
    'is_read' => 0,
    'created_at' => date('Y-m-d H:i:s')
];

$messageId = insert('messages', $welcomeMessageData);

// Update group with last message ID
if ($messageId) {
    update('group_conversations', 
        ['last_message_id' => $messageId], 
        'id = ?', 
        [$groupId]
    );
}

// Return success response
echo json_encode([
    'success' => true,
    'group_id' => $groupId,
    'message' => 'Groupe créé avec succès'
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