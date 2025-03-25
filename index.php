<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle new post creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']);
    $user_id = $_SESSION['user_id'];
    
    // Handle image upload if present
    $image_url = null;
    $upload_error = null;
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] != 4) { // 4 means no file was uploaded
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB in bytes
        
        if ($_FILES['post_image']['error'] !== 0) {
            switch ($_FILES['post_image']['error']) {
                case 1:
                case 2:
                    $upload_error = "The image is too large. Maximum size is 2MB.";
                    break;
                case 3:
                    $upload_error = "The image was only partially uploaded. Please try again.";
                    break;
                default:
                    $upload_error = "There was an error uploading your image. Please try again.";
            }
        } elseif (!in_array($_FILES['post_image']['type'], $allowed_types)) {
            $upload_error = "Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.";
        } elseif ($_FILES['post_image']['size'] > $max_size) {
            $upload_error = "The image is too large. Maximum size is 2MB.";
        } else {
            $target_dir = "uploads/posts/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Generate a unique filename
            $file_extension = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
            $unique_filename = uniqid('post_', true) . '.' . $file_extension;
            $target_file = $target_dir . $unique_filename;
            
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
            } else {
                $upload_error = "Failed to save the image. Please try again.";
            }
        }
    }

    if (!$upload_error) {
        try {
            $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_url) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $content, $image_url]);
            header("Location: index.php");
            exit();
        } catch(PDOException $e) {
            $error = "Error creating post: " . $e->getMessage();
        }
    } else {
        $error = $upload_error;
    }
}

