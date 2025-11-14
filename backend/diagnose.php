<?php
/**
 * 3D Print Pro - Comprehensive Diagnostic Tool
 * 
 * This script performs a comprehensive diagnostic of your backend installation
 * and provides actionable recommendations for fixing issues.
 * 
 * Usage: php diagnose.php
 * Or via browser: http://yourdomain.com/backend/diagnose.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start output buffering
ob_start();

$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'overall_status' => 'unknown',
    'categories' => [],
    'recommendations' => []
];

function addCategory(string $name, array $checks): void
{
    global $report;
    $report['categories'][$name] = $checks;
}

function addRecommendation(string $priority, string $message, string $action = ''): void
{
    global $report;
    $report['recommendations'][] = [
        'priority' => $priority,
        'message' => $message,
        'action' => $action
    ];
}

// ============================================
// CATEGORY 1: System Information
// ============================================
$system = [
    'php_version' => PHP_VERSION,
    'php_sapi' => php_sapi_name(),
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? getcwd(),
    'script_filename' => __FILE__,
    'current_user' => get_current_user(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

addCategory('System Information', $system);

// ============================================
// CATEGORY 2: PHP Extensions
// ============================================
$requiredExtensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring'),
    'openssl' => extension_loaded('openssl'),
    'curl' => extension_loaded('curl')
];

$optionalExtensions = [
    'zip' => extension_loaded('zip'),
    'gd' => extension_loaded('gd'),
    'intl' => extension_loaded('intl')
];

addCategory('Required Extensions', $requiredExtensions);
addCategory('Optional Extensions', $optionalExtensions);

foreach ($requiredExtensions as $ext => $loaded) {
    if (!$loaded) {
        addRecommendation('HIGH', "Missing required extension: $ext", "Install PHP extension: $ext");
    }
}

// ============================================
// CATEGORY 3: File System
// ============================================
$paths = [
    'backend_root' => __DIR__,
    'public' => __DIR__ . '/public',
    'src' => __DIR__ . '/src',
    'vendor' => __DIR__ . '/vendor',
    'vendor_autoload' => __DIR__ . '/vendor/autoload.php',
    'env_file' => __DIR__ . '/.env',
    'env_example' => __DIR__ . '/.env.example',
    'public_index' => __DIR__ . '/public/index.php',
    'public_htaccess' => __DIR__ . '/public/.htaccess',
    'storage_logs' => __DIR__ . '/storage/logs',
    'storage_cache' => __DIR__ . '/storage/cache'
];

$fileSystem = [];
foreach ($paths as $name => $path) {
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    $writable = $exists && is_writable($path);
    
    $fileSystem[$name] = [
        'path' => $path,
        'exists' => $exists,
        'readable' => $readable,
        'writable' => $writable,
        'type' => $exists ? (is_dir($path) ? 'directory' : 'file') : 'missing'
    ];
}

addCategory('File System', $fileSystem);

// Check critical paths
if (!$fileSystem['vendor_autoload']['exists']) {
    addRecommendation('CRITICAL', 'Composer autoloader not found', 'Run: composer install --no-dev --optimize-autoloader');
}

if (!$fileSystem['env_file']['exists']) {
    addRecommendation('CRITICAL', '.env file not found', 'Copy .env.example to .env and configure it');
}

if (!$fileSystem['storage_logs']['writable']) {
    addRecommendation('HIGH', 'storage/logs is not writable', 'Run: chmod -R 775 storage/ && chown -R www-data:www-data storage/');
}

// ============================================
// CATEGORY 4: Environment Configuration
// ============================================
$envConfig = ['status' => 'not_loaded'];

if ($fileSystem['vendor_autoload']['exists'] && $fileSystem['env_file']['exists']) {
    try {
        require __DIR__ . '/vendor/autoload.php';
        
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        
        $envConfig = [
            'status' => 'loaded',
            'APP_ENV' => $_ENV['APP_ENV'] ?? 'not_set',
            'APP_DEBUG' => $_ENV['APP_DEBUG'] ?? 'not_set',
            'APP_URL' => $_ENV['APP_URL'] ?? 'not_set',
            'DB_HOST' => $_ENV['DB_HOST'] ?? 'not_set',
            'DB_PORT' => $_ENV['DB_PORT'] ?? 'not_set',
            'DB_DATABASE' => $_ENV['DB_DATABASE'] ?? 'not_set',
            'DB_USERNAME' => $_ENV['DB_USERNAME'] ?? 'not_set',
            'DB_PASSWORD' => isset($_ENV['DB_PASSWORD']) ? (strlen($_ENV['DB_PASSWORD']) > 0 ? 'set' : 'empty') : 'not_set',
            'JWT_SECRET' => $_ENV['JWT_SECRET'] ?? 'not_set',
            'JWT_SECRET_is_default' => ($_ENV['JWT_SECRET'] ?? 'change_this_secret') === 'change_this_secret',
            'CORS_ORIGIN' => $_ENV['CORS_ORIGIN'] ?? 'not_set',
            'TELEGRAM_BOT_TOKEN' => isset($_ENV['TELEGRAM_BOT_TOKEN']) && strlen($_ENV['TELEGRAM_BOT_TOKEN']) > 0 ? 'set' : 'not_set',
            'TELEGRAM_CHAT_ID' => $_ENV['TELEGRAM_CHAT_ID'] ?? 'not_set'
        ];
        
        // Security checks
        if ($envConfig['JWT_SECRET_is_default']) {
            addRecommendation('CRITICAL', 'JWT_SECRET is using default value', 'Generate a secure random key: openssl rand -base64 64');
        }
        
        if ($envConfig['APP_ENV'] === 'production' && $_ENV['APP_DEBUG'] === 'true') {
            addRecommendation('HIGH', 'APP_DEBUG is enabled in production', 'Set APP_DEBUG=false in .env');
        }
        
        if ($envConfig['CORS_ORIGIN'] === '*') {
            addRecommendation('MEDIUM', 'CORS_ORIGIN allows all domains', 'Set CORS_ORIGIN to your frontend domain');
        }
        
    } catch (Exception $e) {
        $envConfig = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
        addRecommendation('HIGH', 'Failed to load .env file', 'Check .env file syntax');
    }
}

addCategory('Environment Configuration', $envConfig);

// ============================================
// CATEGORY 5: Database Connection
// ============================================
$database = ['status' => 'not_tested'];

if ($envConfig['status'] === 'loaded') {
    try {
        $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};charset=utf8mb4";
        $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        $stmt = $pdo->query('SELECT VERSION() as version');
        $version = $stmt->fetch()['version'];
        
        // Check if database exists
        $stmt = $pdo->query("SHOW DATABASES LIKE '{$_ENV['DB_DATABASE']}'");
        $dbExists = $stmt->rowCount() > 0;
        
        $database = [
            'status' => 'connected',
            'mysql_version' => $version,
            'database_exists' => $dbExists,
            'database_name' => $_ENV['DB_DATABASE']
        ];
        
        if ($dbExists) {
            $pdo->exec("USE `{$_ENV['DB_DATABASE']}`");
            
            // Check tables
            $stmt = $pdo->query('SHOW TABLES');
            $tables = array_column($stmt->fetchAll(), "Tables_in_{$_ENV['DB_DATABASE']}");
            $database['tables_count'] = count($tables);
            $database['tables'] = $tables;
            
            // Check admin user
            if (in_array('users', $tables)) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND active = TRUE");
                $adminCount = (int) $stmt->fetch()['count'];
                $database['admin_users'] = $adminCount;
                
                if ($adminCount === 0) {
                    addRecommendation('HIGH', 'No admin users found', 'Run: php database/seeds/seed-admin-user.php');
                }
            } else {
                addRecommendation('CRITICAL', 'Database tables not found', 'Import: backend/database/migrations/20231113_initial.sql');
            }
        } else {
            addRecommendation('CRITICAL', "Database '{$_ENV['DB_DATABASE']}' does not exist", "Create database via cPanel or phpMyAdmin");
        }
        
    } catch (PDOException $e) {
        $database = [
            'status' => 'error',
            'error' => $e->getMessage(),
            'error_code' => $e->getCode()
        ];
        addRecommendation('CRITICAL', 'Database connection failed', 'Check DB credentials in .env: ' . $e->getMessage());
    }
}

addCategory('Database Connection', $database);

// ============================================
// CATEGORY 6: Application Classes
// ============================================
$classes = ['status' => 'not_tested'];

if ($fileSystem['vendor_autoload']['exists']) {
    $requiredClasses = [
        'App\Bootstrap\App',
        'App\Config\Database',
        'App\Helpers\Response',
        'App\Helpers\Validator',
        'App\Helpers\TelegramService',
        'App\Middleware\AuthMiddleware',
        'App\Middleware\CorsMiddleware',
        'App\Middleware\ErrorMiddleware',
        'App\Services\AuthService',
        'App\Controllers\AuthController'
    ];
    
    $classes = ['status' => 'checked', 'loaded' => []];
    
    foreach ($requiredClasses as $class) {
        $classes['loaded'][$class] = class_exists($class);
        
        if (!$classes['loaded'][$class]) {
            addRecommendation('HIGH', "Class not found: $class", 'Run: composer dump-autoload --optimize');
        }
    }
}

addCategory('Application Classes', $classes);

// ============================================
// CATEGORY 7: Web Server Configuration
// ============================================
$webServer = [
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
    'is_apache' => isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false,
    'is_nginx' => isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
    'htaccess_exists' => $fileSystem['public_htaccess']['exists'] ?? false,
    'mod_rewrite' => function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : 'unknown'
];

addCategory('Web Server', $webServer);

if ($webServer['is_apache'] && !$webServer['htaccess_exists']) {
    addRecommendation('HIGH', '.htaccess file missing in public/', 'Ensure public/.htaccess exists for URL rewriting');
}

// ============================================
// CATEGORY 8: URL Routing Test
// ============================================
$routing = ['status' => 'not_tested'];

if ($envConfig['status'] === 'loaded' && $database['status'] === 'connected') {
    try {
        // Try to initialize the app
        App\Config\Database::init([
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset' => 'utf8mb4'
        ]);
        
        $testResult = App\Config\Database::testConnection();
        
        $routing = [
            'status' => 'ready',
            'database_class_works' => $testResult['connected'],
            'message' => 'Application ready to handle requests'
        ];
        
    } catch (Throwable $e) {
        $routing = [
            'status' => 'error',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        addRecommendation('HIGH', 'Application initialization error', $e->getMessage());
    }
}

addCategory('Application Routing', $routing);

// ============================================
// Overall Status
// ============================================
$criticalIssues = count(array_filter($report['recommendations'], fn($r) => $r['priority'] === 'CRITICAL'));
$highIssues = count(array_filter($report['recommendations'], fn($r) => $r['priority'] === 'HIGH'));

if ($criticalIssues > 0) {
    $report['overall_status'] = 'critical';
} elseif ($highIssues > 0) {
    $report['overall_status'] = 'warning';
} elseif (count($report['recommendations']) > 0) {
    $report['overall_status'] = 'notice';
} else {
    $report['overall_status'] = 'healthy';
}

$report['summary'] = [
    'critical_issues' => $criticalIssues,
    'high_issues' => $highIssues,
    'total_recommendations' => count($report['recommendations'])
];

$output = ob_get_clean();

// Output
$format = $_GET['format'] ?? 'html';
$isCli = php_sapi_name() === 'cli';

if ($isCli || $format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit($report['overall_status'] === 'healthy' ? 0 : 1);
}

// HTML Output
$statusColors = [
    'healthy' => '#10b981',
    'notice' => '#3b82f6',
    'warning' => '#f59e0b',
    'critical' => '#ef4444'
];
$statusColor = $statusColors[$report['overall_status']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Print Pro - System Diagnostics</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: <?php echo $statusColor; ?>; color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .summary { background: #f9fafb; padding: 20px; border-radius: 6px; margin-bottom: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .summary-item { text-align: center; padding: 15px; background: white; border-radius: 6px; }
        .summary-value { font-size: 32px; font-weight: 700; color: <?php echo $statusColor; ?>; }
        .summary-label { font-size: 14px; color: #6b7280; margin-top: 5px; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 20px; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; }
        .category { background: #f9fafb; padding: 20px; border-radius: 6px; margin-bottom: 15px; }
        .category-title { font-weight: 600; margin-bottom: 10px; }
        .items { display: grid; gap: 8px; }
        .item { display: flex; justify-content: space-between; padding: 8px; background: white; border-radius: 4px; font-size: 13px; }
        .item-key { color: #6b7280; font-family: 'Courier New', monospace; }
        .item-value { color: #111827; font-family: 'Courier New', monospace; }
        .value-true { color: #10b981; font-weight: 600; }
        .value-false { color: #ef4444; font-weight: 600; }
        .recommendation { border-left: 4px solid; padding: 15px; margin-bottom: 10px; border-radius: 4px; }
        .recommendation.critical { border-color: #ef4444; background: #fef2f2; }
        .recommendation.high { border-color: #f59e0b; background: #fffbeb; }
        .recommendation.medium { border-color: #3b82f6; background: #eff6ff; }
        .rec-priority { font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .rec-message { font-weight: 600; margin-bottom: 5px; }
        .rec-action { font-size: 13px; color: #6b7280; font-family: 'Courier New', monospace; }
        .actions { text-align: center; padding-top: 20px; }
        .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 5px; }
        .button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>System Diagnostics Report</h1>
            <p>3D Print Pro Backend - <?php echo strtoupper($report['overall_status']); ?></p>
        </div>
        
        <div class="content">
            <div class="summary">
                <div class="summary-item">
                    <div class="summary-value"><?php echo $report['summary']['critical_issues']; ?></div>
                    <div class="summary-label">Critical Issues</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo $report['summary']['high_issues']; ?></div>
                    <div class="summary-label">High Priority</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo $report['summary']['total_recommendations']; ?></div>
                    <div class="summary-label">Total Recommendations</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo count($report['categories']); ?></div>
                    <div class="summary-label">Categories Checked</div>
                </div>
            </div>
            
            <?php if (!empty($report['recommendations'])): ?>
            <div class="section">
                <div class="section-title">🔧 Recommendations</div>
                <?php foreach ($report['recommendations'] as $rec): ?>
                <div class="recommendation <?php echo strtolower($rec['priority']); ?>">
                    <div class="rec-priority" style="color: <?php 
                        echo $rec['priority'] === 'CRITICAL' ? '#ef4444' : 
                            ($rec['priority'] === 'HIGH' ? '#f59e0b' : '#3b82f6'); 
                    ?>;">
                        <?php echo $rec['priority']; ?>
                    </div>
                    <div class="rec-message"><?php echo htmlspecialchars($rec['message']); ?></div>
                    <?php if ($rec['action']): ?>
                    <div class="rec-action"><?php echo htmlspecialchars($rec['action']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php foreach ($report['categories'] as $categoryName => $categoryData): ?>
            <div class="category">
                <div class="category-title"><?php echo htmlspecialchars($categoryName); ?></div>
                <div class="items">
                    <?php foreach ($categoryData as $key => $value): ?>
                    <div class="item">
                        <span class="item-key"><?php echo htmlspecialchars($key); ?>:</span>
                        <span class="item-value <?php 
                            echo is_bool($value) ? ($value ? 'value-true' : 'value-false') : ''; 
                        ?>">
                            <?php 
                            if (is_bool($value)) {
                                echo $value ? 'YES' : 'NO';
                            } elseif (is_array($value)) {
                                echo json_encode($value, JSON_UNESCAPED_UNICODE);
                            } else {
                                echo htmlspecialchars((string)$value);
                            }
                            ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="actions">
                <a href="test-setup.php" class="button">Run Setup Test</a>
                <a href="test-db.php" class="button">Run Database Test</a>
                <a href="test-routes.php" class="button">Run Routes Test</a>
                <a href="?format=json" class="button">Download JSON Report</a>
            </div>
        </div>
    </div>
</body>
</html>
