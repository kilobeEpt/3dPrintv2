<?php
/**
 * 3D Print Pro - Standalone API Entry Point
 * NO COMPOSER DEPENDENCIES REQUIRED
 * 
 * This version works without Composer by using simple standalone implementations
 * of JWT, routing, and .env parsing.
 */

declare(strict_types=1);

// Set JSON response header early
header('Content-Type: application/json; charset=utf-8');

// Error handling
$isDevelopment = getenv('APP_ENV') === 'development' || (isset($_GET['debug']) && $_GET['debug'] === '1');

if ($isDevelopment) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set('Europe/Moscow');

// Load standalone libraries (no Composer needed)
require_once __DIR__ . '/../standalone/SimpleEnv.php';
require_once __DIR__ . '/../standalone/SimpleJWT.php';
require_once __DIR__ . '/../standalone/SimpleRouter.php';
require_once __DIR__ . '/../standalone/autoload.php';

// Load environment variables
SimpleEnv::load(__DIR__ . '/../.env');

// Initialize router
$router = new SimpleRouter();

// ============================================
// CORS Middleware (runs for all routes)
// ============================================
$router->addGlobalMiddleware(function() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    $allowedOrigins = explode(',', SimpleEnv::get('CORS_ORIGIN', '*'));
    
    if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    
    return null; // Continue to next middleware/handler
});

// ============================================
// Database Connection Helper
// ============================================
function getDbConnection(): PDO
{
    static $pdo = null;
    
    if ($pdo === null) {
        $host = SimpleEnv::get('DB_HOST', 'localhost');
        $port = SimpleEnv::get('DB_PORT', '3306');
        $database = SimpleEnv::get('DB_DATABASE', '');
        $username = SimpleEnv::get('DB_USERNAME', '');
        $password = SimpleEnv::get('DB_PASSWORD', '');
        
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        
        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed'
            ]));
        }
    }
    
    return $pdo;
}

// ============================================
// Auth Middleware Helper
// ============================================
function requireAuth(): ?array
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authorization required'
        ]);
        exit;
    }
    
    $token = substr($authHeader, 7);
    $secret = SimpleEnv::get('JWT_SECRET', 'change-this-secret-key');
    
    try {
        $payload = SimpleJWT::decode($token, $secret, ['HS256']);
        
        return [
            'user_id' => $payload->user_id ?? null,
            'login' => $payload->login ?? null,
            'role' => $payload->role ?? null
        ];
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired token'
        ]);
        exit;
    }
}

// ============================================
// Request Body Helper
// ============================================
function getRequestBody(): array
{
    $body = file_get_contents('php://input');
    return json_decode($body, true) ?: [];
}

// ============================================
// PUBLIC ROUTES (No Auth Required)
// ============================================

// Health check
$router->get('/api/health', function() {
    try {
        $db = getDbConnection();
        $db->query('SELECT 1');
        $dbStatus = 'connected';
    } catch (Exception $e) {
        $dbStatus = 'disconnected';
    }
    
    return [
        'success' => true,
        'message' => 'API is running',
        'mode' => 'standalone',
        'timestamp' => date('Y-m-d H:i:s'),
        'database' => $dbStatus
    ];
});

// Get services
$router->get('/api/services', function() {
    $db = getDbConnection();
    $stmt = $db->query('SELECT * FROM services WHERE active = 1 ORDER BY display_order ASC');
    $services = $stmt->fetchAll();
    
    // Get features for each service
    foreach ($services as &$service) {
        $stmt = $db->prepare('SELECT feature FROM service_features WHERE service_id = ? ORDER BY display_order ASC');
        $stmt->execute([$service['id']]);
        $service['features'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    return [
        'success' => true,
        'data' => $services
    ];
});

// Get portfolio
$router->get('/api/portfolio', function() {
    $db = getDbConnection();
    $stmt = $db->query('SELECT * FROM portfolio WHERE active = 1 ORDER BY display_order ASC');
    $portfolio = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $portfolio
    ];
});

// Get testimonials
$router->get('/api/testimonials', function() {
    $db = getDbConnection();
    $stmt = $db->query('SELECT * FROM testimonials WHERE approved = 1 AND active = 1 ORDER BY created_at DESC');
    $testimonials = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $testimonials
    ];
});

// Get FAQ
$router->get('/api/faq', function() {
    $db = getDbConnection();
    $stmt = $db->query('SELECT * FROM faq WHERE active = 1 ORDER BY display_order ASC');
    $faq = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $faq
    ];
});

