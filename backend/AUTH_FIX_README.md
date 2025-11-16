# Authentication Fix - 401 Unauthorized Issue

## Problem Summary

The admin login was returning 401 Unauthorized even with correct credentials (admin/admin123). This was caused by:

1. **Missing .env file** - No environment configuration existed
2. **Outdated seed script** - Used Composer dependencies instead of standalone mode
3. **No admin creation tool** - Difficult to create/reset admin accounts
4. **No auth testing tool** - No way to verify authentication works

## Solution Implemented

### 1. Created .env File ✅
- Location: `/backend/.env`
- Contains all necessary configuration:
  - Database credentials
  - JWT settings
  - Admin user defaults
  - CORS configuration
  - Telegram integration (optional)

### 2. Created create-admin.php Script ✅
- Location: `/backend/create-admin.php`
- **Standalone mode** - NO Composer required
- Creates or updates admin users with proper password hashing
- Tests password verification after creation
- Provides detailed output and error messages

**Usage:**
```bash
# Use defaults from .env
php create-admin.php

# Specify credentials
php create-admin.php admin mypassword

# Full specification
php create-admin.php admin mypassword "Admin Name" admin@example.com
```

### 3. Created test-auth.php Script ✅
- Location: `/backend/test-auth.php`
- **Standalone mode** - NO Composer required
- Tests authentication end-to-end with 7 comprehensive tests:
  1. API Health Check
  2. Database Connection
  3. Admin User Exists
  4. Password Verification (Direct)
  5. Login API Endpoint
  6. Authenticated Request (GET /api/auth/me)
  7. Invalid Credentials Handling

**Usage:**
```bash
# Test with defaults
php test-auth.php

# Specify credentials
php test-auth.php admin admin123

# Full URL specification
php test-auth.php http://localhost:8080 admin admin123
```

### 4. Fixed seed-admin-user.php ✅
- Location: `/backend/database/seeds/seed-admin-user.php`
- Converted from Composer dependencies to standalone mode
- Uses SimpleEnv instead of vlucas/phpdotenv
- Uses standalone autoloader instead of Composer

### 5. Updated deploy.sh ✅
- Location: `/backend/deploy.sh`
- Now automatically creates admin user during deployment
- Added test-auth.php to manual steps
- Provides better error handling and feedback

## How Authentication Works

### Login Flow:
1. Frontend sends POST to `/api/auth/login` with JSON: `{"login": "admin", "password": "admin123"}`
2. `AuthController::login()` extracts credentials from request body
3. `AuthService::authenticate()` queries database for user
4. Password verified with `password_verify($password, $user['password_hash'])`
5. If valid, JWT token generated and returned
6. Frontend stores token in localStorage
7. Subsequent requests include token in `Authorization: Bearer <token>` header
8. Backend validates token with `AuthService::verifyToken()`

### Password Hashing:
- Passwords hashed with `password_hash($password, PASSWORD_BCRYPT)`
- Verification with `password_verify($password, $hash)`
- BCRYPT algorithm provides secure one-way hashing

### JWT Token:
- Generated with SimpleJWT (standalone replacement for firebase/php-jwt)
- Contains user ID, login, email, role
- Expires after 1 hour (configurable via JWT_EXPIRATION)
- Refresh token valid for 30 days

## Deployment Steps

### 1. Configure Environment
Edit `/backend/.env`:
```env
DB_HOST=your_host
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# IMPORTANT: Change this!
JWT_SECRET=generate_with_openssl_rand_base64_64

ADMIN_LOGIN=admin
ADMIN_PASSWORD=change_this_immediately
```

### 2. Run Deployment Script
```bash
cd /backend
./deploy.sh
```

This will:
- Check all files and directories
- Validate .env configuration
- **Automatically create admin user**
- Set proper file permissions
- Test API endpoints

### 3. Create Database and Tables
```bash
# Create database
mysql -u username -p -e "CREATE DATABASE IF NOT EXISTS ch167436_3dprint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
mysql -u username -p ch167436_3dprint < database/migrations/20231113_initial.sql

# Seed initial data
mysql -u username -p ch167436_3dprint < database/seeds/initial_data.sql
```

### 4. Create Admin User (if automatic creation failed)
```bash
php create-admin.php
```

### 5. Test Authentication
```bash
# Local testing
php test-auth.php

# Remote testing
php test-auth.php https://your-domain.com admin admin123
```

### 6. Login to Admin Panel
Open: `https://your-domain.com/admin.html`
- Login: admin
- Password: admin123 (or whatever you set)

## Troubleshooting

