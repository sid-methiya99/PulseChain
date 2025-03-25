# PulseChain Social Media Platform

PulseChain is a modern social media platform built with PHP, MySQL, and JavaScript. It provides users with a feature-rich environment for social interaction, content sharing, and networking.

## Features

### User Management
- User registration and authentication
- Secure password handling with password hashing
- Profile management with profile pictures
- Show/hide password functionality for better UX

### Social Features
- Friend system with friend requests
- Friend suggestions
- Real-time messaging system
- Post creation with text and image support
- Like and comment functionality on posts
- Notification system for various interactions

### Content Sharing
- Create posts with text content
- Upload images (JPG, PNG, GIF, WebP)
- Image size limit: 2MB
- Responsive image display

### User Interface
- Modern, responsive design using Bootstrap 5
- Font Awesome icons for enhanced visual appeal
- Sticky sidebars for better navigation
- Real-time updates for likes and comments
- Clean and intuitive user experience

## Technical Stack

- **Backend**: PHP 7+
- **Database**: MySQL
- **Frontend**: 
  - HTML5
  - CSS3
  - JavaScript (jQuery)
  - Bootstrap 5
  - Font Awesome 6

## Project Structure

```
pulsexchain/
├── ajax/                    # AJAX handlers
│   ├── add_comment.php
│   ├── get_new_messages.php
│   ├── send_message.php
│   └── ...
├── config/                  # Configuration files
│   └── database.php
├── includes/               # Reusable components
│   └── navbar.php
├── uploads/                # User uploaded content
│   └── posts/
├── index.php              # Main feed page
├── login.php              # Login page
├── signup.php             # Registration page
└── README.md
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements for all database queries
- Session-based authentication
- Input validation and sanitization
- XSS prevention through proper escaping
- File upload validation and restrictions

## Getting Started

1. Clone the repository
2. Set up a MySQL database
3. Configure database connection in `config/database.php`
4. Ensure proper permissions on the `uploads` directory
5. Access the application through your web server

## Database Schema

The application uses the following main tables:
- users
- posts
- comments
- likes
- friendships
- messages
- notifications

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details. 