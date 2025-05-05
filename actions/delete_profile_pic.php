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

// Get profile picture ID
$pictureId = isset($_POST['picture_id']) ? (int)$_POST['picture_id'] : 0;

if ($pictureId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de photo de profil invalide']);
    exit;
}

// Get profile picture
$picture = fetchRow(
    "SELECT * FROM profile_pictures WHERE id = ?",
    [$pictureId],
    "i"
);

if (!$picture) {
    echo json_encode(['success' => false, 'message' => 'Photo de profil introuvable']);
    exit;
}

// Check if user has permission to delete this profile picture
if ($picture['user_id'] !== $userId) {
    echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de supprimer cette photo de profil']);
    exit;
}

// Check if this is the active profile picture
if ($picture['is_active']) {
    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre photo de profil actuelle. Veuillez d\'abord changer de photo de profil.']);
    exit;
}

// Start transaction
global $conn;
$conn->begin_transaction();

try {
    // Mark profile picture as deleted
    $stmt = $conn->prepare("UPDATE profile_pictures SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $pictureId);
    $success = $stmt->execute();
    
    if ($success) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Photo de profil supprimée avec succès']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la photo de profil']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la photo de profil: ' . $e->getMessage()]);
}
exit;