<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\SettingsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SettingsController
{
    private SettingsService $service;

    public function __construct()
    {
        $this->service = new SettingsService();
    }

    public function getPublicSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $settings = $this->service->getPublicSettings();
        return Response::success($settings, 'Public settings retrieved successfully');
    }

    public function getAdminSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $settings = $this->service->getAdminSettings();
        return Response::success($settings, 'Admin settings retrieved successfully');
    }

    public function updateGeneralSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        $result = $this->service->updateGeneralSettings($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to update settings');
        }

        $settings = $this->service->getAdminSettings();

        return Response::success($settings, 'General settings updated successfully');
    }

    public function updateCalculatorSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        $result = $this->service->updateCalculatorSettings($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to update calculator settings');
        }

        $settings = $this->service->getAdminSettings();

        return Response::success($settings['calculator'], 'Calculator settings updated successfully');
    }

    public function updateFormSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        $result = $this->service->updateFormSettings($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to update form settings');
        }

        $settings = $this->service->getAdminSettings();

        return Response::success($settings['forms'], 'Form settings updated successfully');
    }

    public function updateTelegramSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        $result = $this->service->updateTelegramSettings($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to update Telegram settings');
        }

        $settings = $this->service->getAdminSettings();

        return Response::success($settings['integrations']['telegram'], 'Telegram settings updated successfully');
    }
}
