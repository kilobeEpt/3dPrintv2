# Backend Deployment Guide

Complete guide for deploying the 3D Print Pro API to production.

## Pre-Deployment Checklist

Before deploying, ensure:

- ✅ All tests pass locally
- ✅ Health check endpoint returns success
- ✅ Database migrations are up to date
- ✅ Environment variables are configured
- ✅ Dependencies are installed (`composer install`)
- ✅ Production `.env` is ready (do NOT commit it)

## Environment Configuration

### Production .env Template

```env
# Environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# CORS - Your production frontend URL
CORS_ORIGIN=https://yourdomain.com,https://www.yourdomain.com

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ch167436_3dprint
DB_USERNAME=api_user
DB_PASSWORD=strong_random_password_here

# JWT Authentication
JWT_SECRET=generate_strong_random_secret_at_least_64_characters_long
JWT_ALGORITHM=HS256
JWT_EXPIRATION=3600

# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_chat_id_here

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=60
RATE_LIMIT_WINDOW=60
```

### Security Best Practices

1. **Never commit `.env` to version control**
2. **Generate strong secrets:**
   ```bash
   openssl rand -base64 64
   ```
3. **Create dedicated database user:**
   ```sql
   CREATE USER 'api_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON ch167436_3dprint.* TO 'api_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

## Deployment Options

### Option 1: Traditional VPS (Recommended)

Deploy on VPS with full control (DigitalOcean, Linode, AWS EC2, etc.)

#### 1. Server Requirements

- Ubuntu 20.04+ or Debian 11+
- PHP 7.4+ with extensions: pdo, pdo_mysql, mbstring, json
- MySQL 8.0+
- Apache 2.4+ or Nginx 1.18+
- Composer

#### 2. Install Prerequisites

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-pdo \
    php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip

# Install MySQL
sudo apt install -y mysql-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### 3. Deploy Code

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/yourusername/3dprint-pro.git
cd 3dprint-pro/backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/3dprint-pro
sudo chmod -R 755 /var/www/3dprint-pro

# Create .env file
sudo nano .env
# Paste production configuration
```

#### 4. Configure Database

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS ch167436_3dprint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'api_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON ch167436_3dprint.* TO 'api_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Run migrations
mysql -u root -p < database/migrations/20231113_initial.sql
mysql -u root -p ch167436_3dprint < database/seeds/initial_data.sql
```

#### 5A. Configure Apache

```bash
# Install Apache
sudo apt install -y apache2

# Create virtual host
sudo nano /etc/apache2/sites-available/3dprint-api.conf
```

Paste this configuration:

```apache
<VirtualHost *:80>
    ServerName api.yourdomain.com
    DocumentRoot /var/www/3dprint-pro/backend/public

    <Directory /var/www/3dprint-pro/backend/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/3dprint-api-error.log
    CustomLog ${APACHE_LOG_DIR}/3dprint-api-access.log combined
</VirtualHost>
```

Enable site and modules:

```bash
# Enable mod_rewrite
sudo a2enmod rewrite

# Enable site
sudo a2ensite 3dprint-api.conf

# Restart Apache
sudo systemctl restart apache2
```

#### 5B. Configure Nginx (Alternative)

```bash
# Install Nginx and PHP-FPM
sudo apt install -y nginx php8.1-fpm

# Create server block
sudo nano /etc/nginx/sites-available/3dprint-api
```

Use the config from `nginx.conf.example` (update paths and domain).

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/3dprint-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

#### 6. Configure SSL (HTTPS)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache
# OR for Nginx: python3-certbot-nginx

# Get SSL certificate
sudo certbot --apache -d api.yourdomain.com
# OR for Nginx: sudo certbot --nginx -d api.yourdomain.com

# Auto-renewal is configured automatically
# Test renewal:
sudo certbot renew --dry-run
```

#### 7. Configure Firewall

```bash
# Allow HTTP, HTTPS, SSH
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

#### 8. Test Deployment

```bash
curl https://api.yourdomain.com/api/health
```

### Option 2: Shared Hosting (Timeweb, cPanel, etc.)

#### Requirements

- PHP 7.4+ with PDO MySQL
- MySQL 8.0+
- SSH access (optional but recommended)
- Ability to set custom document root

#### Deployment Steps

1. **Upload Files**
   - Use FTP/SFTP or File Manager
   - Upload entire `backend` directory

2. **Set Document Root**
   - In control panel, set document root to: `public_html/backend/public`
   - Or create subdomain: `api.yourdomain.com` → `backend/public`

3. **Configure Database**
   - Create database via control panel
   - Create database user
   - Import `database/migrations/20231113_initial.sql`
   - Import `database/seeds/initial_data.sql`

4. **Create .env File**
   - Create `.env` in backend root
   - Use File Manager or SSH
   - Paste production configuration

5. **Install Dependencies**
   - Via SSH: `cd backend && composer install`
   - OR upload `vendor` directory if no SSH access

6. **Test**
   - Visit: `https://api.yourdomain.com/api/health`

### Option 3: Platform as a Service (PaaS)

#### Heroku

Create `Procfile` in backend root:
```
web: vendor/bin/heroku-php-apache2 public/
```

Deploy:
```bash
# Login to Heroku
heroku login

# Create app
heroku create 3dprint-api

# Add MySQL addon
heroku addons:create jawsdb:kitefin

# Set environment variables
heroku config:set APP_ENV=production
heroku config:set JWT_SECRET=$(openssl rand -base64 64)
# ... set other variables

# Deploy
git subtree push --prefix backend heroku main

# Run migrations
heroku run bash
mysql -h $JAWSDB_HOST -u $JAWSDB_USER -p$JAWSDB_PASSWORD $JAWSDB_DATABASE < database/migrations/20231113_initial.sql
```

