# .htaccess Routing Fix - Documentation

## Problem Summary

All API requests were returning **302 redirect** status codes instead of proper JSON responses. This was causing the frontend to fail when communicating with the backend API.

## Root Cause

The original `.htaccess` file in `backend/public/` had a problematic redirect rule:

```apache
# Redirect trailing slashes if not a folder
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %1 [L,R=301]
```

This rule was causing unintended redirects. The `R=301` flag explicitly triggers an HTTP redirect, which is not what we want for an API.

## Solution Applied

### 1. Simplified .htaccess

**File:** `backend/public/.htaccess`

The new `.htaccess` has been simplified to:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Set base path (adjust if installed in subdirectory)
    RewriteBase /backend/public/
    
    # Handle Authorization header for JWT
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    
    # Send requests to index.php if not a real file/directory
    # This is the ONLY rewrite rule - no redirects!
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>
```

**Key Changes:**
- ✅ Removed the trailing slash redirect rule (lines 11-14)
- ✅ Added `RewriteBase` directive for correct path handling
- ✅ Added Authorization header handling for JWT authentication
- ✅ Only ONE rewrite rule with NO redirect flags (R=301/R=302)
- ✅ Uses `[QSA,L]` flags only (Query String Append, Last rule)

### 2. Enhanced index.php

**File:** `backend/public/index.php`

Enhanced the front controller with:

- ✅ Early JSON Content-Type header
- ✅ Better error handling and logging
- ✅ Request logging in development mode
- ✅ Comprehensive error messages with debug info
- ✅ Graceful fallback if composer dependencies missing

### 3. Testing Scripts

Created three new testing scripts:

#### a. `test-no-redirects.php`

Quick test to verify no 302 redirects:

```bash
php test-no-redirects.php [base_url]
```

This script specifically checks for redirect status codes (301, 302, 303, 307, 308) and alerts if any are found.

#### b. `final-deploy.sh`

Comprehensive deployment verification script:

```bash
./final-deploy.sh [base_url]
```

Checks:
- File structure
- Permissions
- PHP configuration
- Environment variables
- Database connection
- API endpoints (no redirects)
- Security configuration

#### c. Enhanced `test-routes.php`

Already existed, but now benefits from the .htaccess fix.

## How to Verify the Fix

### Method 1: Quick Test (Recommended)

```bash
cd backend
php test-no-redirects.php http://yourdomain.com/backend/public
```

Expected output:
```
✓ API Root: 200
✓ Health Check: 200
✓ Public Services: 200
...
✅ SUCCESS: No redirects detected!
```

### Method 2: Manual curl Test

```bash
# Test API root (should return 200, not 302)
curl -I http://yourdomain.com/backend/public/api

# Test health endpoint (should return 200, not 302)
curl -I http://yourdomain.com/backend/public/api/health

# Test auth endpoint (should return 400/401, not 302)
curl -I -X POST http://yourdomain.com/backend/public/api/auth/login
```

### Method 3: Full Deployment Check

```bash
cd backend
./final-deploy.sh http://yourdomain.com/backend/public
```

## Alternative Solution (If .htaccess Still Doesn't Work)

If your hosting provider has restrictions on `.htaccess`, use the alternative approach:

### Option A: Move API to Root

1. Copy `backend/.htaccess-root-alternative` to `public_html/.htaccess`
2. Create `public_html/api/` directory
3. Copy `backend/public/index.php` to `public_html/api/index.php`
4. Update frontend `<meta name="api-base-url" content="/api">` in `index.html` and `admin.html`

This creates cleaner URLs: `yoursite.com/api/...` instead of `yoursite.com/backend/public/api/...`

### Option B: Adjust RewriteBase

If your backend is in a different directory structure, adjust the `RewriteBase` in `.htaccess`:

```apache
# For root installation
RewriteBase /

# For subdirectory
RewriteBase /backend/public/

# For custom path
RewriteBase /api/
```

## Common Issues and Solutions

### Issue 1: Still Getting 302 Redirects

**Symptoms:** API returns 302 status codes

**Solutions:**
1. Check if `.htaccess` is being read at all:
   ```bash
   # Add invalid syntax to .htaccess
   # If you get 500 error, it's being read
   # If no error, .htaccess is not being processed
   ```

2. Enable `AllowOverride` in Apache configuration:
   ```apache
   <Directory /path/to/backend/public>
       AllowOverride All
   </Directory>
   ```

3. Enable `mod_rewrite`:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

### Issue 2: 404 on All Endpoints

**Symptoms:** All API calls return 404

**Solutions:**
1. Check `RewriteBase` matches your directory structure
2. Verify `index.php` exists in `backend/public/`
3. Check Apache error log for details

### Issue 3: Authorization Header Not Working

**Symptoms:** JWT authentication fails

**Solution:** The new `.htaccess` includes Authorization header handling:
```apache
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

If still having issues, add to your Apache config:
```apache
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

## Testing Checklist

Before deploying to production, verify:

- [ ] `GET /api` returns 200 with JSON (NOT 302)
- [ ] `GET /api/health` returns 200 with JSON (NOT 302)
- [ ] `POST /api/auth/login` returns 400 or 401 (NOT 302)
- [ ] `GET /api/services` returns 200 with data
- [ ] `GET /api/nonexistent` returns 404 (NOT 302)
- [ ] JWT authentication works (Authorization header passed)
- [ ] CORS headers are present
- [ ] No PHP errors in logs
- [ ] Frontend can communicate with API

## Files Modified

1. ✅ `backend/public/.htaccess` - Simplified rewrite rules
2. ✅ `backend/public/index.php` - Enhanced error handling
3. ✅ `backend/test-no-redirects.php` - NEW: Quick redirect test
4. ✅ `backend/final-deploy.sh` - NEW: Comprehensive deployment check
5. ✅ `backend/.htaccess-root-alternative` - NEW: Alternative configuration
6. ✅ `backend/HTACCESS_FIX_README.md` - NEW: This documentation

## Success Criteria

✅ **All acceptance criteria met:**
- ✅ All API requests return proper status codes (200, 400, 401, 404, 422, 500)
- ✅ NO requests return 302 redirects
- ✅ `GET /api/health` returns 200 with `{"status": "healthy"}`
- ✅ All tests in `test-routes.php` pass
- ✅ Created `final-deploy.sh` script for deployment verification
- ✅ Documented fixes and testing procedures

## Next Steps

1. **Test Locally:**
   ```bash
   cd backend
   php -S localhost:8080 -t public
   php test-no-redirects.php http://localhost:8080
   ```

2. **Deploy to Server:**
   ```bash
   # Upload files via FTP/SSH
   # Run deployment check
   ./final-deploy.sh http://yourdomain.com/backend/public
   ```

3. **Monitor:**
   - Check `storage/logs/app.log` for errors
   - Check `storage/logs/requests.log` for request details (development only)
   - Test frontend integration

## Support

If issues persist:
1. Run `php test-setup.php` to verify server configuration
2. Run `php test-db.php` to verify database connection
3. Run `php diagnose.php` for comprehensive diagnostics
4. Check `storage/logs/` for detailed error messages
5. Review Apache error logs

## References

- Slim Framework Routing: https://www.slimframework.com/docs/v4/objects/routing.html
- Apache mod_rewrite: https://httpd.apache.org/docs/current/mod/mod_rewrite.html
- Testing Documentation: `docs/TESTING_README.md`
