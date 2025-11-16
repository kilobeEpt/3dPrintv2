#!/usr/bin/env php
<?php

$baseUrl = $argv[1] ?? 'http://localhost';
$baseUrl = rtrim($baseUrl, '/');

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "🧪 COMPLETE BACKEND TEST - NEW ARCHITECTURE\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "Base URL: {$baseUrl}\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$passed = 0;
$failed = 0;
$token = null;

function test($name, $url, $method = 'GET', $data = null, $expectedCode = 200, $authToken = null) {
    global $passed, $failed, $baseUrl;
    
    $fullUrl = $baseUrl . $url;
    $options = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n",
            'ignore_errors' => true
        ]
    ];
    
    if ($authToken) {
        $options['http']['header'] .= "Authorization: Bearer {$authToken}\r\n";
    }
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    $result = @file_get_contents($fullUrl, false, $context);
    
    $statusCode = 500;
    if (isset($http_response_header) && isset($http_response_header[0])) {
        preg_match('/\d{3}/', $http_response_header[0], $matches);
        $statusCode = (int)($matches[0] ?? 500);
    }
    
    $response = json_decode($result, true);
    
    $success = ($statusCode === $expectedCode);
    
    if ($success) {
        echo "✅ {$name}\n";
        echo "   Status: {$statusCode}\n";
        $passed++;
    } else {
        echo "❌ {$name}\n";
        echo "   Expected: {$expectedCode}, Got: {$statusCode}\n";
        if ($response && isset($response['message'])) {
            echo "   Message: {$response['message']}\n";
        }
        $failed++;
    }
    
    return $response;
}

echo "📋 TEST 1: Health Check\n";
echo "─────────────────────────────────────────────────────────────────\n";
test('GET /api/health', '/api/health', 'GET', null, 200);
echo "\n";

echo "📋 TEST 2: Authentication\n";
echo "─────────────────────────────────────────────────────────────────\n";
$loginResult = test('POST /api/auth/login (admin/admin123)', '/api/auth/login', 'POST', [
    'login' => 'admin',
    'password' => 'admin123'
], 200);

if ($loginResult && isset($loginResult['data']['access_token'])) {
    $token = $loginResult['data']['access_token'];
    echo "   🔑 Token obtained: " . substr($token, 0, 20) . "...\n";
}

if ($token) {
    test('GET /api/auth/me (with token)', '/api/auth/me', 'GET', null, 200, $token);
} else {
    echo "⚠️  Skipping auth/me test - no token\n";
}

test('GET /api/auth/me (without token)', '/api/auth/me', 'GET', null, 401);
echo "\n";

echo "📋 TEST 3: Public Endpoints (GET)\n";
echo "─────────────────────────────────────────────────────────────────\n";
test('GET /api/services', '/api/services', 'GET', null, 200);
test('GET /api/portfolio', '/api/portfolio', 'GET', null, 200);
test('GET /api/testimonials', '/api/testimonials', 'GET', null, 200);
test('GET /api/faq', '/api/faq', 'GET', null, 200);
test('GET /api/content', '/api/content', 'GET', null, 200);
test('GET /api/settings/public', '/api/settings/public', 'GET', null, 200);
echo "\n";

echo "📋 TEST 4: Protected Endpoints (without auth)\n";
echo "─────────────────────────────────────────────────────────────────\n";
test('POST /api/services (no auth)', '/api/services', 'POST', ['name' => 'Test'], 401);
test('PUT /api/services (no auth)', '/api/services', 'PUT', ['id' => 1], 401);
test('DELETE /api/services (no auth)', '/api/services?id=999', 'DELETE', null, 401);
echo "\n";

if ($token) {
    echo "📋 TEST 5: Protected Endpoints (with auth)\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    test('GET /api/settings (with auth)', '/api/settings', 'GET', null, 200, $token);
    test('GET /api/orders (with auth)', '/api/orders', 'GET', null, 200, $token);
    test('GET /api/telegram/status (with auth)', '/api/telegram/status', 'GET', null, 200, $token);
    echo "\n";
}

echo "📋 TEST 6: Order Creation (public)\n";
echo "─────────────────────────────────────────────────────────────────\n";
test('POST /api/orders (valid)', '/api/orders', 'POST', [
    'name' => 'Test User',
    'phone' => '+79991234567',
    'email' => 'test@example.com',
    'message' => 'Test order from automated test'
], 200);
test('POST /api/orders (missing data)', '/api/orders', 'POST', [
    'name' => 'Test User'
], 422);
echo "\n";

echo "📋 TEST 7: Invalid Endpoints\n";
echo "─────────────────────────────────────────────────────────────────\n";
test('GET /api/nonexistent', '/api/nonexistent', 'GET', null, 404);
test('POST /api/invalid', '/api/invalid', 'POST', [], 404);
echo "\n";

echo "═══════════════════════════════════════════════════════════════════\n";
echo "📊 TEST RESULTS\n";
echo "═══════════════════════════════════════════════════════════════════\n";
$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

echo "Total Tests:  {$total}\n";
echo "Passed:       {$passed}\n";
echo "Failed:       {$failed}\n";
echo "Success Rate: {$percentage}%\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

if ($failed === 0) {
    echo "✅ ALL TESTS PASSED - SYSTEM READY!\n\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED - PLEASE FIX ERRORS\n\n";
    exit(1);
}
