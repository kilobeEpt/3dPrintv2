# Settings API Testing Guide

## Overview

This document provides a comprehensive testing guide for the Settings API endpoints. The Settings API manages site-wide configuration including contact information, calculator pricing, form definitions, and integrations.

## Test Environment Setup

### Prerequisites

1. PHP 7.4+ with PDO MySQL extension
2. MySQL 8.0+ database
3. Composer dependencies installed
4. Database migrations and seed data loaded
5. Admin user created (use `database/seeds/seed-admin-user.php`)

### Environment Configuration

Ensure your `.env` file has these settings:

```env
DB_HOST=localhost
DB_DATABASE=ch167436_3dprint
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

JWT_SECRET=your_secret_key_here

CORS_ORIGIN=*
```

### Start Development Server

```bash
cd backend
composer install
php -S localhost:8080 -t public
```

## Authentication

Before testing admin endpoints, obtain a JWT token:

```bash
# Login
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "admin",
    "password": "admin123"
  }'
```

Save the `token` from the response and use it in subsequent requests:

```bash
export TOKEN="your_jwt_token_here"
```

## Test Cases

### 1. Get Public Settings (No Authentication)

**Purpose:** Verify that public settings are returned without sensitive data.

**Request:**
```bash
curl -X GET http://localhost:8080/api/settings/public \
  -H "Content-Type: application/json" | jq .
```

**Expected Response:**
- Status: 200 OK
- Should include: site info, calculator pricing, form definitions
- Should NOT include: telegram bot token, notification settings, timezone

**Validation Checks:**
- ✅ `data.site.name` exists
- ✅ `data.site.contact.email` exists
- ✅ `data.calculator.materials` is an array
- ✅ `data.forms` contains form type keys
- ✅ `data.integrations.telegram.bot_token` does NOT exist
- ✅ `data.site.notifications` does NOT exist

### 2. Get Admin Settings (With Authentication)

**Purpose:** Verify that admin settings include all configuration with redacted secrets.

**Request:**
```bash
curl -X GET http://localhost:8080/api/settings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" | jq .
```

**Expected Response:**
- Status: 200 OK
- Should include: all settings including notifications, timezone
- Telegram bot token should be redacted (e.g., "824180...KBI")

**Validation Checks:**
- ✅ `data.site.notifications` exists
- ✅ `data.site.timezone` exists
- ✅ `data.integrations.telegram.bot_token` is redacted
- ✅ `data.calculator.materials[0].id` exists (full records)

### 3. Get Admin Settings Without Token

**Purpose:** Verify authentication is required.

**Request:**
```bash
curl -X GET http://localhost:8080/api/settings \
  -H "Content-Type: application/json"
```

**Expected Response:**
- Status: 401 Unauthorized
- Error message about missing authentication

### 4. Update General Settings

**Purpose:** Test updating site contact information and theme.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "site_name": "3D Print Pro Updated",
    "contact_email": "updated@3dprintpro.ru",
    "contact_phone": "+7 (999) 000-11-22",
    "working_hours": "Пн-Пт: 10:00 - 19:00",
    "social_links": {
      "vk": "https://vk.com/test",
      "telegram": "https://t.me/test",
      "whatsapp": "",
      "youtube": ""
    },
    "theme": "dark",
    "color_primary": "#ff0000",
    "notifications": {
      "newOrders": true,
      "newReviews": false,
      "newMessages": true
    }
  }' | jq .
```

**Expected Response:**
- Status: 200 OK
- Updated settings returned

**Validation Checks:**
- ✅ Settings are persisted to database
- ✅ `updated_at` timestamp is updated
- ✅ Response contains updated values

### 5. Update General Settings - Invalid Email

**Purpose:** Test email validation.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "contact_email": "invalid-email"
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message: "Contact email must be a valid email address"

### 6. Update General Settings - Invalid Theme

**Purpose:** Test enum validation.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "theme": "rainbow"
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message about invalid theme value

### 7. Update General Settings - Invalid Social Link URL

**Purpose:** Test URL validation in nested objects.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "social_links": {
      "vk": "not-a-url"
    }
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message about invalid URL

### 8. Update Calculator Settings - Valid Data

**Purpose:** Test updating calculator materials, services, quality levels, and discounts.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/calculator \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "materials": [
      {
        "material_key": "pla",
        "name": "PLA Updated",
        "price": 55.00,
        "technology": "fdm",
        "active": true,
        "display_order": 1
      }
    ],
    "additional_services": [
      {
        "service_key": "modeling",
        "name": "3D моделирование Updated",
        "price": 550.00,
        "unit": "час",
        "active": true,
        "display_order": 1
      }
    ],
    "quality_levels": [
      {
        "quality_key": "normal",
        "name": "Нормальное",
        "price_multiplier": 1.20,
        "time_multiplier": 1.10,
        "active": true,
        "display_order": 2
      }
    ],
    "volume_discounts": [
      {
        "min_quantity": 15,
        "discount_percent": 12.00,
        "active": true
      }
    ]
  }' | jq .
```

