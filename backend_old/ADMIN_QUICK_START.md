# Admin Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Configure Database
Edit `.env` file:
```bash
DB_HOST=localhost
DB_DATABASE=ch167436_3dprint
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Step 2: Create Database & Tables
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ch167436_3dprint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p ch167436_3dprint < database/migrations/20231113_initial.sql

# Import initial data (optional)
mysql -u root -p ch167436_3dprint < database/seeds/initial_data.sql
```

### Step 3: Create Admin User
```bash
php create-admin.php
```

### Step 4: Test Authentication
```bash
php test-auth.php
```

### Step 5: Login
Open in browser: `http://your-domain.com/admin.html`
- Login: **admin**
- Password: **admin123**

**⚠️ IMPORTANT: Change password immediately after first login!**

---

## 🔧 Common Commands

### Create/Reset Admin Password
```bash
# Use defaults from .env
php create-admin.php

# Specify custom credentials
php create-admin.php admin newpassword "Admin Name" admin@example.com
```

### Test Authentication
```bash
# Local
php test-auth.php

# Remote
php test-auth.php https://your-domain.com admin admin123
```

### Test Everything
```bash
# Quick test
php test-standalone.php

# Full test (30 checks)
php ultimate-final-check.php https://your-domain.com
```

### Deploy
```bash
./deploy.sh
```

---

## 🔒 Security Checklist

Before production:

- [ ] Change default admin password
- [ ] Generate secure JWT_SECRET (64+ characters)
- [ ] Set APP_DEBUG=false
- [ ] Configure CORS_ORIGIN with actual domain
- [ ] Enable HTTPS/SSL
- [ ] Set .env permissions to 600

```bash
# Generate JWT secret
openssl rand -base64 64

# Set .env permissions
chmod 600 .env
```

---

## ❓ Troubleshooting

### Can't login - 401 error
```bash
# Create admin user
php create-admin.php admin admin123

# Test auth
php test-auth.php
```

### Database connection error
```bash
# Check .env has correct credentials
cat .env | grep DB_

# Test database
php test-db.php
```

### Password doesn't work
```bash
# Reset password
php create-admin.php admin newpassword
```

---

## 📚 Full Documentation

- **AUTH_FIX_README.md** - Complete authentication guide
- **README.md** - Full backend documentation
- **DEPLOYMENT_INSTRUCTIONS.md** - Deployment guide
- **TROUBLESHOOTING.md** - Common issues and solutions

---

## 🎯 Quick Reference

| Task | Command |
|------|---------|
| Create admin | `php create-admin.php` |
| Test auth | `php test-auth.php` |
| Test API | `php ultimate-final-check.php URL` |
| Deploy | `./deploy.sh` |
| View logs | `tail -f storage/logs/app.log` |
| Reset password | `php bin/reset-password.php admin` |

---

## 📞 Need Help?

1. Check logs: `storage/logs/app.log`
2. Run diagnostics: `php diagnose.php`
3. Read troubleshooting: `TROUBLESHOOTING.md`
4. Test each component: `test-*.php` scripts
