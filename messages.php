<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    // Get user's conversations
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            u.user_id,
            u.username,
            u.profile_picture,
            u.full_name,
            (SELECT m.content 
             FROM messages m 
             WHERE (m.sender_id = u.user_id AND m.receiver_id = ?) 
                OR (m.sender_id = ? AND m.receiver_id = u.user_id)
             ORDER BY m.created_at DESC LIMIT 1) as last_message,
            (SELECT m.created_at 
             FROM messages m 
             WHERE (m.sender_id = u.user_id AND m.receiver_id = ?) 
                OR (m.sender_id = ? AND m.receiver_id = u.user_id)
             ORDER BY m.created_at DESC LIMIT 1) as last_message_time,
            (SELECT COUNT(*) 
             FROM messages m 
             WHERE m.sender_id = u.user_id 
                AND m.receiver_id = ? 
                AND m.is_read = 0) as unread_count
        FROM messages m
        JOIN users u ON (u.user_id = m.sender_id OR u.user_id = m.receiver_id)
        WHERE m.sender_id = ? OR m.receiver_id = ?
        AND u.user_id != ?
        GROUP BY u.user_id
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([
        $_SESSION['user_id'], $_SESSION['user_id'],
        $_SESSION['user_id'], $_SESSION['user_id'],
        $_SESSION['user_id'],
        $_SESSION['user_id'], $_SESSION['user_id'],
        $_SESSION['user_id']
    ]);
    $conversations = $stmt->fetchAll();

    // Get selected conversation if any
    $selected_user = null;
    $messages = [];
    if (isset($_GET['user'])) {
        $stmt = $conn->prepare("SELECT user_id, username, full_name, profile_picture FROM users WHERE user_id = ?");
        $stmt->execute([$_GET['user']]);
        $selected_user = $stmt->fetch();

        if ($selected_user) {
            // Get messages
            $stmt = $conn->prepare("
                SELECT m.*, u.username, u.profile_picture
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([
                $_SESSION['user_id'], $_GET['user'],
                $_GET['user'], $_SESSION['user_id']
            ]);
            $messages = $stmt->fetchAll();

            // Mark messages as read
            $stmt = $conn->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
            ");
            $stmt->execute([$_GET['user'], $_SESSION['user_id']]);
        }
    }
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - PulseChain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .messages-container {
            height: calc(100vh - 200px);
            overflow-y: auto;
        }
        .message-list {
            height: 100%;
            overflow-y: auto;
        }
        .conversation-item {
            cursor: pointer;
        }
        .conversation-item:hover {
            background-color: #f8f9fa;
        }
        .conversation-item.active {
            background-color: #e9ecef;
        }
        .message {
            max-width: 75%;
            margin-bottom: 1rem;
        }
        .message.sent {
            margin-left: auto;
        }
        .message.received {
            margin-right: auto;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Conversations List -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Messages</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($conversations as $conv): ?>
                            <a href="?user=<?php echo $conv['user_id']; ?>" 
                               class="list-group-item list-group-item-action conversation-item <?php echo isset($_GET['user']) && $_GET['user'] == $conv['user_id'] ? 'active' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($conv['profile_picture']); ?>" 
                                         class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($conv['full_name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo $conv['last_message'] ? htmlspecialchars(substr($conv['last_message'], 0, 30)) . '...' : 'No messages yet'; ?>
                                        </small>
                                    </div>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="col-md-8">
                <?php if ($selected_user): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo htmlspecialchars($selected_user['profile_picture']); ?>" 
                                 class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                            <h5 class="mb-0"><?php echo htmlspecialchars($selected_user['full_name']); ?></h5>
                        </div>
                    </div>
                    <div class="card-body messages-container" id="messagesContainer">
                        <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                            <div class="card <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'bg-primary text-white' : ''; ?>">
                                <div class="card-body py-2">
                                    <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                </div>
                                <small class="text-muted px-3 pb-1">
                                    <?php echo date('g:i A', strtotime($message['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <form id="messageForm">
                            <input type="hidden" name="receiver_id" value="<?php echo $selected_user['user_id']; ?>">
                            <div class="input-group">
                                <textarea class="form-control" name="content" placeholder="Type a message..." rows="1" required></textarea>
                                <button class="btn btn-primary" type="submit">Send</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <p class="mb-0">Select a conversation to start messaging</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/messages.js"></script>
</body>
</html> 