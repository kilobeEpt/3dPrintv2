#!/usr/bin/env php
<?php
/**
 * Quick Test Script - Verify No 302 Redirects
 * 
 * This script quickly checks that API endpoints return proper status codes
 * and NOT 302 redirects which indicate .htaccess routing issues
 * 
 * Usage: php test-no-redirects.php [base_url]
 */

declare(strict_types=1);

// Color codes
define('RED', "\033[0;31m");
define('GREEN', "\033[0;32m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('NC', "\033[0m"); // No Color

function printStatus(string $message, string $color = NC): void {
    echo $color . $message . NC . PHP_EOL;
}

function testEndpoint(string $method, string $url, int $expectedStatus = null): array {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // IMPORTANT: Don't follow redirects
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'response' => $response,
        'error' => $error,
        'is_redirect' => ($httpCode >= 300 && $httpCode < 400)
    ];
}

// Banner
printStatus("========================================", BLUE);
printStatus("  Testing API - No Redirects Check", BLUE);
printStatus("========================================", BLUE);
echo PHP_EOL;

// Determine base URL
$baseUrl = $argv[1] ?? 'http://localhost:8080/backend/public';
printStatus("Base URL: $baseUrl", BLUE);
echo PHP_EOL;

$tests = [
    ['GET', '/api', 'API Root', [200]],
    ['GET', '/api/health', 'Health Check', [200, 503]],
    ['GET', '/api/services', 'Public Services', [200]],
    ['GET', '/api/portfolio', 'Public Portfolio', [200]],
    ['GET', '/api/testimonials', 'Public Testimonials', [200]],
    ['GET', '/api/faq', 'Public FAQ', [200]],
    ['GET', '/api/content', 'Public Content', [200]],
    ['GET', '/api/stats', 'Public Stats', [200]],
    ['GET', '/api/settings/public', 'Public Settings', [200]],
    ['POST', '/api/auth/login', 'Auth Login (no data)', [400, 401]],
    ['GET', '/api/auth/me', 'Protected Route (no token)', [401]],
    ['GET', '/api/admin/services', 'Admin Route (no token)', [401]],
    ['GET', '/api/nonexistent', '404 Handler', [404]],
];

$passed = 0;
$failed = 0;
$hasRedirects = false;

foreach ($tests as $test) {
    [$method, $endpoint, $name, $expectedStatuses] = $test;
    
    $result = testEndpoint($method, $baseUrl . $endpoint);
    
    $statusEmoji = in_array($result['status'], $expectedStatuses) ? '✓' : '✗';
    $statusColor = in_array($result['status'], $expectedStatuses) ? GREEN : RED;
    
    if ($result['is_redirect']) {
        $statusEmoji = '✗';
        $statusColor = RED;
        $hasRedirects = true;
        printStatus("$statusEmoji $name: {$result['status']} (REDIRECT - THIS IS THE PROBLEM!)", $statusColor);
        $failed++;
    } elseif ($result['error']) {
        printStatus("$statusEmoji $name: Connection Error - {$result['error']}", YELLOW);
        $failed++;
    } elseif (in_array($result['status'], $expectedStatuses)) {
        printStatus("$statusEmoji $name: {$result['status']}", $statusColor);
        $passed++;
    } else {
        printStatus("$statusEmoji $name: {$result['status']} (expected: " . implode('/', $expectedStatuses) . ")", $statusColor);
        $failed++;
    }
}

echo PHP_EOL;
printStatus("========================================", BLUE);
printStatus("Results: $passed passed, $failed failed", $failed === 0 ? GREEN : RED);
printStatus("========================================", BLUE);
echo PHP_EOL;

if ($hasRedirects) {
    printStatus("❌ CRITICAL: API is returning redirects (302/301)", RED);
    printStatus("This means .htaccess is not configured correctly", YELLOW);
    echo PHP_EOL;
    printStatus("Solutions:", BLUE);
    printStatus("1. Check backend/public/.htaccess has correct RewriteRule", YELLOW);
    printStatus("2. Verify RewriteBase matches your directory structure", YELLOW);
    printStatus("3. Ensure mod_rewrite is enabled in Apache", YELLOW);
    printStatus("4. Check Apache AllowOverride directive allows .htaccess", YELLOW);
    echo PHP_EOL;
    exit(1);
}

if ($failed === 0) {
    printStatus("✅ SUCCESS: No redirects detected, all endpoints working!", GREEN);
    exit(0);
} else {
    printStatus("⚠️  Some tests failed, but no redirects detected", YELLOW);
    exit(1);
}
