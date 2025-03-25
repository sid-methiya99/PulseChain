<div class="comment mb-2" data-comment-id="<?php echo $comment['comment_id']; ?>">
    <div class="d-flex">
        <img src="<?php echo htmlspecialchars($comment['profile_picture']); ?>" 
             class="rounded-circle me-2" style="width: 24px; height: 24px; object-fit: cover;">
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <a href="profile.php?id=<?php echo $comment['user_id']; ?>" class="text-decoration-none">
                        <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                    </a>
                    <small class="text-muted ms-2"><?php echo date('F j, Y, g:i a', strtotime($comment['created_at'])); ?></small>
                </div>
                <?php if ($comment['user_id'] == $_SESSION['user_id'] || $post['user_id'] == $_SESSION['user_id']): ?>
                <div class="dropdown">
                    <button class="btn btn-link btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                        <li>
                            <button class="dropdown-item edit-comment" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editCommentModal"
                                    data-comment-id="<?php echo $comment['comment_id']; ?>"
                                    data-content="<?php echo htmlspecialchars($comment['content']); ?>">
                                <i class="fas fa-edit me-2"></i>Edit
                            </button>
                        </li>
                        <?php endif; ?>
                        <li>
                            <button type="button" 
                                    class="dropdown-item text-danger delete-comment" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteConfirmationModal"
                                    data-comment-id="<?php echo $comment['comment_id']; ?>">
                                <i class="fas fa-trash-alt me-2"></i>Delete
                            </button>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <div class="comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
            <form class="edit-comment-form mt-2" style="display: none;">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($comment['content']); ?>">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <button class="btn btn-secondary cancel-edit-comment" type="button">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div> 