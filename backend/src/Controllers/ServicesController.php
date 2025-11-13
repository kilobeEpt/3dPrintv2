<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\ServicesService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServicesController
{
    private ServicesService $service;

    public function __construct()
    {
        $this->service = new ServicesService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $services = $this->service->getAll(true);
        return Response::success($services, 'Services retrieved successfully');
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $service = $this->service->getById($id);

        if (!$service) {
            return Response::notFound('Service not found');
        }

        return Response::success($service, 'Service retrieved successfully');
    }

    public function adminIndex(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $services = $this->service->getAll(false);
        return Response::success($services, 'Services retrieved successfully');
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $result = $this->service->create($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to create service');
        }

        $service = $this->service->getById($result['id']);

        return Response::success($service, 'Service created successfully', 201);
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
            if ($result['error'] === 'Service not found') {
                return Response::notFound('Service not found');
            }
            return Response::badRequest($result['error'] ?? 'Failed to update service');
        }

        $service = $this->service->getById($id);

        return Response::success($service, 'Service updated successfully');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        
        $existing = $this->service->getById($id);
        if (!$existing) {
            return Response::notFound('Service not found');
        }

        $this->service->delete($id);

        return Response::success(null, 'Service deleted successfully');
    }
}
