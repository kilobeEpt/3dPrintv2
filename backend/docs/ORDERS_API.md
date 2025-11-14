# Orders API Documentation

## Overview

The Orders API provides comprehensive management of customer orders and contact form submissions. It includes public endpoints for submission and admin endpoints for managing orders with advanced filtering and pagination.

## Architecture

### Components

1. **OrdersRepository** (`src/Repositories/OrdersRepository.php`)
   - Data access layer for orders CRUD operations
   - Handles pagination and filtering at database level
   - Manages JSON encoding/decoding for calculator_data
   - Generates unique order numbers with collision detection

2. **OrdersService** (`src/Services/OrdersService.php`)
   - Business logic layer
   - Validates incoming data
   - Implements rate limiting (5 submissions per hour per IP)
   - Handles order number generation
   - Integrates with TelegramService for notifications

3. **OrdersController** (`src/Controllers/OrdersController.php`)
   - HTTP request/response handling
   - Routes requests to service layer
   - Returns standardized JSON responses

4. **TelegramService** (`src/Helpers/TelegramService.php`)
   - Sends formatted notifications to Telegram
   - Formats order data into readable messages
   - Handles connection testing and error reporting

## Routes

### Public Routes

#### Submit Order
```
POST /api/orders
```

**No authentication required**

Accepts order or contact form submissions from the public frontend.

**Request Body:**
```json
{
  "client_name": "Иван Петров",
  "client_email": "ivan@example.com",
  "client_phone": "+7 (999) 123-45-67",
  "telegram": "@ivanpetrov",
  "type": "order",
  "service": "3D печать FDM",
  "subject": "Заказ прототипа",
  "message": "Хочу заказать печать детали...",
  "amount": 1250.50,
  "calculator_data": {
    "material": "PLA",
    "weight": 25,
    "volume": 31.25,
    "quality": "normal",
    "quantity": 5,
    "additionalServices": ["Моделирование"],
    "total": 1250.50
  }
}
```

**Required Fields:**
- `client_name` - Customer name (2-100 chars)
- `client_email` - Valid email address (max 255 chars)
- `client_phone` - Phone number (10-30 chars)

**Optional Fields:**
- `telegram` - Telegram username (max 100 chars)
- `type` - "order" or "contact" (auto-detected if omitted)
- `service` - Service name (max 255 chars)
- `subject` - Subject line (max 255 chars)
- `message` - Message content (max 5000 chars)
- `amount` - Order amount in rubles (numeric, >= 0)
- `calculator_data` - JSON object with calculation details

