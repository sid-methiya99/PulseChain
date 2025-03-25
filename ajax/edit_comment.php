<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['comment_id']) || !isset($_POST['content'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    // Verify comment ownership
    $stmt = $conn->prepare("
        SELECT user_id 
        FROM comments 
        WHERE comment_id = ?
    ");
    $stmt->execute([$_POST['comment_id']]);
    $comment = $stmt->fetch();

    if (!$comment || $comment['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access');
    }

    // Update comment
    $stmt = $conn->prepare("
        UPDATE comments 
        SET content = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE comment_id = ? AND user_id = ?
    ");
    $stmt->execute([
        trim($_POST['content']),
        $_POST['comment_id'],
        $_SESSION['user_id']
    ]);

    echo json_encode([
        'success' => true,
        'content' => nl2br(htmlspecialchars(trim($_POST['content']))),
        'updated_at' => date('F j, Y, g:i a')
    ]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 