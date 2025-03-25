$(document).ready(function() {
    // Accept friend request
    $('.accept-request').click(function() {
        const button = $(this);
        const userId = button.data('user-id');
        const container = button.closest('.col-md-6');
        
        // Disable button while processing
        button.prop('disabled', true);
        
        $.post('ajax/accept_friend.php', {
            user_id: userId
        }, function(response) {
            if (response.success) {
                container.fadeOut(function() {
                    $(this).remove();
                    // Reload page if no more requests
                    if ($('.accept-request').length === 0) {
                        location.reload();
                    }
                });
            } else {
                alert('Error accepting friend request: ' + (response.error || 'Unknown error'));
                button.prop('disabled', false);
            }
        }, 'json')
        .fail(function() {
            alert('Error processing request. Please try again.');
            button.prop('disabled', false);
        });
    });

    // Reject friend request
    $('.reject-request').click(function() {
        const button = $(this);
        const userId = button.data('user-id');
        const container = button.closest('.col-md-6');
        
        $.post('ajax/reject_friend.php', {
            user_id: userId
        }, function(response) {
            if (response.success) {
                container.fadeOut(function() {
                    $(this).remove();
                    // Reload page if no more requests
                    if ($('.reject-request').length === 0) {
                        location.reload();
                    }
                });
            } else {
                alert('Error rejecting friend request');
            }
        }, 'json');
    });

    // Unfriend
    $('.unfriend-btn').click(function() {
        if (confirm('Are you sure you want to unfriend this user?')) {
            const button = $(this);
            const userId = button.data('user-id');
            const container = button.closest('.col-md-6');
            
            $.post('ajax/unfriend.php', {
                user_id: userId
            }, function(response) {
                if (response.success) {
                    container.fadeOut(function() {
                        $(this).remove();
                        // Reload page if no more friends
                        if ($('.unfriend-btn').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert('Error removing friend');
                }
            }, 'json');
        }
    });

    // Message friend (will implement in messaging system)
    $('.message-friend').click(function() {
        const userId = $(this).data('user-id');
        window.location.href = 'messages.php?user=' + userId;
    });
}); 