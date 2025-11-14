<?php
/**
 * 3D Print Pro - Database Connection Test
 * 
 * This script tests the database connection and verifies schema
 * Run this after test-setup.php passes
 * 
 * Usage: php test-db.php
 * Or via browser: http://yourdomain.com/backend/test-db.php
 */

declare(strict_types=1);

// Start output buffering
ob_start();

$results = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

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

// Check if composer autoloader exists
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    addCheck('Prerequisites', false, 'Composer autoloader not found. Run composer install first.', null);
    $results['success'] = false;
    goto output;
}

require $autoloadPath;

// Load environment
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    addCheck('Prerequisites', false, '.env file not found. Copy .env.example to .env', null);
    $results['success'] = false;
    goto output;
}

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    addCheck('Prerequisites', true, 'Environment loaded successfully', null);
} catch (Exception $e) {
    addCheck('Prerequisites', false, 'Failed to load environment: ' . $e->getMessage(), null);
    $results['success'] = false;
    goto output;
}

// Database configuration
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? '',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
];

// Check 1: Database Configuration
$configValid = !empty($dbConfig['host']) && !empty($dbConfig['database']) && !empty($dbConfig['username']);
addCheck(
    'Database Configuration',
    $configValid,
    $configValid ? 'Database configuration loaded from .env' : 'Incomplete database configuration',
    [
        'host' => $dbConfig['host'],
        'port' => $dbConfig['port'],
        'database' => $dbConfig['database'],
        'username' => $dbConfig['username'],
        'password' => str_repeat('*', min(8, strlen($dbConfig['password'])))
    ]
);

if (!$configValid) {
    $results['success'] = false;
    goto output;
}

// Check 2: PDO Connection
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $stmt = $pdo->query('SELECT VERSION() as version');
    $version = $stmt->fetch()['version'];
    
    addCheck(
        'Database Connection',
        true,
        'Successfully connected to MySQL server',
        ['version' => $version]
    );
} catch (PDOException $e) {
    addCheck(
        'Database Connection',
        false,
        'Failed to connect to database: ' . $e->getMessage(),
        ['error_code' => $e->getCode()]
    );
    $results['success'] = false;
    goto output;
}

// Check 3: Database Exists
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$dbConfig['database']}'");
    $dbExists = $stmt->rowCount() > 0;
    
    addCheck(
        'Database Exists',
        $dbExists,
        $dbExists 
            ? "Database '{$dbConfig['database']}' exists" 
            : "Database '{$dbConfig['database']}' not found - needs to be created",
        null
    );
    
    if (!$dbExists) {
        $results['success'] = false;
        goto output;
    }
    
    // Select the database
    $pdo->exec("USE `{$dbConfig['database']}`");
    
} catch (PDOException $e) {
    addCheck(
        'Database Exists',
        false,
        'Error checking database: ' . $e->getMessage(),
        null
    );
    $results['success'] = false;
    goto output;
}

// Check 4: Required Tables
$requiredTables = [
    'users',
    'services',
    'service_features',
    'portfolio',
    'testimonials',
    'faq',
    'orders',
    'materials',
    'additional_services',
    'quality_levels',
    'volume_discounts',
    'site_settings',
    'site_content',
    'site_stats',
    'integrations',
    'form_fields',
    'audit_logs'
];

try {
    $stmt = $pdo->query('SHOW TABLES');
    $existingTables = array_column($stmt->fetchAll(), "Tables_in_{$dbConfig['database']}");
    
    $missingTables = array_diff($requiredTables, $existingTables);
    
    addCheck(
        'Database Tables',
        empty($missingTables),
        empty($missingTables) 
            ? 'All required tables exist (' . count($existingTables) . ' tables)' 
            : 'Missing tables: ' . implode(', ', $missingTables),
        [
            'required' => count($requiredTables),
            'found' => count($existingTables),
            'missing' => array_values($missingTables),
            'existing' => $existingTables
        ]
    );
    
    if (!empty($missingTables)) {
        $results['success'] = false;
    }
    
} catch (PDOException $e) {
    addCheck(
        'Database Tables',
        false,
        'Error checking tables: ' . $e->getMessage(),
        null
    );
    $results['success'] = false;
}

// Check 5: Admin User Exists
if (in_array('users', $existingTables ?? [])) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND active = TRUE");
        $adminCount = (int) $stmt->fetch()['count'];
        
        addCheck(
            'Admin User',
            $adminCount > 0,
            $adminCount > 0 
                ? "Found {$adminCount} active admin user(s)" 
                : 'No admin users found - run database seed script',
            ['admin_count' => $adminCount]
        );
        
        if ($adminCount === 0) {
            $results['success'] = false;
        }
        
    } catch (PDOException $e) {
        addCheck(
            'Admin User',
            false,
            'Error checking admin users: ' . $e->getMessage(),
            null
        );
    }
}

// Check 6: Test Full Connection with App\Config\Database
try {
    App\Config\Database::init($dbConfig);
    $testResult = App\Config\Database::testConnection();
    
    addCheck(
        'Application Database Class',
        $testResult['connected'],
        $testResult['message'],
        $testResult
    );
    
    if (!$testResult['connected']) {
        $results['success'] = false;
    }
    
} catch (Throwable $e) {
    addCheck(
        'Application Database Class',
        false,
        'Error testing Database class: ' . $e->getMessage(),
        [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    );
    $results['success'] = false;
}

// Check 7: Sample Data
if (in_array('services', $existingTables ?? [])) {
    try {
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM services');
        $servicesCount = (int) $stmt->fetch()['count'];
        
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM site_settings');
        $settingsCount = (int) $stmt->fetch()['count'];
        
        addCheck(
            'Sample Data',
            $servicesCount > 0 && $settingsCount > 0,
            "Services: {$servicesCount}, Settings: {$settingsCount}",
            [
                'services' => $servicesCount,
                'settings' => $settingsCount,
                'recommendation' => ($servicesCount === 0 || $settingsCount === 0) ? 'Run seed script to populate database' : 'Data looks good'
            ]
        );
        
    } catch (PDOException $e) {
        addCheck(
            'Sample Data',
            false,
            'Error checking sample data: ' . $e->getMessage(),
            null
        );
    }
}

output:
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
    <title>3D Print Pro - Database Test</title>
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
        .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 5px; }
        .button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $results['success'] ? '✓ Database Check Passed' : '✗ Database Issues Found'; ?></h1>
            <p>3D Print Pro Database Configuration Test</p>
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
                <a href="test-setup.php" class="button">← Previous: Setup Test</a>
                <a href="?" class="button">Refresh Test</a>
                <?php if ($results['success']): ?>
                <a href="test-routes.php" class="button">Next: Test Routes →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
