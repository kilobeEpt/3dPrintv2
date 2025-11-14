# Testing Quick Start Guide

Quick guide to set up and run the comprehensive test suite for 3D Print Pro.

## Prerequisites

- ✅ PHP 7.4+ with PDO MySQL extension
- ✅ Composer installed
- ✅ MySQL 8.0+ running
- ✅ Database created and migrated
- ✅ Backend `.env` file configured

## Quick Setup (5 Minutes)

### 1. Install Dependencies

```bash
cd backend
composer install
```

### 2. Configure Environment

Copy `.env.example` to `.env` and configure:

```bash
cp .env.example .env
nano .env
```

Update these critical values:
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET` (generate with: `openssl rand -base64 64`)
- `ADMIN_LOGIN`, `ADMIN_PASSWORD`

### 3. Initialize Database

```bash
# Run migrations (if not already done)
mysql -u root -p < database/migrations/20231113_initial.sql

# Seed initial data
mysql -u root -p < database/seeds/initial_data.sql

# Create admin user
php database/seeds/seed-admin-user.php
```

### 4. Verify Setup

```bash
# Test database connection
php test-connection.php

# Expected output: "✅ ALL TESTS PASSED!"
```

### 5. Start Backend Server

```bash
# Terminal 1: Start backend
cd backend
composer start

# Or run in background:
php -S localhost:8080 -t public > server.log 2>&1 &
```

### 6. Verify Backend is Running

```bash
# In new terminal
curl http://localhost:8080/api/health

# Expected: {"status":"healthy", ...}
```

## Running Tests

### Automated Integration Tests (PHPUnit)

Run all integration tests:

```bash
cd backend
./run-tests.sh
```

Or run specific test suites:

```bash
# Authentication tests only
vendor/bin/phpunit --filter AuthTest

# Orders tests only
vendor/bin/phpunit --filter OrdersTest

# Settings tests only
vendor/bin/phpunit --filter SettingsTest

# Content tests only
vendor/bin/phpunit --filter ContentTest
```

### Manual Testing Checklist

Open the comprehensive checklist:

```bash
cat docs/test-checklist.md
# Or open in browser/editor
```

Follow the checklist for manual testing scenarios.

### Test Results

View test report template:

```bash
cat docs/testing-report.md
# Update with actual results
```

## Expected Test Results

### PHPUnit Tests

**Expected output:**

```
3D Print Pro - Integration Test Suite
========================================

✓ Backend server is running
✓ Environment file found
✓ Database connection successful

Running PHPUnit Integration Tests...
========================================

Authentication API Integration Tests
 ✔ Login with valid credentials
 ✔ Login with invalid credentials
 ✔ Login requires login field
 ✔ Login requires password field
 ✔ Get current user with valid token
 ✔ Get current user without token
 ...

Orders API Integration Tests
 ✔ Create order with valid data
 ✔ Create order with calculator data
 ✔ Create order missing required fields
 ...

Settings API Integration Tests
 ✔ Public settings accessible
 ✔ Public settings excludes sensitive data
 ✔ Admin can update general settings
 ...

Content API Integration Tests
 ✔ Get public services
 ✔ Create service
 ✔ Update service
 ...

========================================
✓ All tests passed!
========================================
```

### Test Coverage

If you have Xdebug installed:

```bash
composer test -- --coverage-text
```

Target: > 70% code coverage

## Troubleshooting

### Issue: Backend server not running

```bash
# Check if process is running
ps aux | grep php

# If running, check port
lsof -i :8080

# Kill existing process
kill <PID>

# Restart server
cd backend && composer start
```

### Issue: Database connection failed

```bash
# Verify MySQL is running
sudo systemctl status mysql

# Test connection manually
mysql -u root -p

# Check credentials in .env
cat .env | grep DB_
```

### Issue: Tests failing with 401 errors

```bash
# Verify admin user exists
mysql -u root -p -D ch167436_3dprint -e "SELECT * FROM users WHERE role='admin';"

# Re-create admin user
php database/seeds/seed-admin-user.php
```

### Issue: JWT token errors

```bash
# Regenerate JWT secret
openssl rand -base64 64

# Update .env with new JWT_SECRET
nano .env
```

### Issue: Port 8080 already in use

```bash
# Use different port
php -S localhost:8081 -t public

# Update APP_URL in .env
APP_URL=http://localhost:8081
```

## Testing Workflow

### 1. Smoke Test (Quick validation)

```bash
# Test critical endpoints
curl http://localhost:8080/api/health
curl http://localhost:8080/api/services
curl http://localhost:8080/api/settings/public
```

### 2. Automated Tests

```bash
cd backend
./run-tests.sh
```

### 3. Manual Testing

- [ ] Login to admin panel: http://localhost:8000/admin.html
- [ ] Create test service
- [ ] Submit test order from public site
- [ ] Update settings
- [ ] Verify public site reflects changes

### 4. Browser Testing

Test in multiple browsers:
- Chrome (http://localhost:8000)
- Firefox
- Safari (if on macOS)

### 5. Update Report

Update `docs/testing-report.md` with:
- Test results (pass/fail)
- Issues found
- Screenshots (if applicable)
- Performance metrics

## Continuous Testing

### On Code Changes

```bash
# Quick test before commit
cd backend && vendor/bin/phpunit

# Full test suite
./run-tests.sh
```

### Pre-Deployment Checklist

- [ ] All PHPUnit tests pass
- [ ] Manual testing checklist completed
- [ ] No console errors in browser
- [ ] Database integrity verified
- [ ] Performance acceptable (< 3s page load)
- [ ] Security checks passed

## Test Data Management

### Reset Test Data

```bash
# Re-seed database
mysql -u root -p < database/seeds/initial_data.sql
php database/seeds/seed-admin-user.php
```

### Backup Test Data

```bash
# Export current data
mysqldump -u root -p ch167436_3dprint > backup_$(date +%Y%m%d).sql
```

### Import Test Data

```bash
# Restore from backup
mysql -u root -p ch167436_3dprint < backup_20241114.sql
```

## CI/CD Integration (Future)

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: ch167436_3dprint
          MYSQL_ROOT_PASSWORD: root
        ports:
          - 3306:3306
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: pdo, pdo_mysql
      
      - name: Install dependencies
        run: cd backend && composer install
      
      - name: Run migrations
        run: mysql -h 127.0.0.1 -u root -proot ch167436_3dprint < backend/database/migrations/20231113_initial.sql
      
      - name: Run tests
        run: cd backend && vendor/bin/phpunit
```

## Resources

- **Test Report Template:** `docs/testing-report.md`
- **Test Checklist:** `docs/test-checklist.md`
- **API Documentation:** `docs/api.md`
- **Backend README:** `backend/README.md`
- **Testing Guide:** `backend/TESTING_GUIDE.md`

## Support

If tests fail or you encounter issues:

1. Check error messages carefully
2. Review troubleshooting section above
3. Check backend logs: `backend/storage/logs/app.log`
4. Verify environment configuration
5. Ensure database is properly seeded

## Next Steps

After successful testing:

1. ✅ Update `docs/testing-report.md` with results
2. ✅ Document any issues found
3. ✅ Create tickets for bugs/improvements
4. ✅ Get sign-off for staging deployment
5. ✅ Proceed with production deployment

---

**Last Updated:** 2024-11-14  
**Version:** 1.0.0
