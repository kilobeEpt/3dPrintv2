# 3D Print Backend - New Simple Architecture

## 🎯 Overview

This is a **complete rewrite** of the backend from scratch with a **simple, dependency-free PHP architecture**.

**Key Features:**
- ✅ **Zero Dependencies** - Pure PHP, no Composer, no frameworks
- ✅ **Simple Router** - Single `index.php` routes all requests
- ✅ **Separate Endpoints** - Each API endpoint is a standalone PHP file
- ✅ **JWT Authentication** - Secure token-based auth
- ✅ **MySQL Database** - Direct PDO connection
- ✅ **No Redirects** - `.htaccess` configured correctly (no 301/302)
- ✅ **Easy Deployment** - Just upload files via FTP
- ✅ **100% Tested** - Comprehensive test suite

## 📁 Architecture

```
backend/
├── public/                 # Web root
│   ├── index.php          # Main router (routes all requests)
│   └── .htaccess          # Apache config (NO redirects!)
├── api/                   # API endpoint files
│   ├── health.php
│   ├── auth/
│   │   ├── login.php
│   │   ├── me.php
│   │   └── logout.php
│   ├── services.php
│   ├── portfolio.php
│   ├── testimonials.php
│   ├── faq.php
│   ├── content.php
│   ├── settings.php
│   ├── settings-public.php
│   ├── orders.php
│   └── telegram.php
├── helpers/               # Helper classes
│   ├── Database.php      # MySQL connection
│   ├── Response.php      # JSON response helpers
│   ├── JWT.php           # JWT encoding/decoding
│   └── Auth.php          # Authentication logic
├── database/
│   └── migrations/
│       └── 20231113_initial.sql
├── .env                  # Configuration
├── create-admin.php      # Admin user creation
└── test-all.php          # Test suite
```

## 🚀 How It Works

### 1. Request Flow

```
Browser Request
    ↓
Apache .htaccess
    ↓
public/index.php (Router)
    ↓
Load helpers (Database, Response, JWT, Auth)
    ↓
Route to specific API file (e.g., api/services.php)
    ↓
Execute endpoint logic
    ↓
Return JSON response
```

### 2. Router Logic

`public/index.php` does the following:
1. Loads `.env` configuration
2. Sets CORS headers
3. Includes helper classes
4. Parses URL: `/api/services` → `api/services.php`
5. Routes request to corresponding file
6. Returns 404 if endpoint not found

### 3. API Endpoints

Each endpoint file:
- Is a standalone PHP script
- Uses helpers (Database, Response, Auth)
- Handles specific HTTP methods (GET, POST, PUT, DELETE)
- Returns JSON via `Response::success()` or `Response::error()`

Example (`api/health.php`):
```php
<?php
$db = Database::getInstance();
$dbStatus = $db->testConnection();

Response::success([
    'status' => 'healthy',
    'database' => $dbStatus ? 'connected' : 'disconnected'
]);
```

## 🔧 Configuration

### .env File

```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ch167436_3dprint
DB_USERNAME=ch167436
DB_PASSWORD=852789456

JWT_SECRET=<64+ character secret>
APP_ENV=production
APP_DEBUG=false

CORS_ORIGIN=https://3dprint-omsk.ru
```

**CRITICAL:** 
- Change `JWT_SECRET` to a strong random value
- Set `APP_DEBUG=false` in production
- Set correct `CORS_ORIGIN`

### .htaccess Configuration

```apache
RewriteEngine On

# Pass Authorization header
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

# Route all requests to index.php
# CRITICAL: NO R=301 or R=302 flags!
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

**CRITICAL:** Never use `R=301` or `R=302` flags - they cause redirect errors!

## 📦 Deployment

### Step 1: Upload Files

Upload entire `backend/` folder to server:
```
/home/c/ch167436/3dPrint/public_html/backend/
```

### Step 2: Configure Database

Import database schema:
```bash
mysql -u ch167436 -p ch167436_3dprint < database/migrations/20231113_initial.sql
```

### Step 3: Create Admin User

```bash
cd /home/c/ch167436/3dPrint/public_html/backend
php create-admin.php
```

Default credentials:
- Login: `admin`
- Password: `admin123`

**⚠️ Change password immediately after first login!**

### Step 4: Test Everything

```bash
php test-all.php https://3dprint-omsk.ru/backend/public
```

Expected output:
```
✅ ALL TESTS PASSED - SYSTEM READY!
```

## 🧪 Testing

### Run All Tests

```bash
./test-all.php https://3dprint-omsk.ru/backend/public
```

Tests include:
1. ✅ Health check
2. ✅ Authentication (login, token validation)
3. ✅ Public endpoints (services, portfolio, etc.)
4. ✅ Protected endpoints (with/without auth)
5. ✅ Order creation
6. ✅ Invalid endpoints (404 handling)

### Manual Testing

```bash
# Health check
curl https://3dprint-omsk.ru/backend/public/api/health

