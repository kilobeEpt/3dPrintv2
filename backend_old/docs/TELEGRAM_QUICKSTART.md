# Telegram Integration - Quick Start Guide

This is a condensed guide for quickly setting up Telegram notifications. For complete documentation, see [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md).

## Setup in 5 Minutes

### Step 1: Create Your Bot (2 minutes)

1. Open Telegram and search for `@BotFather`
2. Send `/newbot` command
3. Choose a name: `Your Business Notifications`
4. Choose a username: `yourbusiness_bot` (must end with `bot`)
5. Copy the bot token (format: `123456789:ABCdef...`)

### Step 2: Get Your Chat ID (1 minute)

#### Option A: Automatic (Recommended)

1. In Telegram, search for your bot and send it any message (e.g., "Hello")
2. In Admin Panel → Settings → Telegram:
   - Paste your bot token
   - Click "Get Chat ID"
   - Copy the displayed chat ID

#### Option B: Manual

1. Send a message to your bot
2. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
3. Find `"chat":{"id":123456789}` in the JSON
4. Copy the ID number

### Step 3: Configure (1 minute)

In Admin Panel → Settings → Telegram:
1. Enter Bot Token
2. Enter Chat ID
3. Click "Save"
4. Click "Test" to verify

### Step 4: Test (1 minute)

1. Click "Send Test Message" in admin panel
2. Check your Telegram - you should receive a test message
3. Submit a test order on your website
4. Verify you receive the order notification

## Configuration Options

### Environment Variables

Add to `.env` file:

```env
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=123456789
```

### API Configuration

Update via Settings API:

```bash
curl -X PUT http://your-api.com/api/settings/telegram \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "bot_token": "123456789:ABCdefGHIjklMNOpqrsTUVwxyz",
    "chat_id": "123456789"
  }'
```

## Quick Commands

### Test Integration
```bash
curl -X POST http://your-api.com/api/telegram/test \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Chat ID
```bash
curl -X GET http://your-api.com/api/telegram/chat-id \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Check Status
```bash
curl -X GET http://your-api.com/api/telegram/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Common Issues

| Problem | Solution |
|---------|----------|
| "Bot token invalid" | Double-check token from BotFather, create new bot if needed |
| "Chat not found" | Send a message to your bot first, then get chat ID again |
| "Integration not enabled" | Make sure both bot token AND chat ID are configured |
| "No updates found" | You need to send a message to the bot before getting chat ID |

## What Gets Notified?

✅ New orders from calculator
✅ New contact form submissions
✅ Manual resends from admin panel

## Notification Format

Orders include:
- Order number and status
- Client information (name, email, phone, Telegram)
- Service details
- Calculator data (material, weight, quantity, etc.)
- Total amount
- Timestamp

## Admin Panel Features

- **Test Message**: Send a test notification
- **Get Chat ID**: Discover available chat IDs automatically
- **Status Check**: Verify bot connection and configuration
- **Resend**: Manually resend any order notification

## Security Notes

✅ Bot tokens stored server-side only
✅ Tokens never exposed to frontend
✅ All management endpoints require admin authentication
✅ Chat IDs validated before saving

## Need Help?

- Full documentation: [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md)
- Check API status: `GET /api/telegram/status`
- View logs: `storage/logs/app.log`
- Telegram Bot API docs: https://core.telegram.org/bots/api

## Testing Checklist

- [ ] Created bot with BotFather
- [ ] Got bot token
- [ ] Sent message to bot
- [ ] Got chat ID
- [ ] Configured in admin panel or .env
- [ ] Sent test message successfully
- [ ] Submitted test order
- [ ] Received order notification
- [ ] Verified all order details in notification

If all checkboxes are complete, your integration is working! 🎉
