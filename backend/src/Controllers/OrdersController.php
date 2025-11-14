<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Services\OrdersService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OrdersController
{
    private OrdersService $service;

    public function __construct(OrdersService $service = null)
    {
        $this->service = $service ?? new OrdersService();
    }

    /**
     * Public endpoint: Submit a new order or contact form
     */
    public function submit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        // Get client IP address for rate limiting
        $serverParams = $request->getServerParams();
        $ipAddress = $serverParams['REMOTE_ADDR'] ?? null;
        
        $result = $this->service->create($data, $ipAddress);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            return Response::badRequest($result['error'] ?? 'Failed to create order');
        }

        $order = $this->service->getById($result['id']);

        return Response::success([
            'order' => $order,
            'telegram_sent' => $result['telegram_sent'] ?? false
        ], 'Order submitted successfully', 201);
    }

    /**
     * Admin endpoint: List all orders with pagination and filters
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $filters = [
            'status' => $queryParams['status'] ?? null,
            'type' => $queryParams['type'] ?? null,
            'search' => $queryParams['search'] ?? null,
            'date_from' => $queryParams['date_from'] ?? null,
            'date_to' => $queryParams['date_to'] ?? null,
        ];

        $page = (int) ($queryParams['page'] ?? 1);
        $perPage = (int) ($queryParams['per_page'] ?? 20);

        $result = $this->service->getAll($filters, $page, $perPage);

        return Response::success($result, 'Orders retrieved successfully');
    }

    /**
     * Admin endpoint: Get single order by ID
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $order = $this->service->getById($id);

        if (!$order) {
            return Response::notFound('Order not found');
        }

        return Response::success($order, 'Order retrieved successfully');
    }

    /**
     * Admin endpoint: Update an order
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = $request->getParsedBody();

        $result = $this->service->update($id, $data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return Response::validationError($result['errors']);
            }
            if ($result['error'] === 'Order not found') {
                return Response::notFound('Order not found');
            }
            return Response::badRequest($result['error'] ?? 'Failed to update order');
        }

        $order = $this->service->getById($id);

        return Response::success($order, 'Order updated successfully');
    }

    /**
     * Admin endpoint: Delete an order
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];

        $existing = $this->service->getById($id);
        if (!$existing) {
            return Response::notFound('Order not found');
        }

        $this->service->delete($id);

        return Response::success(null, 'Order deleted successfully');
    }

    /**
     * Admin endpoint: Resend Telegram notification for an order
     */
    public function resendTelegram(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];

        $result = $this->service->resendTelegram($id);

        if (!$result['success']) {
            if ($result['error'] === 'Order not found') {
                return Response::notFound('Order not found');
            }
            return Response::badRequest($result['error'] ?? 'Failed to send Telegram notification');
        }

        return Response::success([
            'message_id' => $result['message_id'] ?? null
        ], 'Telegram notification sent successfully');
    }
}
