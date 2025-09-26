# GamePlan Scheduler

A professional gaming session and event scheduling application built with PHP, MySQL, Bootstrap, and modern JavaScript. Designed for young gamers to plan and coordinate their gaming activities with friends.

## Features

### Core Functionality
- **User Authentication**: Secure registration and login with password encryption using Argon2ID hashing
- **Profile Management**: User profiles with favorite games and bio, editable with validation
- **Friend System**: Add friends, view online status, and manage connections with search functionality
- **Schedule Management**: Create and manage gaming session schedules with calendar view
- **Event Management**: Organize tournaments and special gaming events with sharing options
- **Sharing System**: Share events with friends and manage permissions via user mapping
- **Real-time Updates**: Live notifications and activity tracking with JavaScript polling
- **Search & Filtering**: Advanced search and filter options for schedules and events
- **Responsive Design**: Mobile-friendly interface with Bootstrap 5 and custom CSS
- **Security**: CSRF protection, input validation, session management, and brute force prevention
- **Accessibility**: Screen reader support, keyboard navigation, and high-contrast modes
- **Performance**: Optimized with lazy loading, debounced search, and efficient queries

### Advanced Features
- **Calendar Integration**: Interactive calendar with drag-and-drop for schedules/events
- **Notifications**: In-app pop-ups, email reminders, and push notifications (future)
- **Analytics Dashboard**: User activity logs and statistics (future)
- **Multi-language Support**: English/Dutch toggle (future)
- **API Ready**: RESTful endpoints for mobile app integration

### Technical Features
- **Database**: MySQL with proper relationships, indexing, and normalization
- **Security**: Argon2ID password hashing, session regeneration, input sanitization, and XSS prevention
- **Validation**: Client-side (JavaScript) and server-side (PHP) validation with real-time feedback
- **GDPR Compliant**: Data export/deletion options and privacy policy
- **Scalability**: Prepared for high traffic with caching and optimization

## Technology Stack

- **Backend**: PHP 8.0+ with PDO for secure database interactions
- **Database**: MySQL 8.0+ with UTF-8 support and foreign key constraints
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3 for responsive grids and components
- **Icons**: Font Awesome 6 for intuitive UI elements
- **Security Libraries**: Built-in PHP functions for hashing and sessions

## Installation

### Prerequisites
- PHP 8.0 or higher with PDO extension
- MySQL 8.0 or higher
- Apache/Nginx web server with mod_rewrite
- Composer (optional for dependencies)
- Node.js (optional for JS minification)

### Setup Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/gameplan-scheduler.git
   cd gameplan-scheduler
   ```

2. **Database Setup**
   - Import `database/schema.sql` into MySQL:
     ```bash
     mysql -u root -p < database/schema.sql
     ```
   - Update credentials in `PHP/functions.php` (e.g., DB_HOST, DB_USER, DB_PASS).

3. **Web Server Configuration**
   - Point your web server document root to the `PHP/` directory
   - Enable mod_rewrite for clean URLs
   - Set file permissions: 755 for directories, 644 for files
   - Ensure PHP settings: upload_max_filesize=5M, session.gc_maxlifetime=1800

4. **Configuration**
   - Update email settings in `PHP/functions.php` for notifications
   - Configure HTTPS in production
   - Set up environment variables for security (e.g., via .env file)

5. **Access the Application**
   - Navigate to the application URL
   - Register a new account or use demo credentials

## Database Schema

The application uses a normalized MySQL database:

- `Users` (user_id PK, username, email, password_hash, last_activity)
- `Games` (game_id PK, title, description)
- `UserGames` (user_id FK, game_id FK)
- `Friends` (friend_id PK, user_id FK, friend_user_id FK)
- `Schedules` (schedule_id PK, user_id FK, game_id FK, date, time, friends)
- `Events` (event_id PK, user_id FK, title, date, time, description, reminder, schedule_id FK)
- `EventUserMap` (event_id FK, friend_id FK)
- `activity_log` (log_id PK, user_id FK, action, timestamp)

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
