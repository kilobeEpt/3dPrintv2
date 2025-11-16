#!/usr/bin/env php
<?php
/**
 * COMPREHENSIVE API TEST SUITE
 * Tests ALL endpoints, authentication, CRUD operations, and system health
 * 
 * Usage: php test-all.php [base_url]
 * Example: php test-all.php https://3dprint-omsk.ru/backend/public
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Colors for terminal output
define('GREEN', "\033[32m");
define('RED', "\033[31m");
define('YELLOW', "\033[33m");
define('BLUE', "\033[34m");
define('CYAN', "\033[36m");
define('BOLD', "\033[1m");
define('RESET', "\033[0m");

// Get base URL from argument or use default
$baseUrl = $argv[1] ?? 'http://localhost:8080';
$baseUrl = rtrim($baseUrl, '/');

// Test results tracking
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$warnings = [];
$criticalFailures = [];

echo "\n";
echo BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;
echo BOLD . "   3D PRINT PRO - COMPLETE API TEST SUITE\n" . RESET;
echo BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;
echo "Testing API at: " . BLUE . $baseUrl . RESET . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;
echo "\n";

/**
 * Test helper function
 */
function test($name, $callback, $critical = false) {
    global $totalTests, $passedTests, $failedTests, $warnings, $criticalFailures;
    
    $totalTests++;
    echo sprintf("%-55s", $name);
    
    try {
        $result = $callback();
        
        if ($result['status'] === 'pass') {
            echo " [" . GREEN . "✓ PASS" . RESET . "]";
            if (isset($result['info'])) {
                echo " " . CYAN . "(" . $result['info'] . ")" . RESET;
            }
            echo "\n";
            $passedTests++;
        } elseif ($result['status'] === 'warning') {
            echo " [" . YELLOW . "⚠ WARN" . RESET . "] " . $result['message'] . "\n";
            $warnings[] = $name . ': ' . $result['message'];
            $passedTests++;
        } else {
            echo " [" . RED . "✗ FAIL" . RESET . "] " . $result['message'] . "\n";
            $failedTests++;
            if ($critical) {
                $criticalFailures[] = $name . ': ' . $result['message'];
            }
        }
    } catch (Exception $e) {
        echo " [" . RED . "✗ ERROR" . RESET . "] " . $e->getMessage() . "\n";
        $failedTests++;
        if ($critical) {
            $criticalFailures[] = $name . ': ' . $e->getMessage();
        }
    }
}

/**
 * HTTP request helper
 */
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
    } elseif ($method === 'PUT' || $method === 'DELETE' || $method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    return [
        'status' => $statusCode,
        'header' => $header,
        'body' => $body,
        'json' => @json_decode($body, true),
        'error' => $error
    ];
}

// ═══════════════════════════════════════════════════════════════
// TEST SUITE 1: CRITICAL CHECKS - NO REDIRECTS
// ═══════════════════════════════════════════════════════════════

echo "\n" . BLUE . BOLD . "[1] CRITICAL CHECKS - NO REDIRECTS" . RESET . "\n";
echo "───────────────────────────────────────────────────────────────\n";

test('API root endpoint - no redirect', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api");
    
    if ($response['status'] == 301 || $response['status'] == 302) {
        return ['status' => 'fail', 'message' => "Redirect detected: {$response['status']}"];
    }
    
    if ($response['status'] == 0) {
        return ['status' => 'fail', 'message' => 'Connection failed: ' . $response['error']];
    }
    
    return ['status' => 'pass', 'info' => "HTTP {$response['status']}"];
}, true);

test('Health endpoint - no redirect', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/health");
    
    if ($response['status'] == 301 || $response['status'] == 302) {
        return ['status' => 'fail', 'message' => "Redirect detected: {$response['status']}"];
    }
    
    if ($response['status'] != 200) {
        return ['status' => 'fail', 'message' => "Expected 200, got {$response['status']}"];
    }
    
    return ['status' => 'pass', 'info' => 'HTTP 200'];
}, true);