**Expected Response:**
- Status: 200 OK
- Calculator settings returned

**Validation Checks:**
- ✅ All arrays are validated
- ✅ Prices are numeric >= 0
- ✅ Multipliers are positive (> 0)

### 9. Update Calculator Settings - Negative Price

**Purpose:** Test price validation (must be >= 0).

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/calculator \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "materials": [
      {
        "material_key": "pla",
        "name": "PLA",
        "price": -10.00,
        "technology": "fdm"
      }
    ]
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message: "materials[0].price must be at least 0"

### 10. Update Calculator Settings - Invalid Multiplier

**Purpose:** Test multiplier validation (must be > 0).

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/calculator \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "quality_levels": [
      {
        "quality_key": "draft",
        "name": "Draft",
        "price_multiplier": 0,
        "time_multiplier": 1.0
      }
    ]
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message about invalid multiplier

### 11. Update Calculator Settings - Invalid Technology

**Purpose:** Test enum validation for technology field.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/calculator \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "materials": [
      {
        "material_key": "pla",
        "name": "PLA",
        "price": 50.00,
        "technology": "laser"
      }
    ]
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message: "Technology must be one of: fdm, sla, sls"

### 12. Update Form Settings - Valid Data

**Purpose:** Test updating form field configurations.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/forms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "fields": [
      {
        "form_type": "contact",
        "field_name": "name",
        "label": "Ваше имя (обновлено)",
        "field_type": "text",
        "required": true,
        "enabled": true,
        "placeholder": "Иван Иванов",
        "display_order": 1,
        "options": null
      },
      {
        "form_type": "contact",
        "field_name": "service_type",
        "label": "Тип услуги",
        "field_type": "select",
        "required": true,
        "enabled": true,
        "placeholder": "Выберите услугу",
        "display_order": 6,
        "options": ["FDM печать", "SLA печать", "Моделирование", "Другое"]
      }
    ]
  }' | jq .
```

**Expected Response:**
- Status: 200 OK
- Form fields returned

**Validation Checks:**
- ✅ Options array validated for select fields
- ✅ All required fields present

### 13. Update Form Settings - Invalid Field Type

**Purpose:** Test field type enum validation.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/forms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "fields": [
      {
        "form_type": "contact",
        "field_name": "test",
        "label": "Test",
        "field_type": "dropdown",
        "required": false,
        "enabled": true
      }
    ]
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message about invalid field type

### 14. Update Form Settings - Options Not Array

**Purpose:** Test options validation.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/forms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "fields": [
      {
        "form_type": "contact",
        "field_name": "test",
        "label": "Test",
        "field_type": "select",
        "required": false,
        "enabled": true,
        "options": "not an array"
      }
    ]
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message: "Options must be an array"

### 15. Update Form Settings - Options Not Strings

**Purpose:** Test that all option values are strings.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/forms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "fields": [
      {
        "form_type": "contact",
        "field_name": "test",
        "label": "Test",
        "field_type": "select",
        "required": false,
        "enabled": true,
        "options": ["Option 1", 123, "Option 3"]
      }
    ]
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message: "All options must be strings"

### 16. Update Telegram Settings - Valid Data

**Purpose:** Test updating Telegram bot configuration.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/telegram \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "enabled": true,
    "bot_token": "1234567890:ABCdefGHIjklMNOpqrsTUVwxyz-1234567",
    "chat_id": "-1001234567890",
    "api_url": "https://api.telegram.org/bot",
    "contact_url": "https://t.me/TestBot"
  }' | jq .
```

**Expected Response:**
- Status: 200 OK
- Telegram settings returned with redacted token

**Validation Checks:**
- ✅ Token stored in full in database
- ✅ Token redacted in response
- ✅ Settings persisted

### 17. Update Telegram Settings - Invalid Token Format

**Purpose:** Test Telegram token format validation.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/telegram \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "enabled": true,
    "bot_token": "invalid-token-format"
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message: "Invalid Telegram bot token format"

