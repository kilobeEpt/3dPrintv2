<?php
/**
 * 3D Print Pro - Final Deployment Verification
 * 
 * Run this script as the final step to verify everything is working
 * 
 * Usage: php final-check.php
 * Or via browser: http://yourdomain.com/backend/final-check.php
 */

declare(strict_types=1);

$checks = [];
$allPassed = true;

function check(string $name, bool $condition, string $success, string $failure): void
{
    global $checks, $allPassed;
    
    $checks[] = [
        'name' => $name,
        'passed' => $condition,
        'message' => $condition ? $success : $failure
    ];
    
    if (!$condition) {
        $allPassed = false;
    }
}

// 1. Check vendor directory
check(
    'Composer Dependencies',
    file_exists(__DIR__ . '/vendor/autoload.php'),
    '✓ Composer autoloader found',
    '✗ Run: composer install'
);

// 2. Check .env file
check(
    'Environment File',
    file_exists(__DIR__ . '/.env'),
    '✓ .env file exists',
    '✗ Copy .env.example to .env'
);

// Load environment if possible
if (file_exists(__DIR__ . '/vendor/autoload.php') && file_exists(__DIR__ . '/.env')) {
    require __DIR__ . '/vendor/autoload.php';
    
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        
        // 3. Check JWT Secret
        check(
            'JWT Secret',
            ($_ENV['JWT_SECRET'] ?? 'change_this_secret') !== 'change_this_secret',
            '✓ JWT_SECRET is configured',
            '✗ Change JWT_SECRET in .env'
        );
        
        // 4. Check App Debug
        check(
            'Debug Mode',
            ($_ENV['APP_ENV'] ?? 'production') !== 'production' || ($_ENV['APP_DEBUG'] ?? 'false') === 'false',
            '✓ APP_DEBUG is disabled for production',
            '⚠ APP_DEBUG should be false in production'
        );
        
        // 5. Database connection
        try {
            $pdo = new PDO(
                "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM users WHERE role = "admin" AND active = TRUE');
            $adminCount = (int) $stmt->fetch()['count'];
            
            check(
                'Database Connection',
                true,
                '✓ Connected to database',
                '✗ Cannot connect to database'
            );
            
            check(
                'Admin User',
                $adminCount > 0,
                "✓ {$adminCount} admin user(s) found",
                '✗ No admin users - run seed script'
            );
            
        } catch (PDOException $e) {
            check(
                'Database Connection',
                false,
                '',
                '✗ Database error: ' . $e->getMessage()
            );
        }
        
        // 6. Check CORS
        check(
            'CORS Configuration',
            !empty($_ENV['CORS_ORIGIN'] ?? ''),
            '✓ CORS_ORIGIN is set',
            '⚠ Set CORS_ORIGIN to your frontend domain'
        );
        
    } catch (Exception $e) {
        check(
            'Environment Loading',
            false,
            '',
            '✗ Error: ' . $e->getMessage()
        );
    }
}

// 7. Check storage permissions
$logsWritable = is_writable(__DIR__ . '/storage/logs');
check(
    'Storage Permissions',
    $logsWritable,
    '✓ storage/logs is writable',
    '✗ Run: chmod -R 775 storage/'
);

// 8. Check .htaccess (Apache)
$isApache = isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false;
$htaccessExists = file_exists(__DIR__ . '/public/.htaccess');
check(
    'URL Rewriting',
    !$isApache || $htaccessExists,
    $isApache ? '✓ .htaccess file exists' : '✓ Not using Apache',
    '✗ .htaccess missing in public/'
);

// 9. Test API endpoint availability
if (function_exists('curl_init')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $apiUrl = $protocol . '://' . $host . $basePath . '/public/api/health';
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $apiWorks = $httpCode === 200 || $httpCode === 503;
    
    check(
        'API Health Endpoint',
        $apiWorks,
        "✓ API responds (HTTP {$httpCode})",
        "✗ API not responding: {$apiUrl}"
    );
}

// Output
$isCli = php_sapi_name() === 'cli';