// Get current user's info
try {
    $stmt = $conn->prepare("
        SELECT u.*, 
        (SELECT COUNT(*) FROM posts WHERE user_id = u.user_id) as post_count,
        (SELECT COUNT(*) FROM friendships WHERE (sender_id = u.user_id OR receiver_id = u.user_id) AND status = 'accepted') as friend_count
        FROM users u 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();

    // Fetch posts (from user and friends)
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.full_name, u.profile_picture,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id AND user_id = ?) as user_liked
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN friendships f ON (f.sender_id = ? AND f.receiver_id = p.user_id) 
            OR (f.receiver_id = ? AND f.sender_id = p.user_id)
        WHERE p.user_id = ? OR (f.status = 'accepted')
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $posts = $stmt->fetchAll();

    // Get friend suggestions
    $stmt = $conn->prepare("
        SELECT u.user_id, u.username, u.full_name, u.profile_picture
        FROM users u
        LEFT JOIN friendships f ON (f.sender_id = ? AND f.receiver_id = u.user_id)
            OR (f.sender_id = u.user_id AND f.receiver_id = ?)
        WHERE u.user_id != ?
        AND f.friendship_id IS NULL
        ORDER BY RAND()
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $suggestions = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - PulseChain</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sticky-sidebar {
            position: sticky;
            top: 1rem;
        }
        .post-card {
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .post-card .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .post-card .card-footer {
            background-color: #fff;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        .create-post-card {
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-stats {
            font-size: 0.9rem;
        }
        .suggestion-card {
            border-radius: 15px;
        }
        .btn-action {
            border-radius: 20px;
        }
        .profile-picture {
            width: 40px;
            height: 40px;
            object-fit: cover;
        }
        .profile-picture-lg {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="row g-4">
            <!-- Left Sidebar -->
            <div class="col-lg-3">
                <div class="sticky-sidebar">
                    <div class="card mb-3">
                        <div class="card-body text-center">
                            <img src="<?php echo htmlspecialchars($current_user['profile_picture']); ?>" 
                                 class="rounded-circle profile-picture-lg mb-3">
                            <h5 class="mb-1"><?php echo htmlspecialchars($current_user['full_name']); ?></h5>
                            <p class="text-muted mb-3">@<?php echo htmlspecialchars($current_user['username']); ?></p>
                            <div class="row profile-stats">
                                <div class="col">
                                    <strong><?php echo $current_user['post_count']; ?></strong><br>
                                    <small class="text-muted">Posts</small>
                                </div>
                                <div class="col">
                                    <strong><?php echo $current_user['friend_count']; ?></strong><br>
                                    <small class="text-muted">Friends</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="list-group">
                        <a href="index.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-home me-2"></i> Home
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                        <a href="friends.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i> Friends
                        </a>
                        <a href="messages.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2"></i> Messages
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-6">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Create Post Form -->
                <div class="card create-post-card mb-4">
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="d-flex mb-3">
                                <img src="<?php echo htmlspecialchars($current_user['profile_picture']); ?>" 
                                     class="rounded-circle profile-picture me-2">
                                <textarea class="form-control" name="post_content" 
                                          placeholder="What's on your mind?" required></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group">
                                    <label class="btn btn-outline-primary btn-sm btn-action">
                                        <i class="fas fa-image"></i> Add Photo
                                        <input type="file" name="post_image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                                    </label>
                                    <small class="text-muted ms-2 file-name"></small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-action">Post</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Posts Feed -->
                <?php foreach ($posts as $post): ?>
                <div class="card post-card mb-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo htmlspecialchars($post['profile_picture']); ?>" 
                                 class="rounded-circle profile-picture me-2">
                            <div>
                                <h6 class="mb-0">
                                    <a href="profile.php?id=<?php echo $post['user_id']; ?>" 
                                       class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($post['full_name']); ?>
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        <?php if ($post['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                 class="img-fluid rounded mb-3">
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button class="btn btn-sm <?php echo $post['user_liked'] ? 'btn-primary' : 'btn-outline-primary'; ?> btn-action like-btn" 
                                        data-post-id="<?php echo $post['post_id']; ?>">
                                    <i class="fas fa-thumbs-up"></i> 
                                    <span class="like-count"><?php echo $post['like_count']; ?></span>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary btn-action comment-btn ms-2" 
                                        data-post-id="<?php echo $post['post_id']; ?>">
                                    <i class="fas fa-comment"></i> 
                                    <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                                </button>
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div class="comments-section mt-3" id="comments-<?php echo $post['post_id']; ?>" style="display: none;">
                            <div class="comments-list">
                                <!-- Comments will be loaded here via AJAX -->
                            </div>
                            <form class="comment-form mt-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Write a comment...">
                                    <button class="btn btn-primary btn-action" type="submit">Send</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Right Sidebar -->
            <div class="col-lg-3">
                <div class="sticky-sidebar">
                    <div class="card suggestion-card">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">People You May Know</h6>
                        </div>
                        <div class="card-body p-0">
                            <?php foreach ($suggestions as $suggestion): ?>
                            <div class="d-flex align-items-center p-3 border-bottom">
                                <img src="<?php echo htmlspecialchars($suggestion['profile_picture']); ?>" 
                                     class="rounded-circle profile-picture me-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">
                                        <a href="profile.php?id=<?php echo $suggestion['user_id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($suggestion['full_name']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($suggestion['username']); ?></small>
                                </div>
                                <button class="btn btn-sm btn-primary btn-action send-friend-request"
                                        data-user-id="<?php echo $suggestion['user_id']; ?>">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle file input change
            $('input[type="file"]').change(function() {
                const input = this;
                const fileName = $(this).val().split('\\').pop();
                const fileSize = input.files[0] ? input.files[0].size : 0;
                const maxSize = 2 * 1024 * 1024; // 2MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                if (fileName) {
                    if (fileSize > maxSize) {
                        alert('The selected file is too large. Maximum size is 2MB.');
                        $(this).val('');
                        $(this).parent().siblings('.file-name').text('');
                        return;
                    }
                    
                    if (input.files[0] && !allowedTypes.includes(input.files[0].type)) {
                        alert('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.');
                        $(this).val('');
                        $(this).parent().siblings('.file-name').text('');
                        return;
                    }
                    
                    $(this).parent().siblings('.file-name').text(fileName);
                } else {
                    $(this).parent().siblings('.file-name').text('');
                }
            });

            // Like button click
            $('.like-btn').click(function() {
                const button = $(this);
                const postId = button.data('post-id');
                
                $.post('ajax/like_post.php', {
                    post_id: postId
                }, function(response) {
                    if (response.success) {
                        const count = response.liked ? 
                            parseInt(button.find('.like-count').text()) + 1 :
                            parseInt(button.find('.like-count').text()) - 1;
                        
                        button.find('.like-count').text(count);
                        button.toggleClass('btn-outline-primary btn-primary');
                    }
                }, 'json');
            });

            // Comment button click
            $('.comment-btn').click(function() {
                const postId = $(this).data('post-id');
                const commentsSection = $(`#comments-${postId}`);
                
                if (commentsSection.is(':hidden')) {
                    $.get('ajax/get_comments.php', {
                        post_id: postId
                    }, function(response) {
                        if (response.success) {
                            let commentsHtml = '';
                            response.comments.forEach(function(comment) {
                                commentsHtml += `
                                    <div class="d-flex mb-2">
                                        <img src="${comment.profile_picture}" class="rounded-circle profile-picture me-2" style="width: 32px; height: 32px;">
                                        <div class="flex-grow-1">
                                            <div class="bg-light rounded p-2">
                                                <strong>${comment.username}</strong><br>
                                                ${comment.content}
                                            </div>
                                            <small class="text-muted">${comment.created_at}</small>
                                        </div>
                                    </div>
                                `;
                            });
                            commentsSection.find('.comments-list').html(commentsHtml);
                        }
                    }, 'json');
                }
                commentsSection.slideToggle();
            });

            // Comment form submit
            $('.comment-form').submit(function(e) {
                e.preventDefault();
                const form = $(this);
                const postId = form.closest('.comments-section').attr('id').split('-')[1];
                const input = form.find('input');
                const content = input.val().trim();
                
                if (!content) return;
                
                const submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.post('ajax/add_comment.php', {
                    post_id: postId,
                    content: content
                }, function(response) {
                    submitBtn.prop('disabled', false).html('Send');
                    
                    if (response.success) {
                        const commentHtml = `
                            <div class="d-flex mb-2">
                                <img src="${response.comment.profile_picture}" 
                                     class="rounded-circle profile-picture me-2" 
                                     style="width: 32px; height: 32px;">
                                <div class="flex-grow-1">
                                    <div class="bg-light rounded p-2">
                                        <strong>${response.comment.username}</strong><br>
                                        ${$('<div>').text(response.comment.content).html()}
                                    </div>
                                    <small class="text-muted">${response.comment.created_at}</small>
                                </div>
                            </div>
                        `;
                        form.closest('.comments-section').find('.comments-list').append(commentHtml);
                        input.val('');
                        
                        // Update comment count
                        const countSpan = form.closest('.card').find('.comment-btn .comment-count');
                        countSpan.text(parseInt(countSpan.text()) + 1);
                    } else {
                        alert(response.error || 'Error adding comment. Please try again.');
                    }
                }, 'json')
                .fail(function() {
                    submitBtn.prop('disabled', false).html('Send');
                    alert('Error adding comment. Please try again.');
                });
            });

            // Friend request button click
            $('.send-friend-request').click(function() {
                const button = $(this);
                const userId = button.data('user-id');
                
                $.post('ajax/send_friend_request.php', {
                    receiver_id: userId
                }, function(response) {
                    if (response.success) {
                        button.prop('disabled', true)
                            .html('<i class="fas fa-check"></i>')
                            .removeClass('btn-primary')
                            .addClass('btn-secondary');
                    }
                }, 'json');
            });
        });
    </script>
</body>
</html> 