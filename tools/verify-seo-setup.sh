#!/bin/bash

# SEO Setup Verification Script
# Checks all SEO-related files and configurations

echo "═══════════════════════════════════════════════════════════════════"
echo "🔍 SEO SETUP VERIFICATION"
echo "═══════════════════════════════════════════════════════════════════"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

ERRORS=0
WARNINGS=0

check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✅ $1 exists${NC}"
        return 0
    else
        echo -e "${RED}❌ $1 missing${NC}"
        ((ERRORS++))
        return 1
    fi
}

check_executable() {
    if [ -x "$1" ]; then
        echo -e "${GREEN}✅ $1 is executable${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠️  $1 is not executable${NC}"
        ((WARNINGS++))
        return 1
    fi
}

echo "📋 Step 1: Checking core SEO files..."
echo "─────────────────────────────────────────────────────────────────"

check_file "robots.txt"
check_file "sitemap.xml"

echo ""
echo "📋 Step 2: Checking sitemap generators..."
echo "─────────────────────────────────────────────────────────────────"

check_file "tools/generate-sitemap.py"
check_executable "tools/generate-sitemap.py"

check_file "tools/generate-sitemap.php"
check_executable "tools/generate-sitemap.php"

echo ""
echo "📋 Step 3: Checking documentation..."
echo "─────────────────────────────────────────────────────────────────"

check_file "docs/seo/sitemap-robots.md"
check_file "docs/seo/QUICKSTART.md"
check_file "SEO_SETUP_COMPLETE.md"

echo ""
echo "📋 Step 4: Validating robots.txt content..."
echo "─────────────────────────────────────────────────────────────────"

if [ -f "robots.txt" ]; then
    # Check for sitemap reference
    if grep -q "Sitemap:" robots.txt; then
        echo -e "${GREEN}✅ Sitemap reference found in robots.txt${NC}"
    else
        echo -e "${RED}❌ No Sitemap reference in robots.txt${NC}"
        ((ERRORS++))
    fi
    
    # Check for admin blocking
    if grep -q "Disallow: /admin.html" robots.txt; then
        echo -e "${GREEN}✅ Admin panel is blocked${NC}"
    else
        echo -e "${RED}❌ Admin panel not blocked${NC}"
        ((ERRORS++))
    fi
    
    # Check for backend blocking
    if grep -q "Disallow: /backend/" robots.txt; then
        echo -e "${GREEN}✅ Backend API is blocked${NC}"
    else
        echo -e "${RED}❌ Backend API not blocked${NC}"
        ((ERRORS++))
    fi
fi

echo ""
echo "📋 Step 5: Validating sitemap.xml structure..."
echo "─────────────────────────────────────────────────────────────────"

if [ -f "sitemap.xml" ]; then
    # Check XML declaration
    if head -n 1 sitemap.xml | grep -q "<?xml version"; then
        echo -e "${GREEN}✅ Valid XML declaration${NC}"
    else
        echo -e "${RED}❌ Invalid XML declaration${NC}"
        ((ERRORS++))
    fi
    
    # Check for urlset
    if grep -q "<urlset" sitemap.xml; then
        echo -e "${GREEN}✅ Valid urlset element${NC}"
    else
        echo -e "${RED}❌ Missing urlset element${NC}"
        ((ERRORS++))
    fi
    
    # Count URLs
    URL_COUNT=$(grep -c "<url>" sitemap.xml)
    if [ $URL_COUNT -eq 7 ]; then
        echo -e "${GREEN}✅ Correct number of URLs (7)${NC}"
    else
        echo -e "${YELLOW}⚠️  Expected 7 URLs, found $URL_COUNT${NC}"
        ((WARNINGS++))
    fi
    
    # Check for required sections
    SECTIONS=("/#home" "/#services" "/#calculator" "/#portfolio" "/#about" "/#contact")
    for section in "${SECTIONS[@]}"; do
        if grep -q "$section" sitemap.xml; then
            echo -e "${GREEN}✅ Section $section present${NC}"
        else
            echo -e "${RED}❌ Section $section missing${NC}"
            ((ERRORS++))
        fi
    done
fi

echo ""
echo "📋 Step 6: Testing sitemap generation..."
echo "─────────────────────────────────────────────────────────────────"

