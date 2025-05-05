<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current user
$currentUser = getUserById($_SESSION['user_id']);

if (!$currentUser) {
    // Invalid user ID in session
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get user ID from query string (if viewing someone else's friends)
$userId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUser['id'];

// Get user
$user = getUserById($userId);

if (!$user) {
    header('Location: index.php');
    exit;
}

// Check if viewing own friends or someone else's
$isOwnFriends = $currentUser['id'] === $user['id'];

// Get user settings
$settings = fetchRow("SELECT * FROM user_settings WHERE user_id = ?", [$user['id']], "i");

// Check privacy settings
$canViewFriends = true;
if (!$isOwnFriends && $settings) {
    if ($settings['privacy_friends'] === 'private') {
        $canViewFriends = false;
    } elseif ($settings['privacy_friends'] === 'friends') {
        // Check if current user is friends with the profile user
        $friendship = fetchRow(
            "SELECT * FROM friendships WHERE 
            ((user_id = ? AND friend_id = ?) OR 
            (user_id = ? AND friend_id = ?)) AND
            status = 'accepted'", 
            [$currentUser['id'], $user['id'], $user['id'], $currentUser['id']], 
            "iiii"
        );
        
        $canViewFriends = $friendship ? true : false;
    }
}

// Get friends
$friends = getUserFriends($user['id']);

// Get pending friend requests (only for own friends page)
$pendingRequests = [];
if ($isOwnFriends) {
    $pendingRequests = getPendingFriendRequests($currentUser['id']);
}

// Get friend suggestions (only for own friends page)
$friendSuggestions = [];
if ($isOwnFriends) {
    $friendSuggestions = getFriendSuggestions($currentUser['id'], 10);
}

// Handle search
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchResults = [];

if ($isOwnFriends && !empty($searchQuery)) {
    // Search for users
    $searchResults = fetchAll(
        "SELECT id, name, profile_pic FROM users 
        WHERE id != ? AND (name LIKE ? OR email LIKE ?) 
        LIMIT 20",
        [$currentUser['id'], "%$searchQuery%", "%$searchQuery%"]
    );
    
    // Check friendship status for each result
    foreach ($searchResults as &$result) {
        $friendship = fetchRow(
            "SELECT * FROM friendships WHERE 
            (user_id = ? AND friend_id = ?) OR 
            (user_id = ? AND friend_id = ?)", 
            [$currentUser['id'], $result['id'], $result['id'], $currentUser['id']], 
            "iiii"
        );
        
        if ($friendship) {
            $result['friendship_status'] = $friendship['status'];
            $result['is_sender'] = $friendship['user_id'] === $currentUser['id'];
        } else {
            $result['friendship_status'] = null;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amis - SocialConnect</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Left Sidebar -->
            <?php if ($isOwnFriends): ?>
            <div class="col-md-3 mb-4">
                <div class="list-group">
                    <a href="friends.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user-friends me-2"></i> Tous les amis
                    </a>
                    <a href="friends.php?tab=requests" class="list-group-item list-group-item-action <?php echo isset($_GET['tab']) && $_GET['tab'] === 'requests' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus me-2"></i> Demandes d'amis
                        <?php if (count($pendingRequests) > 0): ?>
                            <span class="badge bg-primary rounded-pill"><?php echo count($pendingRequests); ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="friends.php?tab=suggestions" class="list-group-item list-group-item-action <?php echo isset($_GET['tab']) && $_GET['tab'] === 'suggestions' ? 'active' : ''; ?>">
                        <i class="fas fa-user-check me-2"></i> Suggestions
                    </a>
                </div>
                
                <!-- Search Friends -->
                <div class="card mt-3">
                    <div class="card-header">
                        Rechercher des amis
                    </div>
                    <div class="card-body">
                        <form action="friends.php" method="get">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Nom ou email" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Content -->
            <div class="<?php echo $isOwnFriends ? 'col-md-9' : 'col-md-12'; ?>">
                <?php if (!$canViewFriends && !$isOwnFriends): ?>
                    <!-- Privacy Notice -->
                    <div class="alert alert-info text-center">
                        <i class="fas fa-lock me-2"></i> La liste d'amis de <?php echo htmlspecialchars($user['name']); ?> est privée
                    </div>
                <?php elseif ($isOwnFriends && isset($_GET['q']) && !empty($searchQuery)): ?>
                    <!-- Search Results -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Résultats de recherche pour "<?php echo htmlspecialchars($searchQuery); ?>"</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($searchResults)): ?>
                                <p class="text-center">Aucun résultat trouvé</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($searchResults as $result): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body d-flex align-items-center">
                                                    <img src="<?php echo $result['profile_pic'] ? 'assets/images/profile/' . $result['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                                         class="rounded-circle me-3" width="50" height="50" alt="Profile Picture">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <a href="profile.php?id=<?php echo $result['id']; ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($result['name']); ?>
                                                            </a>
                                                        </h6>
                                                        <?php if (!$result['friendship_status']): ?>
                                                            <button class="btn btn-sm btn-primary add-friend" data-user-id="<?php echo $result['id']; ?>">
                                                                <i class="fas fa-user-plus me-1"></i> Ajouter
                                                            </button>
                                                        <?php elseif ($result['friendship_status'] === 'pending'): ?>
                                                            <?php if ($result['is_sender']): ?>
                                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                                    <i class="fas fa-clock me-1"></i> Demande envoyée
                                                                </button>
                                                            <?php else: ?>
                                                                <div class="btn-group">
                                                                    <button class="btn btn-sm btn-primary accept-friend" data-user-id="<?php echo $result['id']; ?>">
                                                                        <i class="fas fa-check me-1"></i> Accepter
                                                                    </button>
                                                                    <button class="btn btn-sm btn-outline-secondary reject-friend" data-user-id="<?php echo $result['id']; ?>">
                                                                        <i class="fas fa-times me-1"></i> Refuser
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php elseif ($result['friendship_status'] === 'accepted'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-user-check me-1"></i> Amis
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($isOwnFriends && isset($_GET['tab']) && $_GET['tab'] === 'requests'): ?>
                    <!-- Friend Requests Tab -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Demandes d'amis en attente</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingRequests)): ?>
                                <p class="text-center">Aucune demande d'ami en attente</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($pendingRequests as $request): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 friend-request-item">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <img src="<?php echo $request['profile_pic'] ? 'assets/images/profile/' . $request['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                                             class="rounded-circle me-3" width="60" height="60" alt="Profile Picture">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                <a href="profile.php?id=<?php echo $request['id']; ?>" class="text-decoration-none">
                                                                    <?php echo htmlspecialchars($request['name']); ?>
                                                                </a>
                                                            </h6>
                                                            <div class="text-muted small">
                                                                Demande envoyée <?php echo formatDate($request['created_at']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-grid gap-2">
                                                        <button class="btn btn-primary accept-friend" data-user-id="<?php echo $request['id']; ?>">
                                                            <i class="fas fa-check me-1"></i> Accepter
                                                        </button>
                                                        <button class="btn btn-outline-secondary reject-friend" data-user-id="<?php echo $request['id']; ?>">
                                                            <i class="fas fa-times me-1"></i> Refuser
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($isOwnFriends && isset($_GET['tab']) && $_GET['tab'] === 'suggestions'): ?>
                    <!-- Friend Suggestions Tab -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Suggestions d'amis</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($friendSuggestions)): ?>
                                <p class="text-center">Aucune suggestion d'ami pour le moment</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($friendSuggestions as $suggestion): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <img src="<?php echo $suggestion['profile_pic'] ? 'assets/images/profile/' . $suggestion['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                                             class="rounded-circle me-3" width="60" height="60" alt="Profile Picture">
                                                        <div>
                                                            <h6 class="mb-1">
                                                                <a href="profile.php?id=<?php echo $suggestion['id']; ?>" class="text-decoration-none">
                                                                    <?php echo htmlspecialchars($suggestion['name']); ?>
                                                                </a>
                                                            </h6>
                                                        </div>
                                                    </div>
                                                    <div class="d-grid">
                                                        <button class="btn btn-primary add-friend" data-user-id="<?php echo $suggestion['id']; ?>">
                                                            <i class="fas fa-user-plus me-1"></i> Ajouter en ami
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Friends List -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?php echo $isOwnFriends ? 'Mes amis' : 'Amis de ' . htmlspecialchars($user['name']); ?>
                                <span class="badge bg-primary ms-2"><?php echo count($friends); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($friends)): ?>
                                <p class="text-center">
                                    <?php echo $isOwnFriends ? 'Vous n\'avez pas encore d\'amis' : htmlspecialchars($user['name']) . ' n\'a pas encore d\'amis'; ?>
                                </p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($friends as $friend): ?>
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card h-100 friend-card">
                                                <img src="<?php echo $friend['profile_pic'] ? 'assets/images/profile/' . $friend['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                                     class="card-img-top" alt="Profile Picture">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">
                                                        <a href="profile.php?id=<?php echo $friend['id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($friend['name']); ?>
                                                        </a>
                                                    </h5>
                                                    <div class="d-grid gap-2">
                                                        <a href="profile.php?id=<?php echo $friend['id']; ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-user me-1"></i> Voir profil
                                                        </a>
                                                        <?php if ($isOwnFriends): ?>
                                                            <a href="messages.php?user=<?php echo $friend['id']; ?>" class="btn btn-outline-primary">
                                                                <i class="fas fa-envelope me-1"></i> Message
                                                            </a>
                                                            <button class="btn btn-outline-danger remove-friend" data-user-id="<?php echo $friend['id']; ?>">
                                                                <i class="fas fa-user-times me-1"></i> Retirer
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir retirer cette personne de votre liste d'amis?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmRemoveFriend">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
    
    <script>
        // Remove friend confirmation
        let friendToRemove = null;
        
        $(document).ready(function() {
            // Handle remove friend button click
            $('.remove-friend').click(function() {
                friendToRemove = $(this).data('user-id');
                $('#confirmModal').modal('show');
            });
            
            // Handle confirm remove friend button click
            $('#confirmRemoveFriend').click(function() {
                if (friendToRemove) {
                    // Send AJAX request to remove friend
                    $.ajax({
                        url: 'actions/remove_friend.php',
                        type: 'POST',
                        data: { user_id: friendToRemove },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Reload page to update friend list
                                location.reload();
                            } else {
                                alert(response.message || 'Une erreur est survenue. Veuillez réessayer.');
                            }
                        },
                        error: function() {
                            alert('Une erreur est survenue. Veuillez réessayer.');
                        }
                    });
                }
                
                $('#confirmModal').modal('hide');
            });
        });
    </script>
</body>
</html>