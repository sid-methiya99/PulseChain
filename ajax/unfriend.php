<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    $conn->beginTransaction();

    // Delete comments on each other's posts
    $stmt = $conn->prepare("
        DELETE c FROM comments c
        JOIN posts p ON c.post_id = p.post_id
        WHERE (c.user_id = ? AND p.user_id = ?) 
        OR (c.user_id = ? AND p.user_id = ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'], 
        $_POST['user_id'],
        $_POST['user_id'],
        $_SESSION['user_id']
    ]);

    // Delete friendship
    $stmt = $conn->prepare("
        DELETE FROM friendships 
        WHERE (sender_id = ? AND receiver_id = ?) 
        OR (sender_id = ? AND receiver_id = ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'], 
        $_POST['user_id'],
        $_POST['user_id'],
        $_SESSION['user_id']
    ]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 