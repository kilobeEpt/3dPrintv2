<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

$corsOrigin = $_ENV['CORS_ORIGIN'] ?? '*';
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($corsOrigin === '*') {
    header('Access-Control-Allow-Origin: *');
} else {
    $allowedOrigins = array_map('trim', explode(',', $corsOrigin));
    if (in_array($requestOrigin, $allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../helpers/Auth.php';

set_exception_handler(function($e) {
    $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    
    if ($debug) {
        Response::serverError($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    } else {
        Response::serverError('An error occurred while processing your request');
    }
});

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($scriptName, '', $requestUri);
$path = parse_url($path, PHP_URL_PATH);
$path = rtrim($path, '/');

if (empty($path)) {
    $path = '/';
}

$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    'GET /api/health' => '../api/health.php',
    'POST /api/auth/login' => '../api/auth/login.php',
    'GET /api/auth/me' => '../api/auth/me.php',
    'POST /api/auth/logout' => '../api/auth/logout.php',
    'GET /api/services' => '../api/services.php',
    'POST /api/services' => '../api/services.php',
    'PUT /api/services' => '../api/services.php',
    'DELETE /api/services' => '../api/services.php',
    'GET /api/portfolio' => '../api/portfolio.php',
    'POST /api/portfolio' => '../api/portfolio.php',
    'PUT /api/portfolio' => '../api/portfolio.php',
    'DELETE /api/portfolio' => '../api/portfolio.php',
    'GET /api/testimonials' => '../api/testimonials.php',
    'POST /api/testimonials' => '../api/testimonials.php',
    'PUT /api/testimonials' => '../api/testimonials.php',
    'DELETE /api/testimonials' => '../api/testimonials.php',
    'GET /api/faq' => '../api/faq.php',
    'POST /api/faq' => '../api/faq.php',
    'PUT /api/faq' => '../api/faq.php',
    'DELETE /api/faq' => '../api/faq.php',
    'GET /api/content' => '../api/content.php',
    'PUT /api/content' => '../api/content.php',
    'GET /api/settings' => '../api/settings.php',
    'PUT /api/settings' => '../api/settings.php',
    'GET /api/settings/public' => '../api/settings-public.php',
    'GET /api/orders' => '../api/orders.php',
    'POST /api/orders' => '../api/orders.php',
    'PUT /api/orders' => '../api/orders.php',
    'DELETE /api/orders' => '../api/orders.php',
    'POST /api/telegram/test' => '../api/telegram.php',
    'GET /api/telegram/status' => '../api/telegram.php',
    'POST /api/telegram/send' => '../api/telegram.php',
];

$routeKey = $method . ' ' . $path;

if (isset($routes[$routeKey])) {
    $file = __DIR__ . '/' . $routes[$routeKey];
    if (file_exists($file)) {
        require $file;
    } else {
        Response::notFound('Endpoint not implemented');
    }
} else {
    Response::notFound('Endpoint not found: ' . $routeKey);
}
