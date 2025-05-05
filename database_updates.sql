-- SQL script to remove all pre-created accounts and messages while preserving the admin account

-- Disable foreign key checks to avoid constraint errors
SET FOREIGN_KEY_CHECKS = 0;

-- Delete all messages
TRUNCATE TABLE messages;

-- Delete all group members
TRUNCATE TABLE group_members;

-- Delete all group conversations
TRUNCATE TABLE group_conversations;

-- Delete all conversations
TRUNCATE TABLE conversations;

-- Delete all notifications
TRUNCATE TABLE notifications;

-- Delete all reel comments
TRUNCATE TABLE reel_comments;

-- Delete all reel likes
TRUNCATE TABLE reel_likes;

-- Delete all reels
TRUNCATE TABLE reels;

-- Delete all calls
TRUNCATE TABLE calls;

-- Delete all reactions
TRUNCATE TABLE reactions;

-- Delete all comments
TRUNCATE TABLE comments;

-- Delete all posts
TRUNCATE TABLE posts;

-- Delete all friendships
TRUNCATE TABLE friendships;

-- Delete all user settings except for admin
DELETE FROM user_settings WHERE user_id != 1;

-- Delete all users except for admin
DELETE FROM users WHERE id != 1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Update admin user settings
UPDATE user_settings 
SET privacy_profile = 'public', 
    privacy_posts = 'public', 
    privacy_friends = 'public', 
    email_notifications = 1, 
    updated_at = NOW() 
WHERE user_id = 1;

-- Create message_reactions table for message reactions (similar to post reactions)
CREATE TABLE IF NOT EXISTS message_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('like', 'love', 'haha', 'wow', 'sad', 'angry') DEFAULT 'like',
    created_at DATETIME NOT NULL,
    UNIQUE KEY unique_message_reaction (message_id, user_id),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_message_reactions_message_id ON message_reactions(message_id);
CREATE INDEX idx_message_reactions_user_id ON message_reactions(user_id);