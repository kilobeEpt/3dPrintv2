<?php

namespace App\Bootstrap;

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\ServicesController;
use App\Controllers\PortfolioController;
use App\Controllers\TestimonialsController;
use App\Controllers\FaqController;
use App\Controllers\ContentController;
use App\Controllers\SettingsController;
use App\Controllers\OrdersController;
use App\Controllers\TelegramController;
use App\Helpers\TelegramService;
use App\Services\AuthService;
use App\Services\OrdersService;

/**
 * Standalone Application - No Slim Framework Dependencies
 * Uses SimpleRouter for routing and plain PHP for request handling
 */
class App
{
    private $router;
    private array $config;
    private AuthService $authService;
    private ?array $currentUser = null;

    public function __construct()
    {
        $this->loadEnvironment();
        $this->config = $this->loadConfig();
        $this->initializeDatabase();
        $this->router = new \SimpleRouter();
        $this->authService = new AuthService($this->config['jwt']);
        $this->configureMiddleware();
        $this->registerRoutes();
    }

    private function loadEnvironment(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        
        if (file_exists($envFile)) {
            $env = new \SimpleEnv();
            $env->load($envFile);
        }
    }

    private function loadConfig(): array
    {
        return [
            'app' => [
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'url' => $_ENV['APP_URL'] ?? 'http://localhost:8080'
            ],
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_DATABASE'] ?? 'ch167436_3dprint',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            ],
            'cors' => [
                'origins' => array_filter(array_map('trim', explode(',', $_ENV['CORS_ORIGIN'] ?? '*'))),
                'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
                'headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
                'credentials' => true,
                'maxAge' => 3600
            ],
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? 'change_this_secret',
                'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
                'expiration' => (int)($_ENV['JWT_EXPIRATION'] ?? 3600)
            ],
            'telegram' => [
                'botToken' => $_ENV['TELEGRAM_BOT_TOKEN'] ?? '',
                'chatId' => $_ENV['TELEGRAM_CHAT_ID'] ?? ''
            ]
        ];
    }

    private function initializeDatabase(): void
    {
        Database::init($this->config['database']);
    }

    private function configureMiddleware(): void
    {
        // Global CORS middleware
        $this->router->addGlobalMiddleware(function() {
            $this->handleCors();
            return null; // Continue to next middleware
        });
    }

    private function handleCors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = $this->config['cors']['origins'];
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
            header('Access-Control-Allow-Credentials: true');
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->config['cors']['methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->config['cors']['headers']));
        header('Access-Control-Max-Age: ' . $this->config['cors']['maxAge']);
    }

    private function authMiddleware(array $roles = []): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            return [
                'success' => false,
                'message' => 'Authentication required'
            ];
        }
        
        $token = $matches[1];
        $payload = $this->authService->verifyToken($token);
        
        if (!$payload) {
            http_response_code(401);
            return [
                'success' => false,
                'message' => 'Invalid or expired token'
            ];
        }
        
        $user = $this->authService->getUserById($payload->sub);
        
        if (!$user) {
            http_response_code(401);
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        // Check role if specified
        if (!empty($roles) && !in_array($user['role'], $roles)) {
            http_response_code(403);
            return [
                'success' => false,
                'message' => 'Access denied'
            ];
        }
        
        $this->currentUser = $user;
        return null; // Allow request to continue
    }

    private function registerRoutes(): void
    {
        // Health check endpoint
        $this->router->get('/api/health', function() {
            $dbStatus = Database::testConnection();
            
            $health = [
                'status' => $dbStatus['connected'] ? 'healthy' : 'unhealthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'environment' => $this->config['app']['env'],
                'database' => $dbStatus
            ];

            http_response_code($dbStatus['connected'] ? 200 : 503);
            return $health;
        });

        // API root endpoint
        $this->router->get('/api', function() {
            return [
                'success' => true,
                'message' => 'Welcome to 3D Print Pro API',
                'data' => [
                    'name' => '3D Print Pro API',
                    'version' => '1.0.0',
                    'documentation' => '/api/docs',
                    'endpoints' => [
                        'GET /api/health' => 'Health check and database status',
                        'GET /api' => 'API information',
                        'POST /api/auth/login' => 'Authenticate and get JWT token',
                        'POST /api/auth/logout' => 'Logout (client-side token removal)',
                        'POST /api/auth/refresh' => 'Refresh access token',
                        'GET /api/auth/me' => 'Get current authenticated user (requires token)'
                    ]
                ]
            ];
        });

        // Authentication routes
        $authController = new AuthController($this->authService);
        $this->router->post('/api/auth/login', [$authController, 'login']);
        $this->router->post('/api/auth/logout', [$authController, 'logout']);
        $this->router->post('/api/auth/refresh', [$authController, 'refresh']);
        $this->router->get('/api/auth/me', function() use ($authController) {
            if ($error = $this->authMiddleware()) return $error;
            return $authController->me($this->currentUser);
        });

        // Services routes
        $servicesController = new ServicesController();
        $this->router->get('/api/services', [$servicesController, 'index']);
        $this->router->get('/api/services/{id}', [$servicesController, 'show']);
        
        // Admin services routes
        $this->router->get('/api/admin/services', function() use ($servicesController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $servicesController->adminIndex();
        });
        $this->router->post('/api/admin/services', function() use ($servicesController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $servicesController->store();
        });
        $this->router->put('/api/admin/services/{id}', function($id) use ($servicesController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $servicesController->update($id);
        });
        $this->router->delete('/api/admin/services/{id}', function($id) use ($servicesController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $servicesController->destroy($id);
        });

        // Portfolio routes
        $portfolioController = new PortfolioController();
        $this->router->get('/api/portfolio', [$portfolioController, 'index']);
        $this->router->get('/api/portfolio/categories', [$portfolioController, 'categories']);
        $this->router->get('/api/portfolio/{id}', [$portfolioController, 'show']);
        
        // Admin portfolio routes
        $this->router->post('/api/admin/portfolio', function() use ($portfolioController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $portfolioController->store();
        });
        $this->router->put('/api/admin/portfolio/{id}', function($id) use ($portfolioController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $portfolioController->update($id);
        });
        $this->router->delete('/api/admin/portfolio/{id}', function($id) use ($portfolioController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $portfolioController->destroy($id);
        });

        // Testimonials routes
        $testimonialsController = new TestimonialsController();
        $this->router->get('/api/testimonials', [$testimonialsController, 'index']);
        $this->router->get('/api/testimonials/{id}', [$testimonialsController, 'show']);
        
        // Admin testimonials routes
        $this->router->get('/api/admin/testimonials', function() use ($testimonialsController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $testimonialsController->adminIndex();
        });
        $this->router->post('/api/admin/testimonials', function() use ($testimonialsController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $testimonialsController->store();
        });
        $this->router->put('/api/admin/testimonials/{id}', function($id) use ($testimonialsController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $testimonialsController->update($id);
        });
        $this->router->delete('/api/admin/testimonials/{id}', function($id) use ($testimonialsController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $testimonialsController->destroy($id);
        });

        // FAQ routes
        $faqController = new FaqController();
        $this->router->get('/api/faq', [$faqController, 'index']);
        $this->router->get('/api/faq/{id}', [$faqController, 'show']);
        
        // Admin FAQ routes
        $this->router->get('/api/admin/faq', function() use ($faqController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $faqController->adminIndex();
        });
        $this->router->post('/api/admin/faq', function() use ($faqController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $faqController->store();
        });
        $this->router->put('/api/admin/faq/{id}', function($id) use ($faqController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $faqController->update($id);
        });
        $this->router->delete('/api/admin/faq/{id}', function($id) use ($faqController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $faqController->destroy($id);
        });

        // Content routes
        $contentController = new ContentController();
        $this->router->get('/api/content', [$contentController, 'index']);
        $this->router->get('/api/content/{section}', [$contentController, 'show']);
        $this->router->get('/api/stats', [$contentController, 'getStats']);
        
        // Admin content routes
        $this->router->put('/api/admin/content/{section}', function($section) use ($contentController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $contentController->upsert($section);
        });
        $this->router->delete('/api/admin/content/{section}', function($section) use ($contentController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $contentController->destroy($section);
        });
        $this->router->put('/api/admin/stats', function() use ($contentController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $contentController->updateStats();
        });

        // Orders routes
        $telegramService = new TelegramService(
            $this->config['telegram']['botToken'] ?? '',
            $this->config['telegram']['chatId'] ?? ''
        );
        $ordersService = new OrdersService($telegramService);
        $ordersController = new OrdersController($ordersService);
        
        // Public order submission
        $this->router->post('/api/orders', [$ordersController, 'submit']);
        
        // Admin orders routes
        $this->router->get('/api/orders', function() use ($ordersController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $ordersController->index();
        });
        $this->router->get('/api/orders/{id}', function($id) use ($ordersController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $ordersController->show($id);
        });
        $this->router->put('/api/orders/{id}', function($id) use ($ordersController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $ordersController->update($id);
        });
        $this->router->delete('/api/orders/{id}', function($id) use ($ordersController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $ordersController->destroy($id);
        });
        $this->router->post('/api/orders/{id}/resend-telegram', function($id) use ($ordersController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $ordersController->resendTelegram($id);
        });

        // Settings routes
        $settingsController = new SettingsController();
        $this->router->get('/api/settings/public', [$settingsController, 'getPublicSettings']);
        
        // Admin settings routes
        $this->router->get('/api/settings', function() use ($settingsController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $settingsController->getAdminSettings();
        });
        $this->router->put('/api/settings', function() use ($settingsController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $settingsController->updateGeneralSettings();
        });
        $this->router->put('/api/settings/calculator', function() use ($settingsController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $settingsController->updateCalculatorSettings();
        });
        $this->router->put('/api/settings/forms', function() use ($settingsController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $settingsController->updateFormSettings();
        });
        $this->router->put('/api/settings/telegram', function() use ($settingsController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $settingsController->updateTelegramSettings();
        });

        // Telegram admin routes
        $telegramController = new TelegramController($telegramService);
        $this->router->post('/api/telegram/test', function() use ($telegramController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $telegramController->test();
        });
        $this->router->get('/api/telegram/chat-id', function() use ($telegramController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $telegramController->getChatId();
        });
        $this->router->get('/api/telegram/status', function() use ($telegramController) {
            if ($error = $this->authMiddleware(['admin'])) return $error;
            return $telegramController->status();
        });
    }

    public function run(): void
    {
        $this->router->run();
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
