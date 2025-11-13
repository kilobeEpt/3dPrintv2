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
use App\Helpers\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\ErrorMiddleware;
use App\Services\AuthService;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\App as SlimApp;
use Slim\Routing\RouteCollectorProxy;

class App
{
    private SlimApp $app;
    private array $config;

    public function __construct()
    {
        $this->loadEnvironment();
        $this->config = $this->loadConfig();
        $this->initializeDatabase();
        $this->app = AppFactory::create();
        $this->configureMiddleware();
        $this->registerRoutes();
    }

    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        
        try {
            $dotenv->load();
        } catch (\Exception $e) {
            // .env file not found - will use default values or fail gracefully
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
        // Add error handling middleware
        $errorMiddleware = new ErrorMiddleware($this->config['app']['debug']);
        $this->app->add($errorMiddleware);

        // Add CORS middleware
        $corsMiddleware = new CorsMiddleware($this->config['cors']);
        $this->app->add($corsMiddleware);

        // Add body parsing middleware
        $this->app->addBodyParsingMiddleware();

        // Add routing middleware
        $this->app->addRoutingMiddleware();
    }

    private function registerRoutes(): void
    {
        $authService = new AuthService($this->config['jwt']);
        $authController = new AuthController($authService);

        // Health check endpoint
        $this->app->get('/api/health', function ($request, $response) {
            $dbStatus = Database::testConnection();
            
            $health = [
                'status' => $dbStatus['connected'] ? 'healthy' : 'unhealthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'environment' => $this->config['app']['env'],
                'database' => $dbStatus
            ];

            $statusCode = $dbStatus['connected'] ? 200 : 503;

            return Response::json($health, $statusCode);
        });

        // API root endpoint
        $this->app->get('/api', function ($request, $response) {
            return Response::success([
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
            ], 'Welcome to 3D Print Pro API');
        });

        // Authentication routes
        $this->app->group('/api/auth', function (RouteCollectorProxy $group) use ($authController, $authService) {
            $group->post('/login', [$authController, 'login']);
            $group->post('/logout', [$authController, 'logout']);
            $group->post('/refresh', [$authController, 'refresh']);
            
            $group->get('/me', [$authController, 'me'])
                ->add(new AuthMiddleware($authService));
        });

        // Protected test route (example)
        $this->app->get('/api/protected', function ($request, $response) {
            $user = $request->getAttribute('user');
            return Response::success([
                'message' => 'This is a protected route',
                'user' => $user
            ]);
        })->add(new AuthMiddleware($authService));

        // Admin-only test route (example)
        $this->app->get('/api/admin', function ($request, $response) {
            $user = $request->getAttribute('user');
            return Response::success([
                'message' => 'This is an admin-only route',
                'user' => $user
            ]);
        })->add(new AuthMiddleware($authService, ['admin']));

        // Content API Routes
        $this->registerContentRoutes($authService);

        // 404 handler for undefined routes
        $this->app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/api/{path:.*}', 
            function ($request, $response) {
                return Response::notFound('Endpoint not found');
            }
        );
    }

    private function registerContentRoutes(AuthService $authService): void
    {
        $servicesController = new ServicesController();
        $portfolioController = new PortfolioController();
        $testimonialsController = new TestimonialsController();
        $faqController = new FaqController();
        $contentController = new ContentController();

        // Public Services Routes
        $this->app->get('/api/services', [$servicesController, 'index']);
        $this->app->get('/api/services/{id}', [$servicesController, 'show']);

        // Admin Services Routes
        $this->app->group('/api/admin/services', function (RouteCollectorProxy $group) use ($servicesController) {
            $group->get('', [$servicesController, 'adminIndex']);
            $group->post('', [$servicesController, 'store']);
            $group->put('/{id}', [$servicesController, 'update']);
            $group->patch('/{id}', [$servicesController, 'update']);
            $group->delete('/{id}', [$servicesController, 'destroy']);
        })->add(new AuthMiddleware($authService, ['admin']));

        // Public Portfolio Routes
        $this->app->get('/api/portfolio', [$portfolioController, 'index']);
        $this->app->get('/api/portfolio/categories', [$portfolioController, 'categories']);
        $this->app->get('/api/portfolio/{id}', [$portfolioController, 'show']);

        // Admin Portfolio Routes
        $this->app->group('/api/admin/portfolio', function (RouteCollectorProxy $group) use ($portfolioController) {
            $group->post('', [$portfolioController, 'store']);
            $group->put('/{id}', [$portfolioController, 'update']);
            $group->patch('/{id}', [$portfolioController, 'update']);
            $group->delete('/{id}', [$portfolioController, 'destroy']);
        })->add(new AuthMiddleware($authService, ['admin']));

        // Public Testimonials Routes
        $this->app->get('/api/testimonials', [$testimonialsController, 'index']);
        $this->app->get('/api/testimonials/{id}', [$testimonialsController, 'show']);

        // Admin Testimonials Routes
        $this->app->group('/api/admin/testimonials', function (RouteCollectorProxy $group) use ($testimonialsController) {
            $group->get('', [$testimonialsController, 'adminIndex']);
            $group->post('', [$testimonialsController, 'store']);
            $group->put('/{id}', [$testimonialsController, 'update']);
            $group->patch('/{id}', [$testimonialsController, 'update']);
            $group->delete('/{id}', [$testimonialsController, 'destroy']);
        })->add(new AuthMiddleware($authService, ['admin']));

        // Public FAQ Routes
        $this->app->get('/api/faq', [$faqController, 'index']);
        $this->app->get('/api/faq/{id}', [$faqController, 'show']);

        // Admin FAQ Routes
        $this->app->group('/api/admin/faq', function (RouteCollectorProxy $group) use ($faqController) {
            $group->get('', [$faqController, 'adminIndex']);
            $group->post('', [$faqController, 'store']);
            $group->put('/{id}', [$faqController, 'update']);
            $group->patch('/{id}', [$faqController, 'update']);
            $group->delete('/{id}', [$faqController, 'destroy']);
        })->add(new AuthMiddleware($authService, ['admin']));

        // Public Content Routes
        $this->app->get('/api/content', [$contentController, 'index']);
        $this->app->get('/api/content/{section}', [$contentController, 'show']);
        $this->app->get('/api/stats', [$contentController, 'getStats']);

        // Admin Content Routes
        $this->app->group('/api/admin/content', function (RouteCollectorProxy $group) use ($contentController) {
            $group->put('/{section}', [$contentController, 'upsert']);
            $group->patch('/{section}', [$contentController, 'upsert']);
            $group->delete('/{section}', [$contentController, 'destroy']);
        })->add(new AuthMiddleware($authService, ['admin']));

        // Admin Stats Routes
        $this->app->group('/api/admin/stats', function (RouteCollectorProxy $group) use ($contentController) {
            $group->put('', [$contentController, 'updateStats']);
            $group->patch('', [$contentController, 'updateStats']);
        })->add(new AuthMiddleware($authService, ['admin']));

        // Settings Routes
        $settingsController = new SettingsController();

        // Public Settings Route
        $this->app->get('/api/settings/public', [$settingsController, 'getPublicSettings']);

        // Admin Settings Routes
        $this->app->group('/api/settings', function (RouteCollectorProxy $group) use ($settingsController) {
            $group->get('', [$settingsController, 'getAdminSettings']);
            $group->put('', [$settingsController, 'updateGeneralSettings']);
            $group->patch('', [$settingsController, 'updateGeneralSettings']);
            $group->put('/calculator', [$settingsController, 'updateCalculatorSettings']);
            $group->patch('/calculator', [$settingsController, 'updateCalculatorSettings']);
            $group->put('/forms', [$settingsController, 'updateFormSettings']);
            $group->patch('/forms', [$settingsController, 'updateFormSettings']);
            $group->put('/telegram', [$settingsController, 'updateTelegramSettings']);
            $group->patch('/telegram', [$settingsController, 'updateTelegramSettings']);
        })->add(new AuthMiddleware($authService, ['admin']));
    }

    public function run(): void
    {
        $this->app->run();
    }

    public function getApp(): SlimApp
    {
        return $this->app;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
