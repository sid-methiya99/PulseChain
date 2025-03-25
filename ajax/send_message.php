<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['receiver_id']) || !isset($_POST['content'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    // Insert the message
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['receiver_id'], $_POST['content']]);
    
    // Create notification for the message
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, related_id) VALUES (?, 'message', ?)");
    $stmt->execute([$_POST['receiver_id'], $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 