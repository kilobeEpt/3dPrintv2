<?php

namespace App\Controllers;

use App\Services\ContentService;

class ContentController
{
    use BaseController;
    
    private ContentService $service;

    public function __construct()
    {
        $this->service = new ContentService();
    }

    public function index(): array
    {
        $content = $this->service->getAll();
        return $this->success($content, 'Content sections retrieved successfully');
    }

    public function show(string $section): array
    {
        $content = $this->service->getBySection($section);

        if (!$content) {
            return $this->notFound('Content section not found');
        }

        return $this->success($content, 'Content section retrieved successfully');
    }

    public function upsert(string $section): array
    {
        $data = $this->getRequestData();
        $result = $this->service->upsert($section, $data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to update content section');
        }

        $content = $this->service->getBySection($section);
        return $this->success($content, 'Content section updated successfully');
    }

    public function destroy(string $section): array
    {
        $existing = $this->service->getBySection($section);
        if (!$existing) {
            return $this->notFound('Content section not found');
        }

        $this->service->delete($section);
        return $this->success(null, 'Content section deleted successfully');
    }

    public function getStats(): array
    {
        $stats = $this->service->getStats();
        
        if (!$stats) {
            return $this->notFound('Stats not found');
        }

        return $this->success($stats, 'Stats retrieved successfully');
    }

    public function updateStats(): array
    {
        $data = $this->getRequestData();
        $result = $this->service->updateStats($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to update stats');
        }

        $stats = $this->service->getStats();
        return $this->success($stats, 'Stats updated successfully');
    }
}
