<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['receiver_id']) || !isset($_GET['last_time'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.profile_picture
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?)
            OR (m.sender_id = ? AND m.receiver_id = ?))
        AND m.created_at > ?
        ORDER BY m.created_at ASC
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $_GET['receiver_id'],
        $_GET['receiver_id'],
        $_SESSION['user_id'],
        $_GET['last_time']
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read
    $stmt = $conn->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$_GET['receiver_id'], $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}