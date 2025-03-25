<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from URL or use logged-in user's ID
$profile_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];

try {
    // Fetch user details
    $stmt = $conn->prepare("
        SELECT u.*, 
        (SELECT COUNT(*) FROM posts WHERE user_id = u.user_id) as post_count,
        (SELECT COUNT(*) FROM friendships 
         WHERE (sender_id = u.user_id OR receiver_id = u.user_id) 
         AND status = 'accepted') as friend_count
        FROM users u 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$profile_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: index.php");
        exit();
    }

    // Check friendship status if viewing other's profile
    if ($profile_id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("
            SELECT status FROM friendships 
            WHERE (sender_id = ? AND receiver_id = ?) 
            OR (sender_id = ? AND receiver_id = ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $profile_id, $profile_id, $_SESSION['user_id']]);
        $friendship = $stmt->fetch();
    }

    // Fetch user's posts
    $stmt = $conn->prepare("
        SELECT p.*, 
            u.username, u.profile_picture,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id AND user_id = ?) as user_liked
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $profile_id]);
    $posts = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile - PulseChain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="position-relative d-inline-block">
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                 class="rounded-circle img-thumbnail" 
                                 style="width: 200px; height: 200px; object-fit: cover;">
                            <?php if ($profile_id == $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-primary position-absolute bottom-0 end-0" 
                                    data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-camera"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <?php if ($profile_id == $_SESSION['user_id']): ?>
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($user['bio'] ?? '')); ?></p>
                        <div class="d-flex gap-4">
                            <div><strong><?php echo $user['post_count']; ?></strong> posts</div>
                            <div><strong><?php echo $user['friend_count']; ?></strong> friends</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User's Posts -->
        <div class="posts-container">
            <?php foreach ($posts as $post): ?>
                <?php include 'includes/post_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <?php if ($profile_id == $_SESSION['user_id']): ?>
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="ajax/update_profile.php" method="POST" enctype="multipart/form-data" id="editProfileForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit Comment Modal -->
    <div class="modal fade" id="editCommentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCommentForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Comment</label>
                            <textarea class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html> 