<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id']) || !isset($_POST['content'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    // Check if user can comment (is friend with post owner or is post owner)
    $stmt = $conn->prepare("
        SELECT p.user_id,
        CASE 
            WHEN p.user_id = ? THEN 1
            WHEN EXISTS (
                SELECT 1 FROM friendships f 
                WHERE ((f.sender_id = ? AND f.receiver_id = p.user_id) 
                   OR (f.receiver_id = ? AND f.sender_id = p.user_id))
                AND f.status = 'accepted'
            ) THEN 1
            ELSE 0
        END as can_comment
        FROM posts p 
        WHERE p.post_id = ?
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $_POST['post_id']
    ]);
    $result = $stmt->fetch();

    if (!$result || (!$result['can_comment'] && $result['user_id'] != $_SESSION['user_id'])) {
        throw new Exception('You cannot comment on this post');
    }

    // Add the comment
    $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['post_id'], $_POST['content']]);
    $comment_id = $conn->lastInsertId();

    // Get the comment details including user info
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.profile_picture
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.comment_id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format the comment for response
    $comment['created_at'] = date('g:i A', strtotime($comment['created_at']));

    // Add notification for post owner if it's not their own comment
    if ($result['user_id'] != $_SESSION['user_id']) {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, related_id) 
            VALUES (?, 'comment', ?)
        ");
        $stmt->execute([$result['user_id'], $_POST['post_id']]);
    }

    echo json_encode([
        'success' => true,
        'comment' => $comment
    ]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 