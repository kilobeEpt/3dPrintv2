# 🚀 Quick Reference - 3D Print Pro Backend

## Essential Commands

### Installation
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy environment file
cp .env.example .env

# Edit .env with your settings
nano .env
```

### Database Setup
```bash
# Import schema (via phpMyAdmin or CLI)
mysql -u username -p database_name < database/migrations/20231113_initial.sql

# Import seed data
mysql -u username -p database_name < database/seeds/initial_data.sql

# Create admin user
php database/seeds/seed-admin-user.php
```

### Testing
```bash
# Test setup
php test-setup.php

# Test database
php test-db.php

# Test routes
php test-routes.php

# Full diagnostics
php diagnose.php

# Final verification
php final-check.php
```

### Maintenance
```bash
# Clear autoloader cache
composer dump-autoload --optimize

# Check PHP version
php -v

# Check PHP extensions
php -m

# Test database connection
mysql -u username -p -h localhost database_name

# Check logs
tail -f storage/logs/app.log
tail -f /var/log/apache2/error.log
```

## Environment Variables (.env)

### Required
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ch167436_3dprint
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_pass

JWT_SECRET=your_64_char_random_string_here

CORS_ORIGIN=https://yourdomain.com
```

### Optional
```env
TELEGRAM_BOT_TOKEN=123456789:ABC...
TELEGRAM_CHAT_ID=123456789
```

## API Endpoints

### Public Endpoints
- `GET /api` - API information
- `GET /api/health` - Health check
- `GET /api/services` - List services
- `GET /api/portfolio` - List portfolio
- `GET /api/testimonials` - List testimonials
- `GET /api/faq` - List FAQ
- `GET /api/content` - Get content
- `GET /api/stats` - Get statistics
- `GET /api/settings/public` - Public settings
- `POST /api/orders` - Submit order/contact form
- `POST /api/auth/login` - Login (get JWT)

### Protected Endpoints (Admin)
- `GET /api/auth/me` - Get current user
- `POST /api/auth/refresh` - Refresh token
- `GET /api/orders` - List orders
- `GET /api/orders/{id}` - Get order
- `PUT /api/orders/{id}` - Update order
- `DELETE /api/orders/{id}` - Delete order
- `GET /api/admin/services` - Manage services
- `GET /api/admin/portfolio` - Manage portfolio
- `GET /api/admin/testimonials` - Manage testimonials
- `GET /api/admin/faq` - Manage FAQ
- `GET /api/settings` - Get all settings
- `PUT /api/settings` - Update general settings
- `PUT /api/settings/calculator` - Update calculator
- `PUT /api/settings/forms` - Update forms
- `PUT /api/settings/telegram` - Update Telegram

## Common Fixes

### "Not Found" on all routes
```bash
# Apache: Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check .htaccess exists
ls public/.htaccess
```

### "vendor/autoload.php not found"
```bash
composer install --no-dev --optimize-autoloader
```

### "Database connection failed"
```bash
# Check .env credentials
cat .env | grep DB_

# Test connection
mysql -u username -p -h localhost database_name
```

### "Class not found"
```bash
composer dump-autoload --optimize
```

### "Permission denied"
```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

### JWT errors
```bash
# Generate new secret
openssl rand -base64 64

# Update .env
JWT_SECRET=new_generated_secret
```

### CORS errors
```env
# In .env
CORS_ORIGIN=https://yourdomain.com
```

## File Permissions

### Standard Setup
```bash
# Backend directory
chmod 755 backend/
chmod 755 backend/public/

# Storage (must be writable)
chmod 775 storage/
chmod 775 storage/logs/
chmod 775 storage/cache/

# .env (read only)
chmod 600 .env
```

### Ownership
```bash
# Apache
sudo chown -R www-data:www-data backend/

# Nginx
sudo chown -R nginx:nginx backend/

# Shared hosting
chown -R your-username:your-username backend/
```

## Security Checklist

- [ ] Change `JWT_SECRET` from default
- [ ] Set `APP_DEBUG=false` in production
- [ ] Change admin password
- [ ] Enable HTTPS/SSL
- [ ] Limit `CORS_ORIGIN` to your domain
- [ ] Set correct file permissions
- [ ] Make `.env` not web-accessible
- [ ] Regular database backups
- [ ] Monitor error logs

## Useful URLs

### Testing
- Setup Test: `/backend/test-setup.php`
- Database Test: `/backend/test-db.php`
- Routes Test: `/backend/test-routes.php`
- Diagnostics: `/backend/diagnose.php`
- Final Check: `/backend/final-check.php`

### API
- Health: `/backend/public/api/health`
- API Info: `/backend/public/api`

### Frontend
- Admin Panel: `/admin.html`
- Public Site: `/index.html`

## Password Management

### Generate password hash
```bash
php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"
```

### Reset admin password
```bash
php bin/reset-password.php
```

### Create admin via SQL
```sql
INSERT INTO users (login, password_hash, name, email, role, active, created_at, updated_at)
VALUES (
    'admin',
    '$2y$10$hash_here',
    'Administrator',
    'admin@example.com',
    'admin',
    TRUE,
    NOW(),
    NOW()
);
```

## Telegram Setup

### Get bot token
1. Talk to [@BotFather](https://t.me/botfather)
2. Send `/newbot`
3. Follow instructions
4. Copy token

### Get chat ID
```bash
# Method 1: Use test script
php test-telegram.php

# Method 2: Via API
curl https://api.telegram.org/bot<TOKEN>/getUpdates
```

### Test Telegram
```bash
curl -X POST https://api.telegram.org/bot<TOKEN>/sendMessage \
  -d "chat_id=<CHAT_ID>" \
  -d "text=Test message"
```

## Backup Commands

### Database
```bash
# Backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Restore
mysql -u username -p database_name < backup_20240101.sql
```

### Files
```bash
# Backup
tar -czf backend_backup_$(date +%Y%m%d).tar.gz backend/

# Restore
tar -xzf backend_backup_20240101.tar.gz
```

## Monitoring

### Watch logs in real-time
```bash
tail -f storage/logs/app.log
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
```

### Check disk space
```bash
df -h
du -sh backend/
```

### Check database size
```sql
SELECT 
    table_schema "Database",
    SUM(data_length + index_length) / 1024 / 1024 "Size (MB)"
FROM information_schema.tables
WHERE table_schema = 'ch167436_3dprint'
GROUP BY table_schema;
```

## Logs Management

### Clear old logs
```bash
# Delete logs older than 30 days
find storage/logs/ -name "*.log" -mtime +30 -delete

# Keep only last 1000 lines
tail -n 1000 storage/logs/app.log > storage/logs/app.log.tmp
mv storage/logs/app.log.tmp storage/logs/app.log
```

### Cron job for log rotation
```bash
# Add to crontab
0 0 * * * find /path/to/backend/storage/logs -name "*.log" -mtime +30 -delete
```

## Performance Optimization

### PHP OPcache
```ini
# php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

### Database Indexes
```sql
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_services_active ON services(active, display_order);
```

### Composer Optimization
```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

## Emergency Recovery

### Can't access anything
1. Check server error logs
2. Enable debug mode temporarily
3. Verify .env file exists and is correct
4. Check file permissions
5. Restart web server

### Database corrupted
1. Restore from backup
2. Re-import schema if needed
3. Run integrity check: `mysqlcheck -u username -p database_name`

### Lost admin access
1. Use password reset script
2. Create new admin via SQL
3. Check users table directly in phpMyAdmin

---

**Quick Start:** Run `php final-check.php` to verify everything is working!
