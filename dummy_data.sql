-- First, disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop foreign key constraints
ALTER TABLE comments DROP FOREIGN KEY comments_ibfk_1;
ALTER TABLE comments DROP FOREIGN KEY comments_ibfk_2;
ALTER TABLE likes DROP FOREIGN KEY likes_ibfk_1;
ALTER TABLE likes DROP FOREIGN KEY likes_ibfk_2;
ALTER TABLE friendships DROP FOREIGN KEY friendships_ibfk_1;
ALTER TABLE friendships DROP FOREIGN KEY friendships_ibfk_2;
ALTER TABLE messages DROP FOREIGN KEY messages_ibfk_1;
ALTER TABLE messages DROP FOREIGN KEY messages_ibfk_2;
ALTER TABLE notifications DROP FOREIGN KEY notifications_ibfk_1;
ALTER TABLE posts DROP FOREIGN KEY posts_ibfk_1;

-- Clear existing data
TRUNCATE TABLE likes;
TRUNCATE TABLE comments;
TRUNCATE TABLE friendships;
TRUNCATE TABLE notifications;
TRUNCATE TABLE messages;
TRUNCATE TABLE posts;
TRUNCATE TABLE users;

-- Reset auto-increment values
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE posts AUTO_INCREMENT = 1;
ALTER TABLE comments AUTO_INCREMENT = 1;
ALTER TABLE likes AUTO_INCREMENT = 1;
ALTER TABLE friendships AUTO_INCREMENT = 1;
ALTER TABLE messages AUTO_INCREMENT = 1;
ALTER TABLE notifications AUTO_INCREMENT = 1;

-- Recreate foreign key constraints
ALTER TABLE posts
ADD CONSTRAINT posts_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;

ALTER TABLE comments
ADD CONSTRAINT comments_ibfk_1 
FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
ADD CONSTRAINT comments_ibfk_2 
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;

ALTER TABLE likes
ADD CONSTRAINT likes_ibfk_1 
FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
ADD CONSTRAINT likes_ibfk_2 
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;

ALTER TABLE friendships
ADD CONSTRAINT friendships_ibfk_1 
FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
ADD CONSTRAINT friendships_ibfk_2 
FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE;

ALTER TABLE messages
ADD CONSTRAINT messages_ibfk_1 
FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
ADD CONSTRAINT messages_ibfk_2 
FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE;

ALTER TABLE notifications
ADD CONSTRAINT notifications_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

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