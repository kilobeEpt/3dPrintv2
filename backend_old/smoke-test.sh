#!/bin/bash

# Quick Smoke Test Script
# Validates basic functionality of the 3D Print Pro API

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

API_URL="${API_URL:-http://localhost:8080}"
PASSED=0
FAILED=0

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}3D Print Pro - Quick Smoke Test${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "API URL: ${YELLOW}$API_URL${NC}"
echo ""

# Function to test endpoint
test_endpoint() {
    local name=$1
    local method=$2
    local endpoint=$3
    local expected_code=$4
    local data=$5
    
    echo -n "Testing ${name}... "
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL$endpoint")
    else
        response=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" \
            -H "Content-Type: application/json" \
            -d "$data" \
            "$API_URL$endpoint")
    fi
    
    if [ "$response" = "$expected_code" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $response)"
        ((PASSED++))
    else
        echo -e "${RED}✗ FAIL${NC} (Expected $expected_code, got $response)"
        ((FAILED++))
    fi
}

# Check if server is running
echo -e "${YELLOW}Checking if server is running...${NC}"
if ! curl -s "$API_URL/api/health" > /dev/null 2>&1; then
    echo -e "${RED}Error: Server not running on $API_URL${NC}"
    echo "Start server with: cd backend && composer start"
    exit 1
fi
echo -e "${GREEN}✓ Server is running${NC}"
echo ""

# Public Endpoints
echo -e "${BLUE}Testing Public Endpoints...${NC}"
test_endpoint "Health check" "GET" "/api/health" "200"
test_endpoint "API info" "GET" "/api" "200"
test_endpoint "Services list" "GET" "/api/services" "200"
test_endpoint "Portfolio list" "GET" "/api/portfolio" "200"
test_endpoint "Testimonials list" "GET" "/api/testimonials" "200"
test_endpoint "FAQ list" "GET" "/api/faq" "200"
test_endpoint "Content sections" "GET" "/api/content" "200"
test_endpoint "Site statistics" "GET" "/api/stats" "200"
test_endpoint "Public settings" "GET" "/api/settings/public" "200"
echo ""

# Authentication
echo -e "${BLUE}Testing Authentication...${NC}"
test_endpoint "Login endpoint" "POST" "/api/auth/login" "401" '{"login":"invalid","password":"wrong"}'
test_endpoint "Auth without token" "GET" "/api/auth/me" "401"
echo ""

# Protected Endpoints (should require auth)
echo -e "${BLUE}Testing Protected Endpoints...${NC}"
test_endpoint "Admin services (no auth)" "GET" "/api/admin/services" "401"
test_endpoint "Orders list (no auth)" "GET" "/api/orders" "401"
test_endpoint "Full settings (no auth)" "GET" "/api/settings" "401"
echo ""

# Order Submission (Public)
echo -e "${BLUE}Testing Order Submission...${NC}"
ORDER_DATA='{
    "client_name": "Smoke Test",
    "client_email": "smoke@test.com",
    "client_phone": "+79001234567",
    "message": "Automated smoke test"
}'
test_endpoint "Order submission" "POST" "/api/orders" "201" "$ORDER_DATA"
echo ""

# 404 Handler
echo -e "${BLUE}Testing Error Handling...${NC}"
test_endpoint "404 handler" "GET" "/api/nonexistent-endpoint" "404"
echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Test Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "Total Tests: $((PASSED + FAILED))"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All smoke tests passed!${NC}"
    echo -e "${GREEN}The API is functioning correctly.${NC}"
    exit 0
else
    echo -e "${RED}✗ Some smoke tests failed${NC}"
    echo -e "${YELLOW}Review the errors above and check server logs.${NC}"
    exit 1
fi
