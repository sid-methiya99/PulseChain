$(document).ready(function() {
    // Initialize Bootstrap components
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Initialize modals
    let commentToDelete = null;
    let deleteModal = null;

    // Initialize delete modal if it exists
    const deleteModalElement = document.getElementById('deleteConfirmationModal');
    if (deleteModalElement) {
        deleteModal = new bootstrap.Modal(deleteModalElement);
    }

    // Like functionality
    $('.like-btn').click(function() {
        const button = $(this);
        const postId = button.data('post-id');
        
        $.post('ajax/like_post.php', {post_id: postId}, function(response) {
            if (response.success) {
                const likeCount = button.find('.like-count');
                if (response.liked) {
                    button.removeClass('btn-outline-primary').addClass('btn-primary');
                    likeCount.text(parseInt(likeCount.text()) + 1);
                } else {
                    button.removeClass('btn-primary').addClass('btn-outline-primary');
                    likeCount.text(parseInt(likeCount.text()) - 1);
                }
            }
        }, 'json');
    });

    // Comment functionality
    $('.comment-btn').click(function() {
        const postId = $(this).data('post-id');
        const commentsSection = $(`#comments-${postId}`);
        
        if (commentsSection.is(':hidden')) {
            loadComments(postId);
            commentsSection.show();
        } else {
            commentsSection.hide();
        }
    });

    // Submit new comment
    $('.comment-form').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const postId = form.closest('.comments-section').attr('id').split('-')[1];
        const input = form.find('input');
        const content = input.val().trim();

        if (content) {
            $.post('ajax/add_comment.php', {
                post_id: postId,
                content: content
            }, function(response) {
                if (response.success) {
                    input.val('');
                    loadComments(postId);
                    updateCommentCount(postId);
                }
            }, 'json');
        }
    });

    // Edit comment
    let currentCommentId = null;
    let currentCommentContainer = null;

    $(document).on('click', '.edit-comment', function() {
        const button = $(this);
        currentCommentId = button.data('comment-id');
        currentCommentContainer = button.closest('.comment');
        const content = button.data('content');
        
        // Set the content in the modal
        $('#editCommentModal textarea').val(content);
    });

    // Handle comment edit form submission
    $('#editCommentForm').on('submit', function(e) {
        e.preventDefault();
        const content = $(this).find('textarea').val().trim();

        if (!content) return;

        $.post('ajax/edit_comment.php', {
            comment_id: currentCommentId,
            content: content
        }, function(response) {
            if (response.success) {
                currentCommentContainer.find('.comment-content').html(response.content);
                $('#editCommentModal').modal('hide');
            } else {
                alert('Error updating comment: ' + (response.error || 'Unknown error'));
            }
        }, 'json');
    });

    // Reset modal when closed
    $('#editCommentModal').on('hidden.bs.modal', function() {
        $(this).find('textarea').val('');
        currentCommentId = null;
        currentCommentContainer = null;
    });

    // When delete button is clicked
    $(document).on('click', '.delete-comment', function() {
        commentToDelete = {
            id: $(this).data('comment-id'),
            container: $(this).closest('.comment'),
            postContainer: $(this).closest('.card')
        };
        if (deleteModal) {
            deleteModal.show();
        }
    });

    // When confirm delete button is clicked
    $('#confirmDeleteBtn').click(function() {
        if (!commentToDelete) return;

        $.post('ajax/delete_comment.php', {
            comment_id: commentToDelete.id
        }, function(response) {
            if (response.success) {
                commentToDelete.container.fadeOut(function() {
                    $(this).remove();
                    // Update comment count
                    const commentCount = commentToDelete.postContainer.find('.comment-count');
                    commentCount.text(parseInt(commentCount.text()) - 1);
                });
                if (deleteModal) {
                    deleteModal.hide();
                }
            } else {
                alert('Error deleting comment: ' + (response.error || 'Unknown error'));
            }
        }, 'json');
    });

    // Reset commentToDelete when modal is closed
    $('#deleteConfirmationModal').on('hidden.bs.modal', function() {
        commentToDelete = null;
    });

    // Edit post
    $(document).on('click', '.edit-post', function() {
        const button = $(this);
        const postId = button.data('post-id');
        const content = button.data('content');
        const postCard = button.closest('.card');
        const postContent = postCard.find('.post-content');
        const editForm = postCard.find('.edit-post-form');
        
        editForm.find('textarea').val(content);
        postContent.hide();
        editForm.show();
    });

    // Cancel post edit
    $(document).on('click', '.cancel-edit', function() {
        const form = $(this).closest('.edit-post-form');
        const postCard = form.closest('.card');
        form.hide();
        postCard.find('.post-content').show();
    });

    // Submit post edit
    $(document).on('submit', '.edit-post-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const postCard = form.closest('.card');
        const postId = postCard.find('.edit-post').data('post-id');
        const content = form.find('textarea').val().trim();

        if (!content) return;

        $.post('ajax/edit_post.php', {
            post_id: postId,
            content: content
        }, function(response) {
            if (response.success) {
                postCard.find('.post-content').html(nl2br(response.content));
                form.hide();
                postCard.find('.post-content').show();
            } else {
                alert('Error updating post: ' + (response.error || 'Unknown error'));
            }
        }, 'json');
    });

    // Delete post
    $(document).on('click', '.delete-post', function() {
        if (!confirm('Are you sure you want to delete this post?')) return;

        const button = $(this);
        const postId = button.data('post-id');
        const postCard = button.closest('.card');

        $.post('ajax/delete_post.php', {
            post_id: postId
        }, function(response) {
            if (response.success) {
                postCard.fadeOut(function() {
                    $(this).remove();
                });
            } else {
                alert('Error deleting post: ' + (response.error || 'Unknown error'));
            }
        }, 'json');
    });

    // Helper function to convert newlines to <br>
    function nl2br(str) {
        return str.replace(/\n/g, '<br>');
    }
});

function loadComments(postId) {
    const commentsList = $(`#comments-${postId} .comments-list`);
    
    $.get('ajax/get_comments.php', {
        post_id: postId
    }, function(response) {
        if (response.success) {
            commentsList.html(response.html);
            // Re-initialize any tooltips or popovers if using them
            $('[data-bs-toggle="tooltip"]').tooltip();
            $('[data-bs-toggle="popover"]').popover();
        } else {
            alert('Error loading comments: ' + (response.error || 'Unknown error'));
        }
    }, 'json')
    .fail(function() {
        alert('Error loading comments. Please try again.');
    });
}

function updateCommentCount(postId) {
    const commentCount = $(`[data-post-id="${postId}"] .comment-count`);
    commentCount.text(parseInt(commentCount.text()) + 1);
} 