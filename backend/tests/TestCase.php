<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Config\Database;
use PDO;

/**
 * Base test case with common setup and helper methods
 */
abstract class TestCase extends BaseTestCase
{
    protected static ?PDO $db = null;
    protected static ?string $testAuthToken = null;
    protected static ?array $testUser = null;

    /**
     * Setup database connection before tests
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Load environment variables
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname($envFile));
            $dotenv->load();
        }
        
        // Get database connection
        try {
            self::$db = Database::getConnection();
        } catch (\Exception $e) {
            self::fail("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Make HTTP request to API endpoint
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param string $endpoint API endpoint (e.g., '/api/services')
     * @param array|null $data Request body data
     * @param array $headers Additional headers
     * @return array Response data including status, body, headers
     */
    protected function makeRequest(
        string $method,
        string $endpoint,
        ?array $data = null,
        array $headers = []
    ): array {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8080';
        $url = $baseUrl . $endpoint;
        
        $ch = curl_init();
        
        // Set method and URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Set request body for POST/PUT/PATCH
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH']) && $data !== null) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonData);
        }
        
        // Set headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // Get response headers
        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) {
                return $len;
            }
            $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);
            return $len;
        });
        
        // Execute request
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            self::fail("cURL error: $error");
        }
        
        // Parse JSON response
        $body = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE && $statusCode !== 204) {
            $body = ['raw' => $response];
        }
        
        return [
            'status' => $statusCode,
            'body' => $body,
            'headers' => $responseHeaders
        ];
    }

    /**
     * Authenticate and get JWT token
     * 
     * @return string JWT token
     */
    protected function authenticate(): string
    {
        // Return cached token if available
        if (self::$testAuthToken !== null) {
            return self::$testAuthToken;
        }
        
        // Login with default admin credentials
        $response = $this->makeRequest('POST', '/api/auth/login', [
            'login' => $_ENV['ADMIN_LOGIN'] ?? 'admin',
            'password' => $_ENV['ADMIN_PASSWORD'] ?? 'admin123'
        ]);
        
        $this->assertEquals(200, $response['status'], 'Authentication failed');
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('access_token', $response['body']['data']);
        
        self::$testAuthToken = $response['body']['data']['access_token'];
        self::$testUser = $response['body']['data']['user'] ?? null;
        
        return self::$testAuthToken;
    }

    /**
     * Make authenticated request (with JWT token)
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $data Request body data
     * @param array $additionalHeaders Additional headers
     * @return array Response data
     */
    protected function authenticatedRequest(
        string $method,
        string $endpoint,
        ?array $data = null,
        array $additionalHeaders = []
    ): array {
        $token = $this->authenticate();
        $headers = array_merge(
            ['Authorization: Bearer ' . $token],
            $additionalHeaders
        );
        
        return $this->makeRequest($method, $endpoint, $data, $headers);
    }

    /**
     * Assert response has standard success structure
     * 
     * @param array $response Response data
     * @param string $message Optional failure message
     */
    protected function assertSuccessResponse(array $response, string $message = ''): void
    {
        $this->assertArrayHasKey('body', $response, $message);
        $this->assertArrayHasKey('success', $response['body'], $message);
        $this->assertTrue($response['body']['success'], $message);
    }

    /**
     * Assert response has standard error structure
     * 
     * @param array $response Response data
     * @param string $message Optional failure message
     */
    protected function assertErrorResponse(array $response, string $message = ''): void
    {
        $this->assertArrayHasKey('body', $response, $message);
        $this->assertArrayHasKey('success', $response['body'], $message);
        $this->assertFalse($response['body']['success'], $message);
        $this->assertArrayHasKey('message', $response['body'], $message);
    }

    /**
     * Assert validation error response with errors array
     * 
     * @param array $response Response data
     * @param string $field Field name that should have error
     */
    protected function assertValidationError(array $response, ?string $field = null): void
    {
        $this->assertEquals(422, $response['status'], 'Expected 422 status for validation error');
        $this->assertErrorResponse($response);
        $this->assertArrayHasKey('errors', $response['body'], 'Expected errors array in response');
        
        if ($field !== null) {
            $this->assertArrayHasKey($field, $response['body']['errors'], 
                "Expected validation error for field: $field");
        }
    }

    /**
     * Create test service
     * 
     * @param array $override Override default data
     * @return array Created service data
     */
    protected function createTestService(array $override = []): array
    {
        $data = array_merge([
            'name' => 'Test Service ' . uniqid(),
            'icon' => 'fa-test',
            'description' => 'Test service description',
            'price' => '1000₽/шт',
            'features' => ['Feature 1', 'Feature 2'],
            'active' => true,
            'featured' => false,
            'display_order' => 0
        ], $override);
        
        $response = $this->authenticatedRequest('POST', '/api/admin/services', $data);
        $this->assertEquals(201, $response['status'], 'Failed to create test service');
        
        return $response['body']['data'];
    }

    /**
     * Create test portfolio item
     * 
     * @param array $override Override default data
     * @return array Created portfolio item data
     */
    protected function createTestPortfolio(array $override = []): array
    {
        $data = array_merge([
            'title' => 'Test Portfolio ' . uniqid(),
            'category' => 'prototype',
            'description' => 'Test portfolio description',
            'image_url' => 'https://example.com/test.jpg',
            'details' => 'Test details'
        ], $override);
        
        $response = $this->authenticatedRequest('POST', '/api/admin/portfolio', $data);
        $this->assertEquals(201, $response['status'], 'Failed to create test portfolio item');
        
        return $response['body']['data'];
    }

    /**
     * Create test order
     * 
     * @param array $override Override default data
     * @return array Created order data
     */
    protected function createTestOrder(array $override = []): array
    {
        $data = array_merge([
            'client_name' => 'Test Client ' . uniqid(),
            'client_email' => 'test' . uniqid() . '@example.com',
            'client_phone' => '+79001234567',
            'message' => 'Test order message',
            'type' => 'contact'
        ], $override);
        
        $response = $this->makeRequest('POST', '/api/orders', $data);
        $this->assertEquals(201, $response['status'], 'Failed to create test order');
        
        return $response['body']['data'];
    }

    /**
     * Clean up test data after tests
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // Override in child classes to clean up specific test data
    }
}