**Rate Limiting:**
- Maximum 5 submissions per hour per IP address
- Returns 422 error when limit exceeded

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Order submitted successfully",
  "data": {
    "order": {
      "id": 42,
      "order_number": "ORD-20231113-0042",
      "type": "order",
      "status": "new",
      "client_name": "Иван Петров",
      "client_email": "ivan@example.com",
      "client_phone": "+7 (999) 123-45-67",
      "telegram": "@ivanpetrov",
      "service": "3D печать FDM",
      "subject": "Заказ прототипа",
      "message": "Хочу заказать печать детали...",
      "amount": "1250.50",
      "calculator_data": { ... },
      "telegram_sent": true,
      "telegram_sent_at": null,
      "created_at": "2023-11-13 15:30:45",
      "updated_at": "2023-11-13 15:30:45"
    },
    "telegram_sent": true
  }
}
```

### Admin Routes

All admin routes require authentication with admin role.

#### List Orders
```
GET /api/orders
```

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20, max: 100)
- `status` - Filter by status: new, processing, completed, cancelled
- `type` - Filter by type: order, contact
- `search` - Full-text search in client_name, client_email, message
- `date_from` - Start date filter (YYYY-MM-DD)
- `date_to` - End date filter (YYYY-MM-DD)

**Example:**
```bash
GET /api/orders?page=1&per_page=20&status=new&type=order&search=Ivan
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Orders retrieved successfully",
  "data": {
    "items": [ ... ],
    "pagination": {
      "total": 156,
      "page": 1,
      "per_page": 20,
      "total_pages": 8
    }
  }
}
```

#### Get Single Order
```
GET /api/orders/{id}
```

Returns complete order details including calculator data.

#### Update Order
```
PUT /api/orders/{id}
PATCH /api/orders/{id}
```

**Request Body (all fields optional):**
```json
{
  "status": "processing",
  "type": "order",
  "client_name": "Updated Name",
  "client_email": "updated@example.com",
  "client_phone": "+7 (999) 000-00-00",
  "telegram": "@newhandle",
  "service": "Updated Service",
  "subject": "Updated Subject",
  "message": "Updated message...",
  "amount": 1500.00,
  "calculator_data": { ... },
  "telegram_sent": true
}
```

**Status Options:**
- `new` - New order (default)
- `processing` - Order is being processed
- `completed` - Order completed
- `cancelled` - Order cancelled

#### Delete Order
```
DELETE /api/orders/{id}
```

Permanently deletes an order from the database.

#### Resend Telegram Notification
```
POST /api/orders/{id}/resend-telegram
```

Attempts to resend Telegram notification for an order. Updates `telegram_sent` and `telegram_sent_at` fields on success.

## Order Number Generation

Orders are assigned unique order numbers with the format:

```
ORD-YYYYMMDD-XXXX
```

Where:
- `ORD` - Prefix
- `YYYYMMDD` - Date of creation
- `XXXX` - Sequential 4-digit number (resets daily)

Examples:
- `ORD-20231113-0001`
- `ORD-20231113-0042`
- `ORD-20231114-0001`

The system automatically:
- Finds the last order number for the current day
- Increments the sequence
- Handles collisions with retry logic
- Adds random suffix as fallback if needed

## Telegram Integration

### Message Format

Order notifications sent to Telegram include:

**Order Type:**
- 📦 Новый заказ (for orders)
- ✉️ Новое обращение (for contacts)

**Status Indicators:**
- 🆕 new
- ⏳ processing
- ✅ completed
- ❌ cancelled

**Information Sections:**
- 📋 Order number
- 📊 Status
- 👤 Client details (name, email, phone, telegram)
- 🛠 Service (if specified)
- 📌 Subject (if specified)
- 💬 Message (if specified)
- 💰 Amount (if > 0)
- 📐 Calculator data (material, weight, quality, etc.)
- 🕐 Creation timestamp

### Configuration

Telegram integration requires environment variables:

```env
TELEGRAM_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=-1001234567890
```

To obtain:
1. Create bot via @BotFather on Telegram
2. Get bot token from @BotFather
3. Add bot to your group/channel
4. Get chat ID (use @userinfobot or API method)

### Testing Connection

The TelegramService includes a `testConnection()` method that calls Telegram's `getMe` API to verify the bot token is valid.

## Rate Limiting

The Orders API implements basic IP-based rate limiting on public submissions:

- **Limit:** 5 orders per hour per IP address
- **Implementation:** Tracked in repository layer
- **Response:** 422 Validation Error with "rate_limit" error

**Note:** Current implementation returns 0 for getRecentOrdersByIp() as IP tracking requires adding an `ip_address` column to the orders table. This is a placeholder for future enhancement.

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "client_name": "Client name is required",
    "client_email": "Client email must be a valid email address"
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Order not found"
}
```

### Unauthorized (401)
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### Forbidden (403)
```json
{
  "success": false,
  "message": "Forbidden - Admin access required"
}
```

### Rate Limited (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "rate_limit": "Too many requests. Please try again later."
  }
}
```

## Testing Examples

### Public Order Submission

```bash
# Submit an order with calculator data
curl -X POST http://localhost:8080/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "client_name": "Иван Петров",
    "client_email": "ivan@example.com",
    "client_phone": "+7 (999) 123-45-67",
    "type": "order",
    "service": "3D печать FDM",
    "message": "Прошу рассчитать стоимость",
    "amount": 1250.50,
    "calculator_data": {
      "material": "PLA",
      "weight": 25,
      "quantity": 5,
      "total": 1250.50
    }
  }'
```

### Contact Form Submission

```bash
# Simple contact form
curl -X POST http://localhost:8080/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "client_name": "Мария Иванова",
    "client_email": "maria@example.com",
    "client_phone": "+7 (999) 555-12-34",
    "subject": "Вопрос по услугам",
    "message": "Хочу узнать больше о ваших услугах..."
  }'
```

### Admin Operations

```bash
# Get admin token first
TOKEN=$(curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"your_password"}' | jq -r '.data.token')

