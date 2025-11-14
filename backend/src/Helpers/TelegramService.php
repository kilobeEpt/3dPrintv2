<?php

namespace App\Helpers;

class TelegramService
{
    private string $botToken;
    private string $chatId;
    private bool $enabled;

    public function __construct(string $botToken = '', string $chatId = '')
    {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->enabled = !empty($botToken) && !empty($chatId);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function sendOrderNotification(array $order): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'error' => 'Telegram integration is not enabled'
            ];
        }

        $message = $this->formatOrderMessage($order);
        return $this->sendMessage($message);
    }

    private function formatOrderMessage(array $order): string
    {
        $type = $order['type'] === 'order' ? '📦 Новый заказ' : '✉️ Новое обращение';
        $status = $this->getStatusEmoji($order['status']);
        
        $message = "{$type}\n\n";
        $message .= "📋 Номер: {$order['order_number']}\n";
        $message .= "📊 Статус: {$status} {$order['status']}\n\n";
        
        $message .= "👤 Клиент:\n";
        $message .= "   Имя: {$order['client_name']}\n";
        $message .= "   Email: {$order['client_email']}\n";
        $message .= "   Телефон: {$order['client_phone']}\n";
        
        if (!empty($order['telegram'])) {
            $message .= "   Telegram: {$order['telegram']}\n";
        }
        
        if (!empty($order['service'])) {
            $message .= "\n🛠 Услуга: {$order['service']}\n";
        }
        
        if (!empty($order['subject'])) {
            $message .= "\n📌 Тема: {$order['subject']}\n";
        }
        
        if (!empty($order['message'])) {
            $message .= "\n💬 Сообщение:\n{$order['message']}\n";
        }
        
        if (!empty($order['amount']) && $order['amount'] > 0) {
            $message .= "\n💰 Сумма: " . number_format($order['amount'], 2, '.', ' ') . " ₽\n";
        }
        
        if (!empty($order['calculator_data'])) {
            $message .= "\n📐 Данные калькулятора:\n";
            $message .= $this->formatCalculatorData($order['calculator_data']);
        }
        
        $message .= "\n🕐 Создано: {$order['created_at']}\n";
        
        return $message;
    }

    private function formatCalculatorData(array $data): string
    {
        $result = '';
        
        if (!empty($data['material'])) {
            $result .= "   Материал: {$data['material']}\n";
        }
        
        if (!empty($data['weight'])) {
            $result .= "   Вес: {$data['weight']} г\n";
        }
        
        if (!empty($data['volume'])) {
            $result .= "   Объем: {$data['volume']} см³\n";
        }
        
        if (!empty($data['quality'])) {
            $result .= "   Качество: {$data['quality']}\n";
        }
        
        if (!empty($data['quantity'])) {
            $result .= "   Количество: {$data['quantity']} шт\n";
        }
        
        if (!empty($data['additionalServices']) && is_array($data['additionalServices'])) {
            $result .= "   Доп. услуги: " . implode(', ', $data['additionalServices']) . "\n";
        }
        
        if (!empty($data['total'])) {
            $result .= "   Итого: " . number_format($data['total'], 2, '.', ' ') . " ₽\n";
        }
        
        return $result ?: "   Нет данных\n";
    }

    private function getStatusEmoji(string $status): string
    {
        $emojis = [
            'new' => '🆕',
            'processing' => '⏳',
            'completed' => '✅',
            'cancelled' => '❌'
        ];

        return $emojis[$status] ?? '❓';
    }

    private function sendMessage(string $message): array
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'error' => "cURL error: {$curlError}"
            ];
        }

        $result = json_decode($response, true);

        if ($httpCode === 200 && !empty($result['ok'])) {
            return [
                'success' => true,
                'message_id' => $result['result']['message_id'] ?? null
            ];
        }

        return [
            'success' => false,
            'error' => $result['description'] ?? 'Unknown error',
            'error_code' => $result['error_code'] ?? $httpCode
        ];
    }

    public function testConnection(): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'error' => 'Telegram integration is not enabled'
            ];
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/getMe";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'error' => "cURL error: {$curlError}"
            ];
        }

        $result = json_decode($response, true);

        if ($httpCode === 200 && !empty($result['ok'])) {
            return [
                'success' => true,
                'bot' => $result['result']
            ];
        }

        return [
            'success' => false,
            'error' => $result['description'] ?? 'Unknown error'
        ];
    }
}
