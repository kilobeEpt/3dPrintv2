<?php

namespace App\Controllers;

use App\Services\TestimonialsService;

class TestimonialsController
{
    use BaseController;
    
    private TestimonialsService $service;

    public function __construct()
    {
        $this->service = new TestimonialsService();
    }

    public function index(): array
    {
        $items = $this->service->getAll(true);
        return $this->success($items, 'Testimonials retrieved successfully');
    }

    public function show(string $id): array
    {
        $item = $this->service->getById((int)$id);

        if (!$item) {
            return $this->notFound('Testimonial not found');
        }

        return $this->success($item, 'Testimonial retrieved successfully');
    }

    public function adminIndex(): array
    {
        $items = $this->service->getAll(false);
        return $this->success($items, 'Testimonials retrieved successfully');
    }

    public function store(): array
    {
        $data = $this->getRequestData();
        $result = $this->service->create($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to create testimonial');
        }

        $item = $this->service->getById($result['id']);
        return $this->success($item, 'Testimonial created successfully', 201);
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
                return $this->notFound('Testimonial not found');
            }
            return $this->error($result['error'] ?? 'Failed to update testimonial');
        }

        $item = $this->service->getById((int)$id);
        return $this->success($item, 'Testimonial updated successfully');
    }

    public function destroy(string $id): array
    {
        $existing = $this->service->getById((int)$id);
        if (!$existing) {
            return $this->notFound('Testimonial not found');
        }

        $this->service->delete((int)$id);
        return $this->success(null, 'Testimonial deleted successfully');
    }
}
