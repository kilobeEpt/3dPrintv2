<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\ContentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContentController
{
    private ContentService $service;

    public function __construct()
    {
        $this->service = new ContentService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $content = $this->service->getAll();
        return Response::success($content, 'Content sections retrieved successfully');
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $sectionKey = $args['section'];
        $content = $this->service->getBySection($sectionKey);

        if (!$content) {
            return Response::notFound('Content section not found');
        }

        return Response::success($content, 'Content section retrieved successfully');
    }

    public function upsert(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $sectionKey = $args['section'];
        $data = $request->getParsedBody();
        
        $result = $this->service->upsert($sectionKey, $data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to update content section');
        }

        $content = $this->service->getBySection($sectionKey);

        return Response::success($content, 'Content section updated successfully');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $sectionKey = $args['section'];
        
        $existing = $this->service->getBySection($sectionKey);
        if (!$existing) {
            return Response::notFound('Content section not found');
        }

        $this->service->delete($sectionKey);

        return Response::success(null, 'Content section deleted successfully');
    }

    public function getStats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $stats = $this->service->getStats();
        
        if (!$stats) {
            return Response::notFound('Stats not found');
        }

        return Response::success($stats, 'Stats retrieved successfully');
    }

    public function updateStats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $result = $this->service->updateStats($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to update stats');
        }

        $stats = $this->service->getStats();

        return Response::success($stats, 'Stats updated successfully');
    }
}
