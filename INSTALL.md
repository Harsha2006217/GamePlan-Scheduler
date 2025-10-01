# GamePlan Scheduler - Installation Guide

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.3 or higher
- Web server (Apache, Nginx, or IIS)
- 50MB disk space
- Modern web browser

## Step-by-Step Installation

### 1. Download and Extract

1. Download the GamePlan Scheduler package
2. Extract the files to your web server directory
3. Ensure all files have proper permissions (644 for files, 755 for directories)

### 2. Database Setup

1. Create a new MySQL database for GamePlan Scheduler
2. Import the database schema from `database.sql`:
   ```bash
   mysql -u username -p database_name < database.sql