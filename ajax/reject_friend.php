<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

try {
    $stmt = $conn->prepare("
        UPDATE friendships 
        SET status = 'rejected' 
        WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'
    ");
    $stmt->execute([$_POST['user_id'], $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 