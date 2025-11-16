<?php

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

$auth = new Auth();
$auth->checkAuth();

if (strpos($path, '/api/telegram/status') !== false && $method === 'GET') {
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? '';
    
    $configured = !empty($botToken) && !empty($chatId);
    
    $response = [
        'configured' => $configured,
        'bot_token_set' => !empty($botToken),
        'chat_id_set' => !empty($chatId)
    ];
    
    if ($configured) {
        try {
            $url = "https://api.telegram.org/bot{$botToken}/getMe";
            $result = @file_get_contents($url);
            
            if ($result) {
                $data = json_decode($result, true);
                if (isset($data['ok']) && $data['ok']) {
                    $response['bot_info'] = $data['result'];
                    $response['status'] = 'connected';
                } else {
                    $response['status'] = 'error';
                    $response['error'] = 'Invalid bot token';
                }
            } else {
                $response['status'] = 'error';
                $response['error'] = 'Cannot connect to Telegram API';
            }
        } catch (Exception $e) {
            $response['status'] = 'error';
            $response['error'] = $e->getMessage();
        }
    } else {
        $response['status'] = 'not_configured';
    }
    
    Response::success($response);
}

if (strpos($path, '/api/telegram/test') !== false && $method === 'POST') {
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? '';
    
    if (empty($botToken) || empty($chatId)) {
        Response::badRequest('Telegram is not configured');
    }
    
    try {
        $text = "🧪 Test message from 3D Print Service\n\n";
        $text .= "✅ Telegram integration is working correctly!\n";
        $text .= "📅 Time: " . date('Y-m-d H:i:s');
        
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $result = file_get_contents($url, false, stream_context_create($options));
        $response = json_decode($result, true);
        
        if (isset($response['ok']) && $response['ok']) {
            Response::success($response, 'Test message sent successfully');
        } else {
            Response::error('Failed to send message: ' . ($response['description'] ?? 'Unknown error'), 500);
        }
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}

if (strpos($path, '/api/telegram/send') !== false && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $message = $input['message'] ?? '';
    
    if (empty($message)) {
        Response::badRequest('Message is required');
    }
    
    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? '';
    
    if (empty($botToken) || empty($chatId)) {
        Response::badRequest('Telegram is not configured');
    }
    
    try {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $result = file_get_contents($url, false, stream_context_create($options));
        $response = json_decode($result, true);
        
        if (isset($response['ok']) && $response['ok']) {
            Response::success($response, 'Message sent successfully');
        } else {
            Response::error('Failed to send message: ' . ($response['description'] ?? 'Unknown error'), 500);
        }
    } catch (Exception $e) {
        Response::serverError($e->getMessage());
    }
}

Response::notFound('Telegram endpoint not found');
