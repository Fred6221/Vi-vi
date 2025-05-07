<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $user = getUserById($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vi-vi - Connectez-vous avec vos amis</title>
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
        <?php if (!$user): ?>
            <!-- Welcome Page for Non-Logged In Users -->
            <div class="row">
                <div class="col-md-6">
                    <div class="welcome-text">
                        <h1>Bienvenue sur Vi-vi</h1>
                        <p class="lead">Connectez-vous avec vos amis, partagez des moments et restez en contact avec le monde qui vous entoure.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title text-center">Connexion</h2>
                            <?php include 'includes/login_form.php'; ?>
                            <hr>
                            <p class="text-center">Pas encore de compte? <a href="register.php">Inscrivez-vous</a></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- News Feed for Logged In Users -->
            <div class="row">
                <div class="col-md-3">
                    <!-- User Profile Card -->
                    <div class="card mb-3">
                        <div class="card-body text-center">
                            <img src="<?php echo $user['profile_pic'] ? 'assets/images/profile/' . $user['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" class="rounded-circle mb-3" width="100" height="100" alt="Profile Picture">
                            <h5 class="card-title"><?php echo htmlspecialchars($user['name']); ?></h5>
                            <p class="card-text"><small class="text-muted"><?php echo htmlspecialchars($user['bio'] ?? 'Aucune bio'); ?></small></p>
                            <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">Voir profil</a>
                        </div>
                    </div>
                    
                    <!-- Left Sidebar Menu -->
                    <div class="list-group mb-3">
                        <a href="index.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-home me-2"></i> Accueil
                        </a>
                        <a href="friends.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-friends me-2"></i> Amis
                        </a>
                        <a href="reels.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-film me-2"></i> Reels
                        </a>
                        <a href="messages.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2"></i> Messages
                        </a>
                        <a href="notifications.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-bell me-2"></i> Notifications
                        </a>
                        <a href="settings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-cog me-2"></i> Paramètres
                        </a>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- Create Post Card -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form action="actions/create_post.php" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="3" placeholder="Quoi de neuf, <?php echo htmlspecialchars($user['name']); ?>?"></textarea>
                                </div>
                                <div id="post-image-preview-container" class="mb-3 d-none">
                                    <img id="post-image-preview" class="img-fluid rounded" alt="Preview">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" id="remove-post-image">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <input type="file" name="image" id="post-image-input" class="d-none" accept="image/*">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2" id="add-post-image">
                                            <i class="fas fa-image"></i> Photo
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" disabled>
                                            <i class="fas fa-video"></i> Vidéo
                                        </button>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Publier</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- News Feed Posts -->
                    <div id="news-feed">
                        <?php include 'includes/posts.php'; ?>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <!-- Right Sidebar - Friend Suggestions -->
                    <div class="card mb-3">
                        <div class="card-header">
                            Suggestions d'amis
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php include 'includes/friend_suggestions.php'; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Trending Topics -->
                    <div class="card">
                        <div class="card-header">
                            Tendances
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2"><a href="#" class="text-decoration-none">#Technologie</a></li>
                                <li class="mb-2"><a href="#" class="text-decoration-none">#Voyage</a></li>
                                <li class="mb-2"><a href="#" class="text-decoration-none">#Musique</a></li>
                                <li class="mb-2"><a href="#" class="text-decoration-none">#Sport</a></li>
                                <li><a href="#" class="text-decoration-none">#Cinéma</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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