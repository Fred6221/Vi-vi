<?php
// Define the root directory if not already defined
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

// Include database configuration with absolute path
require_once ROOT_DIR . '/config/database.php';

/**
 * Get user by ID
 * 
 * @param int $userId User ID
 * @return array|null User data or null if not found
 */
function getUserById($userId) {
    return fetchRow("SELECT * FROM users WHERE id = ?", [$userId], "i");
}

/**
 * Get user by email
 * 
 * @param string $email User email
 * @return array|null User data or null if not found
 */
function getUserByEmail($email) {
    return fetchRow("SELECT * FROM users WHERE email = ?", [$email]);
}

/**
 * Authenticate user
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array|false User data or false if authentication fails
 */
function authenticateUser($email, $password) {
    $user = getUserByEmail($email);
    
    if (!$user) {
        return false;
    }
    
    if (password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

/**
 * Register a new user
 * 
 * @param string $name User's full name
 * @param string $email User's email
 * @param string $password User's password
 * @param string $birthdate User's birthdate (YYYY-MM-DD)
 * @param string $gender User's gender
 * @return int|false User ID or false if registration fails
 */
function registerUser($name, $email, $password, $birthdate, $gender) {
    // Check if email already exists
    if (getUserByEmail($email)) {
        return false;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $userData = [
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword,
        'birthdate' => $birthdate,
        'gender' => $gender,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    return insert('users', $userData);
}

/**
 * Update user profile
 * 
 * @param int $userId User ID
 * @param array $data Profile data to update
 * @return bool True on success, false on failure
 */
function updateUserProfile($userId, $data) {
    return update('users', $data, 'id = ?', [$userId]);
}

/**
 * Get posts for news feed
 * 
 * @param int $userId User ID
 * @param int $limit Number of posts to retrieve
 * @param int $offset Offset for pagination
 * @return array Array of posts
 */
function getNewsFeedPosts($userId, $limit = 10, $offset = 0) {
    $sql = "SELECT p.*, u.name, u.profile_pic 
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ? OR p.user_id IN (
                SELECT friend_id FROM friendships 
                WHERE user_id = ? AND status = 'accepted'
            )
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";
    
    return fetchAll($sql, [$userId, $userId, $limit, $offset], "iiis");
}

/**
 * Get user's posts
 * 
 * @param int $userId User ID
 * @param int $limit Number of posts to retrieve
 * @param int $offset Offset for pagination
 * @return array Array of posts
 */
function getUserPosts($userId, $limit = 10, $offset = 0) {
    $sql = "SELECT p.*, u.name, u.profile_pic 
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";
    
    return fetchAll($sql, [$userId, $limit, $offset], "iis");
}

/**
 * Create a new post
 * 
 * @param int $userId User ID
 * @param string $content Post content
 * @param string $image Post image (optional)
 * @return int|false Post ID or false if creation fails
 */
function createPost($userId, $content, $image = null) {
    $postData = [
        'user_id' => $userId,
        'content' => $content,
        'image' => $image,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    return insert('posts', $postData);
}

/**
 * Get comments for a post
 * 
 * @param int $postId Post ID
 * @return array Array of comments
 */
function getPostComments($postId) {
    $sql = "SELECT c.*, u.name, u.profile_pic 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC";
    
    return fetchAll($sql, [$postId], "i");
}

/**
 * Add a comment to a post
 * 
 * @param int $postId Post ID
 * @param int $userId User ID
 * @param string $content Comment content
 * @return int|false Comment ID or false if creation fails
 */
function addComment($postId, $userId, $content) {
    $commentData = [
        'post_id' => $postId,
        'user_id' => $userId,
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    return insert('comments', $commentData);
}

/**
 * Add or update a reaction to a post
 * 
 * @param int $postId Post ID
 * @param int $userId User ID
 * @param string $reactionType Reaction type (like, love, haha, wow, sad, angry)
 * @return bool True on success, false on failure
 */
function addReaction($postId, $userId, $reactionType = 'like') {
    // Check if user already has a reaction
    $existingReaction = fetchRow("SELECT * FROM reactions WHERE post_id = ? AND user_id = ?", [$postId, $userId], "ii");
    
    if ($existingReaction) {
        // If same reaction type, remove it (toggle off)
        if ($existingReaction['reaction_type'] === $reactionType) {
            return delete('reactions', 'post_id = ? AND user_id = ?', [$postId, $userId]);
        } else {
            // Update to new reaction type
            return update(
                'reactions',
                ['reaction_type' => $reactionType, 'created_at' => date('Y-m-d H:i:s')],
                'post_id = ? AND user_id = ?',
                [$postId, $userId]
            );
        }
    } else {
        // Add new reaction
        $reactionData = [
            'post_id' => $postId,
            'user_id' => $userId,
            'reaction_type' => $reactionType,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return insert('reactions', $reactionData) ? true : false;
    }
}

/**
 * For backward compatibility - toggles a like reaction
 * 
 * @param int $postId Post ID
 * @param int $userId User ID
 * @return bool True if liked, false if unliked
 */
function toggleLike($postId, $userId) {
    return addReaction($postId, $userId, 'like');
}

/**
 * Check if user has reacted to a post
 * 
 * @param int $postId Post ID
 * @param int $userId User ID
 * @param string|null $reactionType Specific reaction type to check for, or null for any reaction
 * @return string|bool Reaction type if reacted, false otherwise
 */
function getUserReaction($postId, $userId) {
    $reaction = fetchRow("SELECT reaction_type FROM reactions WHERE post_id = ? AND user_id = ?", [$postId, $userId], "ii");
    return $reaction ? $reaction['reaction_type'] : false;
}

/**
 * For backward compatibility - check if user has liked a post
 * 
 * @param int $postId Post ID
 * @param int $userId User ID
 * @return bool True if liked, false otherwise
 */
function hasLiked($postId, $userId) {
    $reaction = getUserReaction($postId, $userId);
    return $reaction === 'like';
}

/**
 * Get reaction counts for a post
 * 
 * @param int $postId Post ID
 * @return array Counts for each reaction type
 */
function getReactionCounts($postId) {
    $sql = "SELECT reaction_type, COUNT(*) as count 
            FROM reactions 
            WHERE post_id = ? 
            GROUP BY reaction_type";
    
    $results = fetchAll($sql, [$postId], "i");
    
    // Initialize counts for all reaction types
    $counts = [
        'like' => 0,
        'love' => 0,
        'haha' => 0,
        'wow' => 0,
        'sad' => 0,
        'angry' => 0,
        'total' => 0
    ];
    
    // Update counts from results
    foreach ($results as $result) {
        $counts[$result['reaction_type']] = (int)$result['count'];
        $counts['total'] += (int)$result['count'];
    }
    
    return $counts;
}

/**
 * For backward compatibility - get total reaction count for a post
 * 
 * @param int $postId Post ID
 * @return int Number of reactions
 */
function getLikeCount($postId) {
    $counts = getReactionCounts($postId);
    return $counts['total'];
}

/**
 * Get friend suggestions for a user
 * 
 * @param int $userId User ID
 * @param int $limit Number of suggestions to retrieve
 * @return array Array of user suggestions
 */
function getFriendSuggestions($userId, $limit = 5) {
    $sql = "SELECT u.id, u.name, u.profile_pic 
            FROM users u
            WHERE u.id != ? AND u.id NOT IN (
                SELECT friend_id FROM friendships 
                WHERE user_id = ?
                UNION
                SELECT user_id FROM friendships 
                WHERE friend_id = ?
            )
            ORDER BY RAND()
            LIMIT ?";
    
    return fetchAll($sql, [$userId, $userId, $userId, $limit], "iiis");
}

/**
 * Send friend request
 * 
 * @param int $userId User ID
 * @param int $friendId Friend ID
 * @return bool True on success, false on failure
 */
function sendFriendRequest($userId, $friendId) {
    // Check if request already exists
    $existing = fetchRow(
        "SELECT * FROM friendships WHERE 
        (user_id = ? AND friend_id = ?) OR 
        (user_id = ? AND friend_id = ?)", 
        [$userId, $friendId, $friendId, $userId], 
        "iiii"
    );
    
    if ($existing) {
        return false;
    }
    
    $friendshipData = [
        'user_id' => $userId,
        'friend_id' => $friendId,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    return insert('friendships', $friendshipData) ? true : false;
}

/**
 * Accept friend request
 * 
 * @param int $userId User ID
 * @param int $friendId Friend ID
 * @return bool True on success, false on failure
 */
function acceptFriendRequest($userId, $friendId) {
    return update(
        'friendships', 
        ['status' => 'accepted', 'updated_at' => date('Y-m-d H:i:s')], 
        'user_id = ? AND friend_id = ? AND status = "pending"', 
        [$friendId, $userId]
    );
}

/**
 * Reject friend request
 * 
 * @param int $userId User ID
 * @param int $friendId Friend ID
 * @return bool True on success, false on failure
 */
function rejectFriendRequest($userId, $friendId) {
    return delete('friendships', 'user_id = ? AND friend_id = ? AND status = "pending"', [$friendId, $userId]);
}

/**
 * Get user's friends
 * 
 * @param int $userId User ID
 * @return array Array of friends
 */
function getUserFriends($userId) {
    $sql = "SELECT u.id, u.name, u.profile_pic 
            FROM users u
            JOIN friendships f ON (u.id = f.friend_id OR u.id = f.user_id)
            WHERE (f.user_id = ? OR f.friend_id = ?) 
            AND f.status = 'accepted'
            AND u.id != ?";
    
    return fetchAll($sql, [$userId, $userId, $userId], "iii");
}

/**
 * Get pending friend requests
 * 
 * @param int $userId User ID
 * @return array Array of pending friend requests
 */
function getPendingFriendRequests($userId) {
    $sql = "SELECT u.id, u.name, u.profile_pic, f.created_at
            FROM users u
            JOIN friendships f ON u.id = f.user_id
            WHERE f.friend_id = ? AND f.status = 'pending'";
    
    return fetchAll($sql, [$userId], "i");
}

/**
 * Create a notification
 * 
 * @param int $userId User ID to notify
 * @param string $type Notification type
 * @param string $message Notification message
 * @param int $fromUserId User ID who triggered the notification
 * @param int $entityId Related entity ID (post, comment, etc.)
 * @return int|false Notification ID or false if creation fails
 */
function createNotification($userId, $type, $message, $fromUserId = null, $entityId = null) {
    $notificationData = [
        'user_id' => $userId,
        'type' => $type,
        'message' => $message,
        'from_user_id' => $fromUserId,
        'entity_id' => $entityId,
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return insert('notifications', $notificationData);
}

/**
 * Get user's notifications
 * 
 * @param int $userId User ID
 * @param int $limit Number of notifications to retrieve
 * @param int $offset Offset for pagination
 * @return array Array of notifications
 */
function getUserNotifications($userId, $limit = 10, $offset = 0) {
    $sql = "SELECT n.*, u.name, u.profile_pic 
            FROM notifications n
            LEFT JOIN users u ON n.from_user_id = u.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?";
    
    return fetchAll($sql, [$userId, $limit, $offset], "iis");
}

/**
 * Mark notification as read
 * 
 * @param int $notificationId Notification ID
 * @return bool True on success, false on failure
 */
function markNotificationAsRead($notificationId) {
    return update('notifications', ['is_read' => 1], 'id = ?', [$notificationId]);
}

/**
 * Get unread notification count
 * 
 * @param int $userId User ID
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount($userId) {
    $result = fetchRow("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", [$userId], "i");
    return $result ? $result['count'] : 0;
}

/**
 * Get unread message count
 * 
 * @param int $userId User ID
 * @return int Number of unread messages
 */
function getUnreadMessageCount($userId) {
    $result = fetchRow("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0", [$userId], "i");
    return $result ? $result['count'] : 0;
}

/**
 * Get pending friend request count
 * 
 * @param int $userId User ID
 * @return int Number of pending friend requests
 */
function getPendingFriendRequestCount($userId) {
    $result = fetchRow("SELECT COUNT(*) as count FROM friendships WHERE friend_id = ? AND status = 'pending'", [$userId], "i");
    return $result ? $result['count'] : 0;
}

/**
 * Format date to a readable format
 * 
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return "À l'instant";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Il y a " . $minutes . " minute" . ($minutes > 1 ? "s" : "");
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Il y a " . $hours . " heure" . ($hours > 1 ? "s" : "");
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Il y a " . $days . " jour" . ($days > 1 ? "s" : "");
    } else {
        return date("j M Y", $timestamp);
    }
}

/**
 * Sanitize output to prevent XSS
 * 
 * @param string $string String to sanitize
 * @return string Sanitized string
 */
function sanitizeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Upload an image
 * 
 * @param array $file $_FILES array element
 * @param string $directory Directory to upload to
 * @return string|false Filename on success, false on failure
 */
function uploadImage($file, $directory) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check if the uploaded file is an image
    $fileInfo = getimagesize($file['tmp_name']);
    if (!$fileInfo) {
        return false;
    }
    
    // Check file type
    $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
    if (!in_array($fileInfo[2], $allowedTypes)) {
        return false;
    }
    
    // Generate a unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateRandomString() . '.' . $extension;
    $targetPath = $directory . '/' . $filename;
    
    // Create directory if it doesn't exist
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    
    return false;
}