// Get content
$router->get('/api/content', function() {
    $db = getDbConnection();
    $stmt = $db->query('SELECT section, data FROM site_content');
    $rows = $stmt->fetchAll();
    
    $content = [];
    foreach ($rows as $row) {
        $content[$row['section']] = json_decode($row['data'], true);
    }
    
    return [
        'success' => true,
        'data' => $content
    ];
});

// Get stats
$router->get('/api/stats', function() {
    $db = getDbConnection();
    $stmt = $db->query('SELECT * FROM site_stats LIMIT 1');
    $stats = $stmt->fetch();
    
    return [
        'success' => true,
        'data' => $stats ?: []
    ];
});

// Get public settings
$router->get('/api/settings/public', function() {
    $db = getDbConnection();
    
    // Get materials
    $stmt = $db->query('SELECT material_key, name, technology, price FROM materials WHERE active = 1 ORDER BY display_order ASC');
    $materials = $stmt->fetchAll();
    
    // Get services
    $stmt = $db->query('SELECT service_key, name, unit, price FROM additional_services WHERE active = 1 ORDER BY display_order ASC');
    $services = $stmt->fetchAll();
    
    // Get quality levels
    $stmt = $db->query('SELECT quality_key, name, price_multiplier, time_multiplier FROM quality_levels WHERE active = 1 ORDER BY display_order ASC');
    $quality = $stmt->fetchAll();
    
    // Get discounts
    $stmt = $db->query('SELECT min_quantity, discount_percent FROM volume_discounts WHERE active = 1 ORDER BY min_quantity ASC');
    $discounts = $stmt->fetchAll();
    
    // Get form fields
    $stmt = $db->query('SELECT * FROM form_fields WHERE enabled = 1 ORDER BY display_order ASC');
    $formFields = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => [
            'materials' => $materials,
            'services' => $services,
            'quality' => $quality,
            'discounts' => $discounts,
            'formFields' => $formFields
        ]
    ];
});

