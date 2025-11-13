# Testing Guide - Backend API

Quick guide to test that the backend is working correctly.

## Prerequisites

Before testing, ensure:
- ✅ PHP 7.4+ installed with PDO MySQL extension
- ✅ Composer installed
- ✅ MySQL 8.0+ running with database created
- ✅ `.env` file configured with correct credentials

## Step-by-Step Testing

### Step 1: Install Dependencies

```bash
cd backend
composer install
```

**Expected:** Dependencies install without errors, `vendor/` directory created.

### Step 2: Test Database Connection

```bash
php test-connection.php
```

**Expected Output:**
```
===========================================
3D Print Pro - Database Connection Test
===========================================

✅ Environment variables loaded
✅ Required environment variables present

Testing database connection...
-------------------------------------------
Host:     localhost
Database: ch167436_3dprint
Username: root
-------------------------------------------

✅ Database connection successful!

MySQL Version: 8.0.35
Current Database: ch167436_3dprint

Checking database schema...
-------------------------------------------
✅ Found 17 tables:
   - users (1 rows)
   - services (6 rows)
   - portfolio (0 rows)
   ...

Testing sample query...
✅ Found 1 active user(s)

===========================================
✅ ALL TESTS PASSED!
===========================================
```

**If it fails:** Check database credentials in `.env`, ensure MySQL is running.

### Step 3: Start Development Server

```bash
composer start
# Or: php -S localhost:8080 -t public
```

**Expected Output:**
```
PHP 8.1.x Development Server (http://localhost:8080) started
```

**Keep this terminal open** - the server is running.

### Step 4: Test Health Endpoint

In a **new terminal**, run:

```bash
curl http://localhost:8080/api/health
```

**Expected Response (200 OK):**
```json
{
  "status": "healthy",
  "timestamp": "2023-11-13 12:30:00",
  "environment": "development",
  "database": {
    "connected": true,
    "message": "Database connection successful",
    "version": "8.0.35",
    "database": "ch167436_3dprint"
  }
}
```

### Step 5: Test API Info Endpoint

```bash
curl http://localhost:8080/api
```

**Expected Response (200 OK):**
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

### Step 6: Test 404 Handler

```bash
curl http://localhost:8080/api/nonexistent
```

**Expected Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Endpoint not found"
}
```

### Step 7: Test CORS Headers

```bash
curl -i -H "Origin: http://localhost:8000" http://localhost:8080/api/health
```

**Expected:** Response includes these headers:
```
Access-Control-Allow-Origin: http://localhost:8000
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
Access-Control-Allow-Credentials: true
```

### Step 8: Test CORS Preflight

```bash
curl -i -X OPTIONS \
  -H "Origin: http://localhost:8000" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  http://localhost:8080/api/health
```

**Expected:** 200 OK with CORS headers (no body needed for OPTIONS).

### Step 9: Test Browser Access

Open in your web browser:
- http://localhost:8080/api/health
- http://localhost:8080/api

**Expected:** JSON formatted response displayed in browser.

### Step 10: Test Error Handling

Temporarily break the database connection:

1. Edit `.env` and change `DB_PASSWORD` to something incorrect
2. Restart the server
3. Test health endpoint:

```bash
curl http://localhost:8080/api/health
```

**Expected Response (503 Service Unavailable):**
```json
{
  "status": "unhealthy",
  "timestamp": "2023-11-13 12:30:00",
  "environment": "development",
  "database": {
    "connected": false,
    "message": "Database connection failed",
    "error": "SQLSTATE[HY000] [1045] Access denied..."
  }
}
```

4. **Restore correct password** and restart server

## Integration Testing with Frontend

### Step 1: Start Backend (Terminal 1)

```bash
cd backend
composer start
```

### Step 2: Start Frontend (Terminal 2)

```bash
# From project root
python3 -m http.server 8000
```

### Step 3: Test CORS from Frontend

Create a test HTML file `test-api.html`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
</head>
<body>
    <h1>API Connection Test</h1>
    <button onclick="testAPI()">Test API</button>
    <pre id="result"></pre>

    <script>
    async function testAPI() {
        try {
            const response = await fetch('http://localhost:8080/api/health');
            const data = await response.json();
            document.getElementById('result').textContent = JSON.stringify(data, null, 2);
        } catch (error) {
            document.getElementById('result').textContent = 'Error: ' + error.message;
        }
    }
    </script>
</body>
</html>
```

1. Open http://localhost:8000/test-api.html
2. Click "Test API" button
3. **Expected:** JSON response displays without CORS errors

