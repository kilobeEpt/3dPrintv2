# test-all.php - Comprehensive API Test Suite

## Overview

The `test-all.php` script is a comprehensive test suite that validates ALL aspects of the 3D Print Pro backend API. It tests 34 different scenarios across 7 categories to ensure the system is fully functional and production-ready.

## Usage

```bash
# Test local installation
php test-all.php

# Test local with custom port
php test-all.php http://localhost:8080

# Test production server
php test-all.php https://3dprint-omsk.ru/backend/public

# Test staging environment
php test-all.php https://staging.example.com/backend/public
```

## Test Categories

### 1. Critical Checks - No Redirects (3 tests)
**Purpose:** Ensures no 301/302 redirects break the API

- ✅ API root endpoint - no redirect
- ✅ Health endpoint - no redirect  
- ✅ Auth login endpoint - no redirect

**Why Critical:** 302 redirects break Authorization headers and POST data

### 2. API Health & Database (3 tests)
**Purpose:** Validates core infrastructure

- ✅ Health endpoint returns JSON
- ✅ Database connection working
- ✅ API environment configured

**Checks:** Database connectivity, JSON responses, environment setup

### 3. Authentication System (6 tests)
**Purpose:** Tests complete auth flow

- ✅ Login endpoint exists
- ✅ Login rejects invalid credentials
- ✅ Login accepts valid credentials
- ✅ JWT token structure valid
- ✅ Protected endpoint rejects no auth
- ✅ Protected endpoint accepts valid token

**Validates:** JWT generation, token validation, protected routes, error handling

### 4. Public Endpoints (8 tests)
**Purpose:** Tests all public API endpoints (no auth required)

- ✅ Services endpoint
- ✅ Portfolio endpoint
- ✅ Portfolio Categories endpoint
- ✅ Testimonials endpoint
- ✅ FAQ endpoint
- ✅ Content endpoint
- ✅ Statistics endpoint
- ✅ Public Settings endpoint

**Checks:** Response codes, JSON format, data integrity

### 5. Admin Endpoints (6 tests)
**Purpose:** Tests protected admin endpoints (auth required)

- ✅ Orders List
- ✅ Admin Settings
- ✅ Admin Services
- ✅ Admin Testimonials
- ✅ Admin FAQ
- ✅ Telegram Status

**Validates:** Authentication requirement, authorization, admin-only access

### 6. CRUD Operations (5 tests)
**Purpose:** Tests Create, Read, Update, Delete functionality

- ✅ Create order (public)
- ✅ Order validation works
- ✅ View order (admin)
- ✅ Update order (admin)
- ✅ Delete order (admin)

**Tests:** Full lifecycle of an order, validation, permissions

### 7. Frontend Integration (3 tests)
**Purpose:** Validates frontend compatibility

- ✅ CORS headers present
- ✅ JSON Content-Type header
- ✅ Response compression enabled

**Checks:** Cross-origin support, proper headers, performance optimization

## Output

### Success Example
```
═══════════════════════════════════════════════════════════════
   3D PRINT PRO - COMPLETE API TEST SUITE
═══════════════════════════════════════════════════════════════
Testing API at: https://3dprint-omsk.ru/backend/public
Time: 2024-11-16 12:30:45
═══════════════════════════════════════════════════════════════

[1] CRITICAL CHECKS - NO REDIRECTS
───────────────────────────────────────────────────────────────
API root endpoint - no redirect                         [✓ PASS] (HTTP 200)
Health endpoint - no redirect                           [✓ PASS] (HTTP 200)
Auth login endpoint - no redirect                       [✓ PASS] (HTTP 400)

[2] API HEALTH & DATABASE CONNECTION
───────────────────────────────────────────────────────────────
Health endpoint returns JSON                            [✓ PASS] (Valid JSON)
Database connection working                             [✓ PASS] (Connected)
API environment configured                              [✓ PASS] (Env: production)

[3] AUTHENTICATION SYSTEM
───────────────────────────────────────────────────────────────
Login endpoint exists                                   [✓ PASS] (HTTP 400)
Login rejects invalid credentials                       [✓ PASS] (Correctly rejected)
Login accepts valid credentials                         [✓ PASS] (Token received)
JWT token structure valid                               [✓ PASS] (3-part JWT)
Protected endpoint rejects no auth                      [✓ PASS] (Correctly rejected)
Protected endpoint accepts valid token                  [✓ PASS] (User: admin)

[4] PUBLIC ENDPOINTS (No Auth Required)
───────────────────────────────────────────────────────────────
Services endpoint                                       [✓ PASS] (Records: 5)
Portfolio endpoint                                      [✓ PASS] (Records: 8)
Portfolio Categories endpoint                           [✓ PASS] (Records: 4)
Testimonials endpoint                                   [✓ PASS] (Records: 6)
FAQ endpoint                                           [✓ PASS] (Records: 10)
Content endpoint                                       [✓ PASS] (Records: 5)
Statistics endpoint                                    [✓ PASS] (Records: N/A)
Public Settings endpoint                               [✓ PASS] (Records: N/A)

[5] ADMIN ENDPOINTS (Auth Required)
───────────────────────────────────────────────────────────────
Orders List                                            [✓ PASS]
Admin Settings                                         [✓ PASS]
Admin Services                                         [✓ PASS]
Admin Testimonials                                     [✓ PASS]
Admin FAQ                                              [✓ PASS]
Telegram Status                                        [✓ PASS]

[6] CRUD OPERATIONS
───────────────────────────────────────────────────────────────
Create order (public)                                  [✓ PASS] (ORD-20241116-001)
Order validation works                                 [✓ PASS] (Validation working)
View order (admin)                                     [✓ PASS] (Order #1)
Update order (admin)                                   [✓ PASS] (Status updated)
Delete order (admin)                                   [✓ PASS] (Order deleted)

[7] FRONTEND INTEGRATION
───────────────────────────────────────────────────────────────
CORS headers present                                   [✓ PASS] (CORS enabled)
JSON Content-Type header                               [✓ PASS] (application/json)
Response compression enabled                           [⚠ WARN] (Compression not detected)

═══════════════════════════════════════════════════════════════
   TEST RESULTS
═══════════════════════════════════════════════════════════════
Total Tests:    34
Passed:         34
Failed:         0
Success Rate:   100.0%

═══════════════════════════════════════════════════════════════
   ✓ ALL TESTS PASSED - SYSTEM READY FOR PRODUCTION!
═══════════════════════════════════════════════════════════════
```

