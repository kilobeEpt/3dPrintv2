<?php

namespace App\Controllers;

use App\Services\OrdersService;

class OrdersController
{
    use BaseController;
    
    private OrdersService $service;

    public function __construct(OrdersService $service = null)
    {
        $this->service = $service ?? new OrdersService();
    }

    /**
     * Public endpoint: Submit a new order or contact form
     */
    public function submit(): array
    {
        $data = $this->getRequestData();
        
        // Get client IP address for rate limiting
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $result = $this->service->create($data, $ipAddress);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            return $this->error($result['error'] ?? 'Failed to create order');
        }

        $order = $this->service->getById($result['id']);

        return $this->success([
            'order' => $order,
            'telegram_sent' => $result['telegram_sent'] ?? false
        ], 'Order submitted successfully', 201);
    }

    /**
     * Admin endpoint: List all orders with pagination and filters
     */
    public function index(): array
    {
        $queryParams = $this->getQueryParams();

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

        return $this->success($result, 'Orders retrieved successfully');
    }

    /**
     * Admin endpoint: Get single order by ID
     */
    public function show(string $id): array
    {
        $order = $this->service->getById((int)$id);

        if (!$order) {
            return $this->notFound('Order not found');
        }

        return $this->success($order, 'Order retrieved successfully');
    }

    /**
     * Admin endpoint: Update an order
     */
    public function update(string $id): array
    {
        $data = $this->getRequestData();
        $result = $this->service->update((int)$id, $data);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                return $this->validationError($result['errors']);
            }
            if ($result['error'] === 'Order not found') {
                return $this->notFound('Order not found');
            }
            return $this->error($result['error'] ?? 'Failed to update order');
        }

        $order = $this->service->getById((int)$id);
        return $this->success($order, 'Order updated successfully');
    }

    /**
     * Admin endpoint: Delete an order
     */
    public function destroy(string $id): array
    {
        $existing = $this->service->getById((int)$id);
        if (!$existing) {
            return $this->notFound('Order not found');
        }

        $this->service->delete((int)$id);
        return $this->success(null, 'Order deleted successfully');
    }

    /**
     * Admin endpoint: Resend Telegram notification for an order
     */
    public function resendTelegram(string $id): array
    {
        $result = $this->service->resendTelegram((int)$id);

        if (!$result['success']) {
            if ($result['error'] === 'Order not found') {
                return $this->notFound('Order not found');
            }
            return $this->error($result['error'] ?? 'Failed to send Telegram notification');
        }

        return $this->success([
            'message_id' => $result['message_id'] ?? null
        ], 'Telegram notification sent successfully');
    }
}
