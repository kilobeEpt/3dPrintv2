<?php

/**
 * 3D Print Pro - API Front Controller
 * 
 * This file serves as the entry point for all API requests.
 * All requests are routed through here via .htaccess (Apache) or nginx config.
 */

declare(strict_types=1);

// Display errors in development (override in production)
if (getenv('APP_ENV') === 'development' || (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Set timezone
date_default_timezone_set('Europe/Moscow');

// Require Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap and run the application
try {
    $app = new \App\Bootstrap\App();
    $app->run();
} catch (\Throwable $e) {
    // Fallback error handler if bootstrap fails
    http_response_code(500);
    header('Content-Type: application/json');
    
    $error = [
        'success' => false,
        'message' => 'Application failed to start'
    ];
    
    // Include error details in development
    if (getenv('APP_ENV') === 'development' || (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true')) {
        $error['debug'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    }
    
    echo json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit(1);
}
