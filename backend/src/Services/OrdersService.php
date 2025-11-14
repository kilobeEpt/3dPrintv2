<?php

namespace App\Services;

use App\Repositories\OrdersRepository;
use App\Helpers\Validator;
use App\Helpers\TelegramService;

class OrdersService
{
    private OrdersRepository $repository;
    private ?TelegramService $telegramService;

    public function __construct(?TelegramService $telegramService = null)
    {
        $this->repository = new OrdersRepository();
        $this->telegramService = $telegramService;
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        // Validate pagination parameters
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage)); // Max 100 items per page

        return $this->repository->findAll($filters, $page, $perPage);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function create(array $data, ?string $ipAddress = null): array
    {
        // Rate limiting check
        if ($ipAddress && $this->isRateLimited($ipAddress)) {
            return [
                'success' => false,
                'errors' => ['rate_limit' => 'Too many requests. Please try again later.']
            ];
        }

        $validator = new Validator();

        if (!$validator->validate($data, $this->getCreateValidationRules())) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        // Generate unique order number
        $orderNumber = $this->generateUniqueOrderNumber();
        $data['order_number'] = $orderNumber;

        // Determine type based on data
        if (!isset($data['type'])) {
            $data['type'] = !empty($data['calculator_data']) ? 'order' : 'contact';
        }

        // Extract amount from calculator data if present
        if (!isset($data['amount']) && !empty($data['calculator_data']['total'])) {
            $data['amount'] = $data['calculator_data']['total'];
        }

        $orderId = $this->repository->create($data);

        // Send Telegram notification asynchronously (or at least after creation)
        $telegramSent = false;
        if ($this->telegramService && $this->telegramService->isEnabled()) {
            $order = $this->repository->findById($orderId);
            $result = $this->telegramService->sendOrderNotification($order);
            $telegramSent = $result['success'] ?? false;
            
            // Update telegram_sent status
            $this->repository->updateTelegramStatus($orderId, $telegramSent);
        }

        return [
            'success' => true,
            'id' => $orderId,
            'order_number' => $orderNumber,
            'telegram_sent' => $telegramSent
        ];
    }

    public function update(int $id, array $data): array
    {
        $existing = $this->repository->findById($id);

        if (!$existing) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        $validator = new Validator();

        if (!$validator->validate($data, $this->getUpdateValidationRules())) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        $this->repository->update($id, $data);

        return ['success' => true];
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function resendTelegram(int $id): array
    {
        $order = $this->repository->findById($id);

        if (!$order) {
            return ['success' => false, 'error' => 'Order not found'];
        }

        if (!$this->telegramService || !$this->telegramService->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Telegram integration is not enabled'
            ];
        }

        $result = $this->telegramService->sendOrderNotification($order);

        if ($result['success']) {
            $this->repository->updateTelegramStatus($id, true);
        }

        return $result;
    }

    private function generateUniqueOrderNumber(): string
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            $orderNumber = $this->repository->generateOrderNumber();
            $exists = $this->repository->orderNumberExists($orderNumber);
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);

        if ($exists) {
            // Fallback: add random suffix
            $orderNumber .= '-' . strtoupper(substr(md5(uniqid()), 0, 4));
        }

        return $orderNumber;
    }

    private function isRateLimited(string $ipAddress): bool
    {
        // Simple rate limiting: max 5 orders per hour per IP
        $recentOrders = $this->repository->getRecentOrdersByIp($ipAddress, 60);
        return $recentOrders >= 5;
    }

    private function getCreateValidationRules(): array
    {
        return [
            'client_name' => 'required|string|min:2|max:100',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'required|string|min:10|max:30',
            'telegram' => 'string|max:100',
            'service' => 'string|max:255',
            'subject' => 'string|max:255',
            'message' => 'string|max:5000',
            'type' => 'in:order,contact',
            'amount' => 'numeric|min:0',
            'calculator_data' => 'array'
        ];
    }

    private function getUpdateValidationRules(): array
    {
        return [
            'type' => 'in:order,contact',
            'status' => 'in:new,processing,completed,cancelled',
            'client_name' => 'string|min:2|max:100',
            'client_email' => 'email|max:255',
            'client_phone' => 'string|min:10|max:30',
            'telegram' => 'string|max:100',
            'service' => 'string|max:255',
            'subject' => 'string|max:255',
            'message' => 'string|max:5000',
            'amount' => 'numeric|min:0',
            'calculator_data' => 'array',
            'telegram_sent' => 'boolean'
        ];
    }
}
