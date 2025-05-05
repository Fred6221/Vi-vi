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
$callId = isset($_POST['call_id']) ? (int)$_POST['call_id'] : 0;
$response = isset($_POST['response']) ? $_POST['response'] : '';

// Validate call ID
if ($callId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID d\'appel invalide']);
    exit;
}

// Validate response
if ($response !== 'answer' && $response !== 'reject') {
    echo json_encode(['success' => false, 'message' => 'Réponse invalide']);
    exit;
}

// Get call
$call = fetchRow("SELECT * FROM calls WHERE id = ?", [$callId], "i");

if (!$call) {
    echo json_encode(['success' => false, 'message' => 'Appel introuvable']);
    exit;
}

// Check if user is the receiver
if ($call['receiver_id'] !== $userId) {
    echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à répondre à cet appel']);
    exit;
}

// Update call status
$status = $response === 'answer' ? 'answered' : 'rejected';
$updateData = ['status' => $status];

// If call is answered, update the ended_at and duration when the call ends
if ($status === 'answered') {
    // For now, we'll just update the status
    // The ended_at and duration will be updated when the call ends
} else {
    // If call is rejected, update the ended_at to now
    $updateData['ended_at'] = date('Y-m-d H:i:s');
}

update('calls', $updateData, 'id = ?', [$callId]);

// Get caller info
$caller = getUserById($call['caller_id']);

// Return success response with call data
echo json_encode([
    'success' => true,
    'call' => [
        'id' => $call['id'],
        'caller_id' => $call['caller_id'],
        'caller_name' => $caller['name'],
        'caller_pic' => $caller['profile_pic'],
        'call_type' => $call['call_type'],
        'status' => $status,
        'started_at' => $call['started_at']
    ]
]);
exit;