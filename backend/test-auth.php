#!/usr/bin/env php
<?php
/**
 * Test Authentication Script - STANDALONE MODE
 * 
 * Tests the authentication system end-to-end
 * NO COMPOSER REQUIRED - uses standalone components
 * 
 * Usage: 
 *   php test-auth.php
 *   php test-auth.php admin admin123
 *   php test-auth.php http://localhost:8080 admin admin123
 */

declare(strict_types=1);

// Get parameters
$apiUrl = 'http://localhost:8080';
$login = 'admin';
$password = 'admin123';

// Parse command line arguments
if (isset($argv[1])) {
    if (strpos($argv[1], 'http') === 0) {
        $apiUrl = rtrim($argv[1], '/');
        $login = $argv[2] ?? 'admin';
        $password = $argv[3] ?? 'admin123';
    } else {
        $login = $argv[1];
        $password = $argv[2] ?? 'admin123';
    }
}

echo "\n";
echo "==============================================\n";
echo "   3D Print Pro - Authentication Test\n";
echo "==============================================\n";
echo "\n";
echo "API URL:  {$apiUrl}\n";
echo "Login:    {$login}\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n";
echo "\n";

$results = [
    'success' => true,
    'tests' => []
];

function testAuth(string $name, callable $test): void
{
    global $results;
    
    echo "Testing: {$name}... ";
    
    try {
        $result = $test();
        
        if ($result['success']) {
            echo "✅ PASSED\n";
            $results['tests'][] = [
                'name' => $name,
                'passed' => true,
                'details' => $result['details'] ?? null
            ];
        } else {
            echo "❌ FAILED\n";
            echo "   Error: {$result['message']}\n";
            $results['tests'][] = [
                'name' => $name,
                'passed' => false,
                'message' => $result['message'],
                'details' => $result['details'] ?? null
            ];
            $results['success'] = false;
        }
    } catch (Exception $e) {
        echo "❌ EXCEPTION\n";
        echo "   Error: {$e->getMessage()}\n";
        $results['tests'][] = [
            'name' => $name,
            'passed' => false,
            'message' => $e->getMessage()
        ];
        $results['success'] = false;
    }
}

// Test 1: API Health Check
testAuth('API Health Check', function() use ($apiUrl) {
    $ch = curl_init("{$apiUrl}/api/health");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => "API returned HTTP {$httpCode}",
            'details' => $response
        ];
    }
    
    return ['success' => true, 'details' => 'API is responding'];
});

// Test 2: Database Connection
testAuth('Database Connection', function() {
    require_once __DIR__ . '/standalone/autoload.php';
    require_once __DIR__ . '/standalone/SimpleEnv.php';
    
    SimpleEnv::load(__DIR__ . '/.env');
    
    \App\Config\Database::init([
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'ch167436_3dprint',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
    ]);
    
    $test = \App\Config\Database::testConnection();
    
    if (!$test['connected']) {
        return [
            'success' => false,
            'message' => $test['message'] ?? 'Database connection failed'
        ];
    }
    
    return ['success' => true, 'details' => 'Database connected'];
});

// Test 3: Admin User Exists
testAuth('Admin User Exists', function() use ($login) {
    $db = \App\Config\Database::getConnection();
    $stmt = $db->prepare('SELECT id, login, name, email, role, active FROM users WHERE login = ?');
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return [
            'success' => false,
            'message' => "User '{$login}' not found in database"
        ];
    }
    
    if (!$user['active']) {
        return [
            'success' => false,
            'message' => "User '{$login}' is not active"
        ];
    }
    
    if ($user['role'] !== 'admin') {
        return [
            'success' => false,
            'message' => "User '{$login}' is not an admin (role: {$user['role']})"
        ];
    }
    
    return [
        'success' => true,
        'details' => "User ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}"
    ];
});

// Test 4: Password Verification (Direct)
testAuth('Password Verification (Direct)', function() use ($login, $password) {
    $db = \App\Config\Database::getConnection();
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE login = ?');
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return [
            'success' => false,
            'message' => "User not found"
        ];
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        return [
            'success' => false,
            'message' => "Password does not match hash in database"
        ];
    }
    
    return ['success' => true, 'details' => 'Password verification passed'];
});

// Test 5: Login API Endpoint
testAuth('Login API Endpoint', function() use ($apiUrl, $login, $password) {
    $ch = curl_init("{$apiUrl}/api/auth/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'login' => $login,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => "Login failed with HTTP {$httpCode}",
            'details' => $response
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['success']) || !$data['success']) {
        return [
            'success' => false,
            'message' => $data['message'] ?? 'Login failed',
            'details' => $response
        ];
    }
    
    if (!isset($data['data']['token'])) {
        return [
            'success' => false,
            'message' => 'Token not returned in response',
            'details' => $response
        ];
    }
    
    // Store token for next test
    global $authToken;
    $authToken = $data['data']['token'];
    
    return [
        'success' => true,
        'details' => 'JWT token received: ' . substr($authToken, 0, 20) . '...'
    ];
});

// Test 6: Authenticated Request
testAuth('Authenticated Request (GET /api/auth/me)', function() use ($apiUrl) {
    global $authToken;
    
    if (!isset($authToken)) {
        return [
            'success' => false,
            'message' => 'No token available (previous test failed)'
        ];
    }
    
    $ch = curl_init("{$apiUrl}/api/auth/me");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $authToken
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => "Authenticated request failed with HTTP {$httpCode}",
            'details' => $response
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['success']) || !$data['success']) {
        return [
            'success' => false,
            'message' => $data['message'] ?? 'Request failed',
            'details' => $response
        ];
    }
    
    return [
        'success' => true,
        'details' => "User: {$data['data']['name']} ({$data['data']['role']})"
    ];
});

// Test 7: Invalid Credentials
testAuth('Invalid Credentials Handling', function() use ($apiUrl, $login) {
    $ch = curl_init("{$apiUrl}/api/auth/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'login' => $login,
        'password' => 'wrongpassword123'
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 401) {
        return [
            'success' => false,
            'message' => "Expected HTTP 401, got {$httpCode}",
            'details' => $response
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['success']) || $data['success'] !== false) {
        return [
            'success' => false,
            'message' => 'Invalid credentials should return success=false',
            'details' => $response
        ];
    }
    
    return ['success' => true, 'details' => 'Invalid credentials correctly rejected'];
});

echo "\n";
echo "==============================================\n";
echo "   Test Results\n";
echo "==============================================\n";
echo "\n";

$passed = count(array_filter($results['tests'], fn($t) => $t['passed']));
$failed = count($results['tests']) - $passed;
$total = count($results['tests']);

echo "Total Tests:  {$total}\n";
echo "Passed:       {$passed} ✅\n";
echo "Failed:       {$failed} " . ($failed > 0 ? "❌" : "") . "\n";
echo "\n";

if ($results['success']) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "\n";
    echo "✅ Authentication is working correctly\n";
    echo "✅ You can now login to the admin panel at: {$apiUrl}/admin.html\n";
    echo "\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED\n";
    echo "\n";
    echo "Failed tests:\n";
    foreach ($results['tests'] as $test) {
        if (!$test['passed']) {
            echo "  • {$test['name']}: {$test['message']}\n";
        }
    }
    echo "\n";
    echo "Please fix the issues above and run this test again.\n";
    echo "\n";
    exit(1);
}
