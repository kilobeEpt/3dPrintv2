<?php

namespace App\Controllers;

use App\Helpers\TelegramService;

class TelegramController
{
    use BaseController;
    
    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Admin endpoint: Send a test message to verify Telegram integration
     * POST /api/telegram/test
     */
    public function test(): array
    {
        if (!$this->telegramService->isEnabled()) {
            return $this->error('Telegram integration is not enabled. Please configure bot token and chat ID in settings.');
        }

        $result = $this->telegramService->sendTestMessage();

        if (!$result['success']) {
            return $this->error($result['error'] ?? 'Failed to send test message');
        }

        return $this->success([
            'message_id' => $result['message_id'] ?? null,
            'message' => 'Test message sent successfully'
        ], 'Telegram test message sent');
    }

    /**
     * Admin endpoint: Retrieve recent updates to discover chat IDs
     * GET /api/telegram/chat-id
     */
    public function getChatId(): array
    {
        $queryParams = $this->getQueryParams();
        $limit = min((int)($queryParams['limit'] ?? 10), 100);
        $offset = (int)($queryParams['offset'] ?? 0);

        $result = $this->telegramService->getUpdates($limit, $offset);

        if (!$result['success']) {
            return $this->error($result['error'] ?? 'Failed to retrieve updates');
        }

        $chatIds = $result['chat_ids'] ?? [];
        
        if (empty($chatIds)) {
            return $this->success([
                'chat_ids' => [],
                'count' => 0,
                'message' => 'No chat IDs found. Please send a message to your bot first, then try again.'
            ], 'No updates available');
        }

        return $this->success([
            'chat_ids' => $chatIds,
            'count' => count($chatIds),
            'message' => 'Chat IDs retrieved successfully. Use one of these IDs in your Telegram settings.'
        ], 'Chat IDs retrieved');
    }

    /**
     * Admin endpoint: Test bot token validity
     * GET /api/telegram/status
     */
    public function status(): array
    {
        if (!$this->telegramService->isEnabled()) {
            return $this->success([
                'enabled' => false,
                'configured' => false,
                'message' => 'Telegram integration is not configured'
            ], 'Telegram status retrieved');
        }

        $connectionTest = $this->telegramService->testConnection();

        if (!$connectionTest['success']) {
            return $this->success([
                'enabled' => true,
                'configured' => true,
                'connected' => false,
                'error' => $connectionTest['error'] ?? 'Connection failed',
                'message' => 'Telegram integration is configured but connection failed'
            ], 'Telegram status retrieved');
        }

        return $this->success([
            'enabled' => true,
            'configured' => true,
            'connected' => true,
            'bot' => $connectionTest['bot'] ?? null,
            'message' => 'Telegram integration is working properly'
        ], 'Telegram status retrieved');
    }
}
