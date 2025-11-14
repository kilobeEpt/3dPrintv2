<?php

/**
 * 3D Print Pro - API Front Controller
 * 
 * This file serves as the entry point for all API requests.
 * All requests are routed through here via .htaccess (Apache) or nginx config.
 */

declare(strict_types=1);

// Ensure we're returning JSON for API endpoints
header('Content-Type: application/json; charset=utf-8');

// Display errors in development (override in production)
$isDevelopment = getenv('APP_ENV') === 'development' || (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true');

if ($isDevelopment) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set('Europe/Moscow');

// Require Composer autoloader
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Composer dependencies not installed',
        'details' => 'Run: composer install'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit(1);
}

require $autoloadPath;

// Log request for debugging (only in development)
if ($isDevelopment) {
    $logPath = __DIR__ . '/../storage/logs/requests.log';
    $logDir = dirname($logPath);
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    if (is_writable($logDir)) {
        $logEntry = sprintf(
            "[%s] %s %s | Query: %s | Headers: %s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER['QUERY_STRING'] ?? '',
            json_encode(getallheaders() ?: [])
        );
        @file_put_contents($logPath, $logEntry, FILE_APPEND);
    }
}

// Bootstrap and run the application
try {
    $app = new \App\Bootstrap\App();
    $app->run();
} catch (\Throwable $e) {
    // Fallback error handler if bootstrap fails
    http_response_code(500);
    
    $error = [
        'success' => false,
        'message' => 'Application failed to start',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Include error details in development
    if ($isDevelopment) {
        $error['debug'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice($e->getTrace(), 0, 5)
        ];
    }
    
    echo json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
    // Log critical errors
    $errorLogPath = __DIR__ . '/../storage/logs/app.log';
    $errorLogDir = dirname($errorLogPath);
    
    if (!is_dir($errorLogDir)) {
        @mkdir($errorLogDir, 0755, true);
    }
    
    if (is_writable($errorLogDir)) {
        $errorEntry = sprintf(
            "[%s] CRITICAL: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        @file_put_contents($errorLogPath, $errorEntry, FILE_APPEND);
    }
    
    exit(1);
}
