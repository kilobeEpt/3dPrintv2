<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Authentication API Integration Tests
 */
class AuthTest extends TestCase
{
    /**
     * Test successful login with valid credentials
     */
    public function testLoginWithValidCredentials(): void
    {
        $response = $this->makeRequest('POST', '/api/auth/login', [
            'login' => $_ENV['ADMIN_LOGIN'] ?? 'admin',
            'password' => $_ENV['ADMIN_PASSWORD'] ?? 'admin123'
        ]);
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('access_token', $response['body']['data']);
        $this->assertArrayHasKey('refresh_token', $response['body']['data']);
        $this->assertArrayHasKey('user', $response['body']['data']);
        $this->assertIsString($response['body']['data']['access_token']);
        $this->assertNotEmpty($response['body']['data']['access_token']);
    }

    /**
     * Test login fails with invalid credentials
     */
    public function testLoginWithInvalidCredentials(): void
    {
        $response = $this->makeRequest('POST', '/api/auth/login', [
            'login' => 'admin',
            'password' => 'wrongpassword'
        ]);
        
        $this->assertEquals(401, $response['status']);
        $this->assertErrorResponse($response);
    }

    /**
     * Test login validation requires login field
     */
    public function testLoginRequiresLoginField(): void
    {
        $response = $this->makeRequest('POST', '/api/auth/login', [
            'password' => 'admin123'
        ]);
        
        $this->assertValidationError($response, 'login');
    }

    /**
     * Test login validation requires password field
     */
    public function testLoginRequiresPasswordField(): void
    {
        $response = $this->makeRequest('POST', '/api/auth/login', [
            'login' => 'admin'
        ]);
        
        $this->assertValidationError($response, 'password');
    }

    /**
     * Test login with empty credentials
     */
    public function testLoginWithEmptyCredentials(): void
    {
        $response = $this->makeRequest('POST', '/api/auth/login', [
            'login' => '',
            'password' => ''
        ]);
        
        $this->assertValidationError($response);
    }

    /**
     * Test GET /api/auth/me with valid token
     */
    public function testGetCurrentUserWithValidToken(): void
    {
        $response = $this->authenticatedRequest('GET', '/api/auth/me');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('id', $response['body']['data']);
        $this->assertArrayHasKey('login', $response['body']['data']);
        $this->assertArrayHasKey('email', $response['body']['data']);
        $this->assertArrayHasKey('role', $response['body']['data']);
    }

    /**
     * Test GET /api/auth/me without token returns 401
     */
    public function testGetCurrentUserWithoutToken(): void
    {
        $response = $this->makeRequest('GET', '/api/auth/me');
        
        $this->assertEquals(401, $response['status']);
        $this->assertErrorResponse($response);
    }

    /**
     * Test GET /api/auth/me with invalid token returns 401
     */
    public function testGetCurrentUserWithInvalidToken(): void
    {
        $response = $this->makeRequest('GET', '/api/auth/me', null, [
            'Authorization: Bearer invalid.token.here'
        ]);
        
        $this->assertEquals(401, $response['status']);
        $this->assertErrorResponse($response);
    }

    /**
     * Test GET /api/auth/me with malformed Authorization header
     */
    public function testGetCurrentUserWithMalformedAuthHeader(): void
    {
        $response = $this->makeRequest('GET', '/api/auth/me', null, [
            'Authorization: InvalidFormat'
        ]);
        
        $this->assertEquals(401, $response['status']);
        $this->assertErrorResponse($response);
    }

    /**
     * Test POST /api/auth/logout
     */
    public function testLogout(): void
    {
        $response = $this->authenticatedRequest('POST', '/api/auth/logout');
        
        $this->assertEquals(200, $response['status']);
        $this->assertSuccessResponse($response);
    }

    /**
     * Test token contains correct user information
     */
    public function testTokenContainsCorrectUserInfo(): void
    {
        $loginResponse = $this->makeRequest('POST', '/api/auth/login', [
            'login' => $_ENV['ADMIN_LOGIN'] ?? 'admin',
            'password' => $_ENV['ADMIN_PASSWORD'] ?? 'admin123'
        ]);
        
        $this->assertEquals(200, $loginResponse['status']);
        
        $token = $loginResponse['body']['data']['access_token'];
        $user = $loginResponse['body']['data']['user'];
        
        // Verify user data structure
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('login', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('role', $user);
        
        // Verify role
        $this->assertEquals('admin', $user['role']);
        
        // Verify password is not exposed
        $this->assertArrayNotHasKey('password', $user);
        $this->assertArrayNotHasKey('password_hash', $user);
    }

    /**
     * Test authentication protects admin endpoints
     */
    public function testAdminEndpointsRequireAuthentication(): void
    {
        $endpoints = [
            ['GET', '/api/admin/services'],
            ['GET', '/api/admin/testimonials'],
            ['GET', '/api/admin/faq'],
            ['GET', '/api/orders'],
            ['GET', '/api/settings']
        ];
        
        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->makeRequest($method, $endpoint);
            $this->assertEquals(401, $response['status'], 
                "Endpoint $method $endpoint should require authentication");
        }
    }

    /**
     * Test public endpoints do not require authentication
     */
    public function testPublicEndpointsDoNotRequireAuthentication(): void
    {
        $endpoints = [
            ['GET', '/api/health'],
            ['GET', '/api'],
            ['GET', '/api/services'],
            ['GET', '/api/portfolio'],
            ['GET', '/api/testimonials'],
            ['GET', '/api/faq'],
            ['GET', '/api/content'],
            ['GET', '/api/stats'],
            ['GET', '/api/settings/public']
        ];
        
        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->makeRequest($method, $endpoint);
            $this->assertNotEquals(401, $response['status'], 
                "Public endpoint $method $endpoint should not require authentication");
            $this->assertContains($response['status'], [200, 404], 
                "Public endpoint $method $endpoint should return 200 or 404");
        }
    }
}
