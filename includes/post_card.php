<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <img src="<?php echo htmlspecialchars($post['profile_picture']); ?>" 
                 class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
            <div>
                <h6 class="mb-0">
                    <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($post['username']); ?>
                    </a>
                </h6>
                <small class="text-muted"><?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></small>
            </div>
        </div>
        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
        <div class="dropdown">
            <button class="btn btn-link text-dark" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <button class="dropdown-item edit-post" data-post-id="<?php echo $post['post_id']; ?>"
                            data-content="<?php echo htmlspecialchars($post['content']); ?>">
                        <i class="fas fa-edit me-2"></i>Edit
                    </button>
                </li>
                <li>
                    <button class="dropdown-item text-danger delete-post" data-post-id="<?php echo $post['post_id']; ?>">
                        <i class="fas fa-trash-alt me-2"></i>Delete
                    </button>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="card-text post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
        <?php if ($post['image_url']): ?>
            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="img-fluid mb-3">
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-sm <?php echo $post['user_liked'] ? 'btn-primary' : 'btn-outline-primary'; ?> like-btn" 
                        data-post-id="<?php echo $post['post_id']; ?>">
                    <i class="fas fa-thumbs-up"></i> 
                    <span class="like-count"><?php echo $post['like_count']; ?></span>
                </button>
                <button class="btn btn-sm btn-outline-secondary comment-btn" 
                        data-post-id="<?php echo $post['post_id']; ?>">
                    <i class="fas fa-comment"></i> 
                    <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                </button>
            </div>
        </div>

        <!-- Edit Form (Hidden by default) -->
        <form class="edit-post-form mt-3" style="display: none;">
            <div class="mb-3">
                <textarea class="form-control" rows="3" required></textarea>
            </div>
            <div>
                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                <button type="button" class="btn btn-secondary btn-sm cancel-edit">Cancel</button>
            </div>
        </form>

        <!-- Comments Section -->
        <div class="comments-section mt-3" id="comments-<?php echo $post['post_id']; ?>" style="display: none;">
            <div class="comments-list">
                <!-- Comments will be loaded here via AJAX -->
            </div>
            <form class="comment-form mt-2">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Write a comment...">
                    <button class="btn btn-primary" type="submit">Send</button>
                </div>
            </form>
        </div>
    </div>
</div> 