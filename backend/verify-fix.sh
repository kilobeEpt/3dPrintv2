#!/bin/bash

##############################################################################
# Quick Verification Script - Check if .htaccess Fix is Applied
##############################################################################
# This script quickly verifies that the fix has been correctly applied
# Run this immediately after uploading files to production
#
# Usage: ./verify-fix.sh
##############################################################################

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${2}${1}${NC}"
}

print_header() {
    echo ""
    echo "=========================================="
    echo "$1"
    echo "=========================================="
}

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

print_header "Fix Verification Script"
print_status "Checking if .htaccess fix is properly applied..." "$BLUE"
echo ""

# Check 1: .htaccess exists
if [ -f "public/.htaccess" ]; then
    print_status "✓ .htaccess file exists" "$GREEN"
else
    print_status "✗ .htaccess file is MISSING!" "$RED"
    exit 1
fi

# Check 2: No problematic redirect rule
if grep -q "R=301\|R=302" "public/.htaccess"; then
    print_status "✗ .htaccess STILL contains redirect flags (R=301/R=302)!" "$RED"
    print_status "  The fix was NOT applied correctly" "$YELLOW"
    echo ""
    print_status "Lines with redirect flags:" "$YELLOW"
    grep -n "R=301\|R=302" "public/.htaccess"
    exit 1
else
    print_status "✓ No redirect flags found in .htaccess" "$GREEN"
fi

# Check 3: Has RewriteBase
if grep -q "RewriteBase" "public/.htaccess"; then
    print_status "✓ RewriteBase directive is present" "$GREEN"
else
    print_status "⚠ RewriteBase directive is missing (optional but recommended)" "$YELLOW"
fi

# Check 4: Has Authorization header handling
if grep -q "HTTP_AUTHORIZATION" "public/.htaccess"; then
    print_status "✓ Authorization header handling is present" "$GREEN"
else
    print_status "⚠ Authorization header handling is missing (JWT may not work)" "$YELLOW"
fi

# Check 5: Has main rewrite rule
if grep -q "RewriteRule.*index\.php" "public/.htaccess"; then
    print_status "✓ Main rewrite rule to index.php is present" "$GREEN"
else
    print_status "✗ Main rewrite rule to index.php is MISSING!" "$RED"
    exit 1
fi

# Check 6: index.php exists
if [ -f "public/index.php" ]; then
    print_status "✓ index.php exists" "$GREEN"
else
    print_status "✗ index.php is MISSING!" "$RED"
    exit 1
fi

# Check 7: index.php has JSON header
if grep -q "Content-Type.*application/json" "public/index.php"; then
    print_status "✓ index.php sets JSON Content-Type" "$GREEN"
else
    print_status "⚠ index.php may not set JSON Content-Type header" "$YELLOW"
fi

# Check 8: Test scripts exist
MISSING_SCRIPTS=0

if [ -f "test-no-redirects.php" ]; then
    print_status "✓ test-no-redirects.php exists" "$GREEN"
else
    print_status "⚠ test-no-redirects.php is missing" "$YELLOW"
    MISSING_SCRIPTS=1
fi

if [ -f "final-deploy.sh" ]; then
    print_status "✓ final-deploy.sh exists" "$GREEN"
else
    print_status "⚠ final-deploy.sh is missing" "$YELLOW"
    MISSING_SCRIPTS=1
fi

# Summary
echo ""
print_header "Verification Summary"
echo ""

print_status "✅ .htaccess fix has been correctly applied!" "$GREEN"
echo ""

print_status "Next Steps:" "$BLUE"
echo ""
echo "1. Run quick test:"
echo "   php test-no-redirects.php http://yourdomain.com/backend/public"
echo ""
echo "2. Run full deployment check:"
echo "   ./final-deploy.sh http://yourdomain.com/backend/public"
echo ""
echo "3. Test in browser:"
echo "   http://yourdomain.com/backend/public/api/health"
echo "   Should return 200 with JSON (not 302 redirect)"
echo ""
echo "4. Verify frontend can connect:"
echo "   Open your site and check browser console for API errors"
echo ""

if [ $MISSING_SCRIPTS -eq 1 ]; then
    print_status "⚠ Some test scripts are missing, but the core fix is applied" "$YELLOW"
fi

print_status "Fix verification complete! ✅" "$GREEN"
exit 0
