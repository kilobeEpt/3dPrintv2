#!/bin/bash

##############################################################################
# 3D Print Pro - Final Deployment Check Script
##############################################################################
# This script performs comprehensive pre-deployment verification
# Run this before deploying to production to ensure everything works
#
# Usage: ./final-deploy.sh [base_url]
# Example: ./final-deploy.sh http://yourdomain.com/backend/public
#
# If no base_url provided, will try to auto-detect
##############################################################################

set -e

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0

# Print colored output
print_status() {
    echo -e "${2}${1}${NC}"
}

print_header() {
    echo ""
    echo "=========================================="
    echo "$1"
    echo "=========================================="
}

check_pass() {
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
    print_status "✓ $1" "$GREEN"
}

check_fail() {
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    FAILED_CHECKS=$((FAILED_CHECKS + 1))
    print_status "✗ $1" "$RED"
}

check_warn() {
    print_status "⚠ $1" "$YELLOW"
}

check_info() {
    print_status "ℹ $1" "$BLUE"
}

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Banner
print_header "3D Print Pro - Final Deployment Check"
check_info "Starting comprehensive deployment verification..."
check_info "Script directory: $SCRIPT_DIR"

##############################################################################
# PHASE 1: File Structure Check
##############################################################################

print_header "PHASE 1: File Structure"

# Check critical files
if [ -f "public/index.php" ]; then
    check_pass "index.php exists"
else
    check_fail "index.php missing"
fi

if [ -f "public/.htaccess" ]; then
    check_pass ".htaccess exists"
else
    check_fail ".htaccess missing"
fi

if [ -f "vendor/autoload.php" ]; then
    check_pass "Composer dependencies installed"
else
    check_fail "Composer dependencies missing - run: composer install"
fi

if [ -f ".env" ]; then
    check_pass ".env file exists"
else
    check_warn ".env file missing - using defaults"
fi

if [ -f "composer.json" ]; then
    check_pass "composer.json exists"
else
    check_fail "composer.json missing"
fi

# Check directory structure
for dir in "src" "src/Bootstrap" "src/Controllers" "src/Services" "src/Repositories" "src/Middleware" "src/Helpers" "src/Config"; do
    if [ -d "$dir" ]; then
        check_pass "Directory $dir exists"
    else
        check_fail "Directory $dir missing"
    fi
done

##############################################################################
# PHASE 2: Permissions Check
##############################################################################

print_header "PHASE 2: File Permissions"

# Check storage directory permissions
if [ -d "storage" ]; then
    if [ -w "storage" ]; then
        check_pass "storage/ is writable"
    else
        check_fail "storage/ is not writable - run: chmod -R 775 storage/"
    fi
    
    for subdir in "logs" "cache"; do
        if [ ! -d "storage/$subdir" ]; then
            mkdir -p "storage/$subdir"
            check_info "Created storage/$subdir"
        fi
        
        if [ -w "storage/$subdir" ]; then
            check_pass "storage/$subdir is writable"
        else
            check_fail "storage/$subdir is not writable"
        fi
    done
else
    check_fail "storage/ directory missing - creating it"
    mkdir -p storage/{logs,cache}
    chmod -R 775 storage
fi

# Check .htaccess is readable
if [ -r "public/.htaccess" ]; then
    check_pass "public/.htaccess is readable"
else
    check_fail "public/.htaccess is not readable"
fi

##############################################################################
# PHASE 3: PHP Configuration
##############################################################################

print_header "PHASE 3: PHP Configuration"

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
check_info "PHP Version: $PHP_VERSION"

if php -r "exit(version_compare(PHP_VERSION, '7.4.0', '>=') ? 0 : 1);"; then
    check_pass "PHP version is 7.4.0 or higher"
else
    check_fail "PHP version must be 7.4.0 or higher"
fi

# Check required PHP extensions
REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "json" "mbstring" "openssl" "curl")

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -qi "^$ext$"; then
        check_pass "PHP extension $ext is loaded"
    else
        check_fail "PHP extension $ext is missing"
    fi
done

##############################################################################
# PHASE 4: Environment Configuration
##############################################################################

print_header "PHASE 4: Environment Configuration"

