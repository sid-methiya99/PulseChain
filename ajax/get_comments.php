<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    $conn->beginTransaction();

    // First get the post owner
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id = ?");
    $stmt->execute([$_GET['post_id']]);
    $post = $stmt->fetch();

    if (!$post) {
        throw new Exception('Post not found');
    }

    // Delete comments from non-friends (except post owner's comments and commenter's own posts)
    $stmt = $conn->prepare("
        DELETE c FROM comments c
        LEFT JOIN friendships f ON 
            (f.sender_id = c.user_id AND f.receiver_id = p.user_id AND f.status = 'accepted') OR
            (f.receiver_id = c.user_id AND f.sender_id = p.user_id AND f.status = 'accepted')
        JOIN posts p ON c.post_id = p.post_id
        WHERE c.post_id = ? 
        AND c.user_id != p.user_id 
        AND p.user_id != c.user_id
        AND f.friendship_id IS NULL
    ");
    $stmt->execute([$_GET['post_id']]);

    // Then get comments with user info and friendship status
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.profile_picture,
               p.user_id as post_owner_id,
               CASE 
                   WHEN c.user_id = p.user_id THEN 1
                   WHEN p.user_id = c.user_id THEN 1
                   WHEN EXISTS (
                       SELECT 1 FROM friendships f 
                       WHERE ((f.sender_id = c.user_id AND f.receiver_id = p.user_id) 
                          OR (f.receiver_id = c.user_id AND f.sender_id = p.user_id))
                       AND f.status = 'accepted'
                   ) THEN 1
                   ELSE 0
               END as is_visible
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        JOIN posts p ON c.post_id = p.post_id
        WHERE c.post_id = ?
        HAVING is_visible = 1
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$_GET['post_id']]);
    $comments = $stmt->fetchAll();

    ob_start();
    foreach ($comments as $comment) {
        include '../includes/comment_card.php';
    }
    $html = ob_get_clean();

    $conn->commit();
    echo json_encode(['success' => true, 'html' => $html]);

} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 