### Issue: 401 Unauthorized
**Causes:**
- Admin user doesn't exist in database
- Password hash incorrect
- Database connection failed
- JWT_SECRET mismatch
- Token expired

**Solutions:**
1. Run `php create-admin.php` to create/update admin
2. Run `php test-auth.php` to diagnose issue
3. Check database credentials in .env
4. Verify users table exists
5. Check storage/logs/app.log for errors

### Issue: Admin user not created
**Solutions:**
1. Run manually: `php create-admin.php admin mypassword`
2. Or use seed script: `php database/seeds/seed-admin-user.php`
3. Check database connection with: `php test-setup.php`
4. Verify users table exists

### Issue: Password doesn't work
**Solutions:**
1. Reset password: `php create-admin.php admin newpassword`
2. Verify hash: `php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"`
3. Test verification: `php test-auth.php`

### Issue: Database connection failed
**Solutions:**
1. Check .env file exists and has correct credentials
2. Test connection: `php test-setup.php`
3. Verify MySQL is running
4. Check firewall rules
5. Ensure database exists

### Issue: JWT token invalid
**Solutions:**
1. Check JWT_SECRET is set in .env (minimum 32 chars)
2. Verify JWT_SECRET matches on all servers
3. Check token expiration (default 1 hour)
4. Use refresh token endpoint if expired

## Security Checklist

✅ **Before going to production:**

1. Change default admin password:
   ```bash
   php create-admin.php admin YOUR_STRONG_PASSWORD
   ```

2. Generate secure JWT_SECRET:
   ```bash
   openssl rand -base64 64
   # Copy output to .env JWT_SECRET
   ```

3. Disable debug mode in .env:
   ```env
   APP_DEBUG=false
   ```

4. Set specific CORS origin in .env:
   ```env
   CORS_ORIGIN=https://your-domain.com
   ```

5. Set proper file permissions:
   ```bash
   chmod 600 .env
   chmod 755 public/
   chmod 775 storage/
   ```

6. Enable HTTPS/SSL (Let's Encrypt recommended)

7. Review and rotate JWT_SECRET periodically

8. Monitor storage/logs/app.log for suspicious activity

## Testing Checklist

Run all tests to verify authentication works:

```bash
# 1. Test PHP and database setup
php test-setup.php

# 2. Test database connection and schema
php test-db.php

# 3. Test standalone components
php test-standalone.php

# 4. Create admin user
php create-admin.php

# 5. Test authentication
php test-auth.php

# 6. Test all API endpoints
php ultimate-final-check.php http://localhost:8080
```

Expected results:
- ✅ All tests pass
- ✅ Admin login returns 200 with JWT token
- ✅ Authenticated requests work
- ✅ Invalid credentials return 401

## Files Modified/Created

### Created:
- ✅ `/backend/.env` - Environment configuration
- ✅ `/backend/create-admin.php` - Admin user creation script
- ✅ `/backend/test-auth.php` - Authentication testing script
- ✅ `/backend/AUTH_FIX_README.md` - This documentation

### Modified:
- ✅ `/backend/database/seeds/seed-admin-user.php` - Converted to standalone mode
- ✅ `/backend/deploy.sh` - Added automatic admin creation

### Existing (Working):
- ✅ `/backend/src/Controllers/AuthController.php` - Login logic
- ✅ `/backend/src/Services/AuthService.php` - Authentication service
- ✅ `/backend/standalone/SimpleJWT.php` - JWT token handling
- ✅ `/backend/standalone/SimpleEnv.php` - Environment loading

## API Endpoints

### POST /api/auth/login
**Request:**
```json
{
  "login": "admin",
  "password": "admin123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "login": "admin",
      "name": "Administrator",
      "email": "admin@3dprintpro.ru",
      "role": "admin"
    }
  }
}
```

**Response (401):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

### GET /api/auth/me
**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response (200):**
```json
{
  "success": true,
  "message": "User retrieved successfully",
  "data": {
    "id": 1,
    "login": "admin",
    "name": "Administrator",
    "email": "admin@3dprintpro.ru",
    "role": "admin",
    "lastLogin": "2024-01-15 10:30:00",
    "createdAt": "2024-01-01 00:00:00"
  }
}
```

## Summary

✅ **Authentication is now working correctly:**
- .env file created with proper configuration
- Admin user can be created easily with create-admin.php
- Authentication can be tested with test-auth.php
- All scripts use standalone mode (no Composer)
- Deployment script automatically sets up admin user
- Comprehensive documentation provided

🎉 **You can now login to the admin panel at /admin.html with admin/admin123**

⚠️ **Remember to change the default password immediately after first login!**
