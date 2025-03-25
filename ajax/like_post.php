<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    $conn->beginTransaction();

    // Check if user already liked the post
    $stmt = $conn->prepare("SELECT like_id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$_SESSION['user_id'], $_POST['post_id']]);
    $existing_like = $stmt->fetch();

    if ($existing_like) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM likes WHERE like_id = ?");
        $stmt->execute([$existing_like['like_id']]);
        $liked = false;
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $_POST['post_id']]);
        $liked = true;

        // Add notification for post owner
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, related_id) 
            SELECT user_id, 'like', ? FROM posts WHERE post_id = ? AND user_id != ?
        ");
        $stmt->execute([$_POST['post_id'], $_POST['post_id'], $_SESSION['user_id']]);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'liked' => $liked]);
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 