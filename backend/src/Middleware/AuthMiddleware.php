<?php

namespace App\Middleware;

use App\Helpers\Response;
use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $authService;
    private array $allowedRoles;

    public function __construct(AuthService $authService, array $allowedRoles = [])
    {
        $this->authService = $authService;
        $this->allowedRoles = $allowedRoles;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return Response::unauthorized('Authorization header is missing');
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return Response::unauthorized('Invalid authorization header format');
        }

        $token = $matches[1];
        $payload = $this->authService->verifyToken($token);

        if (!$payload) {
            return Response::unauthorized('Invalid or expired token');
        }

        $user = $this->authService->getUserById($payload->sub);

        if (!$user) {
            return Response::unauthorized('User not found');
        }

        if (!empty($this->allowedRoles) && !in_array($user['role'], $this->allowedRoles)) {
            return Response::forbidden('Insufficient permissions');
        }

        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('token_payload', $payload);

        return $handler->handle($request);
    }
}