### Failure Example
```
Auth login endpoint - no redirect                       [✗ FAIL] Redirect detected: 302
Database connection working                             [✗ FAIL] DB Error: Access denied

═══════════════════════════════════════════════════════════════
   TEST RESULTS
═══════════════════════════════════════════════════════════════
Total Tests:    34
Passed:         32
Failed:         2
Success Rate:   94.1%

CRITICAL FAILURES:
  ✗ Auth login endpoint - no redirect: Redirect detected: 302
  ✗ Database connection working: DB Error: Access denied

═══════════════════════════════════════════════════════════════
   ✗ CRITICAL FAILURES DETECTED - FIX BEFORE DEPLOYMENT!
═══════════════════════════════════════════════════════════════
```

## Exit Codes

- **0** - All tests passed, system ready for production
- **1** - Minor issues detected (warnings), review before deployment
- **2** - Critical failures detected, fix before deployment

## Integration with deploy.sh

The `deploy.sh` script automatically runs `test-all.php` during deployment:

```bash
./deploy.sh
# Automatically runs: php test-all.php $BASE_URL
```

## Requirements

- PHP 7.4+ with cURL extension
- Network access to the API being tested
- For admin tests: Valid admin credentials (admin/admin123456)

## Comparison with Other Test Scripts

| Script | Tests | Purpose | When to Use |
|--------|-------|---------|-------------|
| test-all.php | 34 | Complete system validation | Before deployment, after changes |
| ultimate-final-check.php | 30 | Similar comprehensive check | Alternative to test-all.php |
| test-auth.php | 7 | Authentication-focused | After auth changes, admin creation |
| test-standalone.php | 6 | Standalone components | After backend updates |
| test-db.php | - | Database validation | Database configuration |
| test-no-redirects.php | - | Quick redirect check | After .htaccess changes |

## Troubleshooting

### All Tests Fail with "Connection failed"
- Check if the base URL is correct
- Verify the server is running
- Check firewall/network access

### Authentication Tests Fail
- Run `php create-admin.php` to create admin user
- Verify JWT_SECRET in .env is set (min 32 chars)
- Check database connection

### "Redirect detected: 302" Errors
- Check .htaccess for R=301 or R=302 flags
- Verify RewriteBase matches directory structure
- Run `php test-no-redirects.php` for detailed info

### Database Connection Fails
- Verify .env credentials are correct
- Check database exists and user has permissions
- Test with: `php test-db.php`

### Admin Endpoints Return 401
- Verify admin user exists: `php create-admin.php`
- Check JWT token generation
- Verify Authorization header is passed through .htaccess

## Best Practices

1. **Run Before Every Deployment**
   ```bash
   ./deploy.sh  # Includes test-all.php
   ```

2. **Run After Code Changes**
   ```bash
   php test-all.php https://staging.example.com/backend/public
   ```

3. **Use in CI/CD Pipeline**
   ```yaml
   - name: Test API
     run: php backend/test-all.php ${{ secrets.API_URL }}
   ```

4. **Monitor in Production**
   - Run daily via cron
   - Alert on failures
   - Track success rate trends

## Contributing

When adding new features:

1. Add corresponding tests to test-all.php
2. Update test count in documentation
3. Verify all tests pass before merging
4. Update this README if adding new test categories

## License

Part of 3D Print Pro - Standalone Backend System
