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

// Check if request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Get recent incoming calls (within the last 30 seconds)
$recentCalls = fetchAll(
    "SELECT c.*, u.name, u.profile_pic 
    FROM calls c
    JOIN users u ON c.caller_id = u.id
    WHERE c.receiver_id = ? 
    AND c.status = 'missed' 
    AND c.started_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
    ORDER BY c.started_at DESC
    LIMIT 1",
    [$userId],
    "i"
);

if (empty($recentCalls)) {
    // No incoming calls
    echo json_encode(['success' => true, 'has_incoming_call' => false]);
    exit;
}

// Get the most recent call
$call = $recentCalls[0];

// Return call information
echo json_encode([
    'success' => true,
    'has_incoming_call' => true,
    'call' => [
        'id' => $call['id'],
        'caller_id' => $call['caller_id'],
        'caller_name' => $call['name'],
        'caller_pic' => $call['profile_pic'],
        'call_type' => $call['call_type'],
        'started_at' => $call['started_at']
    ]
]);
exit;