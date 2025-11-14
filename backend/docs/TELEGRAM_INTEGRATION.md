# Telegram Integration Guide

This guide explains how to set up and use the Telegram Bot integration for receiving order notifications.

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Setup Process](#setup-process)
- [Configuration](#configuration)
- [API Endpoints](#api-endpoints)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Security Considerations](#security-considerations)

## Overview

The Telegram integration allows your 3D Print Pro application to send real-time notifications to a Telegram chat whenever:
- A new order is submitted
- A new contact form is received
- An admin manually resends a notification

All notifications are formatted with emojis and structured information for easy reading.

## Prerequisites

Before setting up the Telegram integration, you need:

1. A Telegram account
2. The Telegram mobile or desktop app
3. Admin access to your application

## Setup Process

### Step 1: Create a Telegram Bot

1. Open Telegram and search for `@BotFather` (official Telegram bot for creating bots)
2. Start a chat with BotFather by clicking "Start"
3. Send the command `/newbot`
4. Follow the prompts:
   - Choose a name for your bot (e.g., "3D Print Pro Notifications")
   - Choose a username (must end with 'bot', e.g., "my3dprint_bot")
5. BotFather will provide you with a **Bot Token** - save this token securely
   - Format: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`

### Step 2: Get Your Chat ID

There are two methods to get your Chat ID:

#### Method A: Using the Admin Panel (Recommended)

1. Start a chat with your new bot in Telegram (search for the bot username)
2. Send any message to the bot (e.g., "Hello")
3. Log in to your admin panel
4. Go to Settings → Telegram Integration
5. Enter your Bot Token and save
6. Click "Get Chat ID" button
7. The system will display available chat IDs - select the one you just messaged

#### Method B: Using the API Directly

1. Start a chat with your bot and send a message
2. Make an API request to discover your chat ID:

```bash
# First, configure your bot token via settings API or .env
# Then call the chat-id endpoint

curl -X GET http://your-domain.com/api/telegram/chat-id \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

Response:
```json
{
  "success": true,
  "message": "Chat IDs retrieved",
  "data": {
    "chat_ids": [
      {
        "id": 123456789,
        "type": "private",
        "title": "Your Name"
      }
    ],
    "count": 1,
    "message": "Chat IDs retrieved successfully. Use one of these IDs in your Telegram settings."
  }
}
```

3. Use the `id` value as your Chat ID

#### Method C: Manual Method

1. Send a message to your bot
2. Visit: `https://api.telegram.org/bot<YourBotToken>/getUpdates`
3. Look for the `"chat":{"id":123456789}` in the JSON response
4. Use that ID as your Chat ID

### Step 3: Configure the Application

#### Option A: Via Environment Variables

Edit your `.env` file:

```env
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=123456789
```

#### Option B: Via Admin Panel

1. Log in to your admin panel
2. Navigate to Settings → Telegram Integration
3. Enter your Bot Token
4. Enter your Chat ID
5. Click "Save Settings"

### Step 4: Test the Integration

1. In the admin panel, go to Settings → Telegram Integration
2. Click "Send Test Message"
3. Check your Telegram chat - you should receive a test message

Alternatively, use the API:

```bash
curl -X POST http://your-domain.com/api/telegram/test \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

## Configuration

### Environment Variables

| Variable | Description | Required | Example |
|----------|-------------|----------|---------|
| `TELEGRAM_BOT_TOKEN` | Bot token from BotFather | Yes | `123456789:ABCdefGHI...` |
| `TELEGRAM_CHAT_ID` | Chat ID to send messages to | Yes | `123456789` |

### Settings API

The Telegram configuration is managed through the Settings API:

```bash
# Get current settings
curl -X GET http://your-domain.com/api/settings \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Update Telegram settings
curl -X PUT http://your-domain.com/api/settings/telegram \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "bot_token": "123456789:ABCdefGHIjklMNOpqrsTUVwxyz",
    "chat_id": "123456789"
  }'
```

## API Endpoints

### Admin Endpoints

All endpoints require authentication with admin role.

#### Test Telegram Connection

Send a test message to verify the integration is working.

**Endpoint:** `POST /api/telegram/test`

**Headers:**
```
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Telegram test message sent",
  "data": {
    "message_id": 123,
    "message": "Test message sent successfully"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Telegram integration is not enabled. Please configure bot token and chat ID in settings."
}
```

#### Get Chat ID from Updates

Retrieve recent bot updates to discover available chat IDs.

**Endpoint:** `GET /api/telegram/chat-id`

**Query Parameters:**
- `limit` (optional): Number of updates to retrieve (default: 10, max: 100)
- `offset` (optional): Offset for pagination (default: 0)

**Headers:**
```
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Chat IDs retrieved",
  "data": {
    "chat_ids": [
      {
        "id": 123456789,
        "type": "private",
        "title": "John Doe"
      },
      {
        "id": -987654321,
        "type": "group",
        "title": "3D Print Team"
      }
    ],
    "count": 2,
    "message": "Chat IDs retrieved successfully. Use one of these IDs in your Telegram settings."
  }
}
```

**No Updates Response (200 OK):**
```json
{
  "success": true,
  "message": "No updates available",
  "data": {
    "chat_ids": [],
    "count": 0,
    "message": "No chat IDs found. Please send a message to your bot first, then try again."
  }
}
```

#### Check Telegram Status

Check the current status of the Telegram integration.

**Endpoint:** `GET /api/telegram/status`

**Headers:**
```
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Telegram status retrieved",
  "data": {
    "enabled": true,
    "configured": true,
    "connected": true,
    "bot": {
      "id": 123456789,
      "is_bot": true,
      "first_name": "3D Print Bot",
      "username": "my3dprint_bot",
      "can_join_groups": true,
      "can_read_all_group_messages": false,
      "supports_inline_queries": false
    },
    "message": "Telegram integration is working properly"
  }
}
```

**Not Configured Response (200 OK):**
```json
{
  "success": true,
  "message": "Telegram status retrieved",
  "data": {
    "enabled": false,
    "configured": false,
    "message": "Telegram integration is not configured"
  }
}
```

### Automatic Order Notifications

When a new order is submitted via `POST /api/orders`, the system automatically:

1. Creates the order in the database
2. Checks if Telegram integration is enabled
3. Sends a notification if configured
4. Updates the `telegram_sent` flag on the order
5. Logs any errors to the application log

**Order Notification Format:**

```
📦 Новый заказ

