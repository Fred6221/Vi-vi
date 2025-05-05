-- Create profile_pictures table if it doesn't exist
CREATE TABLE IF NOT EXISTS profile_pictures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    upload_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_profile_pictures_user_id ON profile_pictures(user_id);