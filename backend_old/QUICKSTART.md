# Backend Quick Start Guide

This guide will help you get the 3D Print Pro backend API up and running in minutes.

## Prerequisites Check

Before starting, ensure you have:

- ✅ **PHP 7.4 or higher** with PDO MySQL extension
- ✅ **Composer** (PHP dependency manager)
- ✅ **MySQL 8.0+** server running
- ✅ **Database created** (run migrations from `database/migrations/`)

### Verify Prerequisites

```bash
# Check PHP version
php -v
# Should show: PHP 7.4.x or higher

# Check Composer
composer --version
# Should show: Composer version 2.x

# Check MySQL
mysql --version
# Should show: MySQL 8.0 or higher

# Check PHP PDO MySQL extension
php -m | grep pdo_mysql
# Should show: pdo_mysql
```

## Installation Steps

### Step 1: Install Dependencies

```bash
cd backend
composer install
```

This will install:
- Slim Framework 4 (routing)
- Slim PSR-7 (HTTP messages)
- vlucas/phpdotenv (environment management)
- firebase/php-jwt (JWT authentication)

### Step 2: Configure Environment

```bash
# Copy the example environment file
cp .env.example .env

# Edit the .env file
nano .env
```

**Required Configuration:**

```env
# Database - Update with your credentials
DB_HOST=localhost
DB_DATABASE=ch167436_3dprint
DB_USERNAME=root
DB_PASSWORD=your_password_here

# CORS - Your frontend URL
CORS_ORIGIN=http://localhost:8000

# JWT Secret - Generate a random string
JWT_SECRET=replace_with_random_string_at_least_32_characters_long
```

**Generate a secure JWT secret:**

```bash
# Option 1: Using OpenSSL
openssl rand -base64 32

# Option 2: Using PHP
php -r "echo bin2hex(random_bytes(32));"
```

### Step 3: Create Database (if not done already)

```bash
# From project root directory
mysql -u root -p < backend/database/migrations/20231113_initial.sql
mysql -u root -p ch167436_3dprint < backend/database/seeds/initial_data.sql
```

### Step 4: Start Development Server

```bash
# Make sure you're in the backend directory
cd backend

# Start PHP built-in server
php -S localhost:8080 -t public

# Or use Composer script
composer start
```

The API will be available at: `http://localhost:8080/api`

### Step 5: Test the API

**Method 1: Using curl**

```bash
curl http://localhost:8080/api/health
```

**Method 2: Using web browser**

Open in browser: http://localhost:8080/api/health

**Expected Response:**

```json
{
  "status": "healthy",
  "timestamp": "2023-11-13 10:30:00",
  "environment": "development",
  "database": {
    "connected": true,
    "message": "Database connection successful",
    "version": "8.0.35",
    "database": "ch167436_3dprint"
  }
}
```

**If database connection fails:**

```json
{
  "status": "unhealthy",
  "timestamp": "2023-11-13 10:30:00",
  "environment": "development",
  "database": {
    "connected": false,
    "message": "Database connection failed",
    "error": "SQLSTATE[HY000] [1045] Access denied for user..."
  }
}
```

## Troubleshooting

### Composer Install Fails

**Error:** `composer: command not found`

**Solution:** Install Composer:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### PHP Version Too Old

**Error:** `Your PHP version (7.3.x) does not satisfy that requirement`

**Solution:** Upgrade PHP:
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.1 php8.1-pdo php8.1-mysql

# macOS (Homebrew)
brew install php@8.1
```

### PDO MySQL Extension Missing

**Error:** `could not find driver`

**Solution:** Install PHP MySQL extension:
```bash
# Ubuntu/Debian
sudo apt install php-mysql

# macOS (Homebrew)
brew install php
# PDO MySQL is included by default
```

### Database Connection Failed

**Check 1:** Verify database exists
```bash
mysql -u root -p -e "SHOW DATABASES LIKE 'ch167436_3dprint';"
```

**Check 2:** Verify credentials in `.env` match your MySQL setup

**Check 3:** Test MySQL connection manually
```bash
mysql -h localhost -u root -p ch167436_3dprint -e "SELECT 1;"
```

**Check 4:** Ensure MySQL is running
```bash
# Linux
sudo systemctl status mysql

# macOS
brew services list | grep mysql
```

### Port Already in Use

**Error:** `Address already in use`

**Solution:** Use a different port:
```bash
php -S localhost:8081 -t public
```

Update `CORS_ORIGIN` in `.env` if needed.

### CORS Errors from Frontend

**Error:** `Access to fetch at 'http://localhost:8080/api/...' has been blocked by CORS policy`

**Solution:** Verify `CORS_ORIGIN` in `.env` matches your frontend URL exactly:
```env
CORS_ORIGIN=http://localhost:8000
```

For multiple origins:
```env
CORS_ORIGIN=http://localhost:8000,http://127.0.0.1:8000
```

## Next Steps

Once the health check passes:

1. ✅ **API is ready** - Database connected and working
2. 📝 **Read the docs** - See [README.md](README.md) for full documentation
3. 🔌 **Connect frontend** - Update frontend to use API endpoints
4. 🚀 **Deploy** - See deployment guide for production setup

## Production Deployment

For production deployment, see:

- [Backend README](README.md) - Full documentation
- [Apache Setup](public/.htaccess) - Already configured
- [Nginx Setup](nginx.conf.example) - Example configuration

**Quick Production Checklist:**

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate strong `JWT_SECRET`
- [ ] Use HTTPS (SSL certificate)
- [ ] Restrict `CORS_ORIGIN` to your domain
- [ ] Create dedicated database user with limited privileges
- [ ] Set proper file permissions
- [ ] Configure web server (Apache/Nginx)
- [ ] Enable OPcache for PHP
- [ ] Set up error logging

## Support

If you encounter issues:

1. Check the [README.md](README.md) troubleshooting section
2. Verify all prerequisites are met
3. Check PHP error logs
4. Test database connection manually
5. Ensure `.env` configuration is correct

## Development Tips

### Watch for Changes (Unix/Linux/Mac)

```bash
# Install nodemon (if you have Node.js)
npm install -g nodemon

# Run with auto-reload
nodemon --exec "php -S localhost:8080 -t public" --watch src --ext php
```

### Enable Debug Mode

In `.env`:
```env
APP_ENV=development
APP_DEBUG=true
```

This will show detailed error messages and stack traces.

### Test Database Connection Directly

Create a test file `test-db.php`:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    echo "✅ Database connection successful!\n";
    
    $stmt = $pdo->query('SELECT VERSION()');
    echo "MySQL version: " . $stmt->fetchColumn() . "\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
```

Run it:
```bash
php test-db.php
```

## Quick Reference

| Command | Description |
|---------|-------------|
| `composer install` | Install dependencies |
| `composer start` | Start dev server |
| `php -S localhost:8080 -t public` | Start server manually |
| `curl http://localhost:8080/api/health` | Test health endpoint |
| `composer dump-autoload` | Regenerate autoloader |

## What's Next?

Now that your backend is running:

1. **Explore the API** - Visit http://localhost:8080/api
2. **Add endpoints** - See "Adding New Routes" in [README.md](README.md)
3. **Connect frontend** - Update frontend API calls
4. **Implement authentication** - JWT setup included
5. **Deploy to production** - Follow deployment guide

Happy coding! 🚀