## Post-Deployment

### Verify Installation

1. **Check health endpoint:**
   ```bash
   curl https://api.yourdomain.com/api/health
   ```

2. **Check database connection:**
   - Health endpoint should show `"connected": true`

3. **Test CORS:**
   ```bash
   curl -H "Origin: https://yourdomain.com" \
        -H "Access-Control-Request-Method: POST" \
        -H "Access-Control-Request-Headers: Content-Type" \
        -X OPTIONS \
        https://api.yourdomain.com/api/health
   ```

4. **Check logs:**
   ```bash
   # Apache
   sudo tail -f /var/log/apache2/3dprint-api-error.log
   
   # Nginx
   sudo tail -f /var/log/nginx/3dprint-api-error.log
   ```

### Performance Optimization

#### Enable OPcache

Edit PHP configuration:
```bash
sudo nano /etc/php/8.1/fpm/conf.d/10-opcache.ini
```

Add:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

Restart PHP:
```bash
sudo systemctl restart php8.1-fpm
```

#### Enable Gzip Compression

Already configured in `.htaccess` (Apache) and `nginx.conf.example` (Nginx).

#### Database Optimization

```sql
-- Add indexes for common queries
USE ch167436_3dprint;

-- Analyze tables
ANALYZE TABLE users, services, orders, portfolio;

-- Optimize tables
OPTIMIZE TABLE users, services, orders, portfolio;
```

### Monitoring

#### Set Up Log Rotation

```bash
sudo nano /etc/logrotate.d/3dprint-api
```

Add:
```
/var/www/3dprint-pro/backend/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
```

#### Monitor Error Logs

```bash
# Watch API errors in real-time
sudo tail -f /var/log/apache2/3dprint-api-error.log

# Watch PHP errors
sudo tail -f /var/log/php8.1-fpm.log
```

### Backup Strategy

#### Automated Database Backups

```bash
# Create backup script
sudo nano /usr/local/bin/backup-3dprint-db.sh
```

Add:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/3dprint-db"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_NAME="ch167436_3dprint"
DB_USER="backup_user"
DB_PASS="backup_password"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/${DB_NAME}_${TIMESTAMP}.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete
```

Make executable:
```bash
sudo chmod +x /usr/local/bin/backup-3dprint-db.sh
```

Add to crontab:
```bash
sudo crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-3dprint-db.sh
```

#### Code Backups

Use Git for code versioning:
```bash
cd /var/www/3dprint-pro
git remote add origin https://github.com/yourusername/3dprint-pro.git
git push origin main
```

### Security Hardening

1. **Keep software updated:**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Restrict file permissions:**
   ```bash
   sudo chmod 640 /var/www/3dprint-pro/backend/.env
   sudo chown www-data:www-data /var/www/3dprint-pro/backend/.env
   ```

3. **Disable directory listing:**
   - Already configured in `.htaccess` and `nginx.conf.example`

4. **Configure fail2ban:**
   ```bash
   sudo apt install fail2ban
   sudo systemctl enable fail2ban
   ```

5. **Regular security audits:**
   ```bash
   composer audit
   ```

## Troubleshooting Production Issues

### 500 Internal Server Error

Check Apache/Nginx error logs:
```bash
sudo tail -100 /var/log/apache2/3dprint-api-error.log
```

Common causes:
- Incorrect file permissions
- Missing `.env` file
- PHP syntax errors
- Missing PHP extensions

### Database Connection Failed

Verify:
1. Database credentials in `.env`
2. Database user has correct permissions
3. MySQL is running: `sudo systemctl status mysql`
4. Firewall allows MySQL connection

### CORS Issues

Verify `CORS_ORIGIN` in `.env` matches frontend domain exactly (including protocol and subdomain).

### Performance Issues

1. Enable OPcache (see above)
2. Add database indexes
3. Enable query caching
4. Use CDN for static assets
5. Consider adding Redis/Memcached

## Rollback Strategy

If deployment fails:

1. **Revert code:**
   ```bash
   cd /var/www/3dprint-pro
   git reset --hard HEAD~1
   composer install --no-dev --optimize-autoloader
   ```

2. **Restore database:**
   ```bash
   mysql -u root -p ch167436_3dprint < /var/backups/3dprint-db/backup.sql
   ```

3. **Restart services:**
   ```bash
   sudo systemctl restart apache2  # or nginx
   sudo systemctl restart php8.1-fpm
   ```

## Updating Production

```bash
# Backup first!
/usr/local/bin/backup-3dprint-db.sh

# Pull latest code
cd /var/www/3dprint-pro
git pull origin main

# Update dependencies
cd backend
composer install --no-dev --optimize-autoloader

# Run new migrations (if any)
mysql -u root -p ch167436_3dprint < database/migrations/NEW_MIGRATION.sql

# Clear OPcache
sudo systemctl reload php8.1-fpm

# Verify
curl https://api.yourdomain.com/api/health
```

## Support

For deployment issues:
1. Check logs first
2. Verify all prerequisites
3. Test database connection
4. Review security settings
5. Consult [README.md](README.md) troubleshooting section

## Production Checklist

- [ ] `.env` configured with production values
- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Strong `JWT_SECRET` generated
- [ ] HTTPS enabled (SSL certificate)
- [ ] CORS restricted to frontend domain
- [ ] Database user with minimal privileges
- [ ] File permissions set correctly
- [ ] Web server configured (Apache/Nginx)
- [ ] Firewall configured
- [ ] Automated backups enabled
- [ ] Log rotation configured
- [ ] Health endpoint responding
- [ ] Error logging working
- [ ] OPcache enabled
- [ ] Monitoring in place

Congratulations! Your API is now deployed to production. 🚀
