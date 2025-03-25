CREATE DATABASE pulsechain;
USE pulsechain;

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y',
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    post_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE likes (
    like_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, post_id)
);

CREATE TABLE friendships (
    friendship_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (sender_id, receiver_id)
);

CREATE TABLE messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('like', 'comment', 'friend_request', 'friend_accepted', 'message') NOT NULL,
    related_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_comments_post_id ON comments(post_id);
CREATE INDEX idx_comments_user_id ON comments(user_id);
CREATE INDEX idx_likes_post_id ON likes(post_id);
CREATE INDEX idx_likes_user_id ON likes(user_id);
CREATE INDEX idx_friendships_sender ON friendships(sender_id);
CREATE INDEX idx_friendships_receiver ON friendships(receiver_id);
CREATE INDEX idx_messages_sender ON messages(sender_id);
CREATE INDEX idx_messages_receiver ON messages(receiver_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);

ALTER TABLE comments
ADD CONSTRAINT fk_comments_post
FOREIGN KEY (post_id) REFERENCES posts(post_id)
ON DELETE CASCADE;

ALTER TABLE likes
ADD CONSTRAINT fk_likes_post
FOREIGN KEY (post_id) REFERENCES posts(post_id)
ON DELETE CASCADE;

-- Insert dummy users
INSERT INTO users (username, email, password, full_name, bio) VALUES
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'Software Developer'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'Digital Artist'),
('mike_wilson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Wilson', 'Photographer'),
('sarah_brown', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Brown', 'Travel Enthusiast'),
('alex_jones', 'alex@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alex Jones', 'Music Producer');

-- Create some friendships
INSERT INTO friendships (sender_id, receiver_id, status) VALUES
(1, 2, 'accepted'),  -- John and Jane are friends
(1, 3, 'accepted'),  -- John and Mike are friends
(2, 4, 'accepted'),  -- Jane and Sarah are friends
(3, 4, 'accepted'),  -- Mike and Sarah are friends
(1, 4, 'pending'),   -- John sent request to Sarah
(5, 2, 'pending');   -- Alex sent request to Jane

-- Add posts for each user
-- John's posts
INSERT INTO posts (user_id, content) VALUES
(1, 'Just finished working on a new project! Can''t wait to share it with everyone.'),
(1, 'Beautiful day for coding! ☀️ #programming #coding'),
(1, 'Learning new frameworks is always exciting!');

-- Jane's posts
INSERT INTO posts (user_id, content) VALUES
(2, 'Just completed my latest digital artwork. What do you think?'),
(2, 'Art is not what you see, but what you make others see. 🎨'),
(2, 'Working on some new designs today. Stay tuned!');

-- Mike's posts
INSERT INTO posts (user_id, content) VALUES
(3, 'Captured some amazing sunset shots today! Nature is beautiful.'),
(3, 'Photography tip: The best camera is the one you have with you.'),
(3, 'Exploring new photography techniques this weekend. 📸');

-- Sarah's posts
INSERT INTO posts (user_id, content) VALUES
(4, 'Just booked my next adventure! Can''t wait to explore new places!'),
(4, 'Travel is the only thing you buy that makes you richer. ✈️'),
(4, 'Missing the beach vibes... #wanderlust');

-- Alex's posts
INSERT INTO posts (user_id, content) VALUES
(5, 'Working on a new track in the studio today! 🎵'),
(5, 'Music is the universal language of mankind.'),
(5, 'New beats dropping soon! Stay tuned! 🎧');

-- Add some comments
INSERT INTO comments (post_id, user_id, content) VALUES
(1, 2, 'That''s awesome! Can''t wait to see it!'),
(1, 3, 'Great work! Keep it up!'),
(2, 2, 'The weather is perfect for coding!'),
(4, 1, 'Your artwork is amazing as always!'),
(4, 3, 'Love the colors in this one!'),
(7, 1, 'Stunning photos!'),
(7, 2, 'What camera do you use?'),
(10, 2, 'Where are you heading next?'),
(13, 2, 'Looking forward to hearing it!');

-- Add some likes
INSERT INTO likes (post_id, user_id) VALUES
(1, 2), (1, 3), (1, 4),  -- Likes on John's first post
(2, 2), (2, 3),          -- Likes on John's second post
(4, 1), (4, 3),          -- Likes on Jane's first post
(7, 1), (7, 2), (7, 4),  -- Likes on Mike's first post
(10, 2), (10, 3),        -- Likes on Sarah's first post
(13, 2);                 -- Likes on Alex's first post 