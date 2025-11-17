#!/bin/bash

echo "═══════════════════════════════════════════════════════════════════"
echo "🚀 NEW BACKEND DEPLOYMENT"
echo "═══════════════════════════════════════════════════════════════════"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

ERRORS=0

check() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${RED}❌ $1${NC}"
        ((ERRORS++))
    fi
}

echo "📋 Step 1: Checking directory structure..."
echo "─────────────────────────────────────────────────────────────────"

[ -d "public" ]; check "public/ directory exists"
[ -d "api" ]; check "api/ directory exists"
[ -d "api/auth" ]; check "api/auth/ directory exists"
[ -d "helpers" ]; check "helpers/ directory exists"
[ -d "database/migrations" ]; check "database/migrations/ directory exists"

echo ""
echo "📋 Step 2: Checking required files..."
echo "─────────────────────────────────────────────────────────────────"

[ -f "public/index.php" ]; check "public/index.php exists"
[ -f "public/.htaccess" ]; check "public/.htaccess exists"
[ -f ".env" ]; check ".env exists"
[ -f "helpers/Database.php" ]; check "helpers/Database.php exists"
[ -f "helpers/Response.php" ]; check "helpers/Response.php exists"
[ -f "helpers/JWT.php" ]; check "helpers/JWT.php exists"
[ -f "helpers/Auth.php" ]; check "helpers/Auth.php exists"
[ -f "api/health.php" ]; check "api/health.php exists"
[ -f "api/auth/login.php" ]; check "api/auth/login.php exists"
[ -f "create-admin.php" ]; check "create-admin.php exists"
[ -f "test-all.php" ]; check "test-all.php exists"

echo ""
echo "📋 Step 3: Checking .env configuration..."
echo "─────────────────────────────────────────────────────────────────"

grep -q "DB_DATABASE" .env; check ".env has DB_DATABASE"
grep -q "DB_USERNAME" .env; check ".env has DB_USERNAME"
grep -q "DB_PASSWORD" .env; check ".env has DB_PASSWORD"
grep -q "JWT_SECRET" .env; check ".env has JWT_SECRET"

echo ""
echo "📋 Step 4: Checking .htaccess safety..."
echo "─────────────────────────────────────────────────────────────────"

if grep "RewriteRule" public/.htaccess | grep -v "^#" | grep -q "R=301\|R=302"; then
    echo -e "${RED}❌ .htaccess contains redirect flags (R=301 or R=302)${NC}"
    echo -e "${YELLOW}⚠️  This will cause 302 errors! Remove all R= flags.${NC}"
    ((ERRORS++))
else
    echo -e "${GREEN}✅ .htaccess does not contain redirect flags${NC}"
fi

echo ""
echo "📋 Step 5: Setting file permissions..."
echo "─────────────────────────────────────────────────────────────────"

chmod +x create-admin.php
check "create-admin.php is executable"

chmod +x test-all.php
check "test-all.php is executable"

chmod 600 .env 2>/dev/null || chmod 640 .env
check ".env has secure permissions"

echo ""
echo "📋 Step 6: Creating admin user..."
echo "─────────────────────────────────────────────────────────────────"

if php create-admin.php 2>&1 | grep -q "successfully"; then
    echo -e "${GREEN}✅ Admin user created/updated${NC}"
else
    echo -e "${YELLOW}⚠️  Admin user creation had issues (might be OK if already exists)${NC}"
fi

echo ""
echo "📋 Step 7: Generating sitemap.xml..."
echo "─────────────────────────────────────────────────────────────────"

# Используем Python версию (универсально)
if command -v python3 &> /dev/null; then
    cd .. && python3 tools/generate-sitemap.py https://3dprint-omsk.ru > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        cd backend
        echo -e "${GREEN}✅ sitemap.xml generated (Python)${NC}"
    else
        cd backend
        echo -e "${YELLOW}⚠️  sitemap.xml generation failed${NC}"
        ((ERRORS++))
    fi
# Fallback на PHP версию
elif command -v php &> /dev/null; then
    cd .. && php tools/generate-sitemap.php https://3dprint-omsk.ru > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        cd backend
        echo -e "${GREEN}✅ sitemap.xml generated (PHP)${NC}"
    else
        cd backend
        echo -e "${YELLOW}⚠️  sitemap.xml generation failed${NC}"
        ((ERRORS++))
    fi
else
    echo -e "${YELLOW}⚠️  Neither Python nor PHP found, skipping sitemap generation${NC}"
fi

# Проверка наличия robots.txt
cd ..
if [ -f "robots.txt" ]; then
    echo -e "${GREEN}✅ robots.txt exists${NC}"
else
    echo -e "${YELLOW}⚠️  robots.txt not found${NC}"
fi
cd backend

echo ""
echo "═══════════════════════════════════════════════════════════════════"
echo "📊 DEPLOYMENT SUMMARY"
echo "═══════════════════════════════════════════════════════════════════"

if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ All checks passed! Backend is ready.${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Test endpoints: ./test-all.php https://yourdomain.com/backend/public"
    echo "2. Login to admin panel: https://yourdomain.com/admin.html"
    echo "3. Default credentials: admin / admin123"
    echo "4. ⚠️  Change password immediately!"
    echo ""
    exit 0
else
    echo -e "${RED}❌ ${ERRORS} error(s) found. Please fix before deploying.${NC}"
    echo ""
    exit 1
fi
