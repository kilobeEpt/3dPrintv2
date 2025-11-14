<?php
/**
 * Telegram Integration Test Script
 * 
 * This script tests the Telegram integration by:
 * 1. Loading environment variables
 * 2. Testing bot token validity
 * 3. Retrieving chat IDs
 * 4. Sending a test message
 * 
 * Usage: php test-telegram.php
 */

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Helpers\TelegramService;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
try {
    $dotenv->load();
} catch (Exception $e) {
    echo "⚠️  Warning: .env file not found. Using environment variables if set.\n\n";
}

// Get Telegram credentials
$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
$chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? '';

echo "=== Telegram Integration Test ===\n\n";

// Check if credentials are configured
if (empty($botToken)) {
    echo "❌ TELEGRAM_BOT_TOKEN is not configured\n";
    echo "   Please set it in .env file or environment variables\n";
    exit(1);
}

echo "✅ Bot token configured: " . substr($botToken, 0, 6) . "..." . substr($botToken, -3) . "\n";

if (empty($chatId)) {
    echo "⚠️  TELEGRAM_CHAT_ID is not configured\n";
    echo "   Will attempt to discover chat IDs from updates\n\n";
} else {
    echo "✅ Chat ID configured: {$chatId}\n\n";
}

// Create Telegram service
$telegram = new TelegramService($botToken, $chatId);

// Test 1: Check bot token validity
echo "--- Test 1: Checking bot token validity ---\n";
$connectionTest = $telegram->testConnection();

if ($connectionTest['success']) {
    echo "✅ Bot token is valid\n";
    $bot = $connectionTest['bot'];
    echo "   Bot ID: {$bot['id']}\n";
    echo "   Bot name: {$bot['first_name']}\n";
    echo "   Bot username: @{$bot['username']}\n\n";
} else {
    echo "❌ Bot token is invalid\n";
    echo "   Error: {$connectionTest['error']}\n";
    exit(1);
}

// Test 2: Get chat IDs from updates
echo "--- Test 2: Retrieving chat IDs from bot updates ---\n";
$updatesResult = $telegram->getUpdates(10);

if ($updatesResult['success']) {
    $chatIds = $updatesResult['chat_ids'];
    $count = count($chatIds);
    
    if ($count > 0) {
        echo "✅ Found {$count} chat ID(s):\n";
        foreach ($chatIds as $chat) {
            echo "   - ID: {$chat['id']}, Type: {$chat['type']}, Title: {$chat['title']}\n";
        }
        echo "\n";
        
        // If no chat ID configured, suggest using one
        if (empty($chatId) && $count > 0) {
            $suggestedId = $chatIds[0]['id'];
            echo "💡 Suggestion: Set TELEGRAM_CHAT_ID={$suggestedId} in your .env file\n\n";
        }
    } else {
        echo "⚠️  No chat IDs found\n";
        echo "   Please send a message to your bot first:\n";
        echo "   1. Open Telegram\n";
        echo "   2. Search for @{$bot['username']}\n";
        echo "   3. Start a chat and send any message\n";
        echo "   4. Run this script again\n\n";
    }
} else {
    echo "❌ Failed to retrieve updates\n";
    echo "   Error: {$updatesResult['error']}\n\n";
}

// Test 3: Send test message (only if chat ID is configured)
if (!empty($chatId)) {
    echo "--- Test 3: Sending test message ---\n";
    $testResult = $telegram->sendTestMessage();
    
    if ($testResult['success']) {
        echo "✅ Test message sent successfully\n";
        echo "   Message ID: {$testResult['message_id']}\n";
        echo "   Check your Telegram chat to see the message\n\n";
    } else {
        echo "❌ Failed to send test message\n";
        echo "   Error: {$testResult['error']}\n\n";
    }
} else {
    echo "--- Test 3: Sending test message ---\n";
    echo "⏭️  Skipped (no chat ID configured)\n\n";
}

// Test 4: Send order notification (only if chat ID is configured)
if (!empty($chatId)) {
    echo "--- Test 4: Sending mock order notification ---\n";
    
    $mockOrder = [
        'order_number' => 'TEST-' . date('YmdHis'),
        'type' => 'order',
        'status' => 'new',
        'client_name' => 'Тестовый Клиент',
        'client_email' => 'test@example.com',
        'client_phone' => '+71234567890',
        'telegram' => '@test_user',
        'service' => '3D печать FDM',
        'subject' => 'Тестовый заказ',
        'message' => 'Это тестовое сообщение для проверки уведомлений о заказах.',
        'amount' => 1500.00,
        'calculator_data' => [
            'material' => 'PLA',
            'weight' => 50,
            'volume' => 45,
            'quality' => 'Высокое',
            'quantity' => 2,
            'additionalServices' => ['Постобработка', 'Покраска'],
            'total' => 1500.00
        ],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $orderResult = $telegram->sendOrderNotification($mockOrder);
    
    if ($orderResult['success']) {
        echo "✅ Order notification sent successfully\n";
        echo "   Message ID: {$orderResult['message_id']}\n";
        echo "   Check your Telegram chat to see the order notification\n\n";
    } else {
        echo "❌ Failed to send order notification\n";
        echo "   Error: {$orderResult['error']}\n\n";
    }
} else {
    echo "--- Test 4: Sending mock order notification ---\n";
    echo "⏭️  Skipped (no chat ID configured)\n\n";
}

echo "=== Test Complete ===\n";

if (!$telegram->isEnabled()) {
    echo "\n❌ Telegram integration is NOT fully configured\n";
    echo "   Required: TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID\n";
    exit(1);
} else {
    echo "\n✅ Telegram integration is fully configured and working!\n";
    exit(0);
}
