# 🔧 Troubleshooting Guide - 3D Print Pro Backend

## Quick Diagnosis

Run these commands in order:

```bash
# 1. Check system requirements
php test-setup.php

# 2. Check database connection
php test-db.php

# 3. Test API routes
php test-routes.php

# 4. Run comprehensive diagnostics
php diagnose.php

# 5. Final verification
php final-check.php
```

## Common Errors and Solutions

### ❌ Error: "Not Found" on all API requests

**Symptoms:**
- All requests to `/api/*` return 404
- Health check endpoint not accessible
- API root returns "Not Found"

**Causes:**
1. URL rewriting not configured
2. `.htaccess` missing or not working
3. Document root pointing to wrong directory
4. Nginx `try_files` not configured

**Solutions:**

#### For Apache:

1. **Check if mod_rewrite is enabled:**
```bash
# Apache 2.4
apachectl -M | grep rewrite

# If not enabled
sudo a2enmod rewrite
sudo systemctl restart apache2
```

2. **Verify .htaccess exists:**
```bash
ls -la backend/public/.htaccess
```

3. **Check AllowOverride:**
In Apache config:
```apache
<Directory /path/to/backend/public>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

4. **Restart Apache:**
```bash
sudo systemctl restart apache2
```

#### For Nginx:

1. **Check nginx configuration:**
```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /path/to/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

2. **Test and reload:**
```bash
sudo nginx -t
sudo systemctl reload nginx
```

#### For Shared Hosting:

Create `.htaccess` in backend root:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

### ❌ Error: "vendor/autoload.php not found"

**Symptoms:**
- Fatal error about missing autoloader
- "Class not found" errors
- API returns 500 error

**Causes:**
- Composer dependencies not installed
- `vendor/` folder missing or deleted

**Solutions:**

1. **Install Composer (if not installed):**
```bash
curl -sS https://getcomposer.org/installer | php
```

2. **Install dependencies:**
```bash
cd backend
composer install --no-dev --optimize-autoloader
```

3. **If Composer 1 vs 2 conflict:**
```bash
composer self-update --2
composer install --no-dev --optimize-autoloader
```

4. **Alternative (without SSH):**
- Run `composer install` locally
- Upload entire `vendor/` folder via FTP
- Ensure all files uploaded successfully

5. **Verify installation:**
```bash
ls -la vendor/autoload.php
php -r "require 'vendor/autoload.php'; echo 'OK';"
```

---

### ❌ Error: "Database connection failed"

**Symptoms:**
- Health check shows database error
- API returns "Database connection failed"
- Login doesn't work

**Causes:**
- Wrong database credentials in `.env`
- Database doesn't exist
- MySQL server not running
- Firewall blocking connection

**Solutions:**

1. **Check .env configuration:**
```env
DB_HOST=localhost          # Usually localhost
DB_PORT=3306              # Standard MySQL port
DB_DATABASE=your_db_name  # Must match created database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_pass
```

2. **Test connection manually:**
```bash
mysql -h localhost -u your_db_user -p your_db_name
```

3. **Common mistakes:**

**Wrong host:**
```env
# Try these if localhost doesn't work
DB_HOST=localhost
DB_HOST=127.0.0.1
DB_HOST=mysql   # Docker
DB_HOST=host.docker.internal  # Docker on Mac
```

**Wrong port:**
```bash
# Check MySQL port
mysql -u root -p -e "SHOW VARIABLES LIKE 'port';"
```

4. **Create database if missing:**
```sql
CREATE DATABASE ch167436_3dprint 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON ch167436_3dprint.* 
TO 'your_user'@'localhost';

FLUSH PRIVILEGES;
```

5. **Check MySQL is running:**
```bash
sudo systemctl status mysql
sudo systemctl start mysql
```

---

### ❌ Error: "JWT token invalid" or "Unauthorized"

**Symptoms:**
- Cannot login even with correct credentials
- Token validation fails
- "Invalid or expired token" errors

**Causes:**
- JWT_SECRET changed after token issued
- JWT_SECRET using default value
- Token expired
- Server time incorrect

**Solutions:**

1. **Check JWT_SECRET in .env:**
```env
# WRONG - using default
JWT_SECRET=change_this_secret

# CORRECT - random string
JWT_SECRET=your_64_character_random_string_here_generated_by_secure_method
```

