<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get current user
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

if (!$user) {
    // Invalid user ID in session
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Collect user data
$userData = [];

// Basic user information (excluding password)
$userData['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'birthdate' => $user['birthdate'],
    'gender' => $user['gender'],
    'bio' => $user['bio'],
    'location' => $user['location'],
    'profile_pic' => $user['profile_pic'],
    'cover_pic' => $user['cover_pic'],
    'created_at' => $user['created_at'],
    'updated_at' => $user['updated_at'],
    'last_login' => $user['last_login']
];

// User settings
$userData['settings'] = fetchRow("SELECT * FROM user_settings WHERE user_id = ?", [$userId], "i");

// Posts
$userData['posts'] = fetchAll(
    "SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC", 
    [$userId], 
    "i"
);

// Comments
$userData['comments'] = fetchAll(
    "SELECT c.*, p.content as post_content 
    FROM comments c
    JOIN posts p ON c.post_id = p.id
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC", 
    [$userId], 
    "i"
);

// Likes
$userData['likes'] = fetchAll(
    "SELECT l.*, p.content as post_content 
    FROM likes l
    JOIN posts p ON l.post_id = p.id
    WHERE l.user_id = ? 
    ORDER BY l.created_at DESC", 
    [$userId], 
    "i"
);

// Friends
$userData['friends'] = fetchAll(
    "SELECT f.*, 
    CASE 
        WHEN f.user_id = ? THEN f.friend_id 
        ELSE f.user_id 
    END as friend_id,
    u.name as friend_name, u.email as friend_email
    FROM friendships f
    JOIN users u ON (
        CASE 
            WHEN f.user_id = ? THEN f.friend_id 
            ELSE f.user_id 
        END = u.id
    )
    WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'
    ORDER BY f.created_at DESC", 
    [$userId, $userId, $userId, $userId], 
    "iiii"
);

// Friend requests (sent)
$userData['friend_requests_sent'] = fetchAll(
    "SELECT f.*, u.name as friend_name, u.email as friend_email
    FROM friendships f
    JOIN users u ON f.friend_id = u.id
    WHERE f.user_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC", 
    [$userId], 
    "i"
);

// Friend requests (received)
$userData['friend_requests_received'] = fetchAll(
    "SELECT f.*, u.name as friend_name, u.email as friend_email
    FROM friendships f
    JOIN users u ON f.user_id = u.id
    WHERE f.friend_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC", 
    [$userId], 
    "i"
);

// Messages (sent)
$userData['messages_sent'] = fetchAll(
    "SELECT m.*, u.name as receiver_name, u.email as receiver_email
    FROM messages m
    JOIN users u ON m.receiver_id = u.id
    WHERE m.sender_id = ?
    ORDER BY m.created_at DESC", 
    [$userId], 
    "i"
);

// Messages (received)
$userData['messages_received'] = fetchAll(
    "SELECT m.*, u.name as sender_name, u.email as sender_email
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC", 
    [$userId], 
    "i"
);

// Notifications
$userData['notifications'] = fetchAll(
    "SELECT n.*, u.name as from_user_name, u.email as from_user_email
    FROM notifications n
    LEFT JOIN users u ON n.from_user_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC", 
    [$userId], 
    "i"
);

// Activity log
$userData['activities'] = fetchAll(
    "SELECT * FROM user_activities
    WHERE user_id = ?
    ORDER BY created_at DESC", 
    [$userId], 
    "i"
);

// Set filename
$filename = 'socialconnect_data_' . $userId . '_' . date('Y-m-d') . '.json';

// Set headers for file download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output JSON data
echo json_encode($userData, JSON_PRETTY_PRINT);
exit;