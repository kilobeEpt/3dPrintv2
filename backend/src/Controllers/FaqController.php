<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\FaqService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FaqController
{
    private FaqService $service;

    public function __construct()
    {
        $this->service = new FaqService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $faqs = $this->service->getAll(true);
        return Response::success($faqs, 'FAQ items retrieved successfully');
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $faq = $this->service->getById($id);

        if (!$faq) {
            return Response::notFound('FAQ item not found');
        }

        return Response::success($faq, 'FAQ item retrieved successfully');
    }

    public function adminIndex(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $faqs = $this->service->getAll(false);
        return Response::success($faqs, 'FAQ items retrieved successfully');
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $result = $this->service->create($data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to create FAQ item');
        }

        $faq = $this->service->getById($result['id']);

        return Response::success($faq, 'FAQ item created successfully', 201);
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
            if ($result['error'] === 'FAQ not found') {
                return Response::notFound('FAQ item not found');
            }
            return Response::badRequest($result['error'] ?? 'Failed to update FAQ item');
        }

        $faq = $this->service->getById($id);

        return Response::success($faq, 'FAQ item updated successfully');
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        
        $existing = $this->service->getById($id);
        if (!$existing) {
            return Response::notFound('FAQ item not found');
        }

        $this->service->delete($id);

        return Response::success(null, 'FAQ item deleted successfully');
    }
}
