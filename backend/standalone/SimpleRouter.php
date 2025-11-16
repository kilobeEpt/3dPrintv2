<?php
/**
 * Simple Router - Replacement for Slim Framework
 * Handles HTTP routing with middleware support
 */

class SimpleRouter
{
    private $routes = [];
    private $middleware = [];
    private $globalMiddleware = [];
    
    /**
     * Add route
     */
    public function addRoute(string $method, string $pattern, callable $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    /**
     * Add GET route
     */
    public function get(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $middleware);
    }
    
    /**
     * Add POST route
     */
    public function post(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $middleware);
    }
    
    /**
     * Add PUT route
     */
    public function put(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $pattern, $handler, $middleware);
    }
    
    /**
     * Add DELETE route
     */
    public function delete(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $pattern, $handler, $middleware);
    }
    
    /**
     * Add global middleware (runs for all routes)
     */
    public function addGlobalMiddleware(callable $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }
    
    /**
     * Run the router
     */
    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove script name from URI if present
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }
        
        // Ensure URI starts with /
        $uri = '/' . ltrim($uri, '/');
        
        // Handle OPTIONS for CORS
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $params = $this->matchRoute($route['pattern'], $uri);
            
            if ($params !== false) {
                try {
                    // Run global middleware
                    foreach ($this->globalMiddleware as $middleware) {
                        $result = $middleware();
                        if ($result !== null) {
                            echo json_encode($result);
                            return;
                        }
                    }
                    
                    // Run route-specific middleware
                    foreach ($route['middleware'] as $middleware) {
                        $result = $middleware();
                        if ($result !== null) {
                            echo json_encode($result);
                            return;
                        }
                    }
                    
                    // Run handler
                    $result = call_user_func_array($route['handler'], $params);
                    
                    if (is_array($result)) {
                        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } else {
                        echo $result;
                    }
                    
                    return;
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                    return;
                }
            }
        }
        
        // No route found
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found',
            'path' => $uri
        ]);
    }
    
    /**
     * Match route pattern against URI
     */
    private function matchRoute(string $pattern, string $uri)
    {
        // Convert pattern to regex
        // Example: /api/orders/{id} -> /^\/api\/orders\/([^\/]+)$/
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }
        
        return false;
    }
}
