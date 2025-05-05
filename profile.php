<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $currentUser = getUserById($_SESSION['user_id']);
}

// Get profile user ID
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : ($currentUser ? $currentUser['id'] : 0);

// Get profile user
$profileUser = getUserById($profileId);

// If profile doesn't exist, redirect to home
if (!$profileUser) {
    header('Location: index.php');
    exit;
}

// Check if current user is viewing their own profile
$isOwnProfile = $currentUser && $currentUser['id'] === $profileUser['id'];

// Get friendship status if not own profile
$friendshipStatus = null;
if ($currentUser && !$isOwnProfile) {
    $friendship = fetchRow(
        "SELECT * FROM friendships WHERE 
        (user_id = ? AND friend_id = ?) OR 
        (user_id = ? AND friend_id = ?)", 
        [$currentUser['id'], $profileUser['id'], $profileUser['id'], $currentUser['id']], 
        "iiii"
    );
    
    if ($friendship) {
        $friendshipStatus = $friendship['status'];
        $isSender = $friendship['user_id'] === $currentUser['id'];
    }
}

// Get user's posts
$posts = getUserPosts($profileUser['id']);

// Get user's friends
$friends = getUserFriends($profileUser['id']);

// Get user settings
$settings = fetchRow("SELECT * FROM user_settings WHERE user_id = ?", [$profileUser['id']], "i");

// Check privacy settings
$canViewProfile = true;
$canViewPosts = true;
$canViewFriends = true;

