<?php if (isset($user)): ?>
<!-- Mobile Navigation Footer (visible only on small screens) -->
<div class="mobile-nav-footer d-md-none">
    <div class="container">
        <div class="row">
            <div class="col text-center">
                <a href="index.php" class="mobile-nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <div class="mobile-nav-text">Accueil</div>
                </a>
            </div>
            <div class="col text-center">
                <?php 
                $friendRequestCount = getPendingFriendRequestCount($user['id']);
                $hasFriendRequests = $friendRequestCount > 0;
                ?>
                <a href="friends.php" class="mobile-nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'friends.php' ? 'active' : ''; ?>">
                    <?php if ($hasFriendRequests): ?>
                        <span class="badge rounded-pill bg-danger">
                            <?php echo $friendRequestCount > 9 ? '9+' : $friendRequestCount; ?>
                        </span>
                    <?php endif; ?>
                    <i class="fas fa-user-friends"></i>
                    <div class="mobile-nav-text">Amis</div>
                </a>
            </div>
            <div class="col text-center">
                <a href="reels.php" class="mobile-nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'reels.php' ? 'active' : ''; ?>">
                    <i class="fas fa-film"></i>
                    <div class="mobile-nav-text">Reels</div>
                </a>
            </div>
            <div class="col text-center">
                <?php 
                $messageCount = getUnreadMessageCount($user['id']);
                $hasMessages = $messageCount > 0;
                ?>
                <a href="messages.php" class="mobile-nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                    <?php if ($hasMessages): ?>
                        <span class="badge rounded-pill bg-danger">
                            <?php echo $messageCount > 9 ? '9+' : $messageCount; ?>
                        </span>
                    <?php endif; ?>
                    <i class="fas fa-envelope"></i>
                    <div class="mobile-nav-text">Messages</div>
                </a>
            </div>
            <div class="col text-center">
                <?php 
                $notificationCount = getUnreadNotificationCount($user['id']);
                $hasNotifications = $notificationCount > 0;
                ?>
                <a href="notifications.php" class="mobile-nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                    <?php if ($hasNotifications): ?>
                        <span class="badge rounded-pill bg-danger">
                            <?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?>
                        </span>
                    <?php endif; ?>
                    <i class="fas fa-bell"></i>
                    <div class="mobile-nav-text">Notifications</div>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<footer class="bg-light mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <h5 class="mb-3">Vi-vi</h5>
                <p class="text-muted">Connectez-vous avec vos amis, partagez des moments et restez en contact avec le monde qui vous entoure.</p>
                <div class="social-links">
                    <a href="#" class="text-decoration-none me-2">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-decoration-none me-2">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-decoration-none me-2">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-decoration-none">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-md-2 mb-3 mb-md-0">
                <h6 class="mb-3">Liens</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" class="text-decoration-none text-muted">Accueil</a></li>
                    <li class="mb-2"><a href="about.php" class="text-decoration-none text-muted">À propos</a></li>
                    <li class="mb-2"><a href="contact.php" class="text-decoration-none text-muted">Contact</a></li>
                    <li><a href="faq.php" class="text-decoration-none text-muted">FAQ</a></li>
                </ul>
            </div>
            
            <div class="col-md-2 mb-3 mb-md-0">
                <h6 class="mb-3">Légal</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="terms.php" class="text-decoration-none text-muted">Conditions d'utilisation</a></li>
                    <li class="mb-2"><a href="privacy.php" class="text-decoration-none text-muted">Politique de confidentialité</a></li>
                    <li><a href="cookies.php" class="text-decoration-none text-muted">Politique de cookies</a></li>
                </ul>
            </div>
            
            <div class="col-md-4">
                <h6 class="mb-3">Inscrivez-vous à notre newsletter</h6>
                <form action="actions/subscribe_newsletter.php" method="post">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Votre email" aria-label="Email" name="email" required>
                        <button class="btn btn-primary" type="submit">S'inscrire</button>
                    </div>
                </form>
                <p class="text-muted small">Recevez les dernières nouvelles et mises à jour.</p>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-12 text-center">
                <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> Vi-vi. Tous droits réservés.</p>
            </div>
        </div>
    </div>
</footer>