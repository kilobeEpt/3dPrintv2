<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($login) || empty($password)) {
            return Response::badRequest('Login and password are required', [
                'login' => empty($login) ? 'Login is required' : null,
                'password' => empty($password) ? 'Password is required' : null
            ]);
        }

        $user = $this->authService->authenticate($login, $password);

        if (!$user) {
            return Response::unauthorized('Invalid credentials');
        }

        $accessToken = $this->authService->generateToken($user);
        $refreshToken = $this->authService->generateRefreshToken($user);

        return Response::success([
            'token' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => [
                'id' => $user['id'],
                'login' => $user['login'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ], 'Login successful');
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Response::success(null, 'Logout successful');
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            return Response::unauthorized('User not authenticated');
        }

        return Response::success([
            'id' => $user['id'],
            'login' => $user['login'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'lastLogin' => $user['last_login_at'],
            'createdAt' => $user['created_at']
        ], 'User retrieved successfully');
    }

    public function refresh(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $refreshToken = $data['refreshToken'] ?? '';

        if (empty($refreshToken)) {
            return Response::badRequest('Refresh token is required');
        }

        $payload = $this->authService->verifyToken($refreshToken);

        if (!$payload || !isset($payload->type) || $payload->type !== 'refresh') {
            return Response::unauthorized('Invalid refresh token');
        }

        $user = $this->authService->getUserById($payload->sub);

        if (!$user) {
            return Response::unauthorized('User not found');
        }

        $newAccessToken = $this->authService->generateToken($user);
        $newRefreshToken = $this->authService->generateRefreshToken($user);

        return Response::success([
            'token' => $newAccessToken,
            'refreshToken' => $newRefreshToken
        ], 'Token refreshed successfully');
    }
}
