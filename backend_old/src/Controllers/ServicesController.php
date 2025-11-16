<?php

namespace App\Controllers;

use App\Services\ServicesService;

class ServicesController
{
    use BaseController;
    
    private ServicesService $service;

    public function __construct()
    {
        $this->service = new ServicesService();
    }

    public function index(): array
    {
        $services = $this->service->getAll(true);
        return $this->success($services, 'Services retrieved successfully');
    }

    public function show(string $id): array
    {
        $service = $this->service->getById((int)$id);

        if (!$service) {
            return $this->notFound('Service not found');
        }

        return $this->success($service, 'Service retrieved successfully');
    }

    public function adminIndex(): array
    {
        $services = $this->service->getAll(false);
        return $this->success($services, 'Services retrieved successfully');
    }

    public function store(): array
    {
        $data = $this->getRequestData();
        $result = $this->service->create($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to create service');
        }

        $service = $this->service->getById($result['id']);
        return $this->success($service, 'Service created successfully', 201);
    }

    public function update(string $id): array
    {
        $data = $this->getRequestData();
        $result = $this->service->update((int)$id, $data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            if ($result['error'] === 'Service not found') {
                return $this->notFound('Service not found');
            }
            return $this->error($result['error'] ?? 'Failed to update service');
        }

        $service = $this->service->getById((int)$id);
        return $this->success($service, 'Service updated successfully');
    }

    public function destroy(string $id): array
    {
        $existing = $this->service->getById((int)$id);
        if (!$existing) {
            return $this->notFound('Service not found');
        }

        $this->service->delete((int)$id);
        return $this->success(null, 'Service deleted successfully');
    }
}
