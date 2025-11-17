# Settings API Documentation

## Overview

The Settings API provides comprehensive site-wide configuration management for the 3D Print Pro platform. It handles contact information, calculator pricing, form definitions, Telegram bot integration, and theme settings.

## Features

- **Public Settings Endpoint**: Sanitized configuration for frontend consumption (no secrets)
- **Admin Settings Management**: Full CRUD operations with proper authentication
- **Secret Redaction**: Sensitive data (bot tokens) partially redacted in responses
- **Comprehensive Validation**: Nested structure validation with detailed error messages
- **Multi-Table Aggregation**: Combines data from site_settings, integrations, materials, additional_services, quality_levels, volume_discounts, and form_fields
- **Granular Updates**: Separate endpoints for general settings, calculator config, forms, and integrations

## Architecture

### Three-Layer Architecture

```
SettingsController (HTTP Layer)
    ↓
SettingsService (Business Logic Layer)
    ↓
SettingsRepository (Data Access Layer)
```

#### SettingsRepository (`src/Repositories/SettingsRepository.php`)

Handles direct database operations across multiple tables:

**Methods:**
- `getSiteSettings()` - Fetch site_settings singleton
- `updateSiteSettings(array $data)` - Update site settings
- `getIntegration(string $name)` - Get integration by name
- `updateIntegration(string $name, bool $enabled, array $config)` - Update integration
- `getAllMaterials()` - Fetch active materials
- `updateMaterial(int $id, array $data)` - Update material
- `createMaterial(array $data)` - Create new material
- `deleteMaterial(int $id)` - Delete material
- `getAllAdditionalServices()` - Fetch active additional services
- `updateAdditionalService(int $id, array $data)` - Update service
- `createAdditionalService(array $data)` - Create service
- `deleteAdditionalService(int $id)` - Delete service
- `getAllQualityLevels()` - Fetch active quality levels
- `updateQualityLevel(int $id, array $data)` - Update quality level
- `createQualityLevel(array $data)` - Create quality level
- `deleteQualityLevel(int $id)` - Delete quality level
- `getAllVolumeDiscounts()` - Fetch active volume discounts
- `updateVolumeDiscount(int $id, array $data)` - Update discount
- `createVolumeDiscount(array $data)` - Create discount
- `deleteVolumeDiscount(int $id)` - Delete discount
- `getAllFormFields()` - Fetch all enabled form fields
- `getFormFieldsByType(string $formType)` - Fetch fields by form type
- `updateFormField(int $id, array $data)` - Update form field
- `createFormField(array $data)` - Create form field
- `deleteFormField(int $id)` - Delete form field

**Design Decisions:**
- Uses PDO prepared statements for security
- JSON encoding/decoding handled at repository layer
- Upsert logic for singleton tables
- Active/enabled filtering at database level

#### SettingsService (`src/Services/SettingsService.php`)

Handles business logic, validation, and data transformation:

**Methods:**
- `getPublicSettings()` - Aggregate and sanitize settings for public endpoint
- `getAdminSettings()` - Aggregate full settings with redacted secrets
- `updateGeneralSettings(array $data)` - Validate and update general settings
- `updateCalculatorSettings(array $data)` - Validate and update calculator config
- `updateFormSettings(array $data)` - Validate and update form definitions
- `updateTelegramSettings(array $data)` - Validate and update Telegram integration

**Private Methods:**
- `formatMaterialsForPublic(array $materials)` - Simplify materials for frontend
- `formatAdditionalServicesForPublic(array $services)` - Simplify services
- `formatQualityLevelsForPublic(array $levels)` - Simplify quality levels
- `formatVolumeDiscountsForPublic(array $discounts)` - Simplify discounts
- `formatFormFieldsForPublic(array $fields)` - Group and simplify form fields
- `redactToken(string $token)` - Partially redact sensitive tokens

**Validation Logic:**
- Price validation: numeric, >= 0
- Multiplier validation: numeric, >= 0.01 (must be positive)
- URL validation: valid URL format
- Email validation: valid email format
- Enum validation: predefined allowed values
- Array validation: type checking, string elements for options
- Nested validation: per-item validation in arrays

