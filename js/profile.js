$(document).ready(function() {
    // Profile picture preview
    $('input[name="profile_picture"]').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('.profile-picture-preview').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    // Edit Profile Form Submission
    $('#editProfileForm').submit(function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error updating profile: ' + (response.error || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error updating profile: ' + error);
            }
        });
    });

    // Friend request functionality
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
                alert('Error sending friend request: ' + (response.error || 'Unknown error'));
            }
        }, 'json');
    });

    // Unfriend functionality
    $('.unfriend-btn').click(function() {
        if (!confirm('Are you sure you want to unfriend this user?')) return;

        const button = $(this);
        const userId = button.data('user-id');
        
        $.post('ajax/unfriend.php', {
            user_id: userId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error removing friend: ' + (response.error || 'Unknown error'));
            }
        }, 'json');
    });
}); 