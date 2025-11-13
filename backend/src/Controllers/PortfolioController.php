<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\PortfolioService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PortfolioController
{
    private PortfolioService $service;

    public function __construct()
    {
        $this->service = new PortfolioService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $category = $queryParams['category'] ?? null;
        
        $portfolio = $this->service->getAll($category);
        return Response::success($portfolio, 'Portfolio items retrieved successfully');
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $item = $this->service->getById($id);

        if (!$item) {
            return Response::notFound('Portfolio item not found');
        }

        return Response::success($item, 'Portfolio item retrieved successfully');
    }

    public function categories(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $categories = $this->service->getCategories();
        return Response::success($categories, 'Categories retrieved successfully');
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $result = $this->service->create($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to create portfolio item');
        }

        $item = $this->service->getById($result['id']);

        return Response::success($item, 'Portfolio item created successfully', 201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = $request->getParsedBody();
        
        $result = $this->service->update($id, $data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            if ($result['error'] === 'Portfolio item not found') {
                return Response::notFound('Portfolio item not found');
            }
            return Response::badRequest($result['error'] ?? 'Failed to update portfolio item');
        }

        $item = $this->service->getById($id);

        return Response::success($item, 'Portfolio item updated successfully');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        
        $existing = $this->service->getById($id);
        if (!$existing) {
            return Response::notFound('Portfolio item not found');
        }

        $this->service->delete($id);

        return Response::success(null, 'Portfolio item deleted successfully');
    }
}
