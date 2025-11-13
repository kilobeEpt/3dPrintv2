<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\TestimonialsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TestimonialsController
{
    private TestimonialsService $service;

    public function __construct()
    {
        $this->service = new TestimonialsService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $testimonials = $this->service->getAll(true);
        return Response::success($testimonials, 'Testimonials retrieved successfully');
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $testimonial = $this->service->getById($id);

        if (!$testimonial) {
            return Response::notFound('Testimonial not found');
        }

        return Response::success($testimonial, 'Testimonial retrieved successfully');
    }

    public function adminIndex(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $testimonials = $this->service->getAll(false);
        return Response::success($testimonials, 'Testimonials retrieved successfully');
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $result = $this->service->create($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to create testimonial');
        }

        $testimonial = $this->service->getById($result['id']);

        return Response::success($testimonial, 'Testimonial created successfully', 201);
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
            if ($result['error'] === 'Testimonial not found') {
                return Response::notFound('Testimonial not found');
            }
            return Response::badRequest($result['error'] ?? 'Failed to update testimonial');
        }

        $testimonial = $this->service->getById($id);

        return Response::success($testimonial, 'Testimonial updated successfully');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        
        $existing = $this->service->getById($id);
        if (!$existing) {
            return Response::notFound('Testimonial not found');
        }

        $this->service->delete($id);

        return Response::success(null, 'Testimonial deleted successfully');
    }
}