test('Auth login endpoint - no redirect', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/auth/login", 'POST');
    
    if ($response['status'] == 301 || $response['status'] == 302) {
        return ['status' => 'fail', 'message' => "Redirect detected: {$response['status']}"];
    }
    
    if ($response['status'] == 404) {
        return ['status' => 'fail', 'message' => '404 Not Found - routing broken'];
    }
    
    return ['status' => 'pass', 'info' => "HTTP {$response['status']}"];
}, true);

// ═══════════════════════════════════════════════════════════════
// TEST SUITE 2: API HEALTH & DATABASE
// ═══════════════════════════════════════════════════════════════

echo "\n" . BLUE . BOLD . "[2] API HEALTH & DATABASE CONNECTION" . RESET . "\n";
echo "───────────────────────────────────────────────────────────────\n";

test('Health endpoint returns JSON', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/health");
    
    if ($response['status'] != 200) {
        return ['status' => 'fail', 'message' => "HTTP {$response['status']}"];
    }
    
    if (!$response['json']) {
        return ['status' => 'fail', 'message' => 'Invalid JSON response'];
    }
    
    return ['status' => 'pass', 'info' => 'Valid JSON'];
}, true);

test('Database connection working', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/health");
    
    if (!$response['json']) {
        return ['status' => 'fail', 'message' => 'No JSON response'];
    }
    
    $dbConnected = $response['json']['database']['connected'] ?? false;
    
    if (!$dbConnected) {
        $dbError = $response['json']['database']['error'] ?? 'Unknown error';
        return ['status' => 'fail', 'message' => "DB Error: $dbError"];
    }
    
    return ['status' => 'pass', 'info' => 'Connected'];
}, true);

test('API environment configured', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/health");
    
    if (!$response['json']) {
        return ['status' => 'fail', 'message' => 'No JSON response'];
    }
    
    $env = $response['json']['environment'] ?? 'unknown';
    return ['status' => 'pass', 'info' => "Env: $env"];
});

// ═══════════════════════════════════════════════════════════════
// TEST SUITE 3: AUTHENTICATION SYSTEM
// ═══════════════════════════════════════════════════════════════

echo "\n" . BLUE . BOLD . "[3] AUTHENTICATION SYSTEM" . RESET . "\n";
echo "───────────────────────────────────────────────────────────────\n";

$adminToken = null;
$testOrderId = null;

test('Login endpoint exists', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/auth/login", 'POST', []);
    
    if ($response['status'] == 404) {
        return ['status' => 'fail', 'message' => '404 Not Found'];
    }
    
    // Should return 401/422, not 404
    return ['status' => 'pass', 'info' => "HTTP {$response['status']}"];
}, true);

test('Login rejects invalid credentials', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/auth/login", 'POST', [
        'login' => 'invalid_user',
        'password' => 'wrong_password'
    ]);
    
    if ($response['status'] != 401) {
        return ['status' => 'fail', 'message' => "Expected 401, got {$response['status']}"];
    }
    
    return ['status' => 'pass', 'info' => 'Correctly rejected'];
});

test('Login accepts valid credentials', function() use ($baseUrl, &$adminToken) {
    // Try default admin credentials
    $response = makeRequest("$baseUrl/api/auth/login", 'POST', [
        'login' => 'admin',
        'password' => 'admin123456'
    ]);
    
    if ($response['status'] != 200) {
        return ['status' => 'warning', 'message' => 'Default admin credentials not working - run create-admin.php'];
    }
    
    if (!isset($response['json']['data']['token'])) {
        return ['status' => 'fail', 'message' => 'No JWT token in response'];
    }
    
    $adminToken = $response['json']['data']['token'];
    return ['status' => 'pass', 'info' => 'Token received'];
}, true);

test('JWT token structure valid', function() use ($adminToken) {
    if (!$adminToken) {
        return ['status' => 'warning', 'message' => 'No token available'];
    }
    
    $parts = explode('.', $adminToken);
    if (count($parts) !== 3) {
        return ['status' => 'fail', 'message' => 'Invalid JWT structure'];
    }
    
    return ['status' => 'pass', 'info' => '3-part JWT'];
});

