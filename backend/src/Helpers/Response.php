<?php

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response as SlimResponse;

class Response
{
    public static function json(
        array $data,
        int $statusCode = 200,
        array $headers = []
    ): ResponseInterface {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    public static function success(
        $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): ResponseInterface {
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public static function error(
        string $message,
        int $statusCode = 500,
        $errors = null
    ): ResponseInterface {
        $payload = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return self::json($payload, $statusCode);
    }

    public static function notFound(string $message = 'Resource not found'): ResponseInterface
    {
        return self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): ResponseInterface
    {
        return self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): ResponseInterface
    {
        return self::error($message, 403);
    }

    public static function badRequest(
        string $message = 'Bad request',
        $errors = null
    ): ResponseInterface {
        return self::error($message, 400, $errors);
    }

    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): ResponseInterface {
        return self::error($message, 422, $errors);
    }
}
