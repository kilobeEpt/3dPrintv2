<?php

/**
 * Database Connection Test Script
 * 
 * Run this to verify your database configuration before starting the API.
 * Usage: php test-connection.php
 */

declare(strict_types=1);

echo "===========================================\n";
echo "3D Print Pro - Database Connection Test\n";
echo "===========================================\n\n";

// Check if vendor directory exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ ERROR: Composer dependencies not installed!\n";
    echo "   Run: composer install\n\n";
    exit(1);
}

require __DIR__ . '/vendor/autoload.php';

// Check if .env file exists
if (!file_exists(__DIR__ . '/.env')) {
    echo "❌ ERROR: .env file not found!\n";
    echo "   Run: cp .env.example .env\n";
    echo "   Then edit .env with your database credentials\n\n";
    exit(1);
}

// Load environment variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    echo "✅ Environment variables loaded\n";
} catch (Exception $e) {
    echo "❌ ERROR loading .env file: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Check required environment variables
$required = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
$missing = [];

foreach ($required as $var) {
    if (!isset($_ENV[$var])) {
        $missing[] = $var;
    }
}

if (!empty($missing)) {
    echo "❌ ERROR: Missing required environment variables:\n";
    foreach ($missing as $var) {
        echo "   - $var\n";
    }
    echo "\n";
    exit(1);
}

echo "✅ Required environment variables present\n\n";

// Test database connection
echo "Testing database connection...\n";
echo "-------------------------------------------\n";
echo "Host:     {$_ENV['DB_HOST']}\n";
echo "Database: {$_ENV['DB_DATABASE']}\n";
echo "Username: {$_ENV['DB_USERNAME']}\n";
echo "-------------------------------------------\n\n";

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'] ?? '3306',
        $_ENV['DB_DATABASE']
    );

    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "✅ Database connection successful!\n\n";

    // Get MySQL version
    $stmt = $pdo->query('SELECT VERSION() as version');
    $result = $stmt->fetch();
    echo "MySQL Version: {$result['version']}\n";

    // Get database name
    $stmt = $pdo->query('SELECT DATABASE() as db');
    $result = $stmt->fetch();
    echo "Current Database: {$result['db']}\n\n";

    // Check if tables exist
    echo "Checking database schema...\n";
    echo "-------------------------------------------\n";
    
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠️  WARNING: No tables found in database!\n";
        echo "   Run migrations: mysql -u root -p < database/migrations/20231113_initial.sql\n\n";
    } else {
        echo "✅ Found " . count($tables) . " tables:\n";
        foreach ($tables as $table) {
            // Get row count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
            $count = $stmt->fetch()['count'];
            echo "   - {$table} ({$count} rows)\n";
        }
        echo "\n";
    }

    // Test a sample query
    if (in_array('users', $tables)) {
        echo "Testing sample query...\n";
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM users WHERE active = 1');
        $result = $stmt->fetch();
        echo "✅ Found {$result['count']} active user(s)\n\n";
    }

    echo "===========================================\n";
    echo "✅ ALL TESTS PASSED!\n";
    echo "===========================================\n\n";
    echo "Your database is configured correctly.\n";
    echo "You can now start the API server:\n";
    echo "  php -S localhost:8080 -t public\n\n";

} catch (PDOException $e) {
    echo "❌ Database connection FAILED!\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "Troubleshooting steps:\n";
    echo "1. Verify MySQL is running:\n";
    echo "   sudo systemctl status mysql\n\n";
    echo "2. Test connection manually:\n";
    echo "   mysql -h {$_ENV['DB_HOST']} -u {$_ENV['DB_USERNAME']} -p\n\n";
    echo "3. Verify database exists:\n";
    echo "   mysql -u root -p -e \"SHOW DATABASES LIKE '{$_ENV['DB_DATABASE']}';\"\n\n";
    echo "4. Check credentials in .env file\n\n";
    
    exit(1);
}