#### SettingsController (`src/Controllers/SettingsController.php`)

Handles HTTP requests and responses:

**Methods:**
- `getPublicSettings()` - GET /api/settings/public
- `getAdminSettings()` - GET /api/settings (requires admin auth)
- `updateGeneralSettings()` - PUT/PATCH /api/settings (requires admin auth)
- `updateCalculatorSettings()` - PUT/PATCH /api/settings/calculator (requires admin auth)
- `updateFormSettings()` - PUT/PATCH /api/settings/forms (requires admin auth)
- `updateTelegramSettings()` - PUT/PATCH /api/settings/telegram (requires admin auth)

**Response Handling:**
- Returns 200 OK on success with full updated data
- Returns 422 Unprocessable Entity on validation errors
- Returns 401/403 on authentication/authorization failures
- Uses Response helper for consistent JSON structure

## API Endpoints

### 1. Get Public Settings

**GET** `/api/settings/public`

Returns sanitized settings for frontend use. No authentication required.

**Response Structure:**
```json
{
  "site": {
    "name": "string",
    "description": "string",
    "contact": {
      "email": "string",
      "phone": "string",
      "address": "string",
      "working_hours": "string"
    },
    "social_links": {
      "vk": "string",
      "telegram": "string",
      "whatsapp": "string",
      "youtube": "string"
    },
    "theme": {
      "mode": "light|dark",
      "color_primary": "string",
      "color_secondary": "string"
    }
  },
  "calculator": {
    "materials": [
      {
        "key": "string",
        "name": "string",
        "price": 0.00,
        "technology": "fdm|sla|sls"
      }
    ],
    "additional_services": [
      {
        "key": "string",
        "name": "string",
        "price": 0.00,
        "unit": "string"
      }
    ],
    "quality_levels": [
      {
        "key": "string",
        "name": "string",
        "price_multiplier": 1.00,
        "time_multiplier": 1.00
      }
    ],
    "volume_discounts": [
      {
        "min_quantity": 0,
        "discount_percent": 0.00
      }
    ]
  },
  "forms": {
    "contact": [],
    "order": []
  },
  "integrations": {
    "telegram": {
      "enabled": true,
      "contact_url": "string"
    }
  }
}
```

**Security:** Bot tokens and admin-only fields are excluded.

### 2. Get Admin Settings

**GET** `/api/settings`

Returns full settings with redacted secrets. Requires admin authentication.

**Authentication:** Bearer token with admin role

**Response:** Similar to public settings but includes:
- Full database records (with IDs, timestamps)
- Notification preferences
- Timezone
- Redacted bot token (e.g., "824180...KBI")

### 3. Update General Settings

**PUT/PATCH** `/api/settings`

Update site name, contact info, social links, theme, and notifications.

**Authentication:** Bearer token with admin role

**Request Body:**
```json
{
  "site_name": "string",
  "site_description": "string",
  "contact_email": "email",
  "contact_phone": "string",
  "address": "string",
  "working_hours": "string",
  "timezone": "string",
  "social_links": {
    "vk": "url",
    "telegram": "url",
    "whatsapp": "url",
    "youtube": "url"
  },
  "theme": "light|dark",
  "color_primary": "string",
  "color_secondary": "string",
  "notifications": {
    "newOrders": true,
    "newReviews": true,
    "newMessages": false
  }
}
```

**Validation:**
- site_name: string, 1-255 chars
- contact_email: valid email
- contact_phone: string, max 30 chars
- social_links: object with valid URLs
- theme: enum (light, dark)
- color_primary/secondary: string, 4-7 chars
- notifications: object with boolean values

### 4. Update Calculator Settings

**PUT/PATCH** `/api/settings/calculator`

Update calculator materials, services, quality levels, and volume discounts.

**Authentication:** Bearer token with admin role

