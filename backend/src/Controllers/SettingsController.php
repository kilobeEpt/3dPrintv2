<?php

namespace App\Controllers;

use App\Services\SettingsService;

class SettingsController
{
    use BaseController;
    
    private SettingsService $service;

    public function __construct()
    {
        $this->service = new SettingsService();
    }

    public function getPublicSettings(): array
    {
        $settings = $this->service->getPublicSettings();
        return $this->success($settings, 'Public settings retrieved successfully');
    }

    public function getAdminSettings(): array
    {
        $settings = $this->service->getAdminSettings();
        return $this->success($settings, 'Admin settings retrieved successfully');
    }

    public function updateGeneralSettings(): array
    {
        $data = $this->getRequestData();
        $result = $this->service->updateGeneralSettings($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to update settings');
        }

        $settings = $this->service->getAdminSettings();
        return $this->success($settings, 'General settings updated successfully');
    }

    public function updateCalculatorSettings(): array
    {
        $data = $this->getRequestData();
        $result = $this->service->updateCalculatorSettings($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to update calculator settings');
        }

        $settings = $this->service->getAdminSettings();
        return $this->success($settings['calculator'], 'Calculator settings updated successfully');
    }

    public function updateFormSettings(): array
    {
        $data = $this->getRequestData();
        $result = $this->service->updateFormSettings($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to update form settings');
        }

        $settings = $this->service->getAdminSettings();
        return $this->success($settings['forms'], 'Form settings updated successfully');
    }

    public function updateTelegramSettings(): array
    {
        $data = $this->getRequestData();
        $result = $this->service->updateTelegramSettings($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to update Telegram settings');
        }

        $settings = $this->service->getAdminSettings();
        return $this->success($settings['integrations']['telegram'], 'Telegram settings updated successfully');
    }
}
