<?php

namespace App\Controllers;

/**
 * Base Controller - Common functionality for all controllers
 * No framework dependencies - pure PHP
 */
trait BaseController
{
    protected function getRequestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            return is_array($data) ? $data : [];
        }
        
        return $_POST;
    }

    protected function getQueryParams(): array
    {
        return $_GET;
    }

    protected function success($data = null, string $message = 'Success', int $code = 200): array
    {
        http_response_code($code);
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }

    protected function error(string $message, int $code = 400, $errors = null): array
    {
        http_response_code($code);
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return $response;
    }

    protected function notFound(string $message = 'Resource not found'): array
    {
        return $this->error($message, 404);
    }

    protected function unauthorized(string $message = 'Unauthorized'): array
    {
        return $this->error($message, 401);
    }

    protected function forbidden(string $message = 'Forbidden'): array
    {
        return $this->error($message, 403);
    }

    protected function validationError($errors, string $message = 'Validation failed'): array
    {
        return $this->error($message, 422, $errors);
    }
}
