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

// Get form data
$conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$message = trim($_POST['message'] ?? '');
$messageType = isset($_POST['message_type']) ? $_POST['message_type'] : 'text';

// Validate conversation or group ID
if ($conversationId <= 0 && $groupId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de conversation ou de groupe invalide']);
    exit;
}

// Prevent sending empty text messages (unless there's media attached)
if ($messageType === 'text' && empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Le message ne peut pas être vide']);
    exit;
}

// Initialize variables
$receiverId = 0;
$mediaUrl = null;

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
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à envoyer des messages dans cette conversation']);
        exit;
    }

    // Determine receiver ID
    $receiverId = $conversation['user1_id'] === $userId ? $conversation['user2_id'] : $conversation['user1_id'];
}
// Handle group conversation
else if ($groupId > 0) {
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

// Handle file uploads based on message type
if ($messageType === 'photo' && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Handle photo upload
    $uploadDir = '../assets/images/messages/';
    $fileName = time() . '_' . basename($_FILES['photo']['name']);
    $uploadFile = $uploadDir . $fileName;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Seules les images sont acceptées.']);
        exit;
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
        $mediaUrl = 'assets/images/messages/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de l\'image']);
        exit;
    }
} 
else if ($messageType === 'voice' && isset($_FILES['voice']) && $_FILES['voice']['error'] === UPLOAD_ERR_OK) {
    // Handle voice upload
    $uploadDir = '../assets/audio/messages/';
    $fileName = time() . '_' . basename($_FILES['voice']['name']);
    $uploadFile = $uploadDir . $fileName;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Check file type
    $allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'];
    if (!in_array($_FILES['voice']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Seuls les fichiers audio sont acceptés.']);
        exit;
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['voice']['tmp_name'], $uploadFile)) {
        $mediaUrl = 'assets/audio/messages/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de l\'audio']);
        exit;
    }
} 
else if ($messageType === 'video' && isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
    // Handle video upload
    $uploadDir = '../assets/videos/messages/';
    $fileName = time() . '_' . basename($_FILES['video']['name']);
    $uploadFile = $uploadDir . $fileName;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Check file type
    $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
    if (!in_array($_FILES['video']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Seuls les fichiers vidéo sont acceptés.']);
        exit;
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['video']['tmp_name'], $uploadFile)) {
        $mediaUrl = 'assets/videos/messages/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement de la vidéo']);
        exit;
    }
}

// Create message data
$messageData = [
    'conversation_id' => $conversationId > 0 ? $conversationId : null,
    'group_id' => $groupId > 0 ? $groupId : null,
    'sender_id' => $userId,
    'receiver_id' => $receiverId,
    'content' => $message,
    'message_type' => $messageType,
    'media_url' => $mediaUrl,
    'is_read' => 0,
    'created_at' => date('Y-m-d H:i:s')
];

// Insert message
$messageId = insert('messages', $messageData);

if (!$messageId) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message']);
    exit;
}

// Update conversation or group with last message ID
if ($conversationId > 0) {
    update('conversations', 
        ['last_message_id' => $messageId, 'updated_at' => date('Y-m-d H:i:s')], 
        'id = ?', 
        [$conversationId]
    );
    
    // Create notification for receiver
    createNotification(
        $receiverId,
        'message',
        $user['name'] . ' vous a envoyé un message',
        $userId
    );
} else if ($groupId > 0) {
    update('group_conversations', 
        ['last_message_id' => $messageId, 'updated_at' => date('Y-m-d H:i:s')], 
        'id = ?', 
        [$groupId]
    );
    
    // Get all group members except sender
    $groupMembers = fetchAll(
        "SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ?",
        [$groupId, $userId],
        "ii"
    );
    
    // Create notification for all group members
    foreach ($groupMembers as $member) {
        createNotification(
            $member['user_id'],
            'group_message',
            $user['name'] . ' a envoyé un message dans le groupe',
            $userId
        );
    }
}

// Return success response with message data
echo json_encode([
    'success' => true,
    'message' => [
        'id' => $messageId,
        'conversation_id' => $conversationId,
        'group_id' => $groupId,
        'sender_id' => $userId,
        'receiver_id' => $receiverId,
        'content' => $message,
        'message_type' => $messageType,
        'media_url' => $mediaUrl,
        'is_read' => 0,
        'time' => date('H:i')
    ]
]);
exit;