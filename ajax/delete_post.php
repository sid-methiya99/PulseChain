<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    $conn->beginTransaction();

    // Verify post ownership
    $stmt = $conn->prepare("SELECT user_id, image_url FROM posts WHERE post_id = ?");
    $stmt->execute([$_POST['post_id']]);
    $post = $stmt->fetch();

    if (!$post || $post['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access');
    }

    // Delete post image if exists
    if ($post['image_url']) {
        $image_path = '../' . $post['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Delete post and related data (comments and likes will be deleted by foreign key constraints)
    $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$_POST['post_id'], $_SESSION['user_id']]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 