<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    // Fetch pending friend requests
    $stmt = $conn->prepare("
        SELECT f.*, u.username, u.full_name, u.profile_picture 
        FROM friendships f
        JOIN users u ON u.user_id = f.sender_id
        WHERE f.receiver_id = ? AND f.status = 'pending'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_requests = $stmt->fetchAll();

    // Fetch current friends
    $stmt = $conn->prepare("
        SELECT u.user_id, u.username, u.full_name, u.profile_picture,
               f.created_at as friendship_date
        FROM friendships f
        JOIN users u ON (u.user_id = f.sender_id OR u.user_id = f.receiver_id)
        WHERE (f.sender_id = ? OR f.receiver_id = ?)
        AND f.status = 'accepted'
        AND u.user_id != ?
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $friends = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends - PulseChain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Friend Requests Section -->
        <?php if (!empty($pending_requests)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Friend Requests</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($pending_requests as $request): ?>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo htmlspecialchars($request['profile_picture']); ?>" 
                                 class="rounded-circle me-3" style="width: 50px; height: 50px;">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">
                                    <a href="profile.php?id=<?php echo $request['sender_id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($request['full_name']); ?>
                                    </a>
                                </h6>
                                <small class="text-muted">@<?php echo htmlspecialchars($request['username']); ?></small>
                            </div>
                            <div class="ms-3">
                                <button class="btn btn-sm btn-success accept-request" 
                                        data-user-id="<?php echo $request['sender_id']; ?>">
                                    Accept
                                </button>
                                <button class="btn btn-sm btn-danger reject-request"
                                        data-user-id="<?php echo $request['sender_id']; ?>">
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Friends List Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Your Friends</h5>
            </div>
            <div class="card-body">
                <?php if (empty($friends)): ?>
                    <p class="text-muted">You don't have any friends yet.</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($friends as $friend): ?>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($friend['profile_picture']); ?>" 
                                     class="rounded-circle me-3" style="width: 50px; height: 50px;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">
                                        <a href="profile.php?id=<?php echo $friend['user_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($friend['full_name']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($friend['username']); ?></small>
                                    <br>
                                    <small class="text-muted">Friends since <?php echo date('F j, Y', strtotime($friend['friendship_date'])); ?></small>
                                </div>
                                <div class="ms-3">
                                    <button class="btn btn-sm btn-outline-primary message-friend"
                                            data-user-id="<?php echo $friend['user_id']; ?>">
                                        Message
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger unfriend-btn"
                                            data-user-id="<?php echo $friend['user_id']; ?>">
                                        Unfriend
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/friends.js"></script>
</body>
</html> 