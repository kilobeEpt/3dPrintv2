<?php
/**
 * 3D Print Pro - Setup Test Script
 * 
 * This script tests the basic server configuration and requirements
 * Run this first to ensure your environment is properly configured
 * 
 * Usage: php test-setup.php
 * Or via browser: http://yourdomain.com/backend/test-setup.php
 */

declare(strict_types=1);

// Start output buffering for clean JSON response
ob_start();

$results = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Function to add check result
function addCheck(string $name, bool $passed, string $message, $details = null): void
{
    global $results;
    
    $results['checks'][] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'details' => $details
    ];
    
    if (!$passed) {
        $results['success'] = false;
    }
}

// Check 1: PHP Version
$phpVersion = PHP_VERSION;
$phpVersionOk = version_compare($phpVersion, '7.4.0', '>=');
addCheck(
    'PHP Version',
    $phpVersionOk,
    $phpVersionOk ? "PHP $phpVersion is compatible" : "PHP $phpVersion is too old (7.4+ required)",
    $phpVersion
);

// Check 2: Required Extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

addCheck(
    'PHP Extensions',
    empty($missingExtensions),
    empty($missingExtensions) ? 'All required extensions are loaded' : 'Missing extensions: ' . implode(', ', $missingExtensions),
    [
        'required' => $requiredExtensions,
        'missing' => $missingExtensions,
        'loaded' => array_diff($requiredExtensions, $missingExtensions)
    ]
);

// Check 3: Composer Autoloader
$autoloadPath = __DIR__ . '/vendor/autoload.php';
$autoloadExists = file_exists($autoloadPath);
addCheck(
    'Composer Autoloader',
    $autoloadExists,
    $autoloadExists ? 'Composer autoloader found' : 'Composer autoloader not found - run "composer install"',
    ['path' => $autoloadPath, 'exists' => $autoloadExists]
);

// Check 4: Environment File
$envPath = __DIR__ . '/.env';
$envExists = file_exists($envPath);
addCheck(
    'Environment File',
    $envExists,
    $envExists ? '.env file found' : '.env file not found - copy .env.example to .env',
    ['path' => $envPath, 'exists' => $envExists]
);

// Check 5: Directory Permissions
$directories = [
    'storage/logs' => __DIR__ . '/storage/logs',
    'storage/cache' => __DIR__ . '/storage/cache'
];

$permissionErrors = [];
foreach ($directories as $name => $path) {
    if (!is_dir($path)) {
        if (!@mkdir($path, 0755, true)) {
            $permissionErrors[] = "$name (not created)";
        }
    }
    
    if (is_dir($path) && !is_writable($path)) {
        $permissionErrors[] = "$name (not writable)";
    }
}

addCheck(
    'Directory Permissions',
    empty($permissionErrors),
    empty($permissionErrors) ? 'All directories are writable' : 'Permission issues: ' . implode(', ', $permissionErrors),
    [
        'checked' => array_keys($directories),
        'errors' => $permissionErrors
    ]
);

// Check 6: .htaccess File (Apache only)
$htaccessPath = __DIR__ . '/public/.htaccess';
$htaccessExists = file_exists($htaccessPath);
$webServer = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';
$isApache = stripos($webServer, 'apache') !== false;

addCheck(
    '.htaccess File',
    !$isApache || $htaccessExists,
    $isApache 
        ? ($htaccessExists ? '.htaccess found for Apache' : '.htaccess missing for Apache') 
        : 'Not using Apache (nginx or other)',
    [
        'server' => $webServer,
        'isApache' => $isApache,
        'exists' => $htaccessExists
    ]
);

// Check 7: Try loading environment variables
if ($envExists && $autoloadExists) {
    try {
        require $autoloadPath;
        
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        
        $envVars = [
            'APP_ENV' => $_ENV['APP_ENV'] ?? 'not set',
            'DB_HOST' => $_ENV['DB_HOST'] ?? 'not set',
            'DB_DATABASE' => $_ENV['DB_DATABASE'] ?? 'not set',
            'JWT_SECRET' => isset($_ENV['JWT_SECRET']) && $_ENV['JWT_SECRET'] !== 'change_this_secret' ? 'configured' : 'NOT CONFIGURED',
        ];
        
        $envConfigured = $_ENV['JWT_SECRET'] ?? '' !== 'change_this_secret';
        
        addCheck(
            'Environment Configuration',
            $envConfigured,
            $envConfigured ? 'Environment variables loaded' : 'JWT_SECRET needs to be changed',
            $envVars
        );
    } catch (Exception $e) {
        addCheck(
            'Environment Configuration',
            false,
            'Error loading environment: ' . $e->getMessage(),
            null
        );
    }
}

// Check 8: URL Rewriting Test
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
addCheck(
    'Script Access',
    true,
    'Script is accessible',
    ['url' => $currentUrl, 'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown']
);

// Output results
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
    <title>3D Print Pro - Setup Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: <?php echo $results['success'] ? '#10b981' : '#ef4444'; ?>; color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .check { border: 1px solid #e5e7eb; border-radius: 6px; padding: 20px; margin-bottom: 15px; }
        .check-header { display: flex; align-items: center; margin-bottom: 10px; }
        .check-icon { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-weight: bold; flex-shrink: 0; }
        .check-icon.passed { background: #10b981; color: white; }
        .check-icon.failed { background: #ef4444; color: white; }
        .check-name { font-weight: 600; font-size: 16px; }
        .check-message { color: #6b7280; margin-bottom: 10px; }
        .check-details { background: #f9fafb; padding: 12px; border-radius: 4px; font-size: 13px; font-family: 'Courier New', monospace; white-space: pre-wrap; word-break: break-word; }
        .summary { background: #f9fafb; padding: 20px; border-radius: 6px; margin-bottom: 20px; }
        .summary-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .summary-item:last-child { border-bottom: none; }
        .summary-label { font-weight: 500; }
        .summary-value { color: #6b7280; }
        .actions { text-align: center; padding-top: 20px; }
        .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; }
        .button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $results['success'] ? '✓ Setup Check Passed' : '✗ Setup Issues Found'; ?></h1>
            <p>3D Print Pro Backend Configuration Test</p>
        </div>
        
        <div class="content">
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">Test Time:</span>
                    <span class="summary-value"><?php echo $results['timestamp']; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Checks:</span>
                    <span class="summary-value"><?php echo count($results['checks']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Passed:</span>
                    <span class="summary-value"><?php echo count(array_filter($results['checks'], fn($c) => $c['passed'])); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Failed:</span>
                    <span class="summary-value"><?php echo count(array_filter($results['checks'], fn($c) => !$c['passed'])); ?></span>
                </div>
            </div>
            
            <?php foreach ($results['checks'] as $check): ?>
            <div class="check">
                <div class="check-header">
                    <div class="check-icon <?php echo $check['passed'] ? 'passed' : 'failed'; ?>">
                        <?php echo $check['passed'] ? '✓' : '✗'; ?>
                    </div>
                    <div class="check-name"><?php echo htmlspecialchars($check['name']); ?></div>
                </div>
                <div class="check-message"><?php echo htmlspecialchars($check['message']); ?></div>
                <?php if ($check['details']): ?>
                <div class="check-details"><?php echo htmlspecialchars(json_encode($check['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <div class="actions">
                <?php if ($results['success']): ?>
                <a href="test-db.php" class="button">Next: Test Database Connection</a>
                <?php else: ?>
                <a href="?" class="button">Refresh Test</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
