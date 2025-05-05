<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $user = getUserById($_SESSION['user_id']);
}

// Check if tables exist and handle errors
$tableError = false;
$errorMessage = '';

// Get reels
$reels = [];
try {
    // Check if the reels table exists
    $checkTable = executeQuery("SHOW TABLES LIKE 'reels'");
    $tablesExist = $checkTable && $checkTable instanceof mysqli_result && $checkTable->num_rows > 0;
    
    if ($tablesExist) {
        if ($user) {
            // Get reels from friends and public reels
            $reels = fetchAll(
                "SELECT r.*, u.name, u.profile_pic,
                (SELECT COUNT(*) FROM reel_likes WHERE reel_id = r.id) as like_count,
                (SELECT COUNT(*) FROM reel_comments WHERE reel_id = r.id) as comment_count,
                (SELECT COUNT(*) > 0 FROM reel_likes WHERE reel_id = r.id AND user_id = ?) as user_liked
                FROM reels r
                JOIN users u ON r.user_id = u.id
                WHERE r.privacy = 'public' 
                OR r.user_id = ?
                OR (r.privacy = 'friends' AND EXISTS (
                    SELECT 1 FROM friendships 
                    WHERE ((user_id = r.user_id AND friend_id = ?) OR (user_id = ? AND friend_id = r.user_id))
                    AND status = 'accepted'
                ))
                ORDER BY r.created_at DESC
                LIMIT 50",
                [$user['id'], $user['id'], $user['id'], $user['id']],
                "iiii"
            );
        } else {
            // Get only public reels for non-logged in users
            $reels = fetchAll(
                "SELECT r.*, u.name, u.profile_pic,
                (SELECT COUNT(*) FROM reel_likes WHERE reel_id = r.id) as like_count,
                (SELECT COUNT(*) FROM reel_comments WHERE reel_id = r.id) as comment_count,
                0 as user_liked
                FROM reels r
                JOIN users u ON r.user_id = u.id
                WHERE r.privacy = 'public'
                ORDER BY r.created_at DESC
                LIMIT 20",
                [],
                ""
            );
        }
    } else {
        $tableError = true;
        $errorMessage = "Les tables nécessaires pour la fonctionnalité Reels n'existent pas encore.";
    }
} catch (Exception $e) {
    $tableError = true;
    $errorMessage = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reels - Vi-vi</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .reel-container {
            max-width: 500px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .reel-video {
            width: 100%;
            max-height: 80vh;
            object-fit: cover;
            background-color: #000;
        }
        
        .reel-info {
            padding: 15px;
            background-color: #fff;
        }
        
        .reel-actions {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            border-top: 1px solid #eee;
            background-color: #fff;
        }
        
        .reel-action-btn {
            background: none;
            border: none;
            color: #555;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px 10px;
        }
        
        .reel-action-btn:hover {
            color: #0d6efd;
        }
        
        .reel-action-btn.liked {
            color: #e74c3c;
        }
        
        .reel-comments {
            max-height: 200px;
            overflow-y: auto;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
        }
        
        .create-reel-btn {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }
        
        .create-reel-btn i {
            font-size: 24px;
        }
        
        @media (max-width: 576px) {
            .reel-container {
                border-radius: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Reels</h2>
        
        <?php if ($tableError): ?>
            <div class="alert alert-warning">
                <h4><i class="fas fa-exclamation-triangle me-2"></i> Configuration requise</h4>
                <p>Les tables nécessaires pour la fonctionnalité Reels n'existent pas encore dans la base de données.</p>
                <p>Veuillez exécuter le script de configuration pour créer les tables nécessaires.</p>
                <a href="setup_database.php" class="btn btn-primary">Configurer la base de données</a>
            </div>
        <?php elseif (empty($reels)): ?>
            <div class="alert alert-info">
                Aucun reel disponible pour le moment.
            </div>
        <?php else: ?>
            <div class="reels-container">
                <?php foreach ($reels as $reel): ?>
                    <div class="reel-container" id="reel-<?php echo $reel['id']; ?>">
                        <div class="reel-header p-3 d-flex align-items-center">
                            <img src="<?php echo $reel['profile_pic'] ? 'assets/images/profile/' . $reel['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                 class="rounded-circle me-2" width="40" height="40" alt="Profile Picture">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($reel['name']); ?></h6>
                                <small class="text-muted"><?php echo formatDate($reel['created_at']); ?></small>
                            </div>
                        </div>
                        
                        <video class="reel-video" controls poster="<?php echo $reel['thumbnail_url'] ? htmlspecialchars($reel['thumbnail_url']) : ''; ?>">
                            <source src="<?php echo htmlspecialchars($reel['video_url']); ?>" type="video/mp4">
                            Votre navigateur ne supporte pas l'élément vidéo.
                        </video>
                        
                        <div class="reel-info">
                            <p><?php echo nl2br(htmlspecialchars($reel['description'] ?? '')); ?></p>
                            <div class="d-flex align-items-center">
                                <span class="me-3"><i class="fas fa-heart text-danger me-1"></i> <?php echo $reel['like_count']; ?></span>
                                <span><i class="fas fa-comment text-primary me-1"></i> <?php echo $reel['comment_count']; ?></span>
                            </div>
                        </div>
                        
                        <?php if ($user): ?>
                            <div class="reel-actions">
                                <button class="reel-action-btn like-btn <?php echo $reel['user_liked'] ? 'liked' : ''; ?>" 
                                        data-reel-id="<?php echo $reel['id']; ?>" 
                                        data-liked="<?php echo $reel['user_liked'] ? '1' : '0'; ?>">
                                    <i class="<?php echo $reel['user_liked'] ? 'fas' : 'far'; ?> fa-heart"></i> J'aime
                                </button>
                                <button class="reel-action-btn comment-btn" data-reel-id="<?php echo $reel['id']; ?>">
                                    <i class="far fa-comment"></i> Commenter
                                </button>
                                <button class="reel-action-btn share-btn" data-reel-id="<?php echo $reel['id']; ?>">
                                    <i class="far fa-share-square"></i> Partager
                                </button>
                            </div>
                            
                            <div class="reel-comments d-none" id="comments-<?php echo $reel['id']; ?>">
                                <div class="comments-container mb-3">
                                    <!-- Comments will be loaded here via AJAX -->
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <form class="comment-form" data-reel-id="<?php echo $reel['id']; ?>">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Écrire un commentaire..." required>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($user): ?>
        <a href="#" class="create-reel-btn" data-bs-toggle="modal" data-bs-target="#createReelModal">
            <i class="fas fa-plus"></i>
        </a>
        
        <!-- Create Reel Modal -->
        <div class="modal fade" id="createReelModal" tabindex="-1" aria-labelledby="createReelModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createReelModalLabel">Créer un Reel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="create-reel-form" action="actions/create_reel.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="reel-video" class="form-label">Vidéo</label>
                                <input type="file" class="form-control" id="reel-video" name="video" accept="video/*" required>
                            </div>
                            <div class="mb-3">
                                <label for="reel-description" class="form-label">Description</label>
                                <textarea class="form-control" id="reel-description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="reel-privacy" class="form-label">Confidentialité</label>
                                <select class="form-select" id="reel-privacy" name="privacy">
                                    <option value="public">Public</option>
                                    <option value="friends">Amis</option>
                                    <option value="private">Privé</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" id="create-reel-btn">Publier</button>
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
    
    <script>
        $(document).ready(function() {
            // Like button click
            $('.like-btn').click(function() {
                const reelId = $(this).data('reel-id');
                const liked = $(this).data('liked') === 1;
                const btn = $(this);
                
                $.ajax({
                    url: 'actions/toggle_reel_like.php',
                    type: 'POST',
                    data: { reel_id: reelId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (liked) {
                                btn.removeClass('liked');
                                btn.find('i').removeClass('fas').addClass('far');
                                btn.data('liked', 0);
                            } else {
                                btn.addClass('liked');
                                btn.find('i').removeClass('far').addClass('fas');
                                btn.data('liked', 1);
                            }
                            
                            // Update like count
                            const likeCountEl = btn.closest('.reel-container').find('.reel-info .fa-heart').parent();
                            likeCountEl.html(`<i class="fas fa-heart text-danger me-1"></i> ${response.like_count}`);
                        }
                    }
                });
            });
            
            // Comment button click
            $('.comment-btn').click(function() {
                const reelId = $(this).data('reel-id');
                const commentsContainer = $(`#comments-${reelId}`);
                
                if (commentsContainer.hasClass('d-none')) {
                    // Load comments
                    $.ajax({
                        url: 'actions/get_reel_comments.php',
                        type: 'GET',
                        data: { reel_id: reelId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                let commentsHtml = '';
                                
                                if (response.comments.length > 0) {
                                    response.comments.forEach(function(comment) {
                                        commentsHtml += `
                                            <div class="comment mb-2">
                                                <div class="d-flex">
                                                    <img src="${comment.profile_pic ? 'assets/images/profile/' + comment.profile_pic : 'assets/images/default-profile.jpg'}" 
                                                         class="rounded-circle me-2" width="30" height="30" alt="Profile Picture">
                                                    <div>
                                                        <h6 class="mb-0 fs-6">${comment.name}</h6>
                                                        <p class="mb-0">${comment.content}</p>
                                                        <small class="text-muted">${comment.time}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                    });
                                } else {
                                    commentsHtml = '<p class="text-center text-muted">Aucun commentaire pour le moment.</p>';
                                }
                                
                                commentsContainer.find('.comments-container').html(commentsHtml);
                            }
                        }
                    });
                    
                    commentsContainer.removeClass('d-none');
                } else {
                    commentsContainer.addClass('d-none');
                }
            });
            
            // Comment form submit
            $('.comment-form').submit(function(e) {
                e.preventDefault();
                
                const form = $(this);
                const reelId = form.data('reel-id');
                const commentInput = form.find('input');
                const comment = commentInput.val().trim();
                
                if (comment) {
                    $.ajax({
                        url: 'actions/add_reel_comment.php',
                        type: 'POST',
                        data: { reel_id: reelId, comment: comment },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Add comment to list
                                const commentHtml = `
                                    <div class="comment mb-2">
                                        <div class="d-flex">
                                            <img src="${response.comment.profile_pic ? 'assets/images/profile/' + response.comment.profile_pic : 'assets/images/default-profile.jpg'}" 
                                                 class="rounded-circle me-2" width="30" height="30" alt="Profile Picture">
                                            <div>
                                                <h6 class="mb-0 fs-6">${response.comment.name}</h6>
                                                <p class="mb-0">${response.comment.content}</p>
                                                <small class="text-muted">${response.comment.time}</small>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                
                                const commentsContainer = $(`#comments-${reelId}`).find('.comments-container');
                                
                                // Remove "no comments" message if it exists
                                if (commentsContainer.find('.text-muted').length > 0) {
                                    commentsContainer.empty();
                                }
                                
                                commentsContainer.append(commentHtml);
                                
                                // Clear input
                                commentInput.val('');
                                
                                // Update comment count
                                const commentCountEl = form.closest('.reel-container').find('.reel-info .fa-comment').parent();
                                commentCountEl.html(`<i class="fas fa-comment text-primary me-1"></i> ${response.comment_count}`);
                            }
                        }
                    });
                }
            });
            
            // Create reel button click
            $('#create-reel-btn').click(function() {
                const form = $('#create-reel-form');
                
                // Submit form via AJAX
                const formData = new FormData(form[0]);
                
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Close modal
                            $('#createReelModal').modal('hide');
                            
                            // Reload page to show new reel
                            window.location.reload();
                        } else {
                            alert(response.message || 'Une erreur est survenue. Veuillez réessayer.');
                        }
                    },
                    error: function() {
                        alert('Une erreur est survenue. Veuillez réessayer.');
                    }
                });
            });
            
            // Auto-play videos when they come into view
            const videos = document.querySelectorAll('.reel-video');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.play();
                    } else {
                        entry.target.pause();
                    }
                });
            }, { threshold: 0.6 });
            
            videos.forEach(video => {
                observer.observe(video);
            });
        });
    </script>
</body>
</html>