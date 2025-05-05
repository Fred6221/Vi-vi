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
$receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$callType = isset($_POST['call_type']) ? $_POST['call_type'] : 'voice';

// Validate receiver ID
if ($receiverId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de destinataire invalide']);
    exit;
}

// Check if receiver exists
$receiver = getUserById($receiverId);
if (!$receiver) {
    echo json_encode(['success' => false, 'message' => 'Destinataire introuvable']);
    exit;
}

// Create call record
$callData = [
    'caller_id' => $userId,
    'receiver_id' => $receiverId,
    'call_type' => $callType,
    'status' => 'missed', // Default status is missed until answered
    'started_at' => date('Y-m-d H:i:s'),
    'duration' => 0
];

$callId = insert('calls', $callData);

if (!$callId) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'initiation de l\'appel']);
    exit;
}

// Create notification for receiver
createNotification(
    $receiverId,
    'call',
    $user['name'] . ' vous appelle (' . ($callType === 'video' ? 'vidéo' : 'vocal') . ')',
    $userId,
    $callId
);

// Return success response with call data
echo json_encode([
    'success' => true,
    'call' => [
        'id' => $callId,
        'caller_id' => $userId,
        'receiver_id' => $receiverId,
        'call_type' => $callType,
        'status' => 'initiated',
        'started_at' => date('Y-m-d H:i:s')
    ]
]);
exit;