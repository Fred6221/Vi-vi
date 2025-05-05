-- Vi-vi Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS vivi;
USE vivi;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    birthdate DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    bio TEXT,
    location VARCHAR(100),
    profile_pic VARCHAR(255),
    cover_pic VARCHAR(255),
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    last_login DATETIME,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User settings table
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    privacy_profile ENUM('public', 'friends', 'private') DEFAULT 'public',
    privacy_posts ENUM('public', 'friends', 'private') DEFAULT 'friends',
    privacy_friends ENUM('public', 'friends', 'private') DEFAULT 'public',
    email_notifications TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Posts table
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    privacy ENUM('public', 'friends', 'private') DEFAULT 'friends',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reactions table
CREATE TABLE IF NOT EXISTS reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('like', 'love', 'haha', 'wow', 'sad', 'angry') DEFAULT 'like',
    created_at DATETIME NOT NULL,
    UNIQUE KEY unique_reaction (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Friendships table
CREATE TABLE IF NOT EXISTS friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_friendship (user_id, friend_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    from_user_id INT,
    entity_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Conversations table (one-to-one)
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message_id INT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_conversation (user1_id, user2_id),
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group conversations table
CREATE TABLE IF NOT EXISTS group_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    creator_id INT NOT NULL,
    description TEXT,
    image VARCHAR(255),
    last_message_id INT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group members table
CREATE TABLE IF NOT EXISTS group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'member') DEFAULT 'member',
    joined_at DATETIME NOT NULL,
    UNIQUE KEY unique_group_member (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES group_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT,
    group_id INT,
    sender_id INT NOT NULL,
    receiver_id INT,
    content TEXT NOT NULL,
    message_type ENUM('text', 'photo', 'voice', 'video') DEFAULT 'text',
    media_url VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES group_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reels table
CREATE TABLE IF NOT EXISTS reels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    video_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    description TEXT,
    privacy ENUM('public', 'friends', 'private') DEFAULT 'friends',
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reel likes table
CREATE TABLE IF NOT EXISTS reel_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reel_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY unique_reel_like (reel_id, user_id),
    FOREIGN KEY (reel_id) REFERENCES reels(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reel comments table
CREATE TABLE IF NOT EXISTS reel_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reel_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (reel_id) REFERENCES reels(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Calls table
CREATE TABLE IF NOT EXISTS calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caller_id INT NOT NULL,
    receiver_id INT NOT NULL,
    call_type ENUM('voice', 'video') NOT NULL,
    status ENUM('missed', 'answered', 'rejected') NOT NULL,
    started_at DATETIME NOT NULL,
    ended_at DATETIME,
    duration INT DEFAULT 0,
    FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Remember tokens table
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User activities table
CREATE TABLE IF NOT EXISTS user_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('login', 'logout', 'registration', 'password_reset') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_comments_post_id ON comments(post_id);
CREATE INDEX idx_comments_user_id ON comments(user_id);
CREATE INDEX idx_reactions_post_id ON reactions(post_id);
CREATE INDEX idx_reactions_user_id ON reactions(user_id);
CREATE INDEX idx_friendships_user_id ON friendships(user_id);
CREATE INDEX idx_friendships_friend_id ON friendships(friend_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_messages_conversation_id ON messages(conversation_id);
CREATE INDEX idx_messages_group_id ON messages(group_id);
CREATE INDEX idx_messages_sender_id ON messages(sender_id);
CREATE INDEX idx_messages_receiver_id ON messages(receiver_id);
CREATE INDEX idx_reels_user_id ON reels(user_id);
CREATE INDEX idx_reel_likes_reel_id ON reel_likes(reel_id);
CREATE INDEX idx_reel_likes_user_id ON reel_likes(user_id);
CREATE INDEX idx_reel_comments_reel_id ON reel_comments(reel_id);
CREATE INDEX idx_reel_comments_user_id ON reel_comments(user_id);
CREATE INDEX idx_calls_caller_id ON calls(caller_id);
CREATE INDEX idx_calls_receiver_id ON calls(receiver_id);

-- Insert sample admin user (password: admin123)
INSERT INTO users (name, email, password, birthdate, gender, bio, created_at, updated_at)
VALUES ('Admin User', 'admin@vivi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1990-01-01', 'male', 'Administrateur du site', NOW(), NOW());

-- Insert sample users
INSERT INTO users (name, email, password, birthdate, gender, bio, created_at, updated_at)
VALUES 
('Marie Dupont', 'marie@vivi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1992-05-15', 'female', 'Passionnée de photographie', NOW(), NOW()),
('Pierre Martin', 'pierre@vivi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1988-11-23', 'male', 'Amateur de voyages', NOW(), NOW()),
('Sophie Leclerc', 'sophie@vivi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1995-03-10', 'female', 'Étudiante en droit', NOW(), NOW()),
('Thomas Bernard', 'thomas@vivi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1990-07-22', 'male', 'Développeur web', NOW(), NOW());

-- Create user settings for all users
INSERT INTO user_settings (user_id, privacy_profile, privacy_posts, privacy_friends, email_notifications, created_at, updated_at)
VALUES 
(1, 'public', 'public', 'public', 1, NOW(), NOW()),
(2, 'public', 'friends', 'public', 1, NOW(), NOW()),
(3, 'friends', 'friends', 'friends', 1, NOW(), NOW()),
(4, 'public', 'friends', 'public', 0, NOW(), NOW()),
(5, 'friends', 'friends', 'friends', 1, NOW(), NOW());

-- Create some friendships
INSERT INTO friendships (user_id, friend_id, status, created_at, updated_at)
VALUES 
(1, 2, 'accepted', NOW(), NOW()),
(1, 3, 'accepted', NOW(), NOW()),
(1, 4, 'accepted', NOW(), NOW()),
(1, 5, 'accepted', NOW(), NOW()),
(2, 3, 'accepted', NOW(), NOW()),
(2, 4, 'accepted', NOW(), NOW()),
(3, 5, 'accepted', NOW(), NOW()),
(4, 5, 'pending', NOW(), NOW());

-- Create some posts
INSERT INTO posts (user_id, content, privacy, created_at, updated_at)
VALUES 
(2, 'Bonjour à tous ! Bienvenue sur Vi-vi, le nouveau réseau social !', 'public', NOW(), NOW()),
(3, 'Je suis ravi de rejoindre cette communauté !', 'public', NOW(), NOW()),
(4, 'Quelqu\'un a des recommandations de livres intéressants ?', 'friends', NOW(), NOW()),
(5, 'Voici une photo de mon dernier voyage à Paris.', 'public', NOW(), NOW()),
(2, 'Je travaille sur un nouveau projet passionnant !', 'friends', NOW(), NOW());

-- Create some comments
INSERT INTO comments (post_id, user_id, content, created_at, updated_at)
VALUES 
(1, 3, 'Merci pour l\'accueil !', NOW(), NOW()),
(1, 4, 'Heureux d\'être ici !', NOW(), NOW()),
(2, 2, 'Bienvenue parmi nous !', NOW(), NOW()),
(3, 5, 'Je te recommande "Sapiens" de Yuval Noah Harari.', NOW(), NOW()),
(3, 2, '"L\'Alchimiste" de Paulo Coelho est excellent aussi.', NOW(), NOW()),
(4, 3, 'Magnifique photo ! J\'adore Paris.', NOW(), NOW());

-- Create some reactions
INSERT INTO reactions (post_id, user_id, reaction_type, created_at)
VALUES 
(1, 3, 'like', NOW()),
(1, 4, 'love', NOW()),
(1, 5, 'like', NOW()),
(2, 2, 'like', NOW()),
(2, 4, 'like', NOW()),
(3, 5, 'like', NOW()),
(4, 2, 'love', NOW()),
(4, 3, 'love', NOW()),
(5, 3, 'like', NOW()),
(5, 4, 'haha', NOW());

-- Create some conversations
INSERT INTO conversations (user1_id, user2_id, created_at, updated_at)
VALUES 
(2, 3, NOW(), NOW()),
(2, 4, NOW(), NOW()),
(3, 4, NOW(), NOW()),
(3, 5, NOW(), NOW()),
(4, 5, NOW(), NOW());

-- Create some messages
INSERT INTO messages (conversation_id, sender_id, receiver_id, content, message_type, is_read, created_at)
VALUES 
(1, 2, 3, 'Salut Marie ! Comment vas-tu ?', 'text', 1, NOW()),
(1, 3, 2, 'Bonjour Pierre ! Je vais bien, merci. Et toi ?', 'text', 1, NOW()),
(1, 2, 3, 'Très bien ! Tu as des projets pour le weekend ?', 'text', 1, NOW()),
(2, 2, 4, 'Bonjour Sophie ! J\'ai une question à te poser.', 'text', 0, NOW()),
(3, 3, 4, 'Salut Sophie ! Tu as vu le nouveau film dont je t\'ai parlé ?', 'text', 1, NOW()),
(3, 4, 3, 'Pas encore, mais c\'est prévu pour ce weekend !', 'text', 1, NOW()),
(4, 3, 5, 'Bonjour Thomas ! Comment se passe ton projet ?', 'text', 0, NOW()),
(5, 5, 4, 'Salut Sophie ! On se retrouve pour déjeuner demain ?', 'text', 1, NOW()),
(5, 4, 5, 'Avec plaisir ! À midi au restaurant habituel ?', 'text', 1, NOW()),
(5, 5, 4, 'Parfait ! À demain alors.', 'text', 0, NOW());

-- Create a group conversation
INSERT INTO group_conversations (name, creator_id, description, created_at, updated_at)
VALUES ('Amis du lycée', 2, 'Groupe pour rester en contact avec les amis du lycée', NOW(), NOW());

-- Add members to the group
INSERT INTO group_members (group_id, user_id, role, joined_at)
VALUES 
(1, 2, 'admin', NOW()),
(1, 3, 'member', NOW()),
(1, 4, 'member', NOW()),
(1, 5, 'member', NOW());

-- Add some group messages
INSERT INTO messages (group_id, sender_id, content, message_type, created_at)
VALUES 
(1, 2, 'Bienvenue dans notre groupe !', 'text', NOW()),
(1, 3, 'Merci pour l\'invitation !', 'text', NOW()),
(1, 4, 'Super idée de créer ce groupe !', 'text', NOW()),
(1, 5, 'On devrait organiser une rencontre bientôt !', 'text', NOW()),
(1, 2, 'Bonne idée ! Que pensez-vous du weekend prochain ?', 'text', NOW());

-- Create some reels
INSERT INTO reels (user_id, video_url, description, privacy, created_at)
VALUES 
(2, 'assets/videos/reels/sample1.mp4', 'Ma première vidéo sur Vi-vi !', 'public', NOW()),
(3, 'assets/videos/reels/sample2.mp4', 'Visite de Paris', 'public', NOW()),
(4, 'assets/videos/reels/sample3.mp4', 'Tutoriel de cuisine', 'friends', NOW()),
(5, 'assets/videos/reels/sample4.mp4', 'Mon chat fait des bêtises', 'public', NOW());

-- Add some reel likes
INSERT INTO reel_likes (reel_id, user_id, created_at)
VALUES 
(1, 3, NOW()),
(1, 4, NOW()),
(1, 5, NOW()),
(2, 2, NOW()),
(2, 4, NOW()),
(2, 5, NOW()),
(3, 2, NOW()),
(3, 5, NOW()),
(4, 2, NOW()),
(4, 3, NOW());

-- Update reel like counts
UPDATE reels SET like_count = 3 WHERE id = 1;
UPDATE reels SET like_count = 3 WHERE id = 2;
UPDATE reels SET like_count = 2 WHERE id = 3;
UPDATE reels SET like_count = 2 WHERE id = 4;

-- Add some reel comments
INSERT INTO reel_comments (reel_id, user_id, content, created_at)
VALUES 
(1, 3, 'Super vidéo !', NOW()),
(1, 4, 'Bienvenue sur Vi-vi !', NOW()),
(2, 2, 'Paris est magnifique !', NOW()),
(2, 5, 'J\'adore cette ville !', NOW()),
(3, 2, 'Merci pour ce tutoriel !', NOW()),
(4, 3, 'Trop mignon ton chat !', NOW()),
(4, 2, 'Il est vraiment drôle !', NOW());

-- Add some calls
INSERT INTO calls (caller_id, receiver_id, call_type, status, started_at, ended_at, duration)
VALUES 
(2, 3, 'voice', 'answered', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 300),
(3, 2, 'video', 'answered', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 600),
(4, 5, 'voice', 'missed', DATE_SUB(NOW(), INTERVAL 12 HOUR), NULL, 0),
(2, 4, 'video', 'rejected', DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(NOW(), INTERVAL 6 HOUR), 0);

-- Add some notifications
INSERT INTO notifications (user_id, type, message, from_user_id, is_read, created_at)
VALUES 
(3, 'like', 'Pierre a aimé votre publication', 2, 0, NOW()),
(4, 'comment', 'Thomas a commenté votre publication', 5, 1, NOW()),
(2, 'friend_request', 'Sophie vous a envoyé une demande d\'ami', 4, 0, NOW()),
(5, 'friend_accepted', 'Marie a accepté votre demande d\'ami', 3, 1, NOW()),
(3, 'message', 'Pierre vous a envoyé un message', 2, 0, NOW()),
(4, 'group_invite', 'Pierre vous a ajouté au groupe "Amis du lycée"', 2, 1, NOW()),
(2, 'reel_like', 'Sophie a aimé votre reel', 4, 0, NOW()),
(3, 'reel_comment', 'Pierre a commenté votre reel', 2, 1, NOW());

-- Update last_message_id in conversations
UPDATE conversations c
JOIN (
    SELECT conversation_id, MAX(id) as max_id
    FROM messages
    WHERE conversation_id IS NOT NULL
    GROUP BY conversation_id
) m ON c.id = m.conversation_id
SET c.last_message_id = m.max_id;

-- Update last_message_id in group_conversations
UPDATE group_conversations g
JOIN (
    SELECT group_id, MAX(id) as max_id
    FROM messages
    WHERE group_id IS NOT NULL
    GROUP BY group_id
) m ON g.id = m.group_id
SET g.last_message_id = m.max_id;