<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\TelegramService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TelegramController
{
    private TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Admin endpoint: Send a test message to verify Telegram integration
     * POST /api/telegram/test
     */
    public function test(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->telegramService->isEnabled()) {
            return Response::badRequest('Telegram integration is not enabled. Please configure bot token and chat ID in settings.');
        }

        $result = $this->telegramService->sendTestMessage();

        if (!$result['success']) {
            return Response::badRequest($result['error'] ?? 'Failed to send test message');
        }

        return Response::success([
            'message_id' => $result['message_id'] ?? null,
            'message' => 'Test message sent successfully'
        ], 'Telegram test message sent');
    }

    /**
     * Admin endpoint: Retrieve recent updates to discover chat IDs
     * GET /api/telegram/chat-id
     */
    public function getChatId(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $limit = min((int)($queryParams['limit'] ?? 10), 100);
        $offset = (int)($queryParams['offset'] ?? 0);

        $result = $this->telegramService->getUpdates($limit, $offset);

        if (!$result['success']) {
            return Response::badRequest($result['error'] ?? 'Failed to retrieve updates');
        }

        $chatIds = $result['chat_ids'] ?? [];
        
        if (empty($chatIds)) {
            return Response::success([
                'chat_ids' => [],
                'count' => 0,
                'message' => 'No chat IDs found. Please send a message to your bot first, then try again.'
            ], 'No updates available');
        }

        return Response::success([
            'chat_ids' => $chatIds,
            'count' => count($chatIds),
            'message' => 'Chat IDs retrieved successfully. Use one of these IDs in your Telegram settings.'
        ], 'Chat IDs retrieved');
    }

    /**
     * Admin endpoint: Test bot token validity
     * GET /api/telegram/status
     */
    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->telegramService->isEnabled()) {
            return Response::success([
                'enabled' => false,
                'configured' => false,
                'message' => 'Telegram integration is not configured'
            ], 'Telegram status retrieved');
        }

        $connectionTest = $this->telegramService->testConnection();

        if (!$connectionTest['success']) {
            return Response::success([
                'enabled' => true,
                'configured' => true,
                'connected' => false,
                'error' => $connectionTest['error'] ?? 'Connection failed',
                'message' => 'Telegram integration is configured but connection failed'
            ], 'Telegram status retrieved');
        }

        return Response::success([
            'enabled' => true,
            'configured' => true,
            'connected' => true,
            'bot' => $connectionTest['bot'] ?? null,
            'message' => 'Telegram integration is working properly'
        ], 'Telegram status retrieved');
    }
}