# Test Python generator
if command -v python3 &> /dev/null; then
    echo -e "${BLUE}Testing Python generator...${NC}"
    if python3 tools/generate-sitemap.py https://3dprint-omsk.ru > /tmp/sitemap-test.log 2>&1; then
        echo -e "${GREEN}✅ Python generator works${NC}"
    else
        echo -e "${RED}❌ Python generator failed${NC}"
        cat /tmp/sitemap-test.log
        ((ERRORS++))
    fi
else
    echo -e "${YELLOW}⚠️  Python3 not found, skipping Python generator test${NC}"
    ((WARNINGS++))
fi

# Test PHP generator
if command -v php &> /dev/null; then
    echo -e "${BLUE}Testing PHP generator...${NC}"
    if php tools/generate-sitemap.php https://3dprint-omsk.ru > /tmp/sitemap-test-php.log 2>&1; then
        echo -e "${GREEN}✅ PHP generator works${NC}"
    else
        echo -e "${YELLOW}⚠️  PHP generator failed (may not be available in this environment)${NC}"
        ((WARNINGS++))
    fi
else
    echo -e "${YELLOW}⚠️  PHP not found, skipping PHP generator test${NC}"
    ((WARNINGS++))
fi

echo ""
echo "📋 Step 7: Checking deploy.sh integration..."
echo "─────────────────────────────────────────────────────────────────"

if [ -f "backend/deploy.sh" ]; then
    if grep -q "Step 7: Generating sitemap.xml" backend/deploy.sh; then
        echo -e "${GREEN}✅ Sitemap generation integrated in deploy.sh${NC}"
    else
        echo -e "${RED}❌ deploy.sh missing sitemap generation step${NC}"
        ((ERRORS++))
    fi
    
    if grep -q "generate-sitemap.py" backend/deploy.sh; then
        echo -e "${GREEN}✅ Python generator referenced in deploy.sh${NC}"
    else
        echo -e "${RED}❌ deploy.sh missing Python generator reference${NC}"
        ((ERRORS++))
    fi
fi

echo ""
echo "📋 Step 8: Checking test checklist integration..."
echo "─────────────────────────────────────────────────────────────────"

if [ -f "docs/test-checklist.md" ]; then
    if grep -q "## 11. SEO and Indexing" docs/test-checklist.md; then
        echo -e "${GREEN}✅ SEO section added to test checklist${NC}"
    else
        echo -e "${RED}❌ test-checklist.md missing SEO section${NC}"
        ((ERRORS++))
    fi
    
    if grep -q "### Robots.txt" docs/test-checklist.md; then
        echo -e "${GREEN}✅ Robots.txt tests present${NC}"
    else
        echo -e "${RED}❌ Missing robots.txt tests${NC}"
        ((ERRORS++))
    fi
    
    if grep -q "### Sitemap.xml" docs/test-checklist.md; then
        echo -e "${GREEN}✅ Sitemap.xml tests present${NC}"
    else
        echo -e "${RED}❌ Missing sitemap.xml tests${NC}"
        ((ERRORS++))
    fi
fi

echo ""
echo "═══════════════════════════════════════════════════════════════════"
echo "📊 VERIFICATION SUMMARY"
echo "═══════════════════════════════════════════════════════════════════"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ All checks passed! SEO setup is complete and ready.${NC}"
    echo ""
    echo "📝 Next steps:"
    echo "1. Deploy to production: cd backend && ./deploy.sh"
    echo "2. Verify accessibility:"
    echo "   curl https://3dprint-omsk.ru/robots.txt"
    echo "   curl https://3dprint-omsk.ru/sitemap.xml"
    echo "3. Submit to search consoles:"
    echo "   - Google: https://search.google.com/search-console"
    echo "   - Yandex: https://webmaster.yandex.ru/"
    echo "4. Validate with online tools:"
    echo "   - Sitemap: https://www.xml-sitemaps.com/validate-xml-sitemap.html"
    echo "   - Robots: https://www.google.com/webmasters/tools/robots-testing-tool"
    echo ""
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  ${WARNINGS} warning(s) found. Review recommended but not critical.${NC}"
    echo ""
    echo "Setup is functional but consider addressing warnings above."
    echo ""
    exit 0
else
    echo -e "${RED}❌ ${ERRORS} error(s) and ${WARNINGS} warning(s) found.${NC}"
    echo ""
    echo "Please fix the errors above before deploying."
    echo ""
    exit 1
fi
