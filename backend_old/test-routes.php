<?php
/**
 * 3D Print Pro - API Routes Test
 * 
 * This script tests all major API endpoints
 * Run this after test-db.php passes
 * 
 * Usage: php test-routes.php
 * Or via browser: http://yourdomain.com/backend/test-routes.php
 */

declare(strict_types=1);

// Start output buffering
ob_start();

$results = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

function addTest(string $name, string $method, string $endpoint, bool $passed, int $statusCode, $response, string $message = ''): void
{
    global $results;
    
    $results['tests'][] = [
        'name' => $name,
        'method' => $method,
        'endpoint' => $endpoint,
        'passed' => $passed,
        'status_code' => $statusCode,
        'message' => $message,
        'response_preview' => is_array($response) ? (isset($response['message']) ? $response['message'] : 'OK') : substr((string)$response, 0, 100)
    ];
    
    if (!$passed) {
        $results['success'] = false;
    }
}

// Determine base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl = $protocol . '://' . $host . rtrim($scriptPath, '/') . '/public';

// Override if provided via GET
if (isset($_GET['base_url'])) {
    $baseUrl = rtrim($_GET['base_url'], '/');
}

$results['base_url'] = $baseUrl;

// Helper function to make HTTP request
function makeRequest(string $method, string $url, array $data = null, array $headers = []): array
{
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Set method
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    // Set headers
    $defaultHeaders = ['Content-Type: application/json', 'Accept: application/json'];
    $allHeaders = array_merge($defaultHeaders, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    
    // Set body
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'status_code' => 0,
            'body' => null,
            'error' => $error
        ];
    }
    
    $body = substr($response, $headerSize);
    $decodedBody = json_decode($body, true);
    
    return [
        'success' => true,
        'status_code' => $httpCode,
        'body' => $decodedBody ?? $body,
        'error' => null
    ];
}

// Test 1: API Root
$response = makeRequest('GET', $baseUrl . '/api');
addTest(
    'API Root',
    'GET',
    '/api',
    $response['success'] && $response['status_code'] === 200,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'API root endpoint'
);

// Test 2: Health Check
$response = makeRequest('GET', $baseUrl . '/api/health');
addTest(
    'Health Check',
    'GET',
    '/api/health',
    $response['success'] && in_array($response['status_code'], [200, 503]) && isset($response['body']['status']),
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Health check endpoint'
);

// Test 3: Public Services
$response = makeRequest('GET', $baseUrl . '/api/services');
addTest(
    'Public Services',
    'GET',
    '/api/services',
    $response['success'] && $response['status_code'] === 200,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Get all services'
);

// Test 4: Public Portfolio
$response = makeRequest('GET', $baseUrl . '/api/portfolio');
addTest(
    'Public Portfolio',
    'GET',
    '/api/portfolio',
    $response['success'] && $response['status_code'] === 200,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Get all portfolio items'
);

// Test 5: Public Testimonials
$response = makeRequest('GET', $baseUrl . '/api/testimonials');
addTest(
    'Public Testimonials',
    'GET',
    '/api/testimonials',
    $response['success'] && $response['status_code'] === 200,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Get all testimonials'
);

// Test 6: Public FAQ
$response = makeRequest('GET', $baseUrl . '/api/faq');
addTest(
    'Public FAQ',
    'GET',
    '/api/faq',
    $response['success'] && $response['status_code'] === 200,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Get all FAQ'
);

// Test 7: Public Content
$response = makeRequest('GET', $baseUrl . '/api/content');
addTest(
    'Public Content',
    'GET',
    '/api/content',
    $response['success'] && $response['status_code'] === 200,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Get all content'
);

// Test 8: Public Stats
$response = makeRequest('GET', $baseUrl . '/api/stats');
addTest(
    'Public Stats',
    'GET',
    '/api/stats',
    $response['success'] && $response['status_code'] === 200,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Get site stats'
);

// Test 9: Public Settings
$response = makeRequest('GET', $baseUrl . '/api/settings/public');
addTest(
    'Public Settings',
    'GET',
    '/api/settings/public',
    $response['success'] && $response['status_code'] === 200,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Get public settings'
);

// Test 10: Auth - Invalid Credentials
$response = makeRequest('POST', $baseUrl . '/api/auth/login', [
    'login' => 'invalid',
    'password' => 'invalid'
]);
addTest(
    'Auth - Invalid Credentials',
    'POST',
    '/api/auth/login',
    $response['success'] && $response['status_code'] === 401,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Should reject invalid credentials'
);

// Test 11: Auth - Missing Fields
$response = makeRequest('POST', $baseUrl . '/api/auth/login', []);
addTest(
    'Auth - Missing Fields',
    'POST',
    '/api/auth/login',
    $response['success'] && $response['status_code'] === 400,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Should require login and password'
);

// Test 12: Protected Route - No Token
$response = makeRequest('GET', $baseUrl . '/api/auth/me');
addTest(
    'Protected Route - No Token',
    'GET',
    '/api/auth/me',
    $response['success'] && $response['status_code'] === 401,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Should reject request without token'
);

