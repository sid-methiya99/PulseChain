<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    $updates = [];
    $params = [];

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "../uploads/profiles/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Invalid file type. Only JPG, PNG and GIF are allowed.");
        }
        
        $image_name = time() . '_' . basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $updates[] = "profile_picture = ?";
            $params[] = 'uploads/profiles/' . $image_name;
        } else {
            throw new Exception("Failed to upload file");
        }
    }

    // Update other fields
    if (isset($_POST['full_name']) && !empty($_POST['full_name'])) {
        $updates[] = "full_name = ?";
        $params[] = $_POST['full_name'];
    }

    if (isset($_POST['bio'])) {
        $updates[] = "bio = ?";
        $params[] = $_POST['bio'];
    }

    if (!empty($updates)) {
        $params[] = $_SESSION['user_id'];
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No updates provided']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} 