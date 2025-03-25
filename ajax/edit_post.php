<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id']) || !isset($_POST['content'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    // Verify post ownership
    $stmt = $conn->prepare("SELECT user_id FROM posts WHERE post_id = ?");
    $stmt->execute([$_POST['post_id']]);
    $post = $stmt->fetch();

    if (!$post || $post['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access');
    }

    // Update post
    $stmt = $conn->prepare("
        UPDATE posts 
        SET content = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE post_id = ? AND user_id = ?
    ");
    $stmt->execute([
        trim($_POST['content']),
        $_POST['post_id'],
        $_SESSION['user_id']
    ]);

    echo json_encode([
        'success' => true,
        'content' => nl2br(htmlspecialchars(trim($_POST['content'])))
    ]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 