**Request Body:**
```json
{
  "materials": [
    {
      "material_key": "string",
      "name": "string",
      "price": 0.00,
      "technology": "fdm|sla|sls",
      "active": true,
      "display_order": 0
    }
  ],
  "additional_services": [
    {
      "service_key": "string",
      "name": "string",
      "price": 0.00,
      "unit": "string",
      "active": true,
      "display_order": 0
    }
  ],
  "quality_levels": [
    {
      "quality_key": "string",
      "name": "string",
      "price_multiplier": 1.00,
      "time_multiplier": 1.00,
      "active": true,
      "display_order": 0
    }
  ],
  "volume_discounts": [
    {
      "min_quantity": 1,
      "discount_percent": 10.00,
      "active": true
    }
  ]
}
```

**Validation:**
- material_key: required, string, max 50 chars
- name: required, string, max 100 chars
- price: required, numeric, >= 0
- technology: required, enum (fdm, sla, sls)
- price_multiplier: required, numeric, >= 0.01
- time_multiplier: required, numeric, >= 0.01
- min_quantity: required, integer, >= 1
- discount_percent: required, numeric, 0-100

### 5. Update Form Settings

**PUT/PATCH** `/api/settings/forms`

Update dynamic form field configurations.

**Authentication:** Bearer token with admin role

**Request Body:**
```json
{
  "fields": [
    {
      "form_type": "contact|order",
      "field_name": "string",
      "label": "string",
      "field_type": "text|email|tel|textarea|select|checkbox|file|number|url|date",
      "required": true,
      "enabled": true,
      "placeholder": "string",
      "display_order": 0,
      "options": ["string"]
    }
  ]
}
```

**Validation:**
- form_type: required, enum (contact, order)
- field_name: required, string, max 50 chars
- label: required, string, max 100 chars
- field_type: required, enum (text, email, tel, textarea, select, checkbox, file, number, url, date)
- options: array of strings (for select fields)

### 6. Update Telegram Settings

**PUT/PATCH** `/api/settings/telegram`

Update Telegram bot integration settings.

**Authentication:** Bearer token with admin role

**Request Body:**
```json
{
  "enabled": true,
  "bot_token": "string",
  "chat_id": "string",
  "api_url": "url",
  "contact_url": "url"
}
```

**Validation:**
- bot_token: string, format `\d+:[A-Za-z0-9_-]+`
- chat_id: string
- api_url: valid URL
- contact_url: valid URL

**Security:** Bot token is stored in full in database but redacted in responses.

## Data Flow

### Public Settings Request Flow

```
Frontend Request (no auth)
    ↓
GET /api/settings/public
    ↓
SettingsController::getPublicSettings()
    ↓
SettingsService::getPublicSettings()
    ↓
SettingsRepository (multiple queries)
    ├── getSiteSettings()
    ├── getAllMaterials()
    ├── getAllAdditionalServices()
    ├── getAllQualityLevels()
    ├── getAllVolumeDiscounts()
    ├── getAllFormFields()
    └── getIntegration('telegram')
    ↓
Data aggregation & sanitization
    ├── Format materials (remove IDs, timestamps)
    ├── Format services (simplified)
    ├── Format quality levels (simplified)
    ├── Format discounts (simplified)
    ├── Format form fields (grouped by type)
    └── Exclude bot token
    ↓
Return JSON response
```

### Admin Update Request Flow

```
Admin Request (with JWT)
    ↓
PUT /api/settings
    ↓
AuthMiddleware (verify admin role)
    ↓
SettingsController::updateGeneralSettings()
    ↓
Parse request body
    ↓
SettingsService::updateGeneralSettings()
    ↓
Validate data
    ├── Field type validation
    ├── Email/URL format validation
    ├── Enum value validation
    └── Nested object validation
    ↓
SettingsRepository::updateSiteSettings()
    ↓
Execute UPDATE query
    ↓
SettingsService::getAdminSettings() (fetch updated)
    ↓
Return JSON response with updated settings
```

## Database Tables

Settings data is stored across multiple tables:

### site_settings (Singleton)
- id, site_name, site_description
- contact_email, contact_phone, address, working_hours
- timezone, social_links (JSON), theme
- color_primary, color_secondary, notifications (JSON)
- created_at, updated_at

