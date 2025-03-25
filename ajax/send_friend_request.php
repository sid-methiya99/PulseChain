<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['receiver_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

try {
    $stmt = $conn->prepare("
        INSERT INTO friendships (sender_id, receiver_id, status) 
        VALUES (?, ?, 'pending')
    ");
    $stmt->execute([$_SESSION['user_id'], $_POST['receiver_id']]);
    
    // Create notification for friend request
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, related_id) 
        VALUES (?, 'friend_request', ?)
    ");
    $stmt->execute([$_POST['receiver_id'], $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 