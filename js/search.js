$(document).ready(function() {
    // Send Friend Request
    $('.send-friend-request').click(function() {
        const button = $(this);
        const userId = button.data('user-id');
        
        $.post('ajax/send_friend_request.php', {
            receiver_id: userId
        }, function(response) {
            if (response.success) {
                button.prop('disabled', true)
                    .removeClass('btn-primary')
                    .addClass('btn-secondary')
                    .text('Pending Request');
            } else {
                alert('Error sending friend request');
            }
        }, 'json');
    });

    // Message Friend
    $('.message-friend').click(function() {
        const userId = $(this).data('user-id');
        window.location.href = 'messages.php?user=' + userId;
    });
}); 