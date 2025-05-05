<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-comments me-2"></i>
            Vi-vi
        </a>
        
        <?php if (isset($user)): ?>
            <!-- Navbar for logged in users -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Search Form -->
                <form class="d-flex mx-auto my-2 my-lg-0" action="search.php" method="get">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Rechercher..." aria-label="Search">
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <!-- Home -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home"></i>
                            <span class="d-lg-none ms-2">Accueil</span>
                        </a>
                    </li>
                    
                    <!-- Friends -->
                    <?php 
                    $friendRequestCount = getPendingFriendRequestCount($user['id']);
                    $hasFriendRequests = $friendRequestCount > 0;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'friends.php' ? 'active' : ''; ?>" href="friends.php">
                            <?php if ($hasFriendRequests): ?>
                                <span class="badge rounded-pill bg-danger">
                                    <?php echo $friendRequestCount > 9 ? '9+' : $friendRequestCount; ?>
                                </span>
                            <?php endif; ?>
                            <i class="fas fa-user-friends"></i>
                            <span class="d-lg-none ms-2">Amis</span>
                        </a>
                    </li>
                    
                    <!-- Reels -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reels.php' ? 'active' : ''; ?>" href="reels.php">
                            <i class="fas fa-film"></i>
                            <span class="d-lg-none ms-2">Reels</span>
                        </a>
                    </li>
                    
                    <!-- Messages -->
                    <?php 
                    $messageCount = getUnreadMessageCount($user['id']);
                    $hasMessages = $messageCount > 0;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" href="messages.php">
                            <?php if ($hasMessages): ?>
                                <span class="badge rounded-pill bg-danger">
                                    <?php echo $messageCount > 9 ? '9+' : $messageCount; ?>
                                </span>
                            <?php endif; ?>
                            <i class="fas fa-envelope"></i>
                            <span class="d-lg-none ms-2">Messages</span>
                        </a>
                    </li>
                    
                    <!-- Notifications -->
                    <?php 
                    $notificationCount = getUnreadNotificationCount($user['id']);
                    $hasNotifications = $notificationCount > 0;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                            <?php if ($hasNotifications): ?>
                                <span class="badge rounded-pill bg-danger">
                                    <?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?>
                                </span>
                            <?php endif; ?>
                            <i class="fas fa-bell"></i>
                            <span class="d-lg-none ms-2">Notifications</span>
                        </a>
                    </li>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $user['profile_pic'] ? 'assets/images/profile/' . $user['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" class="rounded-circle me-1" width="24" height="24" alt="Profile">
                            <span class="d-lg-none ms-2"><?php echo htmlspecialchars($user['name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item" href="profile.php?id=<?php echo $user['id']; ?>">
                                    <i class="fas fa-user me-2"></i> Mon profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog me-2"></i> Paramètres
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="actions/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php else: ?>
            <!-- Navbar for guests with feature icons -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Search Form (disabled for guests) -->
                <div class="d-flex mx-auto my-2 my-lg-0">
                    <div class="input-group">
                        <input class="form-control" type="search" placeholder="Rechercher..." aria-label="Search" disabled>
                        <button class="btn btn-light" type="button" onclick="window.location.href='login.php'">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <!-- Home -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php" title="Connectez-vous pour accéder à l'accueil">
                            <i class="fas fa-home"></i>
                            <span class="d-lg-none ms-2">Accueil</span>
                        </a>
                    </li>
                    
                    <!-- Friends -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php" title="Connectez-vous pour accéder à vos amis">
                            <i class="fas fa-user-friends"></i>
                            <span class="d-lg-none ms-2">Amis</span>
                        </a>
                    </li>
                    
                    <!-- Reels -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php" title="Connectez-vous pour accéder aux reels">
                            <i class="fas fa-film"></i>
                            <span class="d-lg-none ms-2">Reels</span>
                        </a>
                    </li>
                    
                    <!-- Messages -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php" title="Connectez-vous pour accéder à vos messages">
                            <i class="fas fa-envelope"></i>
                            <span class="d-lg-none ms-2">Messages</span>
                        </a>
                    </li>
                    
                    <!-- Notifications -->
                    <li class="nav-item">
                        <a class="nav-link" href="login.php" title="Connectez-vous pour accéder à vos notifications">
                            <i class="fas fa-bell"></i>
                            <span class="d-lg-none ms-2">Notifications</span>
                        </a>
                    </li>
                    
                    <!-- Login/Register -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i>
                            <span class="d-lg-none ms-2">Compte</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item" href="login.php">
                                    <i class="fas fa-sign-in-alt me-2"></i> Connexion
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="register.php">
                                    <i class="fas fa-user-plus me-2"></i> Inscription
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>