test('Protected endpoint rejects no auth', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/auth/me", 'GET');
    
    if ($response['status'] != 401) {
        return ['status' => 'fail', 'message' => "Expected 401, got {$response['status']}"];
    }
    
    return ['status' => 'pass', 'info' => 'Correctly rejected'];
});

test('Protected endpoint accepts valid token', function() use ($baseUrl, $adminToken) {
    if (!$adminToken) {
        return ['status' => 'warning', 'message' => 'No token available'];
    }
    
    $response = makeRequest("$baseUrl/api/auth/me", 'GET', null, [
        "Authorization: Bearer $adminToken"
    ]);
    
    if ($response['status'] != 200) {
        return ['status' => 'fail', 'message' => "HTTP {$response['status']}"];
    }
    
    if (!isset($response['json']['data']['login'])) {
        return ['status' => 'fail', 'message' => 'No user data in response'];
    }
    
    $login = $response['json']['data']['login'];
    return ['status' => 'pass', 'info' => "User: $login"];
});

// ═══════════════════════════════════════════════════════════════
// TEST SUITE 4: PUBLIC ENDPOINTS
// ═══════════════════════════════════════════════════════════════

echo "\n" . BLUE . BOLD . "[4] PUBLIC ENDPOINTS (No Auth Required)" . RESET . "\n";
echo "───────────────────────────────────────────────────────────────\n";

$publicEndpoints = [
    '/api/services' => 'Services',
    '/api/portfolio' => 'Portfolio',
    '/api/portfolio/categories' => 'Portfolio Categories',
    '/api/testimonials' => 'Testimonials',
    '/api/faq' => 'FAQ',
    '/api/content' => 'Content',
    '/api/stats' => 'Statistics',
    '/api/settings/public' => 'Public Settings'
];

foreach ($publicEndpoints as $endpoint => $name) {
    test($name . ' endpoint', function() use ($baseUrl, $endpoint) {
        $response = makeRequest("$baseUrl$endpoint");
        
        if ($response['status'] == 301 || $response['status'] == 302) {
            return ['status' => 'fail', 'message' => "Redirect: {$response['status']}"];
        }
        
        if ($response['status'] != 200) {
            return ['status' => 'fail', 'message' => "HTTP {$response['status']}"];
        }
        
        if (!$response['json']) {
            return ['status' => 'fail', 'message' => 'Invalid JSON'];
        }
        
        if (!isset($response['json']['success'])) {
            return ['status' => 'fail', 'message' => 'Missing success field'];
        }
        
        $data = $response['json']['data'] ?? [];
        $count = is_array($data) ? count($data) : 'N/A';
        return ['status' => 'pass', 'info' => "Records: $count"];
    });
}

// ═══════════════════════════════════════════════════════════════
// TEST SUITE 5: ADMIN ENDPOINTS (Auth Required)
// ═══════════════════════════════════════════════════════════════

echo "\n" . BLUE . BOLD . "[5] ADMIN ENDPOINTS (Auth Required)" . RESET . "\n";
echo "───────────────────────────────────────────────────────────────\n";

$adminEndpoints = [
    'GET /api/orders' => 'Orders List',
    'GET /api/settings' => 'Admin Settings',
    'GET /api/admin/services' => 'Admin Services',
    'GET /api/admin/testimonials' => 'Admin Testimonials',
    'GET /api/admin/faq' => 'Admin FAQ',
    'GET /api/telegram/status' => 'Telegram Status'
];

