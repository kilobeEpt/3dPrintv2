# Content API Implementation Guide

## Overview

The Content API provides comprehensive CRUD operations for managing site content including services, portfolio, testimonials, FAQ, and content sections. The implementation follows a clean three-layer architecture: Repository (data access) → Service (business logic) → Controller (HTTP handling).

## Architecture

### Layer Responsibilities

**Repository Layer** (`src/Repositories/`):
- Direct database access using PDO prepared statements
- Raw SQL queries with proper escaping
- Returns associative arrays from database
- No business logic or validation

**Service Layer** (`src/Services/`):
- Input validation using Validator helper
- Business logic (slug generation, feature management, etc.)
- Data transformation between API and database formats
- Returns standardized result arrays: `['success' => bool, 'errors' => array, 'id' => int]`

**Controller Layer** (`src/Controllers/`):
- HTTP request/response handling
- Calls service methods
- Returns JSON responses using Response helper
- HTTP status codes: 200 (OK), 201 (Created), 404 (Not Found), 422 (Validation Error)

## Implemented Endpoints

### Services API
- **Public**: `GET /api/services`, `GET /api/services/{id}`
- **Admin**: `GET /api/admin/services`, `POST /api/admin/services`, `PUT/PATCH /api/admin/services/{id}`, `DELETE /api/admin/services/{id}`
- **Features**: Auto-slug generation, service features management, active/inactive filtering

### Portfolio API
- **Public**: `GET /api/portfolio`, `GET /api/portfolio/{id}`, `GET /api/portfolio/categories`
- **Admin**: `POST /api/admin/portfolio`, `PUT/PATCH /api/admin/portfolio/{id}`, `DELETE /api/admin/portfolio/{id}`
- **Features**: Category filtering, image URL validation

### Testimonials API
- **Public**: `GET /api/testimonials`, `GET /api/testimonials/{id}`
- **Admin**: `GET /api/admin/testimonials`, `POST /api/admin/testimonials`, `PUT/PATCH /api/admin/testimonials/{id}`, `DELETE /api/admin/testimonials/{id}`
- **Features**: Approval workflow, rating validation (1-5), display order management

### FAQ API
- **Public**: `GET /api/faq`, `GET /api/faq/{id}`
- **Admin**: `GET /api/admin/faq`, `POST /api/admin/faq`, `PUT/PATCH /api/admin/faq/{id}`, `DELETE /api/admin/faq/{id}`
- **Features**: Active/inactive filtering, display order management

### Content API
- **Public**: `GET /api/content`, `GET /api/content/{section}`, `GET /api/stats`
- **Admin**: `PUT/PATCH /api/admin/content/{section}`, `DELETE /api/admin/content/{section}`, `PUT/PATCH /api/admin/stats`
- **Features**: JSON content storage, section-based access, upsert operations

## Security

All admin endpoints are protected by:
1. JWT authentication via `AuthMiddleware`
2. Role-based access control (requires 'admin' role)
3. Input validation to prevent SQL injection
4. Prepared statements for all database queries

## Validation Rules

The `Validator` helper supports:
- **required**: Field must not be empty
- **string**: Must be a string
- **integer**: Must be an integer
- **numeric**: Must be numeric
- **boolean**: Must be boolean
- **email**: Valid email format
- **url**: Valid URL format
- **min:n**: Minimum value/length
- **max:n**: Maximum value/length
- **in:a,b,c**: Value must be in list
- **array**: Must be an array
- **between:min,max**: Value must be between range

### Entity-Specific Rules

**Services**:
```php
'name' => 'required|string|min:1|max:100',
'slug' => 'string|min:1|max:100',  // auto-generated if not provided
'icon' => 'required|string|min:1|max:50',
'description' => 'required|string|min:1',
'price' => 'required|string|min:1|max:50',
'active' => 'boolean',
'featured' => 'boolean',
'display_order' => 'integer',
'features' => 'array'
```

**Portfolio**:
```php
'title' => 'required|string|min:1|max:200',
'category' => 'required|in:prototype,functional,art,industrial',
'description' => 'required|string|min:1',
'image_url' => 'required|url|max:500',
'details' => 'string'
```

**Testimonials**:
```php
'name' => 'required|string|min:1|max:100',
'position' => 'required|string|min:1|max:100',
'avatar_url' => 'required|url|max:500',
'rating' => 'integer|between:1,5',
'text' => 'required|string|min:1',
'approved' => 'boolean',
'display_order' => 'integer'
```

**FAQ**:
```php
'question' => 'required|string|min:1|max:500',
'answer' => 'required|string|min:1',
'active' => 'boolean',
'display_order' => 'integer'
```

**Content**:
```php
'title' => 'string|max:255',
'content' => 'required'  // must be array/object
```

