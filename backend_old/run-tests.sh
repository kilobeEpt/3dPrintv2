#!/bin/bash

# Test Runner Script for 3D Print Pro Backend
# Runs PHPUnit integration tests and generates report

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}3D Print Pro - Integration Test Suite${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check if backend server is running
echo -e "${YELLOW}Checking if backend server is running...${NC}"
if ! curl -s http://localhost:8080/api/health > /dev/null 2>&1; then
    echo -e "${RED}Error: Backend server not running on localhost:8080${NC}"
    echo -e "${YELLOW}Please start the server first:${NC}"
    echo "  cd backend"
    echo "  composer start"
    echo ""
    echo -e "${YELLOW}Or run in background:${NC}"
    echo "  php -S localhost:8080 -t public > server.log 2>&1 &"
    exit 1
fi
echo -e "${GREEN}✓ Backend server is running${NC}"
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo -e "${RED}Error: .env file not found${NC}"
    echo -e "${YELLOW}Please copy .env.example to .env and configure it${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Environment file found${NC}"
echo ""

# Check if vendor directory exists
if [ ! -d vendor ]; then
    echo -e "${YELLOW}Installing dependencies...${NC}"
    composer install
    echo -e "${GREEN}✓ Dependencies installed${NC}"
    echo ""
fi

# Check if database is accessible
echo -e "${YELLOW}Checking database connection...${NC}"
php test-connection.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}Error: Database connection failed${NC}"
    echo -e "${YELLOW}Please check your database configuration in .env${NC}"
    exit 1
fi
echo ""

# Run PHPUnit tests
echo -e "${BLUE}Running PHPUnit Integration Tests...${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Run tests with detailed output
if vendor/bin/phpunit --testdox --colors=always; then
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${GREEN}✓ All tests passed!${NC}"
    echo -e "${BLUE}========================================${NC}"
    EXIT_CODE=0
else
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${RED}✗ Some tests failed${NC}"
    echo -e "${BLUE}========================================${NC}"
    EXIT_CODE=1
fi

# Generate coverage report (optional, requires xdebug)
if php -m | grep -q xdebug; then
    echo ""
    echo -e "${YELLOW}Generating code coverage report...${NC}"
    vendor/bin/phpunit --coverage-html coverage --coverage-text
    echo -e "${GREEN}Coverage report generated in: coverage/index.html${NC}"
fi

echo ""
echo -e "${BLUE}Test execution completed${NC}"
echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Test Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "Results logged to console output above"
echo -e "Update ${YELLOW}docs/testing-report.md${NC} with findings"
echo ""

exit $EXIT_CODE
