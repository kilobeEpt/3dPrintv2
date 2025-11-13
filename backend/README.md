# 3D Print Pro - Backend API

RESTful API backend for the 3D Print Pro application, built with PHP and Slim Framework.

## Tech Stack

- **PHP 7.4+** - Programming language
- **Slim Framework 4** - Lightweight PHP micro-framework
- **PDO/MySQL** - Database connectivity
- **Composer** - Dependency management
- **JWT** - JSON Web Token authentication
- **phpdotenv** - Environment variable management

## Project Structure

```
backend/
├── public/
│   ├── index.php           # Front controller (entry point)
│   └── .htaccess            # Apache URL rewriting
├── src/
│   ├── Bootstrap/
│   │   └── App.php          # Application bootstrap
│   ├── Config/
│   │   └── Database.php     # PDO database connection
│   ├── Middleware/
│   │   ├── CorsMiddleware.php      # CORS headers
│   │   └── ErrorMiddleware.php     # Error handling
│   └── Helpers/
│       └── Response.php     # JSON response helpers
├── database/
│   ├── migrations/          # Database schema migrations
│   └── seeds/               # Initial data seeds
├── .env.example             # Environment variables template
├── composer.json            # PHP dependencies
├── nginx.conf.example       # Nginx configuration
└── README.md               # This file
```

## Installation

### 1. Install Dependencies

Install Composer dependencies:

```bash
cd backend
composer install
```

### 2. Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
# Environment
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# CORS - Frontend origin(s)
CORS_ORIGIN=http://localhost:8000

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ch167436_3dprint
DB_USERNAME=root
DB_PASSWORD=your_password

# JWT Secret (generate a random string)
JWT_SECRET=your_random_secret_key_here

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
```

### 3. Set Up Database

If you haven't already, create the database and run migrations:

```bash
# Create schema
mysql -u root -p < database/migrations/20231113_initial.sql

# Load seed data
mysql -u root -p ch167436_3dprint < database/seeds/initial_data.sql
```

See [Database Setup Guide](database/README.md) for detailed instructions.

### 4. Set Permissions (Linux/Mac)

Ensure the web server has write access to storage directories:

```bash
mkdir -p storage/logs
chmod -R 775 storage
chown -R www-data:www-data storage  # For Apache
# OR
chown -R nginx:nginx storage         # For Nginx
```

## Running the Application

### Development Server (PHP Built-in)

The easiest way to run the API locally:

```bash
cd backend
php -S localhost:8080 -t public
```

Or use the Composer script:

```bash
composer start
```

The API will be available at: `http://localhost:8080/api`

### Testing the Health Check

```bash
curl http://localhost:8080/api/health
```

Expected response:

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

### Testing Database Connection

If the database is not configured or unreachable, the health check will return:

```json
{
  "status": "unhealthy",
  "timestamp": "2023-11-13 10:30:00",
  "environment": "development",
  "database": {
    "connected": false,
    "message": "Database connection failed",
    "error": "Connection refused"
  }
}
```

## Production Deployment

### Apache Configuration

1. **Point DocumentRoot to `backend/public`:**

   ```apache
   <VirtualHost *:80>
       ServerName api.yourdomain.com
       DocumentRoot /var/www/3dprint-pro/backend/public
       
       <Directory /var/www/3dprint-pro/backend/public>
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/3dprint-api-error.log
       CustomLog ${APACHE_LOG_DIR}/3dprint-api-access.log combined
   </VirtualHost>
   ```

2. **Enable mod_rewrite:**

   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. **The `.htaccess` file in `public/` handles URL rewriting automatically.**

### Nginx Configuration

1. **Copy the example nginx config:**

   ```bash
   sudo cp nginx.conf.example /etc/nginx/sites-available/3dprint-api
   ```

2. **Edit the configuration:**

   ```bash
   sudo nano /etc/nginx/sites-available/3dprint-api
   ```

   Update `server_name` and `root` paths.

3. **Enable the site:**

   ```bash
   sudo ln -s /etc/nginx/sites-available/3dprint-api /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```

### Timeweb Hosting Setup

For Timeweb (or similar shared hosting):

1. **Upload files** via FTP/SFTP to your hosting account
2. **Set document root** to `backend/public` in control panel
3. **Create `.env` file** with production settings
4. **Ensure PHP 7.4+** is enabled
5. **The `.htaccess` file** will handle routing automatically