// Submit order (with rate limiting)
$router->post('/api/orders', function() {
    $db = getDbConnection();
    $data = getRequestBody();
    
    // Rate limiting - check last order from this IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $db->prepare('SELECT COUNT(*) FROM orders WHERE client_ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    $stmt->execute([$ip]);
    $recentOrders = $stmt->fetchColumn();
    
    if ($recentOrders >= 5) {
        http_response_code(429);
        return [
            'success' => false,
            'message' => 'Слишком много заказов. Пожалуйста, попробуйте позже.'
        ];
    }
    
    // Validate required fields
    if (empty($data['client_name']) || empty($data['client_email'])) {
        http_response_code(422);
        return [
            'success' => false,
            'message' => 'Имя и email обязательны'
        ];
    }
    
    // Generate order number
    $orderNumber = 'ORD-' . date('YmdHis') . '-' . substr(md5($ip . time()), 0, 4);
    
    // Insert order
    $stmt = $db->prepare('
        INSERT INTO orders (
            order_number, client_name, client_email, client_phone,
            message, calculator_data, status, client_ip, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ');
    
    $stmt->execute([
        $orderNumber,
        $data['client_name'],
        $data['client_email'],
        $data['client_phone'] ?? null,
        $data['message'] ?? null,
        json_encode($data['calculator_data'] ?? []),
        'new',
        $ip
    ]);
    
    return [
        'success' => true,
        'message' => 'Заказ успешно создан',
        'data' => [
            'order_number' => $orderNumber,
            'id' => $db->lastInsertId()
        ]
    ];
});

// ============================================
// AUTH ROUTES
// ============================================

// Login
$router->post('/api/auth/login', function() {
    $db = getDbConnection();
    $data = getRequestBody();
    
    if (empty($data['login']) || empty($data['password'])) {
        http_response_code(422);
        return [
            'success' => false,
            'message' => 'Login and password are required'
        ];
    }
    
    // Find user
    $stmt = $db->prepare('SELECT * FROM users WHERE login = ? AND active = 1 LIMIT 1');
    $stmt->execute([$data['login']]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($data['password'], $user['password'])) {
        http_response_code(401);
        return [
            'success' => false,
            'message' => 'Invalid credentials'
        ];
    }
    
    // Generate tokens
    $secret = SimpleEnv::get('JWT_SECRET', 'change-this-secret-key');
    $now = time();
    
    $accessPayload = [
        'user_id' => $user['id'],
        'login' => $user['login'],
        'role' => $user['role'],
        'iat' => $now,
        'exp' => $now + 3600 // 1 hour
    ];
    
    $refreshPayload = [
        'user_id' => $user['id'],
        'type' => 'refresh',
        'iat' => $now,
        'exp' => $now + (30 * 24 * 3600) // 30 days
    ];
    
    $accessToken = SimpleJWT::encode($accessPayload, $secret);
    $refreshToken = SimpleJWT::encode($refreshPayload, $secret);
    
    return [
        'success' => true,
        'data' => [
            'token' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => [
                'id' => $user['id'],
                'login' => $user['login'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]
    ];
});

// Get current user
$router->get('/api/auth/me', function() {
    $user = requireAuth();
    
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT id, login, name, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['user_id']]);
    $userData = $stmt->fetch();
    
    if (!$userData) {
        http_response_code(404);
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    return [
        'success' => true,
        'data' => $userData
    ];
});

// Logout
$router->post('/api/auth/logout', function() {
    requireAuth();
    
    return [
        'success' => true,
        'message' => 'Logged out successfully'
    ];
});

// ============================================
// ADMIN ROUTES (Auth Required)
// ============================================

// Get all orders (admin)
$router->get('/api/orders', function() {
    requireAuth();
    
    $db = getDbConnection();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $stmt = $db->query('SELECT COUNT(*) FROM orders');
    $total = $stmt->fetchColumn();
    
    // Get orders
    $stmt = $db->prepare('SELECT * FROM orders ORDER BY created_at DESC LIMIT ? OFFSET ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => $orders,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ];
});

// Get single order
$router->get('/api/orders/{id}', function($id) {
    requireAuth();
    
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        http_response_code(404);
        return [
            'success' => false,
            'message' => 'Order not found'
        ];
    }
    
    return [
        'success' => true,
        'data' => $order
    ];
});

// Update order
$router->put('/api/orders/{id}', function($id) {
    requireAuth();
    
    $db = getDbConnection();
    $data = getRequestBody();
    
    // Build update query dynamically
    $fields = [];
    $values = [];
    
    $allowedFields = ['status', 'client_name', 'client_email', 'client_phone', 'message', 'admin_notes'];
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        http_response_code(422);
        return [
            'success' => false,
            'message' => 'No valid fields to update'
        ];
    }
    
    $values[] = $id;
    $sql = 'UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = ?';
    
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
    
    return [
        'success' => true,
        'message' => 'Order updated successfully'
    ];
});

// Delete order
$router->delete('/api/orders/{id}', function($id) {
    requireAuth();
    
    $db = getDbConnection();
    $stmt = $db->prepare('DELETE FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    
    return [
        'success' => true,
        'message' => 'Order deleted successfully'
    ];
});

// Admin services endpoints
$router->get('/api/admin/services', function() {
    requireAuth();
    
    $db = getDbConnection();
    $stmt = $db->query('SELECT * FROM services ORDER BY display_order ASC');
    $services = $stmt->fetchAll();
    
    foreach ($services as &$service) {
        $stmt = $db->prepare('SELECT feature FROM service_features WHERE service_id = ? ORDER BY display_order ASC');
        $stmt->execute([$service['id']]);
        $service['features'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    return [
        'success' => true,
        'data' => $services
    ];
});

// Get settings
$router->get('/api/settings', function() {
    requireAuth();
    
    $db = getDbConnection();
    
    // Get site settings
    $stmt = $db->query('SELECT * FROM site_settings LIMIT 1');
    $siteSettings = $stmt->fetch() ?: [];
    
    // Get integrations
    $stmt = $db->query('SELECT integration_key, config FROM integrations');
    $integrations = [];
    while ($row = $stmt->fetch()) {
        $integrations[$row['integration_key']] = json_decode($row['config'], true);
    }
    
    // Get materials
    $stmt = $db->query('SELECT * FROM materials ORDER BY display_order ASC');
    $materials = $stmt->fetchAll();
    
    // Get services
    $stmt = $db->query('SELECT * FROM additional_services ORDER BY display_order ASC');
    $services = $stmt->fetchAll();
    
    // Get quality levels
    $stmt = $db->query('SELECT * FROM quality_levels ORDER BY display_order ASC');
    $quality = $stmt->fetchAll();
    
    // Get volume discounts
    $stmt = $db->query('SELECT * FROM volume_discounts ORDER BY min_quantity ASC');
    $discounts = $stmt->fetchAll();
    
    // Get form fields
    $stmt = $db->query('SELECT * FROM form_fields ORDER BY display_order ASC');
    $formFields = $stmt->fetchAll();
    
    return [
        'success' => true,
        'data' => [
            'site' => $siteSettings,
            'integrations' => $integrations,
            'calculator' => [
                'materials' => $materials,
                'services' => $services,
                'quality' => $quality,
                'discounts' => $discounts
            ],
            'forms' => $formFields
        ]
    ];
});

// ============================================
// Run Router
// ============================================

try {
    $router->run();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'debug' => $isDevelopment ? $e->getMessage() : null
    ]);
}
