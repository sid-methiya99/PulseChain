<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$users = [];
$error = null;

if ($search_query) {
    try {
        $stmt = $conn->prepare("
            SELECT u.*, 
            (SELECT COUNT(*) FROM friendships 
             WHERE ((sender_id = u.user_id AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = u.user_id))
             AND status = 'accepted') as is_friend,
            (SELECT status FROM friendships 
             WHERE ((sender_id = u.user_id AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = u.user_id))
             AND status = 'pending') as pending_status
            FROM users u 
            WHERE u.user_id != ? 
            AND (u.username LIKE ? OR u.full_name LIKE ?)
            ORDER BY u.full_name ASC
        ");
        
        $search_param = "%$search_query%";
        $stmt->execute([
            $_SESSION['user_id'], 
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            $search_param,
            $search_param
        ]);
        $users = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - PulseChain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h5>
            </div>
            <div class="card-body">
                <?php if (empty($users) && $search_query): ?>
                    <p class="text-muted">No users found matching your search.</p>
                <?php elseif (!empty($users)): ?>
                    <div class="row">
                        <?php foreach ($users as $user): ?>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                     class="rounded-circle me-3" style="width: 50px; height: 50px;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">
                                        <a href="profile.php?id=<?php echo $user['user_id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                </div>
                                <div class="ms-3">
                                    <?php if ($user['is_friend']): ?>
                                        <button class="btn btn-sm btn-outline-primary message-friend"
                                                data-user-id="<?php echo $user['user_id']; ?>">
                                            Message
                                        </button>
                                    <?php elseif ($user['pending_status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-secondary" disabled>Pending Request</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-primary send-friend-request"
                                                data-user-id="<?php echo $user['user_id']; ?>">
                                            Add Friend
                                        </button>
                                    <?php endif; ?>
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
    <script src="js/search.js"></script>
</body>
</html> 