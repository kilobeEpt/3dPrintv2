<?php

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(): array
    {
        $data = $this->getRequestData();

        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($login) || empty($password)) {
            http_response_code(400);
            return [
                'success' => false,
                'message' => 'Login and password are required',
                'errors' => [
                    'login' => empty($login) ? 'Login is required' : null,
                    'password' => empty($password) ? 'Password is required' : null
                ]
            ];
        }

        $user = $this->authService->authenticate($login, $password);

        if (!$user) {
            http_response_code(401);
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }

        $accessToken = $this->authService->generateToken($user);
        $refreshToken = $this->authService->generateRefreshToken($user);

        http_response_code(200);
        return [
            'success' => true,
            'message' => 'Login successful',
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
    }

    public function logout(): array
    {
        http_response_code(200);
        return [
            'success' => true,
            'message' => 'Logout successful',
            'data' => null
        ];
    }

    public function me(?array $user = null): array
    {
        if (!$user) {
            http_response_code(401);
            return [
                'success' => false,
                'message' => 'User not authenticated'
            ];
        }

        http_response_code(200);
        return [
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => [
                'id' => $user['id'],
                'login' => $user['login'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'lastLogin' => $user['last_login_at'],
                'createdAt' => $user['created_at']
            ]
        ];
    }

    public function refresh(): array
    {
        $data = $this->getRequestData();
        $refreshToken = $data['refreshToken'] ?? '';

        if (empty($refreshToken)) {
            http_response_code(400);
            return [
                'success' => false,
                'message' => 'Refresh token is required'
            ];
        }

        $payload = $this->authService->verifyToken($refreshToken);

        if (!$payload || !isset($payload->type) || $payload->type !== 'refresh') {
            http_response_code(401);
            return [
                'success' => false,
                'message' => 'Invalid refresh token'
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

        $newAccessToken = $this->authService->generateToken($user);
        $newRefreshToken = $this->authService->generateRefreshToken($user);

        http_response_code(200);
        return [
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $newAccessToken,
                'refreshToken' => $newRefreshToken
            ]
        ];
    }

    private function getRequestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            return is_array($data) ? $data : [];
        }
        
        return $_POST;
    }
}
