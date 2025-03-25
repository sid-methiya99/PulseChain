$(document).ready(function() {
    // Auto-scroll messages container to bottom
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Handle message form submission
    $('#messageForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const textarea = form.find('textarea');
        const content = textarea.val().trim();
        const receiverId = form.find('input[name="receiver_id"]').val();

        if (!content) return;

        $.post('ajax/send_message.php', {
            receiver_id: receiverId,
            content: content
        }, function(response) {
            if (response.success) {
                // Add message to container
                const messageHtml = `
                    <div class="message sent">
                        <div class="card bg-primary text-white">
                            <div class="card-body py-2">
                                ${$('<div>').text(content).html()}
                            </div>
                            <small class="text-muted px-3 pb-1">
                                ${new Date().toLocaleTimeString([], {hour: 'numeric', minute:'2-digit'})}
                            </small>
                        </div>
                    </div>
                `;
                $('#messagesContainer').append(messageHtml);
                
                // Clear textarea and scroll to bottom
                textarea.val('');
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } else {
                alert('Error sending message: ' + (response.error || 'Unknown error'));
            }
        }, 'json');
    });

    // Handle conversation click
    $('.conversation').click(function() {
        const userId = $(this).data('user-id');
        window.location.href = `messages.php?user=${userId}`;
    });

    // Auto-resize textarea
    $('textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Poll for new messages every 5 seconds if a conversation is open
    if ($('#messagesContainer').length) {
        const receiverId = $('input[name="receiver_id"]').val();
        let lastMessageTime = $('.message').last().data('time') || 0;

        function pollNewMessages() {
            $.get('ajax/get_new_messages.php', {
                receiver_id: receiverId,
                last_time: lastMessageTime
            }, function(response) {
                if (response.success && response.messages.length > 0) {
                    response.messages.forEach(function(message) {
                        const messageHtml = `
                            <div class="message ${message.sender_id == receiverId ? 'received' : 'sent'}">
                                <div class="card ${message.sender_id == receiverId ? '' : 'bg-primary text-white'}">
                                    <div class="card-body py-2">
                                        ${$('<div>').text(message.content).html()}
                                    </div>
                                    <small class="text-muted px-3 pb-1">
                                        ${new Date(message.created_at).toLocaleTimeString([], {hour: 'numeric', minute:'2-digit'})}
                                    </small>
                                </div>
                            </div>
                        `;
                        $('#messagesContainer').append(messageHtml);
                        lastMessageTime = message.created_at;
                    });
                    
                    // Scroll to bottom if user was already at bottom
                    const shouldScroll = messagesContainer.scrollTop + messagesContainer.clientHeight === messagesContainer.scrollHeight;
                    if (shouldScroll) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }
            }, 'json');
        }

        // Start polling
        setInterval(pollNewMessages, 5000);
    }
});