## Automated Testing Script

Create `run-tests.sh`:

```bash
#!/bin/bash

echo "========================================"
echo "3D Print Pro - API Test Suite"
echo "========================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Test counter
PASSED=0
FAILED=0

# Function to test endpoint
test_endpoint() {
    local name=$1
    local url=$2
    local expected_code=$3
    
    echo -n "Testing $name... "
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$url")
    
    if [ "$response" = "$expected_code" ]; then
        echo -e "${GREEN}✓ PASSED${NC} (HTTP $response)"
        ((PASSED++))
    else
        echo -e "${RED}✗ FAILED${NC} (Expected $expected_code, got $response)"
        ((FAILED++))
    fi
}

# Wait for server to be ready
echo "Checking if server is running..."
if ! curl -s http://localhost:8080/api > /dev/null 2>&1; then
    echo -e "${RED}Error: Server not running on localhost:8080${NC}"
    echo "Start server with: composer start"
    exit 1
fi
echo -e "${GREEN}Server is running${NC}"
echo ""

# Run tests
test_endpoint "Health check" "http://localhost:8080/api/health" "200"
test_endpoint "API info" "http://localhost:8080/api" "200"
test_endpoint "404 handler" "http://localhost:8080/api/nonexistent" "404"

echo ""
echo "========================================"
echo "Results: ${GREEN}$PASSED passed${NC}, ${RED}$FAILED failed${NC}"
echo "========================================"

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed${NC}"
    exit 1
fi
```

Make it executable and run:

```bash
chmod +x run-tests.sh
./run-tests.sh
```

## Troubleshooting

### Issue: "composer: command not found"

**Solution:** Install Composer:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Issue: "php: command not found"

**Solution:** Install PHP:
```bash
# Ubuntu/Debian
sudo apt install php8.1 php8.1-mysql php8.1-xml php8.1-mbstring

# macOS
brew install php
```

### Issue: Database connection failed

**Solution:** Check:
1. MySQL is running: `sudo systemctl status mysql`
2. Database exists: `mysql -u root -p -e "SHOW DATABASES;"`
3. Credentials in `.env` are correct
4. User has proper permissions

### Issue: CORS errors

**Solution:** Verify:
1. `CORS_ORIGIN` in `.env` matches frontend URL exactly
2. Include protocol: `http://localhost:8000` (not `localhost:8000`)
3. Check port matches

### Issue: 500 Internal Server Error

**Solution:** Check:
1. `.env` file exists
2. Vendor dependencies installed: `composer install`
3. PHP error logs for details
4. File permissions

### Issue: Port 8080 already in use

**Solution:** Use different port:
```bash
php -S localhost:8081 -t public
```

Update frontend API calls to use port 8081.

## Performance Testing (Optional)

### Using Apache Bench

```bash
# Install Apache Bench
sudo apt install apache2-utils

# Test health endpoint
ab -n 1000 -c 10 http://localhost:8080/api/health
```

**Expected:** Should handle 100+ requests/second.

### Using wrk

```bash
# Install wrk
sudo apt install wrk

# Test for 30 seconds with 10 connections
wrk -t10 -c10 -d30s http://localhost:8080/api/health
```

## Test Checklist

Use this checklist when testing:

- [ ] Dependencies installed (`composer install`)
- [ ] Database connection test passes
- [ ] Server starts without errors
- [ ] Health endpoint returns 200 OK
- [ ] Database connection shows in health response
- [ ] API info endpoint returns correct data
- [ ] 404 handler works for undefined routes
- [ ] CORS headers present in responses
- [ ] CORS preflight (OPTIONS) works
- [ ] Browser access works
- [ ] Frontend can connect (no CORS errors)
- [ ] Error handling works (test with bad DB credentials)
- [ ] All JSON responses are properly formatted

## Next Steps After Testing

If all tests pass:

1. ✅ Backend is working correctly
2. 📝 Start implementing business logic endpoints
3. 🔐 Add authentication (JWT)
4. 🔌 Connect frontend to backend
5. 📊 Add more endpoints (services, orders, etc.)
6. 🧪 Write unit tests
7. 🚀 Deploy to production

## Support

If tests fail:
1. Review error messages carefully
2. Check [QUICKSTART.md](QUICKSTART.md) troubleshooting
3. Verify all prerequisites are met
4. Check [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md)
5. Review [README.md](README.md) documentation

---

**Last Updated:** November 2024  
**Version:** 1.0.0
