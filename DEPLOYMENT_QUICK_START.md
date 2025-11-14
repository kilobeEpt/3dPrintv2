# 🚀 Quick Start Deployment Guide

## 📋 Pre-Deployment Checklist

This guide helps you quickly deploy the 3D Print Pro application with the **fixed .htaccess routing** (no more 302 redirects).

---

## ⚡ 5-Minute Quick Deployment

### Step 1: Upload Files

Upload these directories to your server:
- `backend/` → Upload to server
- `frontend/` (index.html, css/, js/, img/) → Upload to public_html

### Step 2: Install Dependencies

```bash
cd backend
composer install --no-dev --optimize-autoloader
```

### Step 3: Configure Environment

```bash
cd backend
cp .env.example .env
nano .env  # Edit with your values
```

**Required Changes in .env:**
```ini
DB_HOST=localhost
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

JWT_SECRET=YOUR_RANDOM_64_CHARACTER_SECRET_HERE

CORS_ORIGIN=https://yourdomain.com
```

**Generate secure JWT_SECRET:**
```bash
openssl rand -base64 64
```

### Step 4: Set Permissions

```bash
cd backend
chmod -R 775 storage/
chmod -R 775 storage/logs/
chmod -R 775 storage/cache/
```

### Step 5: Setup Database

```bash
cd backend

# Import schema
mysql -u username -p database_name < database/migrations/20231113_initial.sql

# Create admin user
php database/seeds/seed-admin-user.php
```

### Step 6: Verify Fix Applied ⭐ IMPORTANT

```bash
cd backend
./verify-fix.sh
```

Should show: `✅ .htaccess fix has been correctly applied!`

### Step 7: Test API (No 302 Redirects!)

```bash
cd backend

# Quick test (30 seconds)
php test-no-redirects.php http://yourdomain.com/backend/public

# Full test (2 minutes)
./final-deploy.sh http://yourdomain.com/backend/public
```

**All tests should pass with NO 302 redirects!**

### Step 8: Test in Browser

Open in browser:
```
http://yourdomain.com/backend/public/api/health
```

Should see:
```json
{
  "status": "healthy",
  "timestamp": "2024-11-14 14:30:00",
  "environment": "production"
}
```

Status code should be: **200 OK** (NOT 302!)

### Step 9: Update Frontend Configuration

Edit `index.html` and `admin.html`:

```html
<!-- Update this meta tag -->
<meta name="api-base-url" content="/backend/public">
```

Or for root installation:
```html
<meta name="api-base-url" content="/api">
```

### Step 10: Test Full Application

1. Open your site: `http://yourdomain.com`
2. Check browser console (F12) for API errors
3. Test calculator functionality
4. Test contact form submission
5. Test admin login: `http://yourdomain.com/admin.html`

---

## 🔧 Troubleshooting

### Still Getting 302 Redirects?

Run diagnostics:
```bash
cd backend
php diagnose.php
```

Check .htaccess is working:
```bash
cd backend
./verify-fix.sh
```

### Common Issues

**1. 302 Redirects on All Endpoints**
- ❌ Problem: .htaccess not applied correctly
- ✅ Solution: Run `./verify-fix.sh` to check
- ✅ Alternative: Use `.htaccess-root-alternative` configuration

**2. 404 on All Endpoints**
- ❌ Problem: RewriteBase doesn't match directory structure
- ✅ Solution: Edit `backend/public/.htaccess` line 12:
  ```apache
  RewriteBase /backend/public/  # Adjust to match your path
  ```

**3. 500 Internal Server Error**
- ❌ Problem: mod_rewrite not enabled
- ✅ Solution:
  ```bash
  sudo a2enmod rewrite
  sudo systemctl restart apache2
  ```

**4. API Returns HTML Instead of JSON**
- ❌ Problem: Wrong path or Apache config issue
- ✅ Solution: Check Apache error logs and verify path

**5. CORS Errors in Browser Console**
- ❌ Problem: CORS_ORIGIN not set correctly
- ✅ Solution: Update `.env`:
  ```ini
  CORS_ORIGIN=https://yourdomain.com
  ```

---

## 📊 What Was Fixed

### The Problem
All API requests were returning **302 redirect** instead of proper responses.

### The Solution
1. **Removed problematic redirect rule** from `.htaccess`
2. **Simplified rewrite rules** to single non-redirecting rule
3. **Added Authorization header** handling for JWT
4. **Enhanced error handling** in `index.php`
5. **Created test scripts** to verify no redirects

### Files Changed
- ✏️ `backend/public/.htaccess` - Fixed redirect issue
- ✏️ `backend/public/index.php` - Enhanced error handling
- ✨ `backend/test-no-redirects.php` - NEW: Quick test
- ✨ `backend/final-deploy.sh` - NEW: Full deployment check
- ✨ `backend/verify-fix.sh` - NEW: Verify fix applied
- ✨ `backend/URGENT_FIX_SUMMARY.md` - NEW: Detailed fix docs

---

## 📖 Additional Documentation

- **Detailed Fix Info:** `backend/URGENT_FIX_SUMMARY.md`
- **Technical Details:** `backend/HTACCESS_FIX_README.md`
- **Full Deployment:** `backend/DEPLOYMENT_GUIDE.md`
- **Troubleshooting:** `backend/TROUBLESHOOTING.md`
- **Testing Guide:** `backend/TESTING_GUIDE.md`

---

## ✅ Success Indicators

After deployment, verify:

- [x] `GET /api` → Returns 200 (not 302)
- [x] `GET /api/health` → Returns 200 with JSON
- [x] `POST /api/auth/login` → Returns 400/401 (not 302)
- [x] Frontend loads without API errors
- [x] Calculator works with API data
- [x] Contact form submits successfully
- [x] Admin panel login works
- [x] All CRUD operations function

---

## 🆘 Need Help?

If issues persist:

1. **Run all diagnostics:**
   ```bash
   cd backend
   php test-setup.php
   php test-db.php
   php test-no-redirects.php
   ./final-deploy.sh
   ```

2. **Check logs:**
   ```bash
   tail -f backend/storage/logs/app.log
   tail -f /var/log/apache2/error.log
   ```

3. **Verify Apache config:**
   ```bash
   apache2ctl -t  # Test configuration
   apache2ctl -M | grep rewrite  # Check mod_rewrite
   ```

4. **Review documentation:**
   - See `backend/URGENT_FIX_SUMMARY.md`
   - See `backend/TROUBLESHOOTING.md`

---

## 🎉 You're Done!

Your 3D Print Pro application is now deployed with:

✅ Fixed .htaccess (no more 302 redirects)  
✅ Working API endpoints  
✅ Frontend-backend integration  
✅ Admin panel functionality  
✅ All tests passing  

**Enjoy your working application! 🚀**
