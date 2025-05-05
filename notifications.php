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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get notifications
$notifications = getUserNotifications($currentUser['id'], $perPage, $offset);

// Get total count for pagination
$totalCount = fetchRow(
    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?", 
    [$currentUser['id']], 
    "i"
)['count'];

$totalPages = ceil($totalCount / $perPage);

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    executeQuery(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
        [$currentUser['id']],
        "i"
    );
    
    // Redirect to remove query string
    header('Location: notifications.php');
    exit;
}

// Mark specific notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notificationId = (int)$_GET['mark_read'];
    
    markNotificationAsRead($notificationId);
    
    // Redirect to the target if provided
    if (isset($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
        exit;
    }
    
    // Otherwise redirect to notifications page
    header('Location: notifications.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - SocialConnect</title>
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
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Notifications</h5>
                        <?php if (!empty($notifications)): ?>
                            <a href="notifications.php?mark_all_read=1" class="btn btn-sm btn-light">
                                <i class="fas fa-check-double me-1"></i> Tout marquer comme lu
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center p-5">
                                <i class="fas fa-bell fa-4x text-muted mb-3"></i>
                                <h5>Aucune notification</h5>
                                <p class="text-muted">Vous n'avez pas de notifications pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                    <?php
                                    // Determine notification icon and link
                                    $icon = 'fa-bell';
                                    $bgColor = 'bg-primary';
                                    $link = '#';
                                    
                                    switch ($notification['type']) {
                                        case 'like':
                                            $icon = 'fa-thumbs-up';
                                            $bgColor = 'bg-primary';
                                            $link = 'profile.php?id=' . $notification['from_user_id'] . '#post-' . $notification['entity_id'];
                                            break;
                                        case 'comment':
                                            $icon = 'fa-comment';
                                            $bgColor = 'bg-success';
                                            $link = 'profile.php?id=' . $notification['from_user_id'] . '#post-' . $notification['entity_id'];
                                            break;
                                        case 'post':
                                            $icon = 'fa-file-alt';
                                            $bgColor = 'bg-info';
                                            $link = 'profile.php?id=' . $notification['from_user_id'] . '#post-' . $notification['entity_id'];
                                            break;
                                        case 'friend_request':
                                            $icon = 'fa-user-plus';
                                            $bgColor = 'bg-warning';
                                            $link = 'friends.php?tab=requests';
                                            break;
                                        case 'friend_accepted':
                                            $icon = 'fa-user-check';
                                            $bgColor = 'bg-success';
                                            $link = 'profile.php?id=' . $notification['from_user_id'];
                                            break;
                                        case 'message':
                                            $icon = 'fa-envelope';
                                            $bgColor = 'bg-info';
                                            $link = 'messages.php?user=' . $notification['from_user_id'];
                                            break;
                                    }
                                    
                                    // Add mark_read parameter to link
                                    $link .= (strpos($link, '?') !== false ? '&' : '?') . 'mark_read=' . $notification['id'];
                                    ?>
                                    
                                    <a href="<?php echo $link; ?>" class="list-group-item list-group-item-action notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="notification-icon <?php echo $bgColor; ?> me-3">
                                                <i class="fas <?php echo $icon; ?>"></i>
                                            </div>
                                            
                                            <?php if ($notification['from_user_id']): ?>
                                                <img src="<?php echo $notification['profile_pic'] ? 'assets/images/profile/' . $notification['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                                     class="rounded-circle me-3" width="40" height="40" alt="Profile Picture">
                                            <?php endif; ?>
                                            
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div><?php echo $notification['message']; ?></div>
                                                    <small class="text-muted"><?php echo formatDate($notification['created_at']); ?></small>
                                                </div>
                                            </div>
                                            
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge rounded-pill bg-primary ms-2">Nouveau</span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Pagination des notifications" class="mt-3 mb-3">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="notifications.php?page=<?php echo $page - 1; ?>" aria-label="Précédent">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&laquo;</span>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="notifications.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="notifications.php?page=<?php echo $page + 1; ?>" aria-label="Suivant">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&raquo;</span>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
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