if ($settings) {
    if (!$isOwnProfile && $settings['privacy_profile'] !== 'public') {
        if ($settings['privacy_profile'] === 'private' || 
            ($settings['privacy_profile'] === 'friends' && (!$friendshipStatus || $friendshipStatus !== 'accepted'))) {
            $canViewProfile = false;
        }
    }
    
    if (!$isOwnProfile && $settings['privacy_posts'] !== 'public') {
        if ($settings['privacy_posts'] === 'private' || 
            ($settings['privacy_posts'] === 'friends' && (!$friendshipStatus || $friendshipStatus !== 'accepted'))) {
            $canViewPosts = false;
        }
    }
    
    if (!$isOwnProfile && $settings['privacy_friends'] !== 'public') {
        if ($settings['privacy_friends'] === 'private' || 
            ($settings['privacy_friends'] === 'friends' && (!$friendshipStatus || $friendshipStatus !== 'accepted'))) {
            $canViewFriends = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profileUser['name']); ?> - SocialConnect</title>
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
        <?php if (!$canViewProfile && !$isOwnProfile): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-lock me-2"></i> Ce profil est privé
            </div>
        <?php else: ?>
            <!-- Profile Header -->
            <div class="profile-header position-relative mb-4">
                <!-- Cover Photo -->
                <div class="cover-photo position-relative" style="height: 200px; background-color: #4267B2; background-image: url('<?php echo $profileUser['cover_pic'] ? 'assets/images/covers/' . $profileUser['cover_pic'] : ''; ?>'); background-size: cover; background-position: center; border-radius: 8px;">
                    <?php if ($isOwnProfile): ?>
                        <button class="btn btn-light btn-sm position-absolute bottom-0 end-0 m-3" data-bs-toggle="modal" data-bs-target="#coverPhotoModal">
                            <i class="fas fa-camera me-1"></i> Modifier
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Picture -->
                <div class="profile-picture">
                    <img src="<?php echo $profileUser['profile_pic'] ? 'assets/images/profile/' . $profileUser['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" alt="Profile Picture">
                    <?php if ($isOwnProfile): ?>
                        <div class="edit-icon" data-bs-toggle="modal" data-bs-target="#profilePicModal">
                            <i class="fas fa-camera"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Info -->
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($profileUser['name']); ?></h1>
                    
                    <?php if ($profileUser['bio']): ?>
                        <p class="profile-bio"><?php echo nl2br(htmlspecialchars($profileUser['bio'])); ?></p>
                    <?php elseif ($isOwnProfile): ?>
                        <p class="profile-bio text-muted">
                            <a href="settings.php" class="text-decoration-none">Ajouter une bio</a>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Profile Stats -->
                    <div class="profile-stats">
                        <div class="stat">
                            <div class="stat-value"><?php echo count($posts); ?></div>
                            <div class="stat-label">Publications</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value"><?php echo count($friends); ?></div>
                            <div class="stat-label">Amis</div>
                        </div>
                    </div>
                    
                    <!-- Profile Actions -->
                    <div class="profile-actions mb-3">
                        <?php if ($isOwnProfile): ?>
                            <a href="settings.php" class="btn btn-outline-primary">
                                <i class="fas fa-cog me-1"></i> Modifier le profil
                            </a>
                        <?php else: ?>
                            <?php if (!$friendshipStatus): ?>
                                <button class="btn btn-primary add-friend" data-user-id="<?php echo $profileUser['id']; ?>">
                                    <i class="fas fa-user-plus me-1"></i> Ajouter en ami
                                </button>
                            <?php elseif ($friendshipStatus === 'pending'): ?>
                                <?php if ($isSender): ?>
                                    <button class="btn btn-outline-secondary" disabled>
                                        <i class="fas fa-clock me-1"></i> Demande envoyée
                                    </button>
                                <?php else: ?>
                                    <div class="btn-group">
                                        <button class="btn btn-primary accept-friend" data-user-id="<?php echo $profileUser['id']; ?>">
                                            <i class="fas fa-check me-1"></i> Accepter
                                        </button>
                                        <button class="btn btn-outline-secondary reject-friend" data-user-id="<?php echo $profileUser['id']; ?>">
                                            <i class="fas fa-times me-1"></i> Refuser
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($friendshipStatus === 'accepted'): ?>
                                <button class="btn btn-outline-primary" disabled>
                                    <i class="fas fa-user-check me-1"></i> Amis
                                </button>
                            <?php endif; ?>
                            
                            <a href="messages.php?user=<?php echo $profileUser['id']; ?>" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-envelope me-1"></i> Message
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Additional Info -->
                    <?php if ($profileUser['location'] || $profileUser['birthdate']): ?>
                        <div class="profile-details text-center mb-3">
                            <?php if ($profileUser['location']): ?>
                                <div class="mb-1">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($profileUser['location']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($profileUser['birthdate']): ?>
                                <div>
                                    <i class="fas fa-birthday-cake me-2 text-muted"></i>
                                    <?php echo date('j F Y', strtotime($profileUser['birthdate'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row">
                <!-- Left Column - Friends -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Amis</h5>
                            <a href="friends.php?id=<?php echo $profileUser['id']; ?>" class="text-decoration-none">Voir tout</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!$canViewFriends && !$isOwnProfile): ?>
                                <div class="p-3 text-center text-muted">
                                    <i class="fas fa-lock me-2"></i> Liste d'amis privée
                                </div>
                            <?php elseif (empty($friends)): ?>
                                <div class="p-3 text-center text-muted">
                                    Aucun ami à afficher
                                </div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php 
                                    $displayFriends = array_slice($friends, 0, 6);
                                    foreach ($displayFriends as $friend): 
                                    ?>
                                        <li class="list-group-item">
                                            <a href="profile.php?id=<?php echo $friend['id']; ?>" class="d-flex align-items-center text-decoration-none text-dark">
                                                <img src="<?php echo $friend['profile_pic'] ? 'assets/images/profile/' . $friend['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                                     class="rounded-circle me-2" width="40" height="40" alt="Profile Picture">
                                                <span><?php echo htmlspecialchars($friend['name']); ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Posts -->
                <div class="col-md-8">
                    <?php if ($isOwnProfile): ?>
                        <!-- Create Post Card (only on own profile) -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <form action="actions/create_post.php" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <textarea class="form-control" name="content" rows="3" placeholder="Quoi de neuf, <?php echo htmlspecialchars($profileUser['name']); ?>?"></textarea>
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
                    <?php endif; ?>
                    
                    <!-- Posts -->
                    <?php if (!$canViewPosts && !$isOwnProfile): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-lock me-2"></i> Les publications de cet utilisateur sont privées
                        </div>
                    <?php elseif (empty($posts)): ?>
                        <div class="card">
                            <div class="card-body text-center">
                                <p class="mb-0">Aucune publication à afficher</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div id="profile-posts">
                            <?php foreach ($posts as $post): ?>
                                <?php
                                $likeCount = getLikeCount($post['id']);
                                $hasLiked = $currentUser ? hasLiked($post['id'], $currentUser['id']) : false;
                                $comments = getPostComments($post['id']);
                                $commentCount = count($comments);
                                ?>
                                <div class="card mb-3 post" id="post-<?php echo $post['id']; ?>">
                                    <div class="card-header bg-transparent d-flex align-items-center">
                                        <img src="<?php echo $post['profile_pic'] ? 'assets/images/profile/' . $post['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                             class="rounded-circle me-2" width="40" height="40" alt="Profile Picture">
                                        <div>
                                            <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars($post['name']); ?>
                                            </a>
                                            <div class="text-muted small">
                                                <?php echo formatDate($post['created_at']); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($isOwnProfile): ?>
                                        <div class="dropdown ms-auto">
                                            <button class="btn btn-sm text-muted" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="edit_post.php?id=<?php echo $post['id']; ?>">
                                                        <i class="fas fa-edit me-2"></i> Modifier
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger delete-post" href="#" data-post-id="<?php echo $post['id']; ?>">
                                                        <i class="fas fa-trash-alt me-2"></i> Supprimer
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body">
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                        
                                        <?php if (!empty($post['image'])): ?>
                                        <div class="post-image mb-3">
                                            <img src="assets/images/posts/<?php echo $post['image']; ?>" class="img-fluid rounded" alt="Post Image">
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <?php if ($likeCount > 0): ?>
                                                <span class="text-muted">
                                                    <i class="fas fa-thumbs-up text-primary"></i> <?php echo $likeCount; ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <?php if ($commentCount > 0): ?>
                                                <span class="text-muted">
                                                    <?php echo $commentCount; ?> commentaire<?php echo $commentCount > 1 ? 's' : ''; ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($currentUser): ?>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-around mb-3">
                                            <button class="btn btn-light flex-fill me-2 like-button <?php echo $hasLiked ? 'liked' : ''; ?>" 
                                                    data-post-id="<?php echo $post['id']; ?>">
                                                <i class="fas <?php echo $hasLiked ? 'fa-thumbs-up' : 'fa-thumbs-up'; ?> me-1"></i> 
                                                J'aime
                                            </button>
                                            <button class="btn btn-light flex-fill comment-toggle">
                                                <i class="fas fa-comment me-1"></i> Commenter
                                            </button>
                                        </div>
                                        
                                        <div class="comments-section">
                                            <?php if ($commentCount > 0): ?>
                                            <hr>
                                            <div class="comments-list">
                                                <?php foreach ($comments as $comment): ?>
                                                <div class="d-flex mb-3">
                                                    <img src="<?php echo $comment['profile_pic'] ? 'assets/images/profile/' . $comment['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                                         class="rounded-circle me-2" width="32" height="32" alt="Profile Picture">
                                                    <div class="comment-bubble">
                                                        <div class="fw-bold">
                                                            <a href="profile.php?id=<?php echo $comment['user_id']; ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($comment['name']); ?>
                                                            </a>
                                                        </div>
                                                        <div><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
                                                        <div class="text-muted small"><?php echo formatDate($comment['created_at']); ?></div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <form class="comment-form mt-3" action="actions/add_comment.php" method="post">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <input type="hidden" name="redirect" value="profile.php?id=<?php echo $profileUser['id']; ?>">
                                                <div class="d-flex">
                                                    <img src="<?php echo $currentUser['profile_pic'] ? 'assets/images/profile/' . $currentUser['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                                         class="rounded-circle me-2" width="32" height="32" alt="Profile Picture">
                                                    <div class="flex-grow-1">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="content" placeholder="Écrire un commentaire..." required>
                                                            <button class="btn btn-primary" type="submit">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-3 mb-5">
                            <button id="load-more-profile-posts" class="btn btn-outline-primary" data-user-id="<?php echo $profileUser['id']; ?>" data-offset="10">
                                Charger plus de publications
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Profile Picture Modal -->
    <?php if ($isOwnProfile): ?>
    <div class="modal fade" id="profilePicModal" tabindex="-1" aria-labelledby="profilePicModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profilePicModalLabel">Modifier la photo de profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="actions/update_profile_pic.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profile-pic-input" class="form-label">Choisir une image</label>
                            <input class="form-control" type="file" id="profile-pic-input" name="profile_pic" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <img id="profile-pic-preview" class="img-fluid rounded d-none" alt="Preview">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Cover Photo Modal -->
    <div class="modal fade" id="coverPhotoModal" tabindex="-1" aria-labelledby="coverPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="coverPhotoModalLabel">Modifier la photo de couverture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="actions/update_cover_pic.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="cover-pic-input" class="form-label">Choisir une image</label>
                            <input class="form-control" type="file" id="cover-pic-input" name="cover_pic" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <img id="cover-pic-preview" class="img-fluid rounded d-none" alt="Preview">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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