<?php

namespace App\Controllers;

use App\Services\PortfolioService;

class PortfolioController
{
    use BaseController;
    
    private PortfolioService $service;

    public function __construct()
    {
        $this->service = new PortfolioService();
    }

    public function index(): array
    {
        $queryParams = $this->getQueryParams();
        $category = $queryParams['category'] ?? null;
        
        $portfolio = $this->service->getAll($category);
        return $this->success($portfolio, 'Portfolio items retrieved successfully');
    }

    public function show(string $id): array
    {
        $item = $this->service->getById((int)$id);

        if (!$item) {
            return $this->notFound('Portfolio item not found');
        }

        return $this->success($item, 'Portfolio item retrieved successfully');
    }

    public function categories(): array
    {
        $categories = $this->service->getCategories();
        return $this->success($categories, 'Categories retrieved successfully');
    }

    public function store(): array
    {
        $data = $this->getRequestData();
        $result = $this->service->create($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to create portfolio item');
        }

        $item = $this->service->getById($result['id']);
        return $this->success($item, 'Portfolio item created successfully', 201);
    }

    public function update(string $id): array
    {
        $data = $this->getRequestData();
        $result = $this->service->update((int)$id, $data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            if ($result['error'] === 'Portfolio item not found') {
                return $this->notFound('Portfolio item not found');
            }
            return $this->error($result['error'] ?? 'Failed to update portfolio item');
        }

        $item = $this->service->getById((int)$id);
        return $this->success($item, 'Portfolio item updated successfully');
    }

    public function destroy(string $id): array
    {
        $existing = $this->service->getById((int)$id);
        if (!$existing) {
            return $this->notFound('Portfolio item not found');
        }

        $this->service->delete((int)$id);
        return $this->success(null, 'Portfolio item deleted successfully');
    }
}
