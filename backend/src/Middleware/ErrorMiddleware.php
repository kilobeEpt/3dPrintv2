<?php

namespace App\Middleware;

use App\Helpers\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ErrorMiddleware implements MiddlewareInterface
{
    private bool $displayErrors;

    public function __construct(bool $displayErrors = false)
    {
        $this->displayErrors = $displayErrors;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    private function handleException(Throwable $e): ResponseInterface
    {
        $statusCode = $this->getStatusCode($e);
        $message = $this->displayErrors 
            ? $e->getMessage() 
            : $this->getGenericMessage($statusCode);

        $payload = [
            'success' => false,
            'message' => $message
        ];

        // Include additional debug info in development
        if ($this->displayErrors) {
            $payload['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }

        return Response::json($payload, $statusCode);
    }

    private function getStatusCode(Throwable $e): int
    {
        // If exception has a getCode that's a valid HTTP status code, use it
        $code = $e->getCode();
        if ($code >= 400 && $code < 600) {
            return $code;
        }

        // Default to 500 for unknown errors
        return 500;
    }

    private function getGenericMessage(int $statusCode): string
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable'
        ];

        return $messages[$statusCode] ?? 'An error occurred';
    }
}
