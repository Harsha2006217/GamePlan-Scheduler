# GamePlan Scheduler

A professional gaming session and event scheduling application built with PHP, MySQL, Bootstrap, and modern JavaScript. Designed for young gamers to plan and coordinate their gaming activities with friends.

## Features

### Core Functionality
- **User Authentication**: Secure registration and login with password encryption
- **Profile Management**: User profiles with favorite games and bio
- **Friend System**: Add friends, view online status, and manage connections
- **Schedule Management**: Create and manage gaming session schedules
- **Event Management**: Organize tournaments and special gaming events
- **Sharing System**: Share events with friends and manage permissions

### Advanced Features
- **Real-time Updates**: Live notifications and activity tracking
- **Search & Filtering**: Advanced search and filter options
- **Responsive Design**: Mobile-friendly interface with modern UI
- **Security**: CSRF protection, input validation, and secure sessions
- **Accessibility**: Screen reader support and keyboard navigation
- **Performance**: Optimized with lazy loading and debounced search

### Technical Features
- **Database**: MySQL with proper relationships and indexing
- **Security**: Argon2ID password hashing, session management, brute force protection
- **Validation**: Client and server-side validation with real-time feedback
- **API Ready**: Structured for future API development
- **GDPR Compliant**: Privacy-focused design with data protection

## Technology Stack

- **Backend**: PHP 8.0+ with PDO
- **Database**: MySQL 8.0+ with UTF-8 support
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6
- **Security**: Argon2ID, CSRF tokens, input sanitization

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer (optional, for dependency management)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/gameplan-scheduler.git
   cd gameplan-scheduler
   ```

2. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p < database/schema.sql

   # Or import manually through phpMyAdmin
   ```

3. **Web Server Configuration**
   - Point your web server to the `PHP/` directory
   - Ensure `mod_rewrite` is enabled for clean URLs
   - Set proper file permissions (755 for directories, 644 for files)

4. **Configuration**
   - Update database credentials in `functions.php`
   - Configure email settings if needed
   - Set up SSL certificate for production

5. **Access the Application**
   - Open your browser and navigate to the application URL
   - Register a new account or use demo credentials

## Database Schema

The application uses a normalized MySQL database with the following main tables:

- `Users` - User accounts and profiles
- `Games` - Available games library
- `UserGames` - User favorite games (many-to-many)
- `Friends` - Friend relationships
- `Schedules` - Gaming session schedules
- `Events` - Tournaments and special events
- `EventUserMap` - Event sharing with friends
- `activity_log` - User activity tracking
- `login_attempts` - Security logging

## File Structure

```
gameplan-scheduler/
├── PHP/                    # Main application files
│   ├── functions.php      # Core functions and database operations
│   ├── index.php          # Dashboard/home page
│   ├── login.php          # User login
│   ├── register.php       # User registration
│   ├── profile.php        # User profile management
│   ├── friends.php        # Friend management
│   ├── schedules.php      # Schedule listing and management
│   ├── events.php         # Event listing and management
│   ├── add_schedule.php   # Create new schedule
│   ├── add_event.php      # Create new event
│   ├── edit_schedule.php  # Edit existing schedule
│   ├── edit_event.php     # Edit existing event
│   ├── delete_schedule.php # Delete schedule
│   ├── delete_event.php   # Delete event
│   ├── logout.php         # User logout
│   └── privacy.php        # Privacy policy page
├── CSS/
│   └── style.css          # Main stylesheet
├── JS/
│   └── script.js          # JavaScript functionality
├── database/
│   └── schema.sql         # Database schema and sample data
└── README.md              # This file
```

## Security Features

- **Password Security**: Argon2ID hashing with proper cost factors
- **Session Management**: Secure session handling with regeneration
- **CSRF Protection**: Token-based CSRF prevention
- **Input Validation**: Comprehensive client and server-side validation
- **SQL Injection Prevention**: Prepared statements with PDO
- **XSS Protection**: Input sanitization and output escaping
- **Brute Force Protection**: Login attempt limiting and account locking
- **Activity Logging**: Comprehensive audit trail

## Usage

### For Users
1. **Register**: Create an account with a unique username and email
2. **Login**: Sign in with your credentials
3. **Setup Profile**: Add your favorite games and personal information
4. **Add Friends**: Connect with other gamers
5. **Create Schedules**: Plan gaming sessions with friends
6. **Organize Events**: Set up tournaments and special events
7. **Share & Coordinate**: Share events and coordinate with your gaming community

### For Developers
- All functions are documented in `functions.php`
- Database operations use PDO with prepared statements
- Frontend uses modern JavaScript with progressive enhancement
- CSS uses CSS custom properties for easy theming
- Code follows PSR-12 coding standards

## API Endpoints (Future)

The application is structured to easily add REST API endpoints:

- `GET /api/schedules` - Get user schedules
- `POST /api/schedules` - Create new schedule
- `GET /api/friends` - Get friend list
- `POST /api/friends` - Add friend
- `GET /api/events` - Get events
- `POST /api/events` - Create event

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please contact:
- **Developer**: Harsha Kanaparthi
- **Email**: support@gameplan-scheduler.com
- **Documentation**: [Wiki](https://github.com/yourusername/gameplan-scheduler/wiki)

## Demo Credentials

For testing purposes, you can use:
- **Email**: demo@gameplan.com
- **Password**: DemoPass123!

## Version History

### v1.0.0 (Current)
- Initial release with core functionality
- User authentication and profiles
- Friend system and social features
- Schedule and event management
- Responsive design and accessibility
- Security hardening and validation

## Future Enhancements

- [ ] Mobile app development
- [ ] Real-time chat system
- [ ] Calendar integration
- [ ] Game statistics tracking
- [ ] Tournament bracket generation
- [ ] Push notifications
- [ ] Multi-language support
- [ ] Advanced analytics dashboard

---

**Built with ❤️ for the gaming community by Harsha Kanaparthi**