if ($isCli) {
    echo "\n";
    echo "╔════════════════════════════════════════════════╗\n";
    echo "║   3D Print Pro - Final Deployment Check       ║\n";
    echo "╚════════════════════════════════════════════════╝\n";
    echo "\n";
    
    foreach ($checks as $check) {
        $icon = $check['passed'] ? '✓' : '✗';
        $color = $check['passed'] ? "\033[32m" : "\033[31m";
        $reset = "\033[0m";
        
        echo "{$color}{$icon}{$reset} {$check['name']}\n";
        echo "  {$check['message']}\n\n";
    }
    
    $passedCount = count(array_filter($checks, fn($c) => $c['passed']));
    $totalCount = count($checks);
    
    echo "══════════════════════════════════════════════════\n";
    echo "Result: {$passedCount}/{$totalCount} checks passed\n";
    
    if ($allPassed) {
        echo "\n🎉 SUCCESS! Your backend is ready for production.\n\n";
        echo "Next steps:\n";
        echo "  1. Test login at: https://yourdomain.com/admin.html\n";
        echo "  2. Change admin password immediately\n";
        echo "  3. Configure site content and settings\n";
        echo "  4. Setup Telegram notifications (optional)\n";
        echo "\n";
        exit(0);
    } else {
        echo "\n⚠ WARNING: Some checks failed. Fix the issues above.\n\n";
        exit(1);
    }
}

// HTML Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Print Pro - Final Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: <?php echo $allPassed ? '#10b981' : '#ef4444'; ?>; color: white; padding: 40px; text-align: center; }
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .header .icon { font-size: 64px; margin-bottom: 20px; }
        .content { padding: 30px; }
        .check { display: flex; align-items: start; padding: 20px; border-bottom: 1px solid #e5e7eb; }
        .check:last-child { border-bottom: none; }
        .check-icon { font-size: 24px; margin-right: 15px; flex-shrink: 0; }
        .check-icon.passed { color: #10b981; }
        .check-icon.failed { color: #ef4444; }
        .check-content { flex: 1; }
        .check-name { font-weight: 600; font-size: 16px; margin-bottom: 5px; }
        .check-message { color: #6b7280; font-size: 14px; }
        .summary { background: #f9fafb; padding: 20px; text-align: center; margin: 30px 0; border-radius: 6px; }
        .summary-text { font-size: 18px; font-weight: 600; }
        .next-steps { background: #eff6ff; padding: 20px; border-radius: 6px; border-left: 4px solid #3b82f6; margin-top: 30px; }
        .next-steps h3 { color: #1e40af; margin-bottom: 15px; }
        .next-steps ol { margin-left: 20px; }
        .next-steps li { color: #1e3a8a; margin-bottom: 10px; line-height: 1.6; }
        .actions { text-align: center; margin-top: 30px; }
        .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; margin: 5px; }
        .button:hover { background: #2563eb; }
        .button.success { background: #10b981; }
        .button.success:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon"><?php echo $allPassed ? '🎉' : '⚠️'; ?></div>
            <h1><?php echo $allPassed ? 'Deployment Ready!' : 'Issues Found'; ?></h1>
            <p>3D Print Pro Backend - Final Verification</p>
        </div>
        
        <div class="content">
            <div class="summary">
                <div class="summary-text">
                    <?php
                    $passedCount = count(array_filter($checks, fn($c) => $c['passed']));
                    $totalCount = count($checks);
                    echo "{$passedCount} of {$totalCount} checks passed";
                    ?>
                </div>
            </div>
            
            <?php foreach ($checks as $check): ?>
            <div class="check">
                <div class="check-icon <?php echo $check['passed'] ? 'passed' : 'failed'; ?>">
                    <?php echo $check['passed'] ? '✓' : '✗'; ?>
                </div>
                <div class="check-content">
                    <div class="check-name"><?php echo htmlspecialchars($check['name']); ?></div>
                    <div class="check-message"><?php echo htmlspecialchars($check['message']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if ($allPassed): ?>
            <div class="next-steps">
                <h3>🚀 Next Steps</h3>
                <ol>
                    <li><strong>Test Admin Login:</strong> Go to <code>https://yourdomain.com/admin.html</code> and login</li>
                    <li><strong>Change Password:</strong> Immediately change the default admin password</li>
                    <li><strong>Configure Content:</strong> Add services, portfolio items, testimonials</li>
                    <li><strong>Setup Calculator:</strong> Configure materials, prices, and settings</li>
                    <li><strong>Telegram (Optional):</strong> Setup bot for order notifications</li>
                    <li><strong>Test Frontend:</strong> Verify the public site works correctly</li>
                </ol>
            </div>
            <?php endif; ?>
            
            <div class="actions">
                <?php if ($allPassed): ?>
                <a href="../admin.html" class="button success">Go to Admin Panel</a>
                <a href="public/api/health" class="button">Check API Health</a>
                <?php else: ?>
                <a href="diagnose.php" class="button">Run Full Diagnostics</a>
                <a href="?" class="button">Refresh Check</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
