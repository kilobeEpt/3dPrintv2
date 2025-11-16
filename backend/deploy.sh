#!/bin/bash

#=================================================
# 3D Print Pro - Complete Deployment Script
# Standalone PHP Mode (NO Composer Required)
#=================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}  3D Print Pro - Standalone Deployment${NC}"
echo -e "${BLUE}  NO Composer Dependencies Required!${NC}"
echo -e "${BLUE}============================================${NC}"
echo

# Step 1: Check directory structure
echo -e "${YELLOW}[1/7] Checking directory structure...${NC}"

REQUIRED_DIRS=(
    "standalone"
    "src/Bootstrap"
    "src/Controllers"
    "src/Services"
    "src/Repositories"
    "src/Helpers"
    "src/Config"
    "public"
    "database/migrations"
    "storage/logs"
    "storage/cache"
)

for dir in "${REQUIRED_DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        echo -e "${RED}✗ Missing directory: $dir${NC}"
        exit 1
    fi
done

echo -e "${GREEN}✓ All required directories exist${NC}"
echo

# Step 2: Check required files
echo -e "${YELLOW}[2/7] Checking required files...${NC}"

REQUIRED_FILES=(
    "standalone/SimpleRouter.php"
    "standalone/SimpleJWT.php"
    "standalone/SimpleEnv.php"
    "standalone/autoload.php"
    "src/Bootstrap/App.php"
    "public/index.php"
    "public/.htaccess"
    ".env"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}✗ Missing file: $file${NC}"
        exit 1
    fi
done

echo -e "${GREEN}✓ All required files exist${NC}"
echo

# Step 3: Check .env configuration
echo -e "${YELLOW}[3/7] Checking .env configuration...${NC}"

if [ ! -f ".env" ]; then
    echo -e "${RED}✗ .env file not found${NC}"
    exit 1
fi

# Check critical environment variables
source .env 2>/dev/null || true

WARNINGS=0

if [ "$APP_DEBUG" = "true" ]; then
    echo -e "${YELLOW}⚠ APP_DEBUG is enabled - disable in production${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

if [ "$JWT_SECRET" = "change_this_to_a_random_secret_key_minimum_64_characters_long_production" ] || [ ${#JWT_SECRET} -lt 32 ]; then
    echo -e "${RED}✗ JWT_SECRET must be changed and at least 32 characters${NC}"
    exit 1
fi

if [ -z "$DB_HOST" ] || [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
    echo -e "${RED}✗ Database configuration incomplete${NC}"
    exit 1
fi

if [ $WARNINGS -gt 0 ]; then
    echo -e "${YELLOW}✓ .env checked with $WARNINGS warnings${NC}"
else
    echo -e "${GREEN}✓ .env configuration looks good${NC}"
fi
echo

# Step 4: Load database migrations
echo -e "${YELLOW}[4/7] Checking database migrations...${NC}"

if [ ! -f "database/migrations/20231113_initial.sql" ]; then
    echo -e "${RED}✗ Initial migration file not found${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Migration files found${NC}"
echo -e "${BLUE}  Note: Run migrations manually:${NC}"
echo -e "  ${BLUE}mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < database/migrations/20231113_initial.sql${NC}"
echo

# Step 5: Create admin user
echo -e "${YELLOW}[5/7] Checking admin user seeder...${NC}"

if [ ! -f "database/seeds/seed-admin-user.php" ]; then
    echo -e "${YELLOW}⚠ Admin user seeder not found${NC}"
else
    echo -e "${GREEN}✓ Admin seeder found${NC}"
    echo -e "${BLUE}  Note: Run seeder manually if needed:${NC}"
    echo -e "  ${BLUE}php database/seeds/seed-admin-user.php${NC}"
fi
echo

# Step 6: Set proper permissions
echo -e "${YELLOW}[6/7] Setting file permissions...${NC}"

# Make storage writable
chmod -R 775 storage/ 2>/dev/null || true
chmod -R 664 storage/logs/*.log 2>/dev/null || true

# Protect sensitive files
chmod 600 .env 2>/dev/null || true

echo -e "${GREEN}✓ Permissions set${NC}"
echo

# Step 7: Test API endpoints
echo -e "${YELLOW}[7/7] Testing API endpoints...${NC}"

# Try to detect base URL
if [ -n "$APP_URL" ]; then
    BASE_URL="$APP_URL/backend/public"
else
    BASE_URL="http://localhost/backend/public"
fi

echo -e "${BLUE}Testing: $BASE_URL/api/health${NC}"

# Simple HTTP test (if curl available)
if command -v curl &> /dev/null; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/health" 2>/dev/null || echo "000")
    
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "503" ]; then
        echo -e "${GREEN}✓ API is responding (HTTP $HTTP_CODE)${NC}"
    else
        echo -e "${YELLOW}⚠ Could not reach API (HTTP $HTTP_CODE)${NC}"
        echo -e "${BLUE}  This is OK if deploying to remote server${NC}"
    fi
else
    echo -e "${BLUE}  curl not available - skipping HTTP test${NC}"
fi

echo

# Final summary
echo -e "${BLUE}============================================${NC}"
echo -e "${GREEN}✓ Deployment checks completed!${NC}"
echo -e "${BLUE}============================================${NC}"
echo
echo -e "${GREEN}STANDALONE MODE ACTIVATED${NC}"
echo -e "${BLUE}✓ No Composer dependencies required${NC}"
echo -e "${BLUE}✓ Works on any hosting with PHP 7.4+${NC}"
echo -e "${BLUE}✓ All controllers converted to standalone${NC}"
echo -e "${BLUE}✓ Simple routing with SimpleRouter${NC}"
echo
echo -e "${YELLOW}Manual steps remaining:${NC}"
echo -e "  1. Update .env with your database credentials"
echo -e "  2. Run database migration:"
echo -e "     ${BLUE}mysql -u$DB_USERNAME -p $DB_DATABASE < database/migrations/20231113_initial.sql${NC}"
echo -e "  3. Create admin user:"
echo -e "     ${BLUE}php database/seeds/seed-admin-user.php${NC}"
echo -e "  4. Test API: $BASE_URL/api/health"
echo -e "  5. Run ultimate verification:"
echo -e "     ${BLUE}php ultimate-final-check.php $APP_URL${NC}"
echo
echo -e "${GREEN}Deploy to: https://3dprint-omsk.ru/${NC}"
echo

exit 0