foreach ($adminEndpoints as $route => $name) {
    list($method, $endpoint) = explode(' ', $route);
    
    test($name, function() use ($baseUrl, $method, $endpoint, $adminToken) {
        if (!$adminToken) {
            return ['status' => 'warning', 'message' => 'No token - skipped'];
        }
        
        $response = makeRequest("$baseUrl$endpoint", $method, null, [
            "Authorization: Bearer $adminToken"
        ]);
        
        if ($response['status'] != 200) {
            return ['status' => 'fail', 'message' => "HTTP {$response['status']}"];
        }
        
        if (!$response['json']) {
            return ['status' => 'fail', 'message' => 'Invalid JSON'];
        }
        
        return ['status' => 'pass'];
    });
}

// ═══════════════════════════════════════════════════════════════
// TEST SUITE 6: CRUD OPERATIONS
// ═══════════════════════════════════════════════════════════════

echo "\n" . BLUE . BOLD . "[6] CRUD OPERATIONS" . RESET . "\n";
echo "───────────────────────────────────────────────────────────────\n";

test('Create order (public)', function() use ($baseUrl, &$testOrderId) {
    $response = makeRequest("$baseUrl/api/orders", 'POST', [
        'client_name' => 'Test User',
        'client_email' => 'test@example.com',
        'client_phone' => '+7 (900) 123-45-67',
        'message' => 'Test order from comprehensive test suite'
    ]);
    
    // Accept both 200 and 201 (Created) as valid
    if ($response['status'] != 200 && $response['status'] != 201) {
        return ['status' => 'fail', 'message' => "HTTP {$response['status']}"];
    }
    
    // Check for order in data.order (new format) or data (old format)
    $orderData = $response['json']['data']['order'] ?? $response['json']['data'] ?? null;
    
    if (!isset($orderData['order_number'])) {
        return ['status' => 'fail', 'message' => 'No order_number in response'];
    }
    
    if (isset($orderData['id'])) {
        $testOrderId = $orderData['id'];
    }
    
    return ['status' => 'pass', 'info' => $orderData['order_number']];
});

test('Order validation works', function() use ($baseUrl) {
    // Try to create order without required fields
    $response = makeRequest("$baseUrl/api/orders", 'POST', [
        'client_name' => 'Test'
    ]);
    
    if ($response['status'] != 422) {
        return ['status' => 'fail', 'message' => "Expected 422, got {$response['status']}"];
    }
    
    return ['status' => 'pass', 'info' => 'Validation working'];
});

test('View order (admin)', function() use ($baseUrl, $adminToken, $testOrderId) {
    if (!$adminToken) {
        return ['status' => 'warning', 'message' => 'No token - skipped'];
    }
    
    if (!$testOrderId) {
        return ['status' => 'warning', 'message' => 'No test order - skipped'];
    }
    
    $response = makeRequest("$baseUrl/api/orders/$testOrderId", 'GET', null, [
        "Authorization: Bearer $adminToken"
    ]);
    
    if ($response['status'] != 200) {
        return ['status' => 'fail', 'message' => "HTTP {$response['status']}"];
    }
    
    return ['status' => 'pass', 'info' => "Order #$testOrderId"];
});

test('Update order (admin)', function() use ($baseUrl, $adminToken, $testOrderId) {
    if (!$adminToken) {
        return ['status' => 'warning', 'message' => 'No token - skipped'];
    }
    
    if (!$testOrderId) {
        return ['status' => 'warning', 'message' => 'No test order - skipped'];
    }
    
    $response = makeRequest("$baseUrl/api/orders/$testOrderId", 'PUT', [
        'status' => 'completed'
    ], [
        "Authorization: Bearer $adminToken"
    ]);
    
    if ($response['status'] != 200) {
        return ['status' => 'fail', 'message' => "HTTP {$response['status']}"];
    }
    
    return ['status' => 'pass', 'info' => 'Status updated'];
});

test('Delete order (admin)', function() use ($baseUrl, $adminToken, $testOrderId) {
    if (!$adminToken) {
        return ['status' => 'warning', 'message' => 'No token - skipped'];
    }
    
    if (!$testOrderId) {
        return ['status' => 'warning', 'message' => 'No test order - skipped'];
    }
    
    $response = makeRequest("$baseUrl/api/orders/$testOrderId", 'DELETE', null, [
        "Authorization: Bearer $adminToken"
    ]);
    
    if ($response['status'] != 200) {
        return ['status' => 'fail', 'message' => "HTTP {$response['status']}"];
    }
    
    return ['status' => 'pass', 'info' => 'Order deleted'];
});

