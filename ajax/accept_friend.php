<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // First check if the friend request exists and is pending
    $stmt = $conn->prepare("
        SELECT friendship_id 
        FROM friendships 
        WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'
    ");
    $stmt->execute([$_POST['user_id'], $_SESSION['user_id']]);
    $friendship = $stmt->fetch();

    if (!$friendship) {
        throw new Exception('Friend request not found or already processed');
    }

    // Update friendship status
    $stmt = $conn->prepare("
        UPDATE friendships 
        SET status = 'accepted', 
            updated_at = CURRENT_TIMESTAMP 
        WHERE friendship_id = ?
    ");
    $stmt->execute([$friendship['friendship_id']]);

    // Create notification for the sender
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, related_id, created_at) 
        VALUES (?, 'friend_accepted', ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$_POST['user_id'], $_SESSION['user_id']]);

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);

} catch(Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 