2. **Generate new JWT_SECRET:**
```bash
# Method 1: OpenSSL
openssl rand -base64 64

# Method 2: PHP
php -r "echo bin2hex(random_bytes(32));"

# Method 3: Online
# https://randomkeygen.com/
```

3. **Update .env and re-login:**
- Change JWT_SECRET in `.env`
- Clear browser localStorage
- Login again

4. **Check server time:**
```bash
date
timedatectl status
```

If time is wrong:
```bash
sudo timedatectl set-ntp true
sudo timedatectl set-timezone Europe/Moscow
```

---

### ❌ Error: "Class not found" or "Class 'App\...' not found"

**Symptoms:**
- Fatal error about missing class
- Autoloader not finding classes
- 500 Internal Server Error

**Causes:**
- Autoloader not regenerated after changes
- PSR-4 namespace mismatch
- File not uploaded or corrupted

**Solutions:**

1. **Regenerate autoloader:**
```bash
cd backend
composer dump-autoload --optimize
```

2. **Check file exists:**
```bash
ls -la src/Bootstrap/App.php
ls -la src/Config/Database.php
ls -la src/Helpers/Response.php
```

3. **Verify namespace:**

File: `src/Config/Database.php`
```php
namespace App\Config;  // Must match PSR-4 mapping
```

File: `composer.json`
```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

4. **Clear PHP OPcache:**
```bash
# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Or via web
<?php opcache_reset(); ?>
```

---

### ❌ Error: "Permission denied" or "Failed to open stream"

**Symptoms:**
- Cannot write to log files
- Storage errors
- Cache errors

**Causes:**
- Wrong file permissions
- Wrong file owner
- SELinux blocking

**Solutions:**

1. **Set correct permissions:**
```bash
cd backend

# Make storage writable
chmod -R 775 storage/
chmod -R 775 storage/logs/
chmod -R 775 storage/cache/
```

2. **Set correct owner:**
```bash
# For Apache
sudo chown -R www-data:www-data storage/

# For Nginx
sudo chown -R nginx:nginx storage/

# For shared hosting (replace with your username)
chown -R your-username:your-username storage/
```

3. **Create directories if missing:**
```bash
mkdir -p storage/logs
mkdir -p storage/cache
chmod -R 775 storage/
```

4. **Check SELinux (if enabled):**
```bash
# Check status
getenforce

# Fix context
sudo chcon -R -t httpd_sys_rw_content_t storage/

# Or disable (not recommended)
sudo setenforce 0
```

---

### ❌ Error: CORS errors in browser console

**Symptoms:**
- `Access-Control-Allow-Origin` error
- Frontend can't call API
- OPTIONS request fails

**Causes:**
- CORS_ORIGIN not configured
- Wrong CORS_ORIGIN value
- CORS middleware not working

**Solutions:**

1. **Configure CORS in .env:**
```env
# Single origin
CORS_ORIGIN=https://yourdomain.com

# Multiple origins
CORS_ORIGIN=https://yourdomain.com,https://www.yourdomain.com

# All origins (not recommended for production)
CORS_ORIGIN=*
```

2. **Check browser console:**
```
Access to fetch at 'https://api.domain.com/api/health' 
from origin 'https://domain.com' has been blocked by CORS policy
```

Fix: Add `https://domain.com` to CORS_ORIGIN

3. **For preflight requests:**

Verify OPTIONS requests work:
```bash
curl -X OPTIONS https://api.domain.com/api/health \
  -H "Origin: https://domain.com" \
  -H "Access-Control-Request-Method: GET" \
  -v
```

Should return headers:
```
Access-Control-Allow-Origin: https://domain.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE
```

---

### ❌ Error: "No admin users found"

**Symptoms:**
- Cannot login to admin panel
- Database empty after migration
- No default admin created

**Causes:**
- Seed script not run
- Admin user deleted
- Wrong database

**Solutions:**

1. **Run seed script:**
```bash
cd backend
php database/seeds/seed-admin-user.php
```

2. **Create admin manually via phpMyAdmin:**