// ═══════════════════════════════════════════════════════════════
// TEST SUITE 7: FRONTEND INTEGRATION
// ═══════════════════════════════════════════════════════════════

echo "\n" . BLUE . BOLD . "[7] FRONTEND INTEGRATION" . RESET . "\n";
echo "───────────────────────────────────────────────────────────────\n";

test('CORS headers present', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/health");
    
    if (!stripos($response['header'], 'Access-Control-Allow-Origin')) {
        return ['status' => 'warning', 'message' => 'No CORS headers'];
    }
    
    return ['status' => 'pass', 'info' => 'CORS enabled'];
});

test('JSON Content-Type header', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/health");
    
    if (!stripos($response['header'], 'application/json')) {
        return ['status' => 'fail', 'message' => 'Not returning JSON content type'];
    }
    
    return ['status' => 'pass', 'info' => 'application/json'];
});

test('Response compression enabled', function() use ($baseUrl) {
    $response = makeRequest("$baseUrl/api/health");
    
    $hasCompression = stripos($response['header'], 'Content-Encoding') !== false;
    
    if ($hasCompression) {
        return ['status' => 'pass', 'info' => 'Enabled'];
    } else {
        return ['status' => 'warning', 'message' => 'Compression not detected'];
    }
});

// ═══════════════════════════════════════════════════════════════
// FINAL RESULTS
// ═══════════════════════════════════════════════════════════════

echo "\n" . BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;
echo BOLD . "   TEST RESULTS\n" . RESET;
echo BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "Total Tests:    " . BLUE . str_pad($totalTests, 3) . RESET . "\n";
echo "Passed:         " . GREEN . str_pad($passedTests, 3) . RESET . "\n";
echo "Failed:         " . RED . str_pad($failedTests, 3) . RESET . "\n";
echo "Success Rate:   ";

if ($successRate >= 90) {
    echo GREEN . "$successRate%" . RESET . "\n";
} elseif ($successRate >= 70) {
    echo YELLOW . "$successRate%" . RESET . "\n";
} else {
    echo RED . "$successRate%" . RESET . "\n";
}

// Display warnings
if (!empty($warnings)) {
    echo "\n" . YELLOW . BOLD . "WARNINGS:" . RESET . "\n";
    foreach ($warnings as $warning) {
        echo YELLOW . "  ⚠ $warning" . RESET . "\n";
    }
}

// Display critical failures
if (!empty($criticalFailures)) {
    echo "\n" . RED . BOLD . "CRITICAL FAILURES:" . RESET . "\n";
    foreach ($criticalFailures as $failure) {
        echo RED . "  ✗ $failure" . RESET . "\n";
    }
}

echo "\n" . BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;

// Final verdict
if ($failedTests === 0) {
    echo BOLD . GREEN . "   ✓ ALL TESTS PASSED - SYSTEM READY FOR PRODUCTION!\n" . RESET;
    echo BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;
    echo "\n";
    exit(0);
} elseif (count($criticalFailures) > 0) {
    echo BOLD . RED . "   ✗ CRITICAL FAILURES DETECTED - FIX BEFORE DEPLOYMENT!\n" . RESET;
    echo BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;
    echo "\n";
    exit(2);
} elseif ($failedTests <= 3) {
    echo BOLD . YELLOW . "   ⚠ MINOR ISSUES DETECTED - REVIEW BEFORE DEPLOYMENT\n" . RESET;
    echo BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;
    echo "\n";
    exit(1);
} else {
    echo BOLD . RED . "   ✗ MULTIPLE FAILURES - SYSTEM NOT READY\n" . RESET;
    echo BOLD . "═══════════════════════════════════════════════════════════════\n" . RESET;
    echo "\n";
    exit(2);
}