📋 Номер: ORD-20231113-0042
📊 Статус: 🆕 new

👤 Клиент:
   Имя: Иван Иванов
   Email: ivan@example.com
   Телефон: +71234567890
   Telegram: @ivan_user

🛠 Услуга: 3D печать FDM

💬 Сообщение:
Хочу напечатать прототип изделия

💰 Сумма: 1 500.00 ₽

📐 Данные калькулятора:
   Материал: PLA
   Вес: 50 г
   Объем: 45 см³
   Качество: Высокое
   Количество: 2 шт
   Доп. услуги: Постобработка, Покраска
   Итого: 1 500.00 ₽

🕐 Создано: 2023-11-13 14:30:45
```

**Contact Form Notification Format:**

```
✉️ Новое обращение

📋 Номер: ORD-20231113-0043
📊 Статус: 🆕 new

👤 Клиент:
   Имя: Мария Петрова
   Email: maria@example.com
   Телефон: +79876543210

📌 Тема: Вопрос о сроках

💬 Сообщение:
Здравствуйте! Интересует, какие сроки изготовления детали?

🕐 Создано: 2023-11-13 14:35:12
```

### Resend Notification

Admins can manually resend a Telegram notification for any order:

**Endpoint:** `POST /api/orders/{id}/resend-telegram`

**Headers:**
```
Authorization: Bearer YOUR_ADMIN_TOKEN
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Telegram notification sent successfully",
  "data": {
    "message_id": 124
  }
}
```

## Testing

### Test Checklist

1. **Bot Token Validation**
   ```bash
   curl -X GET http://your-domain.com/api/telegram/status \
     -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
   ```
   - Should return bot information if token is valid

2. **Chat ID Discovery**
   - Send a message to your bot
   ```bash
   curl -X GET http://your-domain.com/api/telegram/chat-id \
     -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
   ```
   - Should return your chat ID

3. **Test Message**
   ```bash
   curl -X POST http://your-domain.com/api/telegram/test \
     -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
   ```
   - Should receive test message in Telegram

4. **Order Notification**
   ```bash
   curl -X POST http://your-domain.com/api/orders \
     -H "Content-Type: application/json" \
     -d '{
       "client_name": "Test User",
       "client_email": "test@example.com",
       "client_phone": "+71234567890",
       "message": "Test order"
     }'
   ```
   - Should receive order notification in Telegram

5. **Resend Notification**
   ```bash
   curl -X POST http://your-domain.com/api/orders/1/resend-telegram \
     -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
   ```
   - Should resend the notification for order #1

## Troubleshooting

### Common Issues

#### 1. "Telegram integration is not enabled"

**Cause:** Bot token or chat ID is not configured.

**Solution:**
- Check `.env` file has `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID`
- Or configure via admin panel
- Verify values are correct (no extra spaces or quotes)

#### 2. "Unauthorized" or "Bot token is invalid"

**Cause:** Invalid or expired bot token.

**Solution:**
- Verify bot token from BotFather
- Create a new bot if necessary
- Update token in settings

#### 3. "Chat not found" or "Forbidden: bot was blocked by the user"

**Cause:** Invalid chat ID or bot was blocked.

**Solution:**
- Verify chat ID is correct
- Start a chat with your bot (click "Start")
- Unblock the bot if you blocked it
- Use `/api/telegram/chat-id` endpoint to get correct ID

#### 4. Notifications not sending on order creation

**Cause:** Multiple possible reasons.

**Solution:**
1. Check Telegram status:
   ```bash
   curl -X GET http://your-domain.com/api/telegram/status \
     -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
   ```
2. Check application logs for errors:
   ```bash
   tail -f storage/logs/app.log | grep Telegram
   ```
3. Test with manual send:
   ```bash
   curl -X POST http://your-domain.com/api/telegram/test \
     -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
   ```

#### 5. Rate limiting or timeout errors

**Cause:** Telegram API rate limits or network issues.

**Solution:**
- Wait a few seconds and retry
- Check your internet connection
- Verify firewall allows outbound HTTPS to api.telegram.org

### Debug Mode

To see detailed error messages, enable debug mode in `.env`:

```env
APP_DEBUG=true
```

Then check application logs at `storage/logs/app.log` for Telegram-related errors:

```bash
grep "\[Telegram\]" storage/logs/app.log
```

### Testing Bot Token Manually

You can test your bot token directly with Telegram API:

```bash
# Get bot information
curl https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getMe