**Stats**:
```php
'total_projects' => 'integer|min:0',
'happy_clients' => 'integer|min:0',
'years_experience' => 'integer|min:0',
'awards' => 'integer|min:0'
```

## Response Format

All responses follow this consistent format:

**Success (200/201)**:
```json
{
  "success": true,
  "message": "Resource retrieved successfully",
  "data": { ... }
}
```

**Error (400/404/422/500)**:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": "Error description"
  }
}
```

## Testing

### Public Endpoints (No Auth)
```bash
# Get all services
curl http://localhost:8080/api/services

# Get service by ID
curl http://localhost:8080/api/services/1

# Get portfolio with category filter
curl http://localhost:8080/api/portfolio?category=prototype

# Get approved testimonials
curl http://localhost:8080/api/testimonials

# Get active FAQ items
curl http://localhost:8080/api/faq

# Get content sections
curl http://localhost:8080/api/content
curl http://localhost:8080/api/content/hero

# Get site stats
curl http://localhost:8080/api/stats
```

### Admin Endpoints (Require Auth)

First, login to get token:
```bash
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}' \
  | jq -r '.data.token')
```

Then use the token for admin operations:
```bash
# Create service
curl -X POST http://localhost:8080/api/admin/services \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Service",
    "icon": "fa-test",
    "description": "Test description",
    "price": "100₽",
    "features": ["Feature 1", "Feature 2"]
  }'

# Update service
curl -X PUT http://localhost:8080/api/admin/services/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Updated Service"}'

# Delete service
curl -X DELETE http://localhost:8080/api/admin/services/1 \
  -H "Authorization: Bearer $TOKEN"

# Create portfolio item
curl -X POST http://localhost:8080/api/admin/portfolio \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Project",
    "category": "prototype",
    "description": "Test description",
    "image_url": "https://example.com/image.jpg"
  }'

# Update content section
curl -X PUT http://localhost:8080/api/admin/content/hero \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Hero Section",
    "content": {
      "heading": "Welcome",
      "subheading": "To our site"
    }
  }'

# Update stats
curl -X PUT http://localhost:8080/api/admin/stats \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "total_projects": 100,
    "happy_clients": 50,
    "years_experience": 5,
    "awards": 10
  }'
```

## Database Schema References

### Services
- Table: `services`
- Related: `service_features` (1:N relationship)
- Key fields: `id`, `name`, `slug`, `icon`, `description`, `price`, `active`, `featured`, `display_order`

### Portfolio
- Table: `portfolio`
- Key fields: `id`, `title`, `category`, `description`, `image_url`, `details`, `created_at`
- Categories: prototype, functional, art, industrial

### Testimonials
- Table: `testimonials`
- Key fields: `id`, `name`, `position`, `avatar_url`, `rating`, `text`, `approved`, `display_order`

### FAQ
- Table: `faq`
- Key fields: `id`, `question`, `answer`, `active`, `display_order`

### Content
- Table: `site_content`
- Key fields: `id`, `section_key`, `title`, `content` (JSON)
- Common sections: hero, about, features, contact

### Stats
- Table: `site_stats` (singleton - one row only)
- Key fields: `id`, `total_projects`, `happy_clients`, `years_experience`, `awards`

## Error Handling

The implementation includes comprehensive error handling:

1. **404 Not Found**: Resource doesn't exist
2. **422 Validation Error**: Input validation failed
3. **401 Unauthorized**: Missing or invalid JWT token
4. **403 Forbidden**: Insufficient permissions (not admin)
5. **400 Bad Request**: Malformed request or business logic error
6. **500 Internal Server Error**: Unexpected server error (caught by ErrorMiddleware)

## Future Enhancements

Consider implementing:
1. Pagination for large datasets (`?page=1&limit=20`)
2. Sorting options (`?sort=name&order=asc`)
3. Full-text search
4. Bulk operations
5. Rate limiting
6. API versioning
7. Response caching
8. File upload handling for images
9. Soft deletes (archive instead of delete)
10. Audit logging of all changes

## Troubleshooting

### Common Issues

**Issue**: "Service not found" on valid ID
- Check if service is active (public endpoints only return active records)
- Use admin endpoint to see all records

**Issue**: Validation errors
- Check request body matches validation rules
- Ensure Content-Type header is `application/json`
- Verify all required fields are present

**Issue**: 401 Unauthorized
- Verify JWT token is valid and not expired
- Check Authorization header format: `Bearer <token>`
- Login again to get fresh token

**Issue**: 403 Forbidden
- Verify user has 'admin' role
- Check JWT payload contains correct role

## Additional Resources

- Complete API documentation: `/docs/api.md`
- Database schema: `/backend/database/migrations/20231113_initial.sql`
- Seed data: `/backend/database/seeds/initial_data.sql`
- Authentication guide: `/backend/docs/AUTHENTICATION.md`