## Environment Variables

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_HOST` | Database host | `localhost` |
| `DB_DATABASE` | Database name | `ch167436_3dprint` |
| `DB_USERNAME` | Database user | `root` |
| `DB_PASSWORD` | Database password | `secret` |
| `JWT_SECRET` | JWT signing secret | `random_string_here` |

### Optional Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment | `production` |
| `APP_DEBUG` | Debug mode | `false` |
| `CORS_ORIGIN` | Allowed origins | `*` |
| `DB_PORT` | Database port | `3306` |
| `DB_CHARSET` | Database charset | `utf8mb4` |
| `JWT_ALGORITHM` | JWT algorithm | `HS256` |
| `JWT_EXPIRATION` | Token lifetime (seconds) | `3600` |

## API Endpoints

### Health Check

Test API and database connectivity:

```
GET /api/health
```

**Response:**

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

### API Info

Get API information:

```
GET /api
```

**Response:**

```json
{
  "success": true,
  "message": "Welcome to 3D Print Pro API",
  "data": {
    "name": "3D Print Pro API",
    "version": "1.0.0",
    "documentation": "/api/docs",
    "endpoints": {
      "GET /api/health": "Health check and database status",
      "GET /api": "API information"
    }
  }
}
```

## Response Format

All API responses follow a consistent JSON format:

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }  // Optional validation errors
}
```

## CORS Configuration

CORS is configured via the `CORS_ORIGIN` environment variable:

```env
# Single origin
CORS_ORIGIN=http://localhost:8000

# Multiple origins (comma-separated)
CORS_ORIGIN=http://localhost:8000,https://yourdomain.com,https://www.yourdomain.com

# Allow all (not recommended for production)
CORS_ORIGIN=*
```

The API allows the following methods by default:
- GET, POST, PUT, DELETE, PATCH, OPTIONS

## Error Handling

Errors are handled consistently across the API:

- **400** - Bad Request
- **401** - Unauthorized
- **403** - Forbidden
- **404** - Not Found
- **422** - Validation Error
- **500** - Internal Server Error
- **503** - Service Unavailable

In development mode (`APP_DEBUG=true`), detailed error information is included in responses.

## Security Best Practices

### Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Generate a strong random `JWT_SECRET`
- [ ] Use HTTPS (SSL certificate)
- [ ] Restrict `CORS_ORIGIN` to your frontend domain
- [ ] Keep database credentials secure (use `.env`, never commit)
- [ ] Create a dedicated database user with minimal privileges:
  ```sql
  CREATE USER 'api_user'@'localhost' IDENTIFIED BY 'strong_password';
  GRANT SELECT, INSERT, UPDATE, DELETE ON ch167436_3dprint.* TO 'api_user'@'localhost';
  ```
- [ ] Disable directory listing
- [ ] Keep Composer dependencies updated
- [ ] Enable rate limiting (implement middleware)
- [ ] Implement request logging
- [ ] Set up monitoring and alerts

## Troubleshooting

### 500 Internal Server Error

Check PHP error logs:

```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP-FPM
tail -f /var/log/php8.1-fpm.log
```

### Database Connection Errors

Test MySQL connection manually:

```bash
mysql -h localhost -u root -p ch167436_3dprint -e "SELECT 1;"
```

Check database credentials in `.env` file.

### CORS Errors

Ensure `CORS_ORIGIN` includes your frontend URL and matches exactly (including protocol and port).

### Composer Dependencies Missing

```bash
composer install --no-dev --optimize-autoloader
```

### URL Rewriting Not Working

**Apache:** Enable mod_rewrite:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Nginx:** Verify `try_files` directive is present in location block.

## Development

### Code Style

Follow PSR-12 coding standards for PHP.

### Adding New Routes

Edit `src/Bootstrap/App.php` in the `registerRoutes()` method:

```php
$this->app->get('/api/your-route', function ($request, $response) {
    return Response::success(['message' => 'Hello World']);
});
```

### Creating Controllers

Create controller classes in `src/Controllers/`:

```php
<?php

namespace App\Controllers;

use App\Helpers\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class YourController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Response::success(['data' => 'Your data here']);
    }
}
```

### Running Tests

```bash
composer test
```

## Support

For issues or questions:

1. Check this documentation
2. Review database setup guide: `database/README.md`
3. Check API health endpoint: `GET /api/health`
4. Review error logs

## License

MIT License - See LICENSE file for details.