// Test 13: Admin Route - No Token
$response = makeRequest('GET', $baseUrl . '/api/admin/services');
addTest(
    'Admin Route - No Token',
    'GET',
    '/api/admin/services',
    $response['success'] && $response['status_code'] === 401,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Should reject admin request without token'
);

// Test 14: 404 Not Found
$response = makeRequest('GET', $baseUrl . '/api/nonexistent');
addTest(
    '404 Handler',
    'GET',
    '/api/nonexistent',
    $response['success'] && $response['status_code'] === 404,
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Should return 404 for nonexistent routes'
);

// Test 15: CORS Preflight
$response = makeRequest('OPTIONS', $baseUrl . '/api/services', null, ['Origin: http://localhost:8000']);
addTest(
    'CORS Preflight',
    'OPTIONS',
    '/api/services',
    $response['success'] && in_array($response['status_code'], [200, 204]),
    $response['status_code'],
    $response['body'],
    $response['error'] ?? 'Should handle OPTIONS requests'
);

$output = ob_get_clean();

// Determine format
$format = $_GET['format'] ?? 'html';
$isCli = php_sapi_name() === 'cli';

if ($isCli || $format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit($results['success'] ? 0 : 1);
}

// HTML Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Print Pro - Routes Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: <?php echo $results['success'] ? '#10b981' : '#ef4444'; ?>; color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .test { border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; margin-bottom: 12px; }
        .test-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .test-left { display: flex; align-items: center; flex: 1; }
        .test-icon { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-size: 12px; flex-shrink: 0; }
        .test-icon.passed { background: #10b981; color: white; }
        .test-icon.failed { background: #ef4444; color: white; }
        .test-name { font-weight: 600; font-size: 14px; }
        .test-method { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-left: 8px; }
        .method-get { background: #3b82f6; color: white; }
        .method-post { background: #10b981; color: white; }
        .method-options { background: #6b7280; color: white; }
        .test-status { padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
        .status-2xx { background: #d1fae5; color: #065f46; }
        .status-4xx { background: #fed7aa; color: #92400e; }
        .status-5xx { background: #fecaca; color: #991b1b; }
        .test-endpoint { color: #6b7280; font-size: 13px; font-family: 'Courier New', monospace; }
        .test-message { color: #6b7280; font-size: 12px; margin-top: 4px; }
        .summary { background: #f9fafb; padding: 20px; border-radius: 6px; margin-bottom: 20px; }
        .summary-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .summary-item:last-child { border-bottom: none; }
        .summary-label { font-weight: 500; }
        .summary-value { color: #6b7280; font-family: 'Courier New', monospace; font-size: 13px; }
        .actions { text-align: center; padding-top: 20px; }
        .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 5px; }
        .button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $results['success'] ? '✓ Routes Test Passed' : '✗ Routes Test Issues'; ?></h1>
            <p>3D Print Pro API Endpoints Test</p>
        </div>
        
        <div class="content">
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">Base URL:</span>
                    <span class="summary-value"><?php echo htmlspecialchars($results['base_url']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Test Time:</span>
                    <span class="summary-value"><?php echo $results['timestamp']; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Tests:</span>
                    <span class="summary-value"><?php echo count($results['tests']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Passed:</span>
                    <span class="summary-value"><?php echo count(array_filter($results['tests'], fn($t) => $t['passed'])); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Failed:</span>
                    <span class="summary-value"><?php echo count(array_filter($results['tests'], fn($t) => !$t['passed'])); ?></span>
                </div>
            </div>
            
            <?php foreach ($results['tests'] as $test): ?>
            <div class="test">
                <div class="test-header">
                    <div class="test-left">
                        <div class="test-icon <?php echo $test['passed'] ? 'passed' : 'failed'; ?>">
                            <?php echo $test['passed'] ? '✓' : '✗'; ?>
                        </div>
                        <div>
                            <div>
                                <span class="test-name"><?php echo htmlspecialchars($test['name']); ?></span>
                                <span class="test-method method-<?php echo strtolower($test['method']); ?>"><?php echo $test['method']; ?></span>
                            </div>
                            <div class="test-endpoint"><?php echo htmlspecialchars($test['endpoint']); ?></div>
                        </div>
                    </div>
                    <div>
                        <?php
                        $statusClass = 'status-5xx';
                        if ($test['status_code'] >= 200 && $test['status_code'] < 300) $statusClass = 'status-2xx';
                        elseif ($test['status_code'] >= 400 && $test['status_code'] < 500) $statusClass = 'status-4xx';
                        ?>
                        <span class="test-status <?php echo $statusClass; ?>"><?php echo $test['status_code']; ?></span>
                    </div>
                </div>
                <?php if ($test['message']): ?>
                <div class="test-message"><?php echo htmlspecialchars($test['message']); ?> - <?php echo htmlspecialchars($test['response_preview']); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <div class="actions">
                <a href="test-db.php" class="button">← Previous: Database Test</a>
                <a href="?" class="button">Refresh Test</a>
                <?php if ($results['success']): ?>
                <a href="../index.html" class="button">Go to Frontend →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
