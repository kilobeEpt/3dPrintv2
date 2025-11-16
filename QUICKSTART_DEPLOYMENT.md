# ⚡ QUICK START DEPLOYMENT

## 🎯 For Timeweb (ch167436.tw1.ru) - 5 минут

### Step 1: Upload Files (2 minutes)

```bash
# Via FTP or File Manager - upload all files to public_html/
```

### Step 2: Database (2 minutes)

```bash
# 1. Create database via hosting panel: ch167436_3dprint
# 2. Import via phpMyAdmin:
#    - backend/database/migrations/20231113_initial.sql
#    - backend/database/seeds/initial_data.sql
```

### Step 3: Configure (1 minute)

```bash
# SSH into server
cd ~/public_html/backend

# Copy and edit .env
cp .env.example .env
nano .env

# Set:
# DB_HOST=localhost
# DB_DATABASE=ch167436_3dprint
# DB_USERNAME=ch167436_admin
# DB_PASSWORD=your_password
# JWT_SECRET=$(openssl rand -base64 64)
# CORS_ORIGIN=https://ch167436.tw1.ru

# Create admin
php database/seeds/seed-admin-user.php

# Activate standalone mode (NO COMPOSER NEEDED)
./activate-standalone.sh
```

### Step 4: Test (30 seconds)

```bash
# Run final check
php ultimate-final-check.php https://ch167436.tw1.ru

# Open in browser
https://ch167436.tw1.ru/admin.html
# Login: admin / admin123456
```

---

## ✅ Success Criteria

All must be ✅:

- [ ] `https://ch167436.tw1.ru/api/health` returns 200 (NOT 301/302)
- [ ] `https://ch167436.tw1.ru/admin.html` loads and login works
- [ ] No 404 errors on /api/auth/login
- [ ] Dashboard shows data
- [ ] Orders can be created
- [ ] No console errors

---

## 🔧 If Something Fails

### 1. API Returns 301/302

```bash
cd backend/public
cat .htaccess

# Should NOT contain R=301 or R=302
# RewriteBase should be / (for root) or /backend/public/ (for subfolder)
```

### 2. Composer Dependencies Error

```bash
cd backend
./activate-standalone.sh
```

### 3. Login 404 Error

```bash
# Check RewriteBase in .htaccess
# Try different values:
RewriteBase /
# or
RewriteBase /backend/public/
```

### 4. CORS Error

```bash
# In .env set:
CORS_ORIGIN=*
```

### 5. Database Error

```bash
# Test connection
php backend/test-db.php

# Check credentials in .env
```

---

## 📱 Full Documentation

See: `ULTIMATE_DEPLOYMENT_GUIDE.md` for complete instructions

---

## 🚀 READY!

If all tests pass → Production ready! 🎉
