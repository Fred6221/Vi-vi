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

// Get user settings
$settings = fetchRow("SELECT * FROM user_settings WHERE user_id = ?", [$currentUser['id']], "i");

// Initialize success and error messages
$successMessage = '';
$errorMessage = '';

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    // Validate name
    if (empty($name)) {
        $errorMessage = 'Le nom est requis';
    } else {
        // Update profile
        $profileData = [
            'name' => $name,
            'bio' => $bio,
            'location' => $location,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $success = updateUserProfile($currentUser['id'], $profileData);
        
        if ($success) {
            $successMessage = 'Profil mis à jour avec succès';
            // Refresh user data
            $currentUser = getUserById($currentUser['id']);
        } else {
            $errorMessage = 'Erreur lors de la mise à jour du profil';
        }
    }
}

// Handle privacy settings update
if (isset($_POST['update_privacy'])) {
    $privacyProfile = $_POST['privacy_profile'] ?? 'public';
    $privacyPosts = $_POST['privacy_posts'] ?? 'friends';
    $privacyFriends = $_POST['privacy_friends'] ?? 'public';
    
    // Validate privacy settings
    $validOptions = ['public', 'friends', 'private'];
    if (!in_array($privacyProfile, $validOptions) || 
        !in_array($privacyPosts, $validOptions) || 
        !in_array($privacyFriends, $validOptions)) {
        $errorMessage = 'Options de confidentialité invalides';
    } else {
        // Update privacy settings
        $privacyData = [
            'privacy_profile' => $privacyProfile,
            'privacy_posts' => $privacyPosts,
            'privacy_friends' => $privacyFriends,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $success = update('user_settings', $privacyData, 'user_id = ?', [$currentUser['id']]);
        
        if ($success) {
            $successMessage = 'Paramètres de confidentialité mis à jour avec succès';
            // Refresh settings
            $settings = fetchRow("SELECT * FROM user_settings WHERE user_id = ?", [$currentUser['id']], "i");
        } else {
            $errorMessage = 'Erreur lors de la mise à jour des paramètres de confidentialité';
        }
    }
}

// Handle notification settings update
if (isset($_POST['update_notifications'])) {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    
    // Update notification settings
    $notificationData = [
        'email_notifications' => $emailNotifications,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $success = update('user_settings', $notificationData, 'user_id = ?', [$currentUser['id']]);
    
    if ($success) {
        $successMessage = 'Préférences de notification mises à jour avec succès';
        // Refresh settings
        $settings = fetchRow("SELECT * FROM user_settings WHERE user_id = ?", [$currentUser['id']], "i");
    } else {
        $errorMessage = 'Erreur lors de la mise à jour des préférences de notification';
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = 'Tous les champs de mot de passe sont requis';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'Les nouveaux mots de passe ne correspondent pas';
    } elseif (strlen($newPassword) < 6) {
        $errorMessage = 'Le nouveau mot de passe doit contenir au moins 6 caractères';
    } else {
        // Verify current password
        if (password_verify($currentPassword, $currentUser['password'])) {
            // Update password
            $passwordData = [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $success = updateUserProfile($currentUser['id'], $passwordData);
            
            if ($success) {
                $successMessage = 'Mot de passe changé avec succès';
            } else {
                $errorMessage = 'Erreur lors du changement de mot de passe';
            }
        } else {
            $errorMessage = 'Mot de passe actuel incorrect';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - SocialConnect</title>
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
            <!-- Settings Navigation -->
            <div class="col-md-3 mb-4">
                <div class="list-group">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-user me-2"></i> Profil
                    </a>
                    <a href="#privacy" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-lock me-2"></i> Confidentialité
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-bell me-2"></i> Notifications
                    </a>
                    <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-key me-2"></i> Mot de passe
                    </a>
                    <a href="#account" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-user-cog me-2"></i> Compte
                    </a>
                </div>
            </div>
            
            <!-- Settings Content -->
            <div class="col-md-9">
                <!-- Success/Error Messages -->
                <?php if ($successMessage): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $successMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $errorMessage; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="tab-content">
                    <!-- Profile Settings -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Informations du profil</h5>
                            </div>
                            <div class="card-body">
                                <form action="settings.php" method="post">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nom complet</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                                        <div class="form-text">L'email ne peut pas être modifié.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="location" class="form-label">Localisation</label>
                                        <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($currentUser['location'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="birthdate" class="form-label">Date de naissance</label>
                                        <input type="date" class="form-control" id="birthdate" value="<?php echo htmlspecialchars($currentUser['birthdate']); ?>" disabled>
                                        <div class="form-text">La date de naissance ne peut pas être modifiée.</div>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">Enregistrer les modifications</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Privacy Settings -->
                    <div class="tab-pane fade" id="privacy">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Paramètres de confidentialité</h5>
                            </div>
                            <div class="card-body">
                                <form action="settings.php" method="post">
                                    <div class="mb-3">
                                        <label class="form-label">Qui peut voir mon profil?</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="privacy_profile" id="privacy_profile_public" value="public" <?php echo ($settings['privacy_profile'] ?? 'public') === 'public' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="privacy_profile_public">
                                                <i class="fas fa-globe me-1"></i> Public (Tout le monde)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="privacy_profile" id="privacy_profile_friends" value="friends" <?php echo ($settings['privacy_profile'] ?? '') === 'friends' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="privacy_profile_friends">
                                                <i class="fas fa-user-friends me-1"></i> Amis uniquement
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="privacy_profile" id="privacy_profile_private" value="private" <?php echo ($settings['privacy_profile'] ?? '') === 'private' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="privacy_profile_private">
                                                <i class="fas fa-lock me-1"></i> Privé (Moi uniquement)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Qui peut voir mes publications?</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="privacy_posts" id="privacy_posts_public" value="public" <?php echo ($settings['privacy_posts'] ?? '') === 'public' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="privacy_posts_public">
                                                <i class="fas fa-globe me-1"></i> Public (Tout le monde)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="privacy_posts" id="privacy_posts_friends" value="friends" <?php echo ($settings['privacy_posts'] ?? 'friends') === 'friends' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="privacy_posts_friends">
                                                <i class="fas fa-user-friends me-1"></i> Amis uniquement
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="privacy_posts" id="privacy_posts_private" value="private" <?php echo ($settings['privacy_posts'] ?? '') === 'private' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="privacy_posts_private">
                                                <i class="fas fa-lock me-1"></i> Privé (Moi uniquement)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Qui peut voir ma liste d'amis?</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="privacy_friends" id="privacy_friends_public" value="public" <?php echo ($settings['privacy_friends'] ?? 'public') === 'public' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="privacy_friends_public">
                                                <i class="fas fa-globe me-1"></i> Public (Tout le monde)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="privacy_friends" id="privacy_friends_friends" value="friends" <?php echo ($settings['privacy_friends'] ?? '') === 'friends' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="privacy_friends_friends">
                                                <i class="fas fa-user-friends me-1"></i> Amis uniquement
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="privacy_friends" id="privacy_friends_private" value="private" <?php echo ($settings['privacy_friends'] ?? '') === 'private' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="privacy_friends_private">
                                                <i class="fas fa-lock me-1"></i> Privé (Moi uniquement)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_privacy" class="btn btn-primary">Enregistrer les modifications</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notification Settings -->
                    <div class="tab-pane fade" id="notifications">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Préférences de notification</h5>
                            </div>
                            <div class="card-body">
                                <form action="settings.php" method="post">
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="email_notifications" name="email_notifications" <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_notifications">Recevoir des notifications par email</label>
                                    </div>
                                    
                                    <button type="submit" name="update_notifications" class="btn btn-primary">Enregistrer les modifications</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Settings -->
                    <div class="tab-pane fade" id="password">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Changer le mot de passe</h5>
                            </div>
                            <div class="card-body">
                                <form action="settings.php" method="post">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Le mot de passe doit contenir au moins 6 caractères.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-primary">Changer le mot de passe</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Settings -->
                    <div class="tab-pane fade" id="account">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Paramètres du compte</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6>Télécharger mes données</h6>
                                    <p class="text-muted">Téléchargez une copie de vos données personnelles.</p>
                                    <a href="actions/download_data.php" class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Télécharger mes données
                                    </a>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <h6 class="text-danger">Supprimer mon compte</h6>
                                    <p class="text-muted">La suppression de votre compte est définitive et supprimera toutes vos données.</p>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                        <i class="fas fa-trash-alt me-1"></i> Supprimer mon compte
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountModalLabel">Confirmer la suppression du compte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger">Attention! La suppression de votre compte est définitive et ne peut pas être annulée.</p>
                    <p>Toutes vos données personnelles, publications, commentaires et messages seront supprimés.</p>
                    
                    <form action="actions/delete_account.php" method="post" id="delete-account-form">
                        <div class="mb-3">
                            <label for="delete_password" class="form-label">Entrez votre mot de passe pour confirmer</label>
                            <input type="password" class="form-control" id="delete_password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="confirm_delete" name="confirm_delete" required>
                            <label class="form-check-label" for="confirm_delete">Je comprends que cette action est irréversible</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="delete-account-form" class="btn btn-danger">Supprimer mon compte</button>
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
</body>
</html>