if [ -f ".env" ]; then
    # Source .env file
    export $(grep -v '^#' .env | xargs)
    
    # Check critical environment variables
    if [ -n "$DB_HOST" ]; then
        check_pass "DB_HOST is set"
    else
        check_warn "DB_HOST not set, using default"
    fi
    
    if [ -n "$DB_DATABASE" ]; then
        check_pass "DB_DATABASE is set"
    else
        check_warn "DB_DATABASE not set, using default"
    fi
    
    if [ -n "$JWT_SECRET" ]; then
        if [ "$JWT_SECRET" != "change_this_to_a_random_secret_key_in_production" ] && [ "$JWT_SECRET" != "change_this_secret" ]; then
            check_pass "JWT_SECRET is configured"
        else
            check_fail "JWT_SECRET must be changed in production!"
        fi
    else
        check_fail "JWT_SECRET not set"
    fi
    
    if [ -n "$APP_ENV" ]; then
        check_info "Environment: $APP_ENV"
        if [ "$APP_ENV" = "production" ]; then
            if [ "$APP_DEBUG" = "true" ]; then
                check_fail "APP_DEBUG should be false in production!"
            else
                check_pass "APP_DEBUG is disabled in production"
            fi
        fi
    fi
else
    check_warn "No .env file found, using default configuration"
fi

##############################################################################
# PHASE 5: Database Connection
##############################################################################

print_header "PHASE 5: Database Connection"

check_info "Running database connection test..."

if [ -f "test-db.php" ]; then
    DB_TEST_OUTPUT=$(php test-db.php 2>&1)
    
    if echo "$DB_TEST_OUTPUT" | grep -q '"connected":true'; then
        check_pass "Database connection successful"
        
        # Check tables
        if echo "$DB_TEST_OUTPUT" | grep -q '"tables":\['; then
            check_pass "Database tables found"
        else
            check_warn "No database tables found - run migrations"
        fi
    else
        check_fail "Database connection failed"
        check_info "Run: php test-db.php for details"
    fi
else
    check_warn "test-db.php not found, skipping database test"
fi

##############################################################################
# PHASE 6: API Endpoints Test
##############################################################################

print_header "PHASE 6: API Endpoints Test"

# Determine base URL
if [ -n "$1" ]; then
    BASE_URL="$1"
    check_info "Using provided base URL: $BASE_URL"
elif [ -n "$APP_URL" ]; then
    BASE_URL="$APP_URL"
    check_info "Using APP_URL from .env: $BASE_URL"
else
    BASE_URL="http://localhost:8080/backend/public"
    check_warn "No base URL provided, using default: $BASE_URL"
fi

# Check if we can run curl tests
if command -v curl >/dev/null 2>&1; then
    check_pass "curl is available for API tests"
    
    check_info "Testing API endpoints..."
    
    # Test /api endpoint
    API_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" "$BASE_URL/api" 2>/dev/null || echo "000")
    
    if [ "$API_RESPONSE" = "200" ]; then
        check_pass "GET /api returns 200"
    elif [ "$API_RESPONSE" = "302" ] || [ "$API_RESPONSE" = "301" ]; then
        check_fail "GET /api returns $API_RESPONSE (REDIRECT!) - .htaccess issue"
    elif [ "$API_RESPONSE" = "000" ]; then
        check_warn "Cannot connect to $BASE_URL - server may not be running"
    else
        check_fail "GET /api returns $API_RESPONSE (expected 200)"
    fi
    
    # Test /api/health endpoint
    HEALTH_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" "$BASE_URL/api/health" 2>/dev/null || echo "000")
    
    if [ "$HEALTH_RESPONSE" = "200" ] || [ "$HEALTH_RESPONSE" = "503" ]; then
        check_pass "GET /api/health returns $HEALTH_RESPONSE"
    elif [ "$HEALTH_RESPONSE" = "302" ] || [ "$HEALTH_RESPONSE" = "301" ]; then
        check_fail "GET /api/health returns $HEALTH_RESPONSE (REDIRECT!) - .htaccess issue"
    elif [ "$HEALTH_RESPONSE" = "000" ]; then
        check_warn "Cannot connect to health endpoint"
    else
        check_fail "GET /api/health returns $HEALTH_RESPONSE (expected 200 or 503)"
    fi
    
    # Test auth endpoint (should return 400 or 401, not 302)
    AUTH_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X POST -H "Content-Type: application/json" -H "Accept: application/json" "$BASE_URL/api/auth/login" 2>/dev/null || echo "000")
    
    if [ "$AUTH_RESPONSE" = "400" ] || [ "$AUTH_RESPONSE" = "401" ]; then
        check_pass "POST /api/auth/login returns $AUTH_RESPONSE (correct)"
    elif [ "$AUTH_RESPONSE" = "302" ] || [ "$AUTH_RESPONSE" = "301" ]; then
        check_fail "POST /api/auth/login returns $AUTH_RESPONSE (REDIRECT!) - .htaccess issue"
    elif [ "$AUTH_RESPONSE" = "000" ]; then
        check_warn "Cannot connect to auth endpoint"
    else
        check_warn "POST /api/auth/login returns $AUTH_RESPONSE"
    fi
    
    # Test 404 handler
    NOTFOUND_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" "$BASE_URL/api/nonexistent" 2>/dev/null || echo "000")
    
    if [ "$NOTFOUND_RESPONSE" = "404" ]; then
        check_pass "GET /api/nonexistent returns 404 (correct)"
    elif [ "$NOTFOUND_RESPONSE" = "302" ] || [ "$NOTFOUND_RESPONSE" = "301" ]; then
        check_fail "GET /api/nonexistent returns $NOTFOUND_RESPONSE (REDIRECT!) - .htaccess issue"
    elif [ "$NOTFOUND_RESPONSE" = "000" ]; then
        check_warn "Cannot connect to API"
    else
        check_warn "GET /api/nonexistent returns $NOTFOUND_RESPONSE"
    fi
    