```sql
INSERT INTO users (
    login, 
    password_hash, 
    name, 
    email, 
    role, 
    active, 
    created_at, 
    updated_at
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: password
    'Administrator',
    'admin@example.com',
    'admin',
    TRUE,
    NOW(),
    NOW()
);
```

3. **Generate password hash:**
```bash
php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
```

4. **Use password reset tool:**
```bash
cd backend
php bin/reset-password.php
```

---

### ❌ Error: 500 Internal Server Error

**Symptoms:**
- White screen
- Generic 500 error
- No detailed error message

**Causes:**
- PHP error
- Configuration error
- Missing dependencies

**Solutions:**

1. **Enable error display temporarily:**

In `.env`:
```env
APP_DEBUG=true
```

Or in `public/index.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', '1');
```

2. **Check server error logs:**
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP-FPM
tail -f /var/log/php8.1-fpm.log
```

3. **Check application logs:**
```bash
tail -f storage/logs/app.log
```

4. **Common PHP errors:**

**Memory limit:**
```ini
# php.ini
memory_limit = 256M
```

**Execution time:**
```ini
# php.ini
max_execution_time = 300
```

**File uploads:**
```ini
# php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

---

### ❌ Error: Telegram notifications not working

**Symptoms:**
- Orders submitted but no Telegram message
- Telegram test fails
- "Invalid bot token" error

**Causes:**
- Wrong bot token
- Wrong chat ID
- Bot not started
- Firewall blocking Telegram API

**Solutions:**

1. **Verify bot token:**
```bash
# Test via curl
curl https://api.telegram.org/bot<YOUR_TOKEN>/getMe
```

Should return bot info, not error.

2. **Get correct chat ID:**
```bash
cd backend
php test-telegram.php

# Or via web
https://yourdomain.com/backend/test-telegram.php
```

3. **Start bot chat:**
- Open Telegram
- Search for your bot (@your_bot_name)
- Click START button
- Send a message

4. **Update .env:**
```env
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=123456789
```

5. **Test connection:**
```bash
curl -X POST https://api.telegram.org/bot<TOKEN>/sendMessage \
  -d "chat_id=<CHAT_ID>" \
  -d "text=Test message"
```

**See also:** `docs/TELEGRAM_INTEGRATION.md`

---

## Debugging Tools

### 1. PHP Info
Create `info.php`:
```php
<?php phpinfo(); ?>
```
Access at: `https://yourdomain.com/backend/info.php`
**DELETE after checking!**

### 2. Test Script
```php
<?php
// test.php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "PHP Version: " . PHP_VERSION . "\n";
echo "DB Host: " . $_ENV['DB_HOST'] . "\n";
echo "App Env: " . $_ENV['APP_ENV'] . "\n";

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    echo "✓ Database connected\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
```

### 3. Curl Tests
```bash
# Test health endpoint
curl https://yourdomain.com/backend/public/api/health

# Test login
curl -X POST https://yourdomain.com/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"password"}'

# Test with token
curl https://yourdomain.com/backend/public/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Performance Issues

### Slow Response Times

1. **Enable OPcache:**
```ini
# php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

2. **Add database indexes:**
```sql
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_services_active ON services(active);
```

3. **Enable query caching:**
```ini
# my.cnf
query_cache_type=1
query_cache_size=64M
```

---

## Security Checklist

- [ ] JWT_SECRET is a strong random string
- [ ] APP_DEBUG=false in production
- [ ] HTTPS enabled (SSL certificate)
- [ ] .env file not accessible from web
- [ ] File permissions correct (no 777)
- [ ] Admin password changed from default
- [ ] CORS_ORIGIN limited to your domain
- [ ] Regular backups configured
- [ ] Error logs monitored

---

## Getting More Help

1. **Check logs first:**
```bash
tail -100 storage/logs/app.log
tail -100 /var/log/apache2/error.log
tail -100 /var/log/nginx/error.log
```

2. **Run diagnostics:**
```bash
php diagnose.php
```

3. **Check documentation:**
- [README.md](README.md)
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
- [docs/AUTHENTICATION.md](docs/AUTHENTICATION.md)

4. **Test incrementally:**
- test-setup.php ✓
- test-db.php ✓
- test-routes.php ✓
- final-check.php ✓

---

**Last Updated:** 2024-11-14
