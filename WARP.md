# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is an AdminLTE-based Inventory Management System (IMS) built for Benguet State University - Bokod Campus. It's a PHP web application with MySQL/PostgreSQL database support, featuring real-time inventory tracking, property & equipment management, request & approval workflows, and role-based access control.

## Development Commands

### Local Development Setup
```powershell
# Start the application with Docker
docker-compose up -d

# Stop the application
docker-compose down

# View logs
docker-compose logs -f web

# Access local application
# http://localhost:8080
```

### Build Commands
```powershell
# Build Docker image
docker build -t adminlte-inventory .

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### Database Management
```powershell
# Import database schema (local MySQL)
mysql -h localhost -P 3306 -u inventory_user -p inventory_password inv_system < "inv_system (2).sql"

# Connect to local database
mysql -h localhost -P 3306 -u inventory_user -p inventory_password inv_system
```

### Production Deployment
```powershell
# Deploy to Render (automatic on git push to main)
git add .
git commit -m "Deploy updates"
git push origin main
```

## Architecture & Structure

### Core Components
- **Frontend**: AdminLTE 3.2.0 theme with Bootstrap 5, custom CSS styling
- **Backend**: PHP 8.1 with Apache web server
- **Database**: MySQL 8.0 (local) / PostgreSQL (Render production)
- **Authentication**: Session-based with role management (Admin, User, IT)

### Key Directories
- `includes/` - Core PHP configuration, database connections, utility functions
- `layouts/` - Shared templates (header.php, footer.php)
- `templates/` - Page-specific templates
- `css/` - Custom stylesheets
- `dist/` - AdminLTE distribution files
- `plugins/` - Third-party plugins
- `uploads/` - File upload storage
- `vendor/` - Composer dependencies

### Database Configuration
The application uses environment variables for database configuration:
- Local development: MySQL on port 3306
- Production (Render): PostgreSQL with connection details from environment

Key files:
- `includes/config.php` - Database connection setup with env var support
- `includes/database.php` - Database abstraction layer
- `includes/functions.php` - Common utility functions

### Main Application Files
- `index.php` - Landing page with modern UI
- `login.php` - Authentication entry point
- `admin.php` - Admin dashboard
- `home.php` - Main dashboard after login
- Various module files: `items.php`, `requests.php`, `reports.php`, etc.

### Default Credentials
- Admin: username `admin`, password `admin`
- User: username `user`, password `user`
- IT: username `IT`, password `user`

## Environment Configuration

### Required Environment Variables
```env
DB_HOST=<database_host>
DB_USER=<database_username>
DB_PASS=<database_password>
DB_NAME=<database_name>
DB_PORT=<database_port>
```

### Docker Environment
- Web server runs on port 80 (mapped to 8080 locally)
- MySQL service on port 3306 (local development)
- Automatic SSL/HTTPS on Render deployment

## Development Practices

### File Handling
- File uploads go to `uploads/` directory
- Proper permissions set via Docker (www-data:www-data)
- Temporary file storage on Render (files lost on restart)

### Security Considerations
- Input sanitization via `remove_junk()` and `real_escape()` functions
- SQL injection protection using prepared statements
- Session-based authentication with role verification
- XSS protection through HTML entity encoding

### Code Style
- PHP files follow standard formatting
- Database operations abstracted through helper functions
- Frontend uses Bootstrap classes with custom CSS variables
- Modular structure with shared includes

## Testing & Debugging

### Local Testing
```powershell
# Check database connection
php -r "require 'includes/config.php'; echo 'Config loaded successfully';"

# Test Apache configuration
docker-compose exec web apache2ctl configtest

# View PHP error logs
docker-compose logs web | grep -i error
```

### Production Monitoring
- Use Render dashboard logs for debugging
- Database connection issues often related to environment variables
- File permission problems common on deployment

## Database Migrations

The application includes a custom migration system that works with both MySQL and PostgreSQL databases, perfect for Render deployment without shell access.

### Migration Commands
```powershell
# One-click database initialization (RECOMMENDED)
# Visit: http://localhost:8080/init_db.php

# Auto-generate all migrations from schema
# Visit: http://localhost:8080/auto_migrate.php

# Create new migration via web interface
# Visit: http://localhost:8080/create_migration.php

# Create migration via CLI (local development)
php create_migration.php "add_user_settings_table"

# Run migrations via web interface
# Visit: http://localhost:8080/migrate.php
```

### Migration Files Structure
- Location: `migrations/` directory
- Naming: `YYYY_MM_DD_HHMMSS_description.php`
- Class: `Migration_description` extending `Migration`

### Web-based Migration System
- **URL**: `/init_db.php` - **One-click database initialization (RECOMMENDED)**
- **URL**: `/auto_migrate.php` - **Auto-generate all migrations from existing schema**  
- **URL**: `/migrate.php` - Main migration management interface
- **URL**: `/create_migration.php` - Generate new migration files
- Features: Complete automation, run individual migrations, rollback, bulk execution
- Real-time logging and status tracking
- Works on free Render accounts (no shell access needed)

### Automatic Migration Generation
The system can automatically analyze your `inv_system (4).sql` file and generate migrations for:
- **25+ database tables** including users, items, requests, employees, etc.
- **Complete table structures** with all columns and data types
- **Indexes and constraints** for optimal performance
- **Sample data** for immediate testing
- **Foreign key relationships** between tables

### Migration Methods Available
```php
// Table operations
$this->createTable($name, $columns);
$this->dropTable($name);

// Column operations
$this->addColumn($table, $column, $definition);
$this->dropColumn($table, $column);

// Index operations
$this->addIndex($table, $name, $columns);
$this->dropIndex($name, $table);

// Data operations
$this->insertData($table, $dataArray);
$this->executeSQL($sql);
$this->executeSQLBatch($sqlArray);
```

### Example Migration
```php
class Migration_add_user_settings extends Migration {
    public function up() {
        $columns = [
            'id INT AUTO_INCREMENT PRIMARY KEY',
            'user_id INT NOT NULL',
            'setting_key VARCHAR(100)',
            'setting_value TEXT'
        ];
        $this->createTable('user_settings', $columns);
    }
    
    public function down() {
        $this->dropTable('user_settings');
    }
}
```

## Additional Notes

- Application supports both MySQL and PostgreSQL databases
- Uses AdminLTE theme for consistent UI/UX
- Implements PHPWord for document generation
- Session management handles multi-role authentication
- Notification system for user interactions
- Bulk operations support for inventory management
- Custom migration system for schema changes without shell access