else
    check_warn "curl not available, skipping API endpoint tests"
    check_info "Install curl to enable API tests: apt-get install curl"
fi

# Run comprehensive test-routes.php if available
if [ -f "test-routes.php" ]; then
    check_info "Running comprehensive route tests..."
    
    ROUTE_TEST_OUTPUT=$(php test-routes.php 2>&1)
    
    if echo "$ROUTE_TEST_OUTPUT" | grep -q '"success":true'; then
        check_pass "All route tests passed"
    else
        check_fail "Some route tests failed"
        check_info "Run: php test-routes.php for details"
    fi
fi

##############################################################################
# PHASE 7: Security Check
##############################################################################

print_header "PHASE 7: Security Configuration"

# Check .env is not accessible via web
if command -v curl >/dev/null 2>&1 && [ -n "$BASE_URL" ]; then
    ENV_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/../.env" 2>/dev/null || echo "000")
    
    if [ "$ENV_RESPONSE" = "403" ] || [ "$ENV_RESPONSE" = "404" ]; then
        check_pass ".env file is not accessible via web"
    elif [ "$ENV_RESPONSE" = "200" ]; then
        check_fail ".env file IS accessible via web - SECURITY ISSUE!"
    fi
fi

# Check if composer.json is not accessible
if command -v curl >/dev/null 2>&1 && [ -n "$BASE_URL" ]; then
    COMPOSER_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/composer.json" 2>/dev/null || echo "000")
    
    if [ "$COMPOSER_RESPONSE" = "403" ] || [ "$COMPOSER_RESPONSE" = "404" ]; then
        check_pass "composer.json is not accessible via web"
    elif [ "$COMPOSER_RESPONSE" = "200" ]; then
        check_fail "composer.json IS accessible - SECURITY ISSUE!"
    fi
fi

# Check storage permissions
if [ -d "storage" ]; then
    STORAGE_PERMS=$(stat -c "%a" storage 2>/dev/null || stat -f "%Lp" storage)
    if [ "$STORAGE_PERMS" = "775" ] || [ "$STORAGE_PERMS" = "755" ]; then
        check_pass "storage/ has correct permissions"
    else
        check_warn "storage/ permissions are $STORAGE_PERMS (recommended: 775)"
    fi
fi

##############################################################################
# FINAL SUMMARY
##############################################################################

print_header "DEPLOYMENT VERIFICATION SUMMARY"

echo ""
print_status "Total Checks: $TOTAL_CHECKS" "$BLUE"
print_status "Passed: $PASSED_CHECKS" "$GREEN"
print_status "Failed: $FAILED_CHECKS" "$RED"
echo ""

if [ $FAILED_CHECKS -eq 0 ]; then
    print_status "🎉 ALL CHECKS PASSED - READY FOR DEPLOYMENT!" "$GREEN"
    echo ""
    check_info "Next steps:"
    echo "  1. Upload files to production server"
    echo "  2. Run: composer install --no-dev --optimize-autoloader"
    echo "  3. Set APP_ENV=production in .env"
    echo "  4. Set APP_DEBUG=false in .env"
    echo "  5. Change JWT_SECRET to a random 64-character string"
    echo "  6. Update CORS_ORIGIN to your frontend domain"
    echo "  7. Run migrations: php database/migrations/20231113_initial.sql"
    echo "  8. Run seeder: php database/seeds/seed-admin-user.php"
    echo "  9. Test all endpoints with this script"
    echo ""
    exit 0
else
    print_status "❌ DEPLOYMENT CHECK FAILED - $FAILED_CHECKS ISSUES FOUND" "$RED"
    echo ""
    check_info "Please fix the failed checks before deploying"
    echo ""
    exit 1
fi
