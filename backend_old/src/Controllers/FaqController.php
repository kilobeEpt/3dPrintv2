<?php

namespace App\Controllers;

use App\Services\FaqService;

class FaqController
{
    use BaseController;
    
    private FaqService $service;

    public function __construct()
    {
        $this->service = new FaqService();
    }

    public function index(): array
    {
        $items = $this->service->getAll(true);
        return $this->success($items, 'FAQ items retrieved successfully');
    }

    public function show(string $id): array
    {
        $item = $this->service->getById((int)$id);

        if (!$item) {
            return $this->notFound('FAQ item not found');
        }

        return $this->success($item, 'FAQ item retrieved successfully');
    }

    public function adminIndex(): array
    {
        $items = $this->service->getAll(false);
        return $this->success($items, 'FAQ items retrieved successfully');
    }

    public function store(): array
    {
        $data = $this->getRequestData();
        $result = $this->service->create($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to create FAQ item');
        }

        $item = $this->service->getById($result['id']);
        return $this->success($item, 'FAQ item created successfully', 201);
    }

    public function update(string $id): array
    {
        $data = $this->getRequestData();
        $result = $this->service->update((int)$id, $data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            if (strpos($result['error'] ?? '', 'not found') !== false) {
                return $this->notFound('FAQ item not found');
            }
            return $this->error($result['error'] ?? 'Failed to update FAQ item');
        }

        $item = $this->service->getById((int)$id);
        return $this->success($item, 'FAQ item updated successfully');
    }

    public function destroy(string $id): array
    {
        $existing = $this->service->getById((int)$id);
        if (!$existing) {
            return $this->notFound('FAQ item not found');
        }

        $this->service->delete((int)$id);
        return $this->success(null, 'FAQ item deleted successfully');
    }
}