### integrations
- id, integration_name, enabled
- config (JSON: botToken, chatId, apiUrl, contactUrl)
- created_at, updated_at

### materials
- id, material_key, name, price, technology
- active, display_order
- created_at, updated_at

### additional_services
- id, service_key, name, price, unit
- active, display_order
- created_at, updated_at

### quality_levels
- id, quality_key, name
- price_multiplier, time_multiplier
- active, display_order
- created_at, updated_at

### volume_discounts
- id, min_quantity, discount_percent, active
- created_at, updated_at

### form_fields
- id, form_type, field_name, label, field_type
- required, enabled, placeholder
- display_order, options (JSON)
- created_at, updated_at

## Security Considerations

### Secret Redaction

**Bot Token Redaction Logic:**
```php
private function redactToken(string $token): string
{
    if (empty($token) || strlen($token) < 10) {
        return '';
    }
    
    $length = strlen($token);
    $visibleChars = 6;
    $start = substr($token, 0, $visibleChars);
    $end = substr($token, -3);
    
    return $start . '...' . $end;
}
```

**Example:**
- Full token: `8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI`
- Redacted: `824180...KBI`

### Public vs Admin Endpoints

**Public Endpoint** (`/api/settings/public`):
- No authentication required
- Excludes: bot_token, notifications, timezone
- Simplified data format (no IDs, timestamps)
- Safe for frontend consumption

**Admin Endpoints** (require admin auth):
- JWT authentication required
- Role-based access control (admin role)
- Full database records returned
- Sensitive fields redacted but identifiable

### Validation Security

- All inputs validated before database operations
- SQL injection prevented via prepared statements
- Type coercion for numeric values
- URL/email format validation
- Enum values strictly checked
- Nested structure validation
- Array element type checking

## Error Handling

### Validation Errors (422)

**Example Response:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "contact_email": "Contact email must be a valid email address",
    "materials[0]": {
      "price": "Price must be at least 0"
    },
    "quality_levels[1]": {
      "price_multiplier": "Price multiplier must be at least 0.01"
    }
  }
}
```

### Authentication Errors (401/403)

**401 Unauthorized:**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

**403 Forbidden:**
```json
{
  "success": false,
  "message": "Forbidden"
}
```

## Usage Examples

### Fetch Public Settings

```bash
curl http://localhost:8080/api/settings/public
```

### Update Contact Information

```bash
curl -X PUT http://localhost:8080/api/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contact_email": "newinfo@example.com",
    "contact_phone": "+7 (999) 000-11-22"
  }'
```

### Update Calculator Prices

```bash
curl -X PUT http://localhost:8080/api/settings/calculator \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "materials": [
      {
        "material_key": "pla",
        "name": "PLA",
        "price": 55.00,
        "technology": "fdm",
        "active": true,
        "display_order": 1
      }
    ]
  }'
```

### Update Telegram Bot

```bash
curl -X PUT http://localhost:8080/api/settings/telegram \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "enabled": true,
    "bot_token": "1234567890:ABCdefGHIjklMNOpqrsTUVwxyz",
    "chat_id": "-1001234567890"
  }'
```

## Testing

Comprehensive test plan available in `SETTINGS_API_TESTING.md` with 25+ test cases covering:
- Public/admin endpoint functionality
- Authentication/authorization
- Validation rules
- Security (secret redaction, data sanitization)
- Edge cases (empty payloads, large datasets)
- Integration tests
- Performance tests

## Future Enhancements

Potential improvements:
- Caching layer for public settings
- Versioning for calculator pricing changes
- Audit logging for settings changes
- Bulk update operations
- Settings import/export
- Settings rollback functionality
- Real-time settings updates via WebSocket

## Related Documentation

- **API Documentation**: `docs/api.md` - Complete API reference
- **Testing Guide**: `backend/docs/SETTINGS_API_TESTING.md` - Test cases and procedures
- **Authentication Guide**: `backend/docs/AUTHENTICATION.md` - Auth flows and security
- **Database Schema**: `docs/db-schema.md` - Complete schema documentation
- **Deployment Guide**: `backend/DEPLOYMENT.md` - Production deployment