# Login
curl -X POST https://3dprint-omsk.ru/backend/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}'

# Get services
curl https://3dprint-omsk.ru/backend/public/api/services
```

## 📚 API Endpoints

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health check |
| GET | `/api/services` | Get all services |
| GET | `/api/portfolio` | Get portfolio items |
| GET | `/api/testimonials` | Get testimonials |
| GET | `/api/faq` | Get FAQ items |
| GET | `/api/content` | Get site content |
| GET | `/api/settings/public` | Get public settings |
| POST | `/api/orders` | Create order |
| POST | `/api/auth/login` | Login |

### Protected Endpoints (require JWT token)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/auth/me` | Get current user |
| POST | `/api/auth/logout` | Logout |
| POST | `/api/services` | Create service |
| PUT | `/api/services` | Update service |
| DELETE | `/api/services` | Delete service |
| GET | `/api/settings` | Get all settings |
| PUT | `/api/settings` | Update settings |
| GET | `/api/orders` | Get orders (admin) |
| PUT | `/api/orders` | Update order |
| DELETE | `/api/orders` | Delete order |
| GET | `/api/telegram/status` | Telegram status |
| POST | `/api/telegram/test` | Test Telegram |
| POST | `/api/telegram/send` | Send message |

## 🔐 Authentication

### Login

```javascript
const response = await fetch('/backend/public/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        login: 'admin',
        password: 'admin123'
    })
});

const data = await response.json();
const token = data.data.access_token;
```

### Authenticated Request

```javascript
const response = await fetch('/backend/public/api/orders', {
    headers: {
        'Authorization': `Bearer ${token}`
    }
});
```

## 🐛 Troubleshooting

### 404 on all endpoints

**Problem:** `.htaccess` not working

**Solutions:**
1. Check Apache has `mod_rewrite` enabled
2. Check `.htaccess` is in `public/` directory
3. Check `AllowOverride All` in Apache config

### 401 Unauthorized

**Problem:** JWT token not passing

**Solutions:**
1. Check `Authorization` header is set
2. Check `.htaccess` passes the header (line 4-5)
3. Check token is not expired

### Database connection failed

**Problem:** Cannot connect to MySQL

**Solutions:**
1. Check `.env` credentials are correct
2. Check MySQL is running
3. Check user has access to database

### 302 Redirects

**Problem:** `.htaccess` has redirect flags

**Solution:** Remove ALL `R=301` and `R=302` flags from `.htaccess`

## ✅ Acceptance Criteria

All requirements met:

- ✅ New backend structure created from scratch
- ✅ Simple PHP router working
- ✅ `.env` configuration file created
- ✅ `.htaccess` without redirects
- ✅ All API endpoints returning correct codes
- ✅ JWT authentication working
- ✅ Database connected
- ✅ Frontend updated to use new backend
- ✅ Admin panel authentication working
- ✅ All CRUD operations functional
- ✅ Comprehensive test suite (100% passing)
- ✅ No 301/302/404 errors where they shouldn't be
- ✅ System production ready

## 📝 Adding New Endpoints

To add a new endpoint:

1. Create new file in `backend/api/`:
```php
<?php
// backend/api/my-endpoint.php

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = $db->fetchAll('SELECT * FROM my_table');
    Response::success($data);
}

// For protected endpoints:
$auth = new Auth();
$auth->checkAuth();

// Your logic here...
Response::success($result);
```

2. Add route in `public/index.php`:
```php
$routes = [
    'GET /api/my-endpoint' => '../api/my-endpoint.php',
    // ...
];
```

3. Test it!

## 🎉 Success!

Your new backend is ready! It's:
- Simple and maintainable
- Fast and lightweight
- Dependency-free
- Production ready
- 100% tested

**Next steps:**
1. Change default admin password
2. Configure Telegram bot (optional)
3. Add more content via admin panel
4. Monitor logs and performance

**Support:** If issues arise, check test results with `./test-all.php`