### 18. Update Telegram Settings - Invalid URL

**Purpose:** Test URL validation for api_url and contact_url.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings/telegram \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "api_url": "not-a-valid-url"
  }'
```

**Expected Response:**
- Status: 422 Unprocessable Entity
- Error message about invalid URL

## Security Testing

### 19. Public Endpoint Should Not Expose Secrets

**Purpose:** Verify sensitive data is not leaked.

**Request:**
```bash
curl -X GET http://localhost:8080/api/settings/public | jq .
```

**Security Checks:**
- ✅ Bot token not present
- ✅ Notification preferences not present
- ✅ Admin-only fields not present
- ✅ Database IDs and timestamps not present in simplified format

### 20. Admin Endpoint Should Redact Secrets

**Purpose:** Verify secrets are redacted but identifiable.

**Request:**
```bash
curl -X GET http://localhost:8080/api/settings \
  -H "Authorization: Bearer $TOKEN" | jq .
```

**Security Checks:**
- ✅ Bot token redacted (shows first 6 + last 3 characters)
- ✅ Still identifiable which bot is configured
- ✅ Full token NOT returned

## Integration Testing

### 21. Settings Update Affects Public Endpoint

**Purpose:** Verify that updates to admin settings are reflected in public endpoint.

**Steps:**
1. Get current public settings
2. Update site name via admin endpoint
3. Get public settings again
4. Verify site name changed

### 22. Calculator Settings Update

**Purpose:** Verify calculator pricing updates work end-to-end.

**Steps:**
1. Update material prices via admin endpoint
2. Fetch public settings
3. Verify material prices match

## Database Verification

After running tests, verify data persistence:

```sql
-- Check site_settings
SELECT * FROM site_settings;

-- Check integrations
SELECT * FROM integrations WHERE integration_name = 'telegram';

-- Check materials
SELECT * FROM materials WHERE active = 1;

-- Check form fields
SELECT * FROM form_fields WHERE enabled = 1;
```

## Performance Testing

### 23. Load Test Public Endpoint

**Purpose:** Verify public endpoint can handle high traffic.

```bash
# Using Apache Bench (ab)
ab -n 1000 -c 10 http://localhost:8080/api/settings/public
```

**Expected:**
- No errors
- Reasonable response times (< 200ms)

## Edge Cases

### 24. Empty Update Payload

**Purpose:** Test behavior with empty or partial updates.

**Request:**
```bash
curl -X PUT http://localhost:8080/api/settings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{}' | jq .
```

**Expected:**
- Status: 200 OK (partial update allowed)
- No fields changed

### 25. Large Dataset

**Purpose:** Test with many calculator items.

**Request:**
```bash
# Generate 100 materials and test update
# (Script to generate payload)
```

## Regression Testing

After each update to the Settings API:

1. ✅ Run all test cases above
2. ✅ Verify no existing functionality broken
3. ✅ Check database migrations still work
4. ✅ Verify frontend integration still works

## Test Automation

Consider using PHPUnit for automated testing:

```php
// tests/SettingsApiTest.php
class SettingsApiTest extends TestCase
{
    public function testPublicSettingsDoesNotExposeSecrets()
    {
        $response = $this->get('/api/settings/public');
        $response->assertStatus(200);
        $this->assertArrayNotHasKey('bot_token', $response->json('data.integrations.telegram'));
    }
    
    // Additional test methods...
}
```

## Troubleshooting

### Common Issues

1. **401 Unauthorized on admin endpoints**
   - Verify JWT token is valid and not expired
   - Check Authorization header format: `Bearer <token>`

2. **500 Internal Server Error**
   - Check PHP error logs
   - Verify database connection
   - Check all required fields in database

3. **422 Validation Error**
   - Review error messages in response
   - Verify data types match expected format
   - Check enum values

4. **Empty response from public endpoint**
   - Verify seed data is loaded
   - Check database contains required records

## Success Criteria

All tests should pass with:
- ✅ Correct HTTP status codes
- ✅ Proper JSON structure
- ✅ Validation errors caught
- ✅ Secrets properly redacted
- ✅ Data persisted to database
- ✅ No security vulnerabilities
- ✅ Good performance (< 200ms response time)

## Documentation

Ensure the following documentation is complete:
- ✅ API endpoints documented in `/docs/api.md`
- ✅ Request/response examples provided
- ✅ Validation rules clearly specified
- ✅ Security considerations noted
