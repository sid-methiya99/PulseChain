<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['comment_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    $conn->beginTransaction();

    // Check if user owns the comment or the post
    $stmt = $conn->prepare("
        SELECT c.user_id, p.user_id as post_owner_id
        FROM comments c
        JOIN posts p ON c.post_id = p.post_id
        WHERE c.comment_id = ?
    ");
    $stmt->execute([$_POST['comment_id']]);
    $result = $stmt->fetch();

    if (!$result || ($result['user_id'] != $_SESSION['user_id'] && $result['post_owner_id'] != $_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    // Delete comment
    $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
    $stmt->execute([$_POST['comment_id']]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 