# Get recent updates (to find chat ID)
curl https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates

# Send test message
curl -X POST https://api.telegram.org/bot<YOUR_BOT_TOKEN>/sendMessage \
  -d "chat_id=<YOUR_CHAT_ID>&text=Test message"
```

## Security Considerations

### Best Practices

1. **Keep Bot Token Secret**
   - Never commit bot tokens to version control
   - Use `.env` file and add it to `.gitignore`
   - Rotate tokens periodically

2. **Limit Bot Permissions**
   - Bot only needs to send messages
   - Don't add bot to public groups unless necessary
   - Review bot settings in BotFather

3. **Validate Chat IDs**
   - Only use chat IDs you control
   - Verify chat ID before saving in settings
   - Don't expose chat IDs in public APIs

4. **Monitor Logs**
   - Check logs regularly for failed notifications
   - Set up alerts for repeated failures
   - Monitor for unauthorized access attempts

5. **Use HTTPS**
   - Always use HTTPS in production
   - Telegram API requires HTTPS for webhooks
   - Protect admin panel access

### Token Security

The application implements several security measures:

1. **Server-Side Only:** Bot tokens are never sent to frontend
2. **Partial Redaction:** Tokens shown in admin panel are partially masked
3. **Secure Storage:** Tokens stored in database or environment variables
4. **No Logging:** Tokens never logged in plain text

### Access Control

All Telegram management endpoints require:
- Valid JWT authentication token
- Admin role
- CORS validation for web requests

## Advanced Configuration

### Group Chat Setup

To send notifications to a group chat:

1. Create a Telegram group
2. Add your bot to the group
3. Make your bot an admin (recommended)
4. Send a message in the group
5. Use `/api/telegram/chat-id` to get the group chat ID (will be negative)
6. Configure the group chat ID in settings

### Multiple Notifications

To send notifications to multiple chats, you need to:

1. Create multiple bot instances or
2. Modify the code to support multiple chat IDs (not included by default)

### Custom Message Format

To customize notification messages, edit the `formatOrderMessage()` method in:
`backend/src/Helpers/TelegramService.php`

### Webhook Setup (Advanced)

For production systems with high volume, consider setting up Telegram webhooks instead of polling. This requires:
- HTTPS endpoint
- SSL certificate
- Webhook endpoint implementation
- Telegram setWebhook API call

## API Reference Summary

| Endpoint | Method | Purpose | Auth |
|----------|--------|---------|------|
| `/api/telegram/test` | POST | Send test message | Admin |
| `/api/telegram/chat-id` | GET | Get available chat IDs | Admin |
| `/api/telegram/status` | GET | Check integration status | Admin |
| `/api/orders` | POST | Submit order (auto-notifies) | Public |
| `/api/orders/{id}/resend-telegram` | POST | Resend notification | Admin |
| `/api/settings/telegram` | PUT/PATCH | Update bot settings | Admin |

## Support

For issues or questions:
- Check logs: `storage/logs/app.log`
- Review Telegram Bot API docs: https://core.telegram.org/bots/api
- Contact your system administrator

## Changelog

### Version 1.0.0 (2023-11-13)
- Initial Telegram integration
- Order notifications
- Admin test endpoints
- Chat ID discovery
- Status monitoring
- Error logging
