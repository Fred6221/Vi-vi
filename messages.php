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

// Get selected user ID from query string
$selectedUserId = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$selectedUser = null;
$conversation = null;
$messages = [];

// Get all conversations
$conversations = fetchAll(
    "SELECT c.*, 
    u1.name as user1_name, u1.profile_pic as user1_pic,
    u2.name as user2_name, u2.profile_pic as user2_pic,
    m.content as last_message, m.created_at as last_message_time,
    m.sender_id as last_message_sender,
    (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM conversations c
    JOIN users u1 ON c.user1_id = u1.id
    JOIN users u2 ON c.user2_id = u2.id
    LEFT JOIN messages m ON c.last_message_id = m.id
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY m.created_at DESC",
    [$currentUser['id'], $currentUser['id'], $currentUser['id']],
    "iii"
);

// Process conversations to get the other user's info
foreach ($conversations as &$conv) {
    if ($conv['user1_id'] == $currentUser['id']) {
        $conv['other_user_id'] = $conv['user2_id'];
        $conv['other_user_name'] = $conv['user2_name'];
        $conv['other_user_pic'] = $conv['user2_pic'];
    } else {
        $conv['other_user_id'] = $conv['user1_id'];
        $conv['other_user_name'] = $conv['user1_name'];
        $conv['other_user_pic'] = $conv['user1_pic'];
    }
}

// If a user is selected, get or create conversation
if ($selectedUserId > 0) {
    // Check if user exists
    $selectedUser = getUserById($selectedUserId);
    
    if ($selectedUser) {
        // Check if conversation exists
        $conversation = fetchRow(
            "SELECT * FROM conversations 
            WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)",
            [$currentUser['id'], $selectedUserId, $selectedUserId, $currentUser['id']],
            "iiii"
        );
        
        if (!$conversation) {
            // Create new conversation
            $conversationData = [
                'user1_id' => $currentUser['id'],
                'user2_id' => $selectedUserId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $conversationId = insert('conversations', $conversationData);
            
            if ($conversationId) {
                $conversation = [
                    'id' => $conversationId,
                    'user1_id' => $currentUser['id'],
                    'user2_id' => $selectedUserId,
                    'last_message_id' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        // Get messages for this conversation
        if ($conversation) {
            $messages = fetchAll(
                "SELECT m.*, u.name, u.profile_pic 
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC",
                [$conversation['id']],
                "i"
            );
            
            // Mark messages as read
            executeQuery(
                "UPDATE messages 
                SET is_read = 1 
                WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0",
                [$conversation['id'], $currentUser['id']],
                "ii"
            );
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Vi-vi</title>
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
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Messages</h5>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <!-- Conversations List -->
                    <div class="col-md-4 border-end">
                        <div class="conversation-list">
                            <?php if (empty($conversations)): ?>
                                <div class="p-3 text-center text-muted">
                                    Aucune conversation
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <a href="messages.php?user=<?php echo $conv['other_user_id']; ?>" 
                                       class="conversation-item d-flex align-items-center p-3 border-bottom text-decoration-none text-dark
                                              <?php echo $selectedUserId == $conv['other_user_id'] ? 'active' : ''; ?>">
                                        <div class="position-relative">
                                            <img src="<?php echo $conv['other_user_pic'] ? 'assets/images/profile/' . $conv['other_user_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                                 class="rounded-circle me-3" width="50" height="50" alt="Profile Picture">
                                            <?php if ($conv['unread_count'] > 0): ?>
                                                <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger">
                                                    <?php echo $conv['unread_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($conv['other_user_name']); ?></h6>
                                                <?php if ($conv['last_message_time']): ?>
                                                    <small class="text-muted"><?php echo formatDate($conv['last_message_time']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($conv['last_message']): ?>
                                                <p class="mb-0 text-truncate small <?php echo $conv['unread_count'] > 0 && $conv['last_message_sender'] != $currentUser['id'] ? 'fw-bold' : 'text-muted'; ?>">
                                                    <?php if ($conv['last_message_sender'] == $currentUser['id']): ?>
                                                        <i class="fas fa-reply text-muted me-1"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($conv['last_message']); ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="mb-0 small text-muted">Aucun message</p>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Message Thread -->
                    <div class="col-md-8">
                        <?php if ($selectedUser && $conversation): ?>
                            <!-- Message Header -->
                            <div class="p-3 border-bottom d-flex align-items-center">
                                <img src="<?php echo $selectedUser['profile_pic'] ? 'assets/images/profile/' . $selectedUser['profile_pic'] : 'assets/images/default-profile.jpg'; ?>" 
                                     class="rounded-circle me-3" width="40" height="40" alt="Profile Picture">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($selectedUser['name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo $selectedUser['is_active'] ? 'En ligne' : 'Hors ligne'; ?>
                                    </small>
                                </div>
                                <div class="ms-auto d-flex">
                                    <button class="btn btn-outline-success btn-sm me-2" data-bs-toggle="tooltip" title="Appel vocal" onclick="startCall('voice', <?php echo $selectedUser['id']; ?>)">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                    <button class="btn btn-outline-info btn-sm me-2" data-bs-toggle="tooltip" title="Appel vidéo" onclick="startCall('video', <?php echo $selectedUser['id']; ?>)">
                                        <i class="fas fa-video"></i>
                                    </button>
                                    <a href="profile.php?id=<?php echo $selectedUser['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user me-1"></i> Voir profil
                                    </a>
                                </div>
                            </div>
                            
            <!-- Messages -->
            <div class="message-container p-3">
                <?php 
                // Check if there are messages or if this is a new conversation
                $isNewConversation = !empty($conversation) && !$conversation['last_message_id'];
                if (empty($messages) && !$isNewConversation): 
                ?>
                    <div class="text-center text-muted my-5">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <p>Aucun message. Commencez la conversation!</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $prevDate = null;
                    foreach ($messages as $message): 
                        $messageDate = date('Y-m-d', strtotime($message['created_at']));
                        $showDateDivider = $prevDate !== $messageDate;
                        $prevDate = $messageDate;
                        $isSender = $message['sender_id'] == $currentUser['id'];
                    ?>
                        <?php if ($showDateDivider): ?>
                            <div class="text-center my-3">
                                <span class="badge bg-light text-dark">
                                    <?php 
                                    $today = date('Y-m-d');
                                    $yesterday = date('Y-m-d', strtotime('-1 day'));
                                    
                                    if ($messageDate === $today) {
                                        echo 'Aujourd\'hui';
                                    } elseif ($messageDate === $yesterday) {
                                        echo 'Hier';
                                    } else {
                                        echo date('j F Y', strtotime($messageDate));
                                    }
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="message <?php echo $isSender ? 'sent' : 'received'; ?>" data-message-id="<?php echo $message['id']; ?>">
                            <?php if (isset($message['message_type']) && $message['message_type'] == 'photo' && isset($message['media_url'])): ?>
                                <div class="message-content p-0">
                                    <img src="<?php echo htmlspecialchars($message['media_url']); ?>" class="img-fluid rounded message-image" alt="Photo" onclick="showImageModal(this.src)">
                                </div>
                            <?php elseif (isset($message['message_type']) && $message['message_type'] == 'voice' && isset($message['media_url'])): ?>
                                <div class="message-content">
                                    <audio controls class="message-audio">
                                        <source src="<?php echo htmlspecialchars($message['media_url']); ?>" type="audio/mpeg">
                                        Votre navigateur ne supporte pas l'élément audio.
                                    </audio>
                                </div>
                            <?php elseif (isset($message['message_type']) && $message['message_type'] == 'video' && isset($message['media_url'])): ?>
                                <div class="message-content p-0">
                                    <video controls class="message-video">
                                        <source src="<?php echo htmlspecialchars($message['media_url']); ?>" type="video/mp4">
                                        Votre navigateur ne supporte pas l'élément vidéo.
                                    </video>
                                </div>
                            <?php elseif (!empty(trim($message['content']))): ?>
                                <div class="message-content"><?php echo nl2br(htmlspecialchars($message['content'])); ?></div>
                            <?php endif; ?>
                            <div class="message-time">
                                <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                <?php if ($isSender): ?>
                                    <span class="read-receipt ms-1">
                                        <?php if ($message['is_read']): ?>
                                            <span class="read-receipt-read"><i class="fas fa-check"></i><i class="fas fa-check ms-n1"></i></span>
                                        <?php else: ?>
                                            <span class="read-receipt-delivered"><i class="fas fa-check"></i><i class="fas fa-check ms-n1"></i></span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="message-reactions">
                                <div class="reaction-summary"></div>
                                <div class="reaction-buttons">
                                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="like" title="J'aime">👍</button>
                                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="love" title="J'adore">❤️</button>
                                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="haha" title="Haha">😂</button>
                                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="wow" title="Wow">😮</button>
                                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="sad" title="Triste">😢</button>
                                    <button type="button" class="btn btn-sm reaction-btn" data-reaction="angry" title="Grrr">😡</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
                            
                            <!-- Message Input -->
                            <div class="p-3 border-top">
                                <form id="message-form" action="actions/send_message.php" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="conversation_id" value="<?php echo $conversation['id']; ?>">
                                    <input type="hidden" name="message_type" id="message_type" value="text">
                                    <input type="file" id="photo-upload" name="photo" accept="image/*" class="d-none">
                                    <input type="file" id="voice-upload" name="voice" accept="audio/*" class="d-none">
                                    <input type="file" id="video-upload" name="video" accept="video/*" class="d-none">
                                    
                                    <div class="mb-2 d-flex">
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2" id="photo-btn">
                                            <i class="fas fa-image"></i> Photo
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2" id="voice-btn">
                                            <i class="fas fa-microphone"></i> Audio
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2" id="video-btn">
                                            <i class="fas fa-video"></i> Vidéo
                                        </button>
                                    </div>
                                    
                                    <div id="media-preview" class="mb-2 d-none">
                                        <div class="position-relative">
                                            <div id="preview-content"></div>
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" id="remove-media">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="message" id="message-input" placeholder="Écrire un message...">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-5">
                                <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                                <h5>Sélectionnez une conversation</h5>
                                <p class="text-muted">Choisissez une conversation existante ou commencez une nouvelle discussion avec un ami.</p>
                                <div class="mt-3">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                                        <i class="fas fa-users me-2"></i> Créer un groupe
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Context Menu -->
    <div class="message-context-menu" id="messageContextMenu">
        <div class="list-group">
            <button type="button" class="list-group-item list-group-item-action" id="copyMessage">
                <i class="fas fa-copy me-2"></i> Copier
            </button>
            <button type="button" class="list-group-item list-group-item-action" id="forwardMessage">
                <i class="fas fa-share me-2"></i> Transférer
            </button>
            <button type="button" class="list-group-item list-group-item-action" id="replyMessage">
                <i class="fas fa-reply me-2"></i> Répondre
            </button>
            <button type="button" class="list-group-item list-group-item-action text-danger" id="deleteMessage">
                <i class="fas fa-trash me-2"></i> Supprimer
            </button>
        </div>
    </div>
    
    <!-- Forward Message Modal -->
    <div class="modal fade" id="forwardModal" tabindex="-1" aria-labelledby="forwardModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forwardModalLabel">Transférer le message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sélectionner une conversation</label>
                        <div class="forward-conversation-list">
                            <!-- Conversations will be loaded here via AJAX -->
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="forward-btn" disabled>Transférer</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" class="img-fluid" alt="Photo agrandie">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Group Modal -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createGroupModalLabel">Créer un groupe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="create-group-form" action="actions/create_group.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="group-name" class="form-label">Nom du groupe</label>
                            <input type="text" class="form-control" id="group-name" name="group_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="group-description" class="form-label">Description</label>
                            <textarea class="form-control" id="group-description" name="group_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="group-image" class="form-label">Image du groupe</label>
                            <input type="file" class="form-control" id="group-image" name="group_image" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ajouter des membres</label>
                            <div class="friend-selection">
                                <!-- Friends will be loaded here via AJAX -->
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="create-group-btn">Créer</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call Modal -->
    <div class="modal fade" id="callModal" tabindex="-1" aria-labelledby="callModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="callModalLabel">Appel en cours</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="end-call-btn"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="call-status" class="mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Appel en cours...</span>
                        </div>
                        <p id="call-status-text">Appel en cours...</p>
                    </div>
                    <div id="call-user-info" class="mb-4">
                        <img id="call-user-pic" class="rounded-circle mb-3" width="100" height="100" alt="Profile Picture">
                        <h5 id="call-user-name"></h5>
                    </div>
                    <div id="video-container" class="d-none mb-3">
                        <div class="row">
                            <div class="col-8">
                                <video id="remote-video" class="w-100 rounded" autoplay></video>
                            </div>
                            <div class="col-4">
                                <video id="local-video" class="w-100 rounded" autoplay muted></video>
                            </div>
                        </div>
                    </div>
                    <div id="call-controls" class="d-flex justify-content-center">
                        <button type="button" class="btn btn-danger rounded-circle mx-2" id="end-call">
                            <i class="fas fa-phone-slash"></i>
                        </button>
                        <button type="button" class="btn btn-primary rounded-circle mx-2 d-none" id="toggle-mute">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button type="button" class="btn btn-primary rounded-circle mx-2 d-none" id="toggle-video">
                            <i class="fas fa-video"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Incoming Call Modal -->
    <div class="modal fade" id="incomingCallModal" tabindex="-1" aria-labelledby="incomingCallModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="incomingCallModalLabel">Appel entrant</h5>
                </div>
                <div class="modal-body text-center">
                    <div id="incoming-call-user-info" class="mb-4">
                        <img id="incoming-call-user-pic" class="rounded-circle mb-3" width="100" height="100" alt="Profile Picture">
                        <h5 id="incoming-call-user-name"></h5>
                        <p id="incoming-call-type"></p>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-danger rounded-circle mx-3" id="reject-call">
                            <i class="fas fa-phone-slash"></i>
                        </button>
                        <button type="button" class="btn btn-success rounded-circle mx-3" id="answer-call">
                            <i class="fas fa-phone"></i>
                        </button>
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
    
    <script>
        $(document).ready(function() {
            // Variables for long press
            let longPressTimer;
            let selectedMessageId = null;
            let selectedMessageContent = null;
            let isReplying = false;
            let replyToMessageId = null;
            let replyToMessageContent = null;
            
            // Initialize message long press
            initializeMessageLongPress();
            
            // Function to initialize message long press
            function initializeMessageLongPress() {
                // Touch events for mobile
                $(document).on('touchstart', '.message', function(e) {
                    const $message = $(this);
                    longPressTimer = setTimeout(function() {
                        showMessageContextMenu($message, e.originalEvent.touches[0].pageX, e.originalEvent.touches[0].pageY);
                    }, 500);
                });
                
                $(document).on('touchend touchmove', function() {
                    clearTimeout(longPressTimer);
                });
                
                // Mouse events for desktop
                $(document).on('mousedown', '.message', function(e) {
                    const $message = $(this);
                    if (e.which === 3) { // Right click
                        e.preventDefault();
                        showMessageContextMenu($message, e.pageX, e.pageY);
                    } else if (e.which === 1) { // Left click
                        longPressTimer = setTimeout(function() {
                            showMessageContextMenu($message, e.pageX, e.pageY);
                        }, 500);
                    }
                });
                
                $(document).on('mouseup mousemove', function() {
                    clearTimeout(longPressTimer);
                });
                
                // Prevent default context menu
                $(document).on('contextmenu', '.message', function(e) {
                    e.preventDefault();
                });
                
                // Hide context menu when clicking elsewhere
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#messageContextMenu').length) {
                        $('#messageContextMenu').hide();
                    }
                });
                
                // Context menu actions
                $('#copyMessage').click(function() {
                    if (selectedMessageContent) {
                        copyToClipboard(selectedMessageContent);
                        $('#messageContextMenu').hide();
                        showToast('Message copié');
                    }
                });
                
                $('#forwardMessage').click(function() {
                    if (selectedMessageId) {
                        $('#messageContextMenu').hide();
                        loadConversationsForForward();
                        $('#forwardModal').modal('show');
                    }
                });
                
                $('#replyMessage').click(function() {
                    if (selectedMessageId && selectedMessageContent) {
                        $('#messageContextMenu').hide();
                        startReply(selectedMessageId, selectedMessageContent);
                    }
                });
                
                $('#deleteMessage').click(function() {
                    if (selectedMessageId) {
                        $('#messageContextMenu').hide();
                        if (confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
                            deleteMessage(selectedMessageId);
                        }
                    }
                });
                
                // Forward modal
                $('#forward-btn').click(function() {
                    const selectedConversationId = $('input[name="forward_conversation"]:checked').val();
                    if (selectedConversationId && selectedMessageId) {
                        forwardMessage(selectedMessageId, selectedConversationId);
                    }
                });
                
                // Enable/disable forward button based on selection
                $(document).on('change', 'input[name="forward_conversation"]', function() {
                    $('#forward-btn').prop('disabled', !$('input[name="forward_conversation"]:checked').length);
                });
                
                // Cancel reply button
                $(document).on('click', '#cancel-reply', function() {
                    cancelReply();
                });
            }
            
            // Function to show message context menu
            function showMessageContextMenu($message, x, y) {
                selectedMessageId = $message.data('message-id');
                selectedMessageContent = $message.find('.message-content').text().trim();
                
                const $contextMenu = $('#messageContextMenu');
                $contextMenu.css({
                    display: 'block',
                    left: x,
                    top: y
                });
                
                // Adjust position if menu goes off screen
                const menuWidth = $contextMenu.outerWidth();
                const menuHeight = $contextMenu.outerHeight();
                const windowWidth = $(window).width();
                const windowHeight = $(window).height();
                
                if (x + menuWidth > windowWidth) {
                    $contextMenu.css('left', windowWidth - menuWidth - 10);
                }
                
                if (y + menuHeight > windowHeight) {
                    $contextMenu.css('top', windowHeight - menuHeight - 10);
                }
            }
            
            // Function to copy text to clipboard
            function copyToClipboard(text) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
            
            // Function to load conversations for forward
            function loadConversationsForForward() {
                $.ajax({
                    url: 'actions/get_conversations.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let conversationsHtml = '';
                            
                            if (response.conversations.length > 0) {
                                conversationsHtml = '<div class="list-group">';
                                response.conversations.forEach(function(conv) {
                                    conversationsHtml += `
                                        <label class="list-group-item">
                                            <input class="form-check-input me-1" type="radio" name="forward_conversation" value="${conv.id}">
                                            <img src="${conv.other_user_pic ? 'assets/images/profile/' + conv.other_user_pic : 'assets/images/default-profile.jpg'}" 
                                                 class="rounded-circle me-2" width="30" height="30" alt="Profile Picture">
                                            ${conv.other_user_name}
                                        </label>
                                    `;
                                });
                                conversationsHtml += '</div>';
                            } else {
                                conversationsHtml = '<p class="text-center text-muted">Aucune conversation disponible.</p>';
                            }
                            
                            $('.forward-conversation-list').html(conversationsHtml);
                        }
                    },
                    error: function() {
                        $('.forward-conversation-list').html('<p class="text-center text-danger">Erreur lors du chargement des conversations.</p>');
                    }
                });
            }
            
            // Function to forward message
            function forwardMessage(messageId, conversationId) {
                $.ajax({
                    url: 'actions/forward_message.php',
                    type: 'POST',
                    data: {
                        message_id: messageId,
                        conversation_id: conversationId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#forwardModal').modal('hide');
                            showToast('Message transféré');
                        } else {
                            alert(response.message || 'Une erreur est survenue. Veuillez réessayer.');
                        }
                    },
                    error: function() {
                        alert('Une erreur est survenue. Veuillez réessayer.');
                    }
                });
            }
            
            // Function to start reply
            function startReply(messageId, messageContent) {
                isReplying = true;
                replyToMessageId = messageId;
                replyToMessageContent = messageContent;
                
                // Add reply preview above message input
                if (!$('#reply-preview').length) {
                    const replyPreview = `
                        <div id="reply-preview" class="mb-2 p-2 border-start border-primary ps-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Répondre à</small>
                                <button type="button" class="btn btn-sm text-muted p-0" id="cancel-reply">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="text-truncate">${messageContent}</div>
                            <input type="hidden" name="reply_to_message_id" value="${messageId}">
                        </div>
                    `;
                    
                    $('#message-form .input-group').before(replyPreview);
                }
                
                // Focus on message input
                $('#message-input').focus();
            }
            
            // Function to cancel reply
            function cancelReply() {
                isReplying = false;
                replyToMessageId = null;
                replyToMessageContent = null;
                $('#reply-preview').remove();
            }
            
            // Function to delete message
            function deleteMessage(messageId) {
                $.ajax({
                    url: 'actions/delete_message.php',
                    type: 'POST',
                    data: {
                        message_id: messageId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $(`.message[data-message-id="${messageId}"]`).fadeOut(300, function() {
                                $(this).remove();
                            });
                            showToast('Message supprimé');
                        } else {
                            alert(response.message || 'Une erreur est survenue. Veuillez réessayer.');
                        }
                    },
                    error: function() {
                        alert('Une erreur est survenue. Veuillez réessayer.');
                    }
                });
            }
            
            // Function to show toast notification
            function showToast(message) {
                // Create toast if it doesn't exist
                if (!$('#toast-container').length) {
                    $('body').append(`
                        <div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 5">
                            <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-body"></div>
                            </div>
                        </div>
                    `);
                }
                
                // Set message and show toast
                $('#toast .toast-body').text(message);
                const toast = new bootstrap.Toast(document.getElementById('toast'), {
                    delay: 3000
                });
                toast.show();
            }
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
            
            // Scroll to bottom of message container
            const messageContainer = document.querySelector('.message-container');
            if (messageContainer) {
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
            
            // Photo button click
            $('#photo-btn').click(function() {
                $('#photo-upload').click();
            });
            
            // Voice button click
            $('#voice-btn').click(function() {
                $('#voice-upload').click();
            });
            
            // Video button click
            $('#video-btn').click(function() {
                $('#video-upload').click();
            });
            
            // Handle photo upload
            $('#photo-upload').change(function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        $('#message_type').val('photo');
                        $('#preview-content').html(`<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">`);
                        $('#media-preview').removeClass('d-none');
                        $('#message-input').attr('required', false);
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Handle voice upload
            $('#voice-upload').change(function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    $('#message_type').val('voice');
                    $('#preview-content').html(`<div class="p-3"><i class="fas fa-microphone fa-2x"></i> ${file.name}</div>`);
                    $('#media-preview').removeClass('d-none');
                    $('#message-input').attr('required', false);
                }
            });
            
            // Handle video upload
            $('#video-upload').change(function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        $('#message_type').val('video');
                        $('#preview-content').html(`<video src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;"></video>`);
                        $('#media-preview').removeClass('d-none');
                        $('#message-input').attr('required', false);
                    }
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Remove media preview
            $('#remove-media').click(function() {
                $('#message_type').val('text');
                $('#preview-content').html('');
                $('#media-preview').addClass('d-none');
                $('#message-input').attr('required', true);
                $('#photo-upload').val('');
                $('#voice-upload').val('');
                $('#video-upload').val('');
            });
            
            // Submit message form via AJAX
            $('#message-form').submit(function(e) {
                e.preventDefault();
                
                const form = $(this);
                const messageType = $('#message_type').val();
                const messageInput = form.find('#message-input');
                const message = messageInput.val().trim();
                
                // Create FormData object for file uploads
                const formData = new FormData(this);
                
                // We're removing the validation for empty messages as per user request
                // This allows sending empty messages directly
                
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Add message to conversation based on type
                            let messageHtml = '';
                            
                            if (response.message.message_type === 'photo') {
                                messageHtml = `
                                    <div class="message sent">
                                        <div class="message-content p-0">
                                            <img src="${response.message.media_url}" class="img-fluid rounded message-image" alt="Photo" onclick="showImageModal(this.src)">
                                        </div>
                                        <div class="message-time">
                                            ${response.message.time}
                                            <span class="read-receipt ms-1">
                                                <span class="read-receipt-sent"><i class="fas fa-check"></i></span>
                                            </span>
                                        </div>
                                    </div>
                                `;
                            } else if (response.message.message_type === 'voice') {
                                messageHtml = `
                                    <div class="message sent">
                                        <div class="message-content">
                                            <audio controls class="message-audio">
                                                <source src="${response.message.media_url}" type="audio/mpeg">
                                                Votre navigateur ne supporte pas l'élément audio.
                                            </audio>
                                        </div>
                                        <div class="message-time">
                                            ${response.message.time}
                                            <span class="read-receipt ms-1">
                                                <span class="read-receipt-sent"><i class="fas fa-check"></i></span>
                                            </span>
                                        </div>
                                    </div>
                                `;
                            } else if (response.message.message_type === 'video') {
                                messageHtml = `
                                    <div class="message sent">
                                        <div class="message-content p-0">
                                            <video controls class="message-video">
                                                <source src="${response.message.media_url}" type="video/mp4">
                                                Votre navigateur ne supporte pas l'élément vidéo.
                                            </video>
                                        </div>
                                        <div class="message-time">
                                            ${response.message.time}
                                            <span class="read-receipt ms-1">
                                                <span class="read-receipt-sent"><i class="fas fa-check"></i></span>
                                            </span>
                                        </div>
                                    </div>
                                `;
                            } else {
                                messageHtml = `
                                    <div class="message sent">
                                        ${response.message.content.trim() ? `<div class="message-content">${response.message.content.replace(/\n/g, '<br>')}</div>` : ''}
                                        <div class="message-time">
                                            ${response.message.time}
                                            <span class="read-receipt ms-1">
                                                <span class="read-receipt-delivered"><i class="fas fa-check"></i><i class="fas fa-check ms-n1"></i></span>
                                            </span>
                                        </div>
                                        <div class="message-reactions">
                                            <div class="reaction-summary"></div>
                                            <div class="reaction-buttons">
                                                <button type="button" class="btn btn-sm reaction-btn" data-reaction="like" title="J'aime">👍</button>
                                                <button type="button" class="btn btn-sm reaction-btn" data-reaction="love" title="J'adore">❤️</button>
                                                <button type="button" class="btn btn-sm reaction-btn" data-reaction="haha" title="Haha">😂</button>
                                                <button type="button" class="btn btn-sm reaction-btn" data-reaction="wow" title="Wow">😮</button>
                                                <button type="button" class="btn btn-sm reaction-btn" data-reaction="sad" title="Triste">😢</button>
                                                <button type="button" class="btn btn-sm reaction-btn" data-reaction="angry" title="Grrr">😡</button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            $('.message-container').append(messageHtml);
                            
                            // Reset form
                            $('#message_type').val('text');
                            messageInput.val('');
                            $('#preview-content').html('');
                            $('#media-preview').addClass('d-none');
                            $('#photo-upload').val('');
                            $('#voice-upload').val('');
                            $('#video-upload').val('');
                            
                            // Scroll to bottom
                            messageContainer.scrollTop = messageContainer.scrollHeight;
                        }
                    },
                    error: function() {
                        alert('Une erreur est survenue. Veuillez réessayer.');
                    }
                });
            });
            
            // Load friends for group creation
            $('#createGroupModal').on('show.bs.modal', function() {
                $.ajax({
                    url: 'actions/get_friends.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let friendsHtml = '';
                            
                            if (response.friends.length > 0) {
                                friendsHtml = '<div class="list-group">';
                                response.friends.forEach(function(friend) {
                                    friendsHtml += `
                                        <label class="list-group-item">
                                            <input class="form-check-input me-1" type="checkbox" name="friends[]" value="${friend.id}">
                                            <img src="${friend.profile_pic ? 'assets/images/profile/' + friend.profile_pic : 'assets/images/default-profile.jpg'}" 
                                                 class="rounded-circle me-2" width="30" height="30" alt="Profile Picture">
                                            ${friend.name}
                                        </label>
                                    `;
                                });
                                friendsHtml += '</div>';
                            } else {
                                friendsHtml = '<p class="text-center text-muted">Vous n\'avez pas encore d\'amis.</p>';
                            }
                            
                            $('.friend-selection').html(friendsHtml);
                        }
                    },
                    error: function() {
                        $('.friend-selection').html('<p class="text-center text-danger">Erreur lors du chargement des amis.</p>');
                    }
                });
            });
            
            // Create group button click
            $('#create-group-btn').click(function() {
                const form = $('#create-group-form');
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
                            window.location.href = 'messages.php?group=' + response.group_id;
                        } else {
                            alert(response.message || 'Une erreur est survenue. Veuillez réessayer.');
                        }
                    },
                    error: function() {
                        alert('Une erreur est survenue. Veuillez réessayer.');
                    }
                });
            });
            
            // Check for new messages and incoming calls every 5 seconds
            setInterval(function() {
                // Check for new messages
                const conversationId = $('input[name="conversation_id"]').val();
                
                if (conversationId) {
                    $.ajax({
                        url: 'actions/check_new_messages.php',
                        type: 'GET',
                        data: { conversation_id: conversationId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.messages.length > 0) {
                                // Add new messages to conversation
                                response.messages.forEach(function(message) {
                                    let messageHtml = '';
                                    
                                    if (message.message_type === 'photo') {
                                        messageHtml = `
                                            <div class="message received">
                                                <div class="message-content p-0">
                                                    <img src="${message.media_url}" class="img-fluid rounded message-image" alt="Photo" onclick="showImageModal(this.src)">
                                                </div>
                                                <div class="message-time">${message.time}</div>
                                            </div>
                                        `;
                                    } else if (message.message_type === 'voice') {
                                        messageHtml = `
                                            <div class="message received">
                                                <div class="message-content">
                                                    <audio controls class="message-audio">
                                                        <source src="${message.media_url}" type="audio/mpeg">
                                                        Votre navigateur ne supporte pas l'élément audio.
                                                    </audio>
                                                </div>
                                                <div class="message-time">${message.time}</div>
                                            </div>
                                        `;
                                    } else if (message.message_type === 'video') {
                                        messageHtml = `
                                            <div class="message received">
                                                <div class="message-content p-0">
                                                    <video controls class="message-video">
                                                        <source src="${message.media_url}" type="video/mp4">
                                                        Votre navigateur ne supporte pas l'élément vidéo.
                                                    </video>
                                                </div>
                                                <div class="message-time">${message.time}</div>
                                            </div>
                                        `;
                                    } else {
                                        messageHtml = `
                                            <div class="message received" data-message-id="${message.id}">
                                                ${message.content.trim() ? `<div class="message-content">${message.content.replace(/\n/g, '<br>')}</div>` : ''}
                                                <div class="message-time">${message.time}</div>
                                                <div class="message-reactions">
                                                    <div class="reaction-summary"></div>
                                                    <div class="reaction-buttons">
                                                        <button type="button" class="btn btn-sm reaction-btn" data-reaction="like" title="J'aime">👍</button>
                                                        <button type="button" class="btn btn-sm reaction-btn" data-reaction="love" title="J'adore">❤️</button>
                                                        <button type="button" class="btn btn-sm reaction-btn" data-reaction="haha" title="Haha">😂</button>
                                                        <button type="button" class="btn btn-sm reaction-btn" data-reaction="wow" title="Wow">😮</button>
                                                        <button type="button" class="btn btn-sm reaction-btn" data-reaction="sad" title="Triste">😢</button>
                                                        <button type="button" class="btn btn-sm reaction-btn" data-reaction="angry" title="Grrr">😡</button>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                    }
                                    
                                    $('.message-container').append(messageHtml);
                                });
                                
                                // Scroll to bottom
                                messageContainer.scrollTop = messageContainer.scrollHeight;
                            }
                        }
                    });
                }
                
                // Check for incoming calls
                $.ajax({
                    url: 'actions/check_incoming_calls.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.has_incoming_call) {
                            // Show incoming call modal if not already shown
                            if (!$('#incomingCallModal').hasClass('show')) {
                                // Set incoming call info
                                $('#incoming-call-user-name').text(response.call.caller_name);
                                $('#incoming-call-user-pic').attr('src', response.call.caller_pic ? 'assets/images/profile/' + response.call.caller_pic : 'assets/images/default-profile.jpg');
                                $('#incoming-call-type').text(response.call.call_type === 'video' ? 'Appel vidéo' : 'Appel vocal');
                                
                                // Store call ID for later use
                                $('#incomingCallModal').data('callId', response.call.id);
                                
                                // Show incoming call modal
                                new bootstrap.Modal(document.getElementById('incomingCallModal')).show();
                                
                                // Play ringtone
                                const ringtone = new Audio('assets/audio/ringtone.mp3');
                                ringtone.loop = true;
                                ringtone.play();
                                
                                // Store ringtone for later use
                                $('#incomingCallModal').data('ringtone', ringtone);
                            }
                        }
                    }
                });
            }, 5000);
            
            // Answer call button click
            $('#answer-call').click(function() {
                const callId = $('#incomingCallModal').data('callId');
                const ringtone = $('#incomingCallModal').data('ringtone');
                
                // Stop ringtone
                if (ringtone) {
                    ringtone.pause();
                    ringtone.currentTime = 0;
                }
                
                // Hide incoming call modal
                bootstrap.Modal.getInstance(document.getElementById('incomingCallModal')).hide();
                
                // Send response to server
                $.ajax({
                    url: 'actions/respond_to_call.php',
                    type: 'POST',
                    data: {
                        call_id: callId,
                        response: 'answer'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Set call user info
                            $('#call-user-name').text(response.call.caller_name);
                            $('#call-user-pic').attr('src', response.call.caller_pic ? 'assets/images/profile/' + response.call.caller_pic : 'assets/images/default-profile.jpg');
                            
                            // Show appropriate controls based on call type
                            if (response.call.call_type === 'video') {
                                $('#callModalLabel').text('Appel vidéo');
                                $('#video-container').removeClass('d-none');
                                $('#toggle-video').removeClass('d-none');
                            } else {
                                $('#callModalLabel').text('Appel vocal');
                            }
                            
                            $('#toggle-mute').removeClass('d-none');
                            
                            // Show call modal
                            new bootstrap.Modal(document.getElementById('callModal')).show();
                            
                            // Simulate call connection
                            setTimeout(function() {
                                $('#call-status').html('<p class="text-success">Appel connecté</p>');
                                $('#call-status-text').text('Appel en cours...');
                                
                                // Start call timer
                                let seconds = 0;
                                const callTimer = setInterval(function() {
                                    seconds++;
                                    const minutes = Math.floor(seconds / 60);
                                    const remainingSeconds = seconds % 60;
                                    $('#call-status-text').text(`${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`);
                                }, 1000);
                                
                                // Store timer in a data attribute to clear it later
                                $('#callModal').data('callTimer', callTimer);
                            }, 2000);
                        }
                    }
                });
            });
            
            // Reject call button click
            $('#reject-call').click(function() {
                const callId = $('#incomingCallModal').data('callId');
                const ringtone = $('#incomingCallModal').data('ringtone');
                
                // Stop ringtone
                if (ringtone) {
                    ringtone.pause();
                    ringtone.currentTime = 0;
                }
                
                // Hide incoming call modal
                bootstrap.Modal.getInstance(document.getElementById('incomingCallModal')).hide();
                
                // Send response to server
                $.ajax({
                    url: 'actions/respond_to_call.php',
                    type: 'POST',
                    data: {
                        call_id: callId,
                        response: 'reject'
                    },
                    dataType: 'json'
                });
            });
        });
        
        // Function to show image modal
        function showImageModal(src) {
            $('#modalImage').attr('src', src);
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }
        
        // Function to start a call
        function startCall(type, userId) {
            // Set call user info
            $.ajax({
                url: 'actions/get_user_info.php',
                type: 'GET',
                data: { user_id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#call-user-name').text(response.user.name);
                        $('#call-user-pic').attr('src', response.user.profile_pic ? 'assets/images/profile/' + response.user.profile_pic : 'assets/images/default-profile.jpg');
                        
                        // Show appropriate controls based on call type
                        if (type === 'video') {
                            $('#callModalLabel').text('Appel vidéo');
                            $('#video-container').removeClass('d-none');
                            $('#toggle-video').removeClass('d-none');
                        } else {
                            $('#callModalLabel').text('Appel vocal');
                        }
                        
                        $('#toggle-mute').removeClass('d-none');
                        
                        // Show call modal
                        new bootstrap.Modal(document.getElementById('callModal')).show();
                        
                        // Initiate call via AJAX
                        $.ajax({
                            url: 'actions/start_call.php',
                            type: 'POST',
                            data: {
                                receiver_id: userId,
                                call_type: type
                            },
                            dataType: 'json',
                            success: function(callResponse) {
                                if (callResponse.success) {
                                    // Store call ID for later use
                                    $('#callModal').data('callId', callResponse.call.id);
                                    
                                    // Simulate call connection
                                    setTimeout(function() {
                                        $('#call-status').html('<p class="text-success">Appel connecté</p>');
                                        $('#call-status-text').text('Appel en cours...');
                                        
                                        // Start call timer
                                        let seconds = 0;
                                        const callTimer = setInterval(function() {
                                            seconds++;
                                            const minutes = Math.floor(seconds / 60);
                                            const remainingSeconds = seconds % 60;
                                            $('#call-status-text').text(`${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`);
                                        }, 1000);
                                        
                                        // Store timer in a data attribute to clear it later
                                        $('#callModal').data('callTimer', callTimer);
                                    }, 2000);
                                } else {
                                    // Show error message
                                    $('#call-status').html(`<p class="text-danger">${callResponse.message || 'Erreur lors de l\'appel'}</p>`);
                                    
                                    // Hide call modal after a delay
                                    setTimeout(function() {
                                        bootstrap.Modal.getInstance(document.getElementById('callModal')).hide();
                                    }, 3000);
                                }
                            },
                            error: function() {
                                // Show error message
                                $('#call-status').html('<p class="text-danger">Erreur lors de l\'appel</p>');
                                
                                // Hide call modal after a delay
                                setTimeout(function() {
                                    bootstrap.Modal.getInstance(document.getElementById('callModal')).hide();
                                }, 3000);
                            }
                        });
                    }
                }
            });
        }
        
        // End call button click
        $('#end-call, #end-call-btn').click(function() {
            // Clear call timer
            clearInterval($('#callModal').data('callTimer'));
            
            // Hide call modal
            bootstrap.Modal.getInstance(document.getElementById('callModal')).hide();
            
            // Reset call UI
            setTimeout(function() {
                $('#call-status').html(`
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Appel en cours...</span>
                    </div>
                    <p id="call-status-text">Appel en cours...</p>
                `);
                $('#video-container').addClass('d-none');
                $('#toggle-mute').addClass('d-none');
                $('#toggle-video').addClass('d-none');
            }, 500);
        });
    </script>
</body>
</html>