# List all orders
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8080/api/orders

# Filter by status
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8080/api/orders?status=new&per_page=10"

# Search orders
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8080/api/orders?search=Иван"

# Date range filter
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8080/api/orders?date_from=2023-11-01&date_to=2023-11-30"

# Get single order
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8080/api/orders/42

# Update order status
curl -X PUT http://localhost:8080/api/orders/42 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "processing"}'

# Delete order
curl -X DELETE http://localhost:8080/api/orders/42 \
  -H "Authorization: Bearer $TOKEN"

# Resend Telegram notification
curl -X POST http://localhost:8080/api/orders/42/resend-telegram \
  -H "Authorization: Bearer $TOKEN"
```

## Database Schema

The orders table structure:

```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('order', 'contact') NOT NULL DEFAULT 'contact',
    status ENUM('new', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'new',
    
    -- Client information
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(255) NOT NULL,
    client_phone VARCHAR(30) NOT NULL,
    telegram VARCHAR(100) NULL,
    
    -- Order details
    service VARCHAR(255) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NULL,
    amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    
    -- Calculator data (JSON)
    calculator_data JSON NULL,
    
    -- Integration status
    telegram_sent BOOLEAN NOT NULL DEFAULT FALSE,
    telegram_sent_at TIMESTAMP NULL,
    
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_order_number (order_number),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_client_email (client_email),
    INDEX idx_created_at (created_at DESC),
    INDEX idx_status_created (status, created_at DESC),
    FULLTEXT idx_search (client_name, client_email, message)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Future Enhancements

### Planned Features

1. **IP Address Tracking**
   - Add `ip_address VARCHAR(45)` column to orders table
   - Implement proper rate limiting based on stored IPs
   - Track submission patterns for analytics

2. **Email Notifications**
   - Send confirmation emails to clients
   - Send new order notifications to admin
   - Configurable email templates

3. **File Attachments**
   - Allow clients to upload 3D model files
   - Store file references in calculator_data
   - Integrate with file storage service

4. **Order Notes**
   - Add internal notes field for admin use
   - Track order history/changes
   - Admin-only comments system

5. **Advanced Filtering**
   - Filter by amount range
   - Filter by service type
   - Sort by multiple fields

6. **Bulk Operations**
   - Bulk status updates
   - Bulk delete with confirmation
   - Export filtered results to CSV/Excel

7. **Analytics**
   - Orders dashboard with statistics
   - Revenue reports
   - Client retention metrics

## Troubleshooting

### Telegram Notifications Not Sending

1. Check environment variables are set:
   ```bash
   echo $TELEGRAM_BOT_TOKEN
   echo $TELEGRAM_CHAT_ID
   ```

2. Verify bot token format:
   ```
   <number>:<alphanumeric+underscore+hyphen>
   Example: 1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
   ```

3. Test bot connection via API:
   ```bash
   curl https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getMe
   ```

4. Check bot has access to chat:
   - Bot must be added to group/channel
   - Bot must have "Send Messages" permission

5. Use resend endpoint to retry:
   ```bash
   curl -X POST http://localhost:8080/api/orders/{id}/resend-telegram \
     -H "Authorization: Bearer $TOKEN"
   ```

### Rate Limiting Issues

If rate limiting is too strict or not working:

1. Current implementation is a placeholder
2. Full implementation requires IP tracking
3. Add `ip_address` column to orders table
4. Update repository's `getRecentOrdersByIp()` method

### Search Not Working

Full-text search requires:

1. MySQL FULLTEXT index (already created in migration)
2. Search terms must be at least 3 characters (MySQL default)
3. Common words may be ignored (stopwords)
4. Check `ft_min_word_len` MySQL setting

### Pagination Issues

1. Ensure `page` and `per_page` are positive integers
2. Maximum `per_page` is 100
3. Empty results return empty items array, not 404

## Support

For issues or questions about the Orders API:
- Check backend logs in `backend/storage/logs/`
- Review validation errors in API responses
- Test with curl/Postman before frontend integration
- Verify database connection and migrations

## Related Documentation

- [Complete API Documentation](../../docs/api.md)
- [Authentication Guide](AUTHENTICATION.md)
- [Settings API Guide](SETTINGS_API_TESTING.md)
- [Backend README](../README.md)
- [Database Schema](../../docs/db-schema.md)
