<?php
// Get posts for the news feed
$posts = getNewsFeedPosts($user['id']);

if (empty($posts)) {
    echo '<div class="card mb-3">
            <div class="card-body text-center">
                <p class="mb-0">Aucune publication à afficher. Commencez à suivre des amis ou publiez quelque chose!</p>
            </div>
          </div>';
} else {
    foreach ($posts as $post) {
        $likeCount = getLikeCount($post['id']);
        $hasLiked = hasLiked($post['id'], $user['id']);
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
                
                <?php if ($post['user_id'] == $user['id']): ?>
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
                        <?php 
                        // Get reaction counts
                        $reactionCounts = getReactionCounts($post['id']);
                        
                        // Define reaction types and their icons
                        $reactionTypes = [
                            'like' => ['icon' => 'fa-thumbs-up', 'color' => 'primary'],
                            'love' => ['icon' => 'fa-heart', 'color' => 'danger'],
                            'haha' => ['icon' => 'fa-laugh', 'color' => 'warning'],
                            'wow' => ['icon' => 'fa-surprise', 'color' => 'warning'],
                            'sad' => ['icon' => 'fa-sad-tear', 'color' => 'info'],
                            'angry' => ['icon' => 'fa-angry', 'color' => 'danger']
                        ];
                        
                        if ($reactionCounts['total'] > 0): 
                        ?>
                        <div class="reaction-summary">
                            <?php 
                            // Display reaction icons
                            $displayedReactions = 0;
                            foreach ($reactionTypes as $type => $info):
                                if ($reactionCounts[$type] > 0 && $displayedReactions < 3):
                                    $displayedReactions++;
                            ?>
                                <span class="reaction-icon reaction-<?php echo $type; ?>">
                                    <i class="fas <?php echo $info['icon']; ?> text-<?php echo $info['color']; ?>"></i>
                                </span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                            <span class="reaction-count"><?php echo $reactionCounts['total']; ?></span>
                        </div>
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
            
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-around mb-3">
                    <?php 
                    // Get user's current reaction
                    $userReaction = getUserReaction($post['id'], $user['id']);
                    
                    // Define reaction texts
                    $reactionTexts = [
                        'like' => 'J\'aime',
                        'love' => 'J\'adore',
                        'haha' => 'Haha',
                        'wow' => 'Wow',
                        'sad' => 'Triste',
                        'angry' => 'Grrr'
                    ];
                    
                    // Determine which reaction to show on the main button
                    $mainReaction = $userReaction ? $userReaction : 'like';
                    $reactionInfo = $reactionTypes[$mainReaction];
                    $reactionText = $reactionTexts[$mainReaction];
                    ?>
                    
                    <div class="reaction-container flex-fill me-2">
                        <button class="btn btn-light w-100 reaction-button <?php echo $userReaction ? 'reacted reacted-' . $userReaction : ''; ?>" 
                                data-post-id="<?php echo $post['id']; ?>"
                                data-reaction="<?php echo $mainReaction; ?>">
                            <i class="fas <?php echo $reactionInfo['icon']; ?> me-1 text-<?php echo $userReaction ? $reactionInfo['color'] : 'secondary'; ?>"></i> 
                            <?php echo $reactionText; ?>
                        </button>
                        
                        <div class="reaction-selector">
                            <?php foreach ($reactionTypes as $type => $info): ?>
                            <button class="reaction-option" data-reaction="<?php echo $type; ?>" data-post-id="<?php echo $post['id']; ?>" title="<?php echo $reactionTexts[$type]; ?>">
                                <i class="fas <?php echo $info['icon']; ?> text-<?php echo $info['color']; ?>"></i>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
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
                        <div class="d-flex">
                            <img src="<?php echo $user['profile_pic'] ? 'assets/images/profile/' . $user['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
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
        </div>
<?php
    }
}
?>

<div class="text-center mt-3 mb-5">
    <button id="load-more-posts" class="btn btn-outline-primary" data-offset="10">
        Charger plus de publications
    </button>
</div>