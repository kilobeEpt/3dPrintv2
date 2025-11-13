# Content APIs - Quick Reference

This document provides a quick overview of the newly implemented content APIs.

## Overview

The Content API implementation provides full CRUD operations for:
- ✅ **Services** - Service catalog with features
- ✅ **Portfolio** - Project showcase with categories
- ✅ **Testimonials** - Customer reviews with approval workflow
- ✅ **FAQ** - Frequently asked questions
- ✅ **Content** - Dynamic site sections (hero, about, etc.)
- ✅ **Stats** - Site statistics display

## Architecture

```
Controller → Service → Repository → Database
     ↓          ↓          ↓
HTTP Layer  Business   Data Access
             Logic
```

## Files Created

### Helpers
- `src/Helpers/Validator.php` - Comprehensive validation helper

### Repositories (Data Access Layer)
- `src/Repositories/ServicesRepository.php`
- `src/Repositories/PortfolioRepository.php`
- `src/Repositories/TestimonialsRepository.php`
- `src/Repositories/FaqRepository.php`
- `src/Repositories/ContentRepository.php`

### Services (Business Logic Layer)
- `src/Services/ServicesService.php`
- `src/Services/PortfolioService.php`
- `src/Services/TestimonialsService.php`
- `src/Services/FaqService.php`
- `src/Services/ContentService.php`

### Controllers (HTTP Layer)
- `src/Controllers/ServicesController.php`
- `src/Controllers/PortfolioController.php`
- `src/Controllers/TestimonialsController.php`
- `src/Controllers/FaqController.php`
- `src/Controllers/ContentController.php`

### Documentation
- `docs/api.md` - Complete API documentation (21KB)
- `backend/docs/CONTENT_API.md` - Implementation guide

### Routes
All routes registered in `src/Bootstrap/App.php` via `registerContentRoutes()` method.

## Quick Start

### 1. Test Public Endpoints (No Auth Required)

```bash
# Services
curl http://localhost:8080/api/services

# Portfolio (with optional category filter)
curl http://localhost:8080/api/portfolio
curl http://localhost:8080/api/portfolio?category=prototype

# Testimonials (approved only)
curl http://localhost:8080/api/testimonials

# FAQ (active only)
curl http://localhost:8080/api/faq

# Content sections
curl http://localhost:8080/api/content
curl http://localhost:8080/api/content/hero

# Site stats
curl http://localhost:8080/api/stats
```

### 2. Test Admin Endpoints (Auth Required)

```bash
# Get admin token
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}' \
  | jq -r '.data.token')

# Create a service
curl -X POST http://localhost:8080/api/admin/services \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "3D Printing SLA",
    "icon": "fa-print",
    "description": "High-resolution SLA printing",
    "price": "от 100₽/г",
    "features": ["High precision", "Smooth surface"]
  }'

# Update stats
curl -X PUT http://localhost:8080/api/admin/stats \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"total_projects": 500, "happy_clients": 250}'
```

## Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/services` | List active services |
| GET | `/api/services/{id}` | Get service by ID |
| GET | `/api/portfolio` | List portfolio items |
| GET | `/api/portfolio/categories` | List categories |
| GET | `/api/portfolio/{id}` | Get portfolio item |
| GET | `/api/testimonials` | List approved testimonials |
| GET | `/api/testimonials/{id}` | Get testimonial |
| GET | `/api/faq` | List active FAQ |
| GET | `/api/faq/{id}` | Get FAQ item |
| GET | `/api/content` | List all content sections |
| GET | `/api/content/{section}` | Get content section |
| GET | `/api/stats` | Get site statistics |

## Admin Endpoints (Require Authentication)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/services` | List all services (including inactive) |
| POST | `/api/admin/services` | Create service |
| PUT/PATCH | `/api/admin/services/{id}` | Update service |
| DELETE | `/api/admin/services/{id}` | Delete service |
| POST | `/api/admin/portfolio` | Create portfolio item |
| PUT/PATCH | `/api/admin/portfolio/{id}` | Update portfolio item |
| DELETE | `/api/admin/portfolio/{id}` | Delete portfolio item |
| GET | `/api/admin/testimonials` | List all testimonials (including unapproved) |
| POST | `/api/admin/testimonials` | Create testimonial |
| PUT/PATCH | `/api/admin/testimonials/{id}` | Update testimonial |
| DELETE | `/api/admin/testimonials/{id}` | Delete testimonial |
| GET | `/api/admin/faq` | List all FAQ (including inactive) |
| POST | `/api/admin/faq` | Create FAQ item |
| PUT/PATCH | `/api/admin/faq/{id}` | Update FAQ item |
| DELETE | `/api/admin/faq/{id}` | Delete FAQ item |
| PUT/PATCH | `/api/admin/content/{section}` | Update/create content section |
| DELETE | `/api/admin/content/{section}` | Delete content section |
| PUT/PATCH | `/api/admin/stats` | Update statistics |

## Key Features

### Services API
- ✅ Auto-generates slugs from names
- ✅ Manages service features (1:N relationship)
- ✅ Active/inactive filtering
- ✅ Featured services flag
- ✅ Display order management

### Portfolio API
- ✅ Category filtering (prototype, functional, art, industrial)
- ✅ URL validation for images
- ✅ Ordered by creation date (newest first)

### Testimonials API
- ✅ Approval workflow (public shows only approved)
- ✅ Rating validation (1-5)
- ✅ Display order management
- ✅ Admin can see all testimonials

### FAQ API
- ✅ Active/inactive filtering
- ✅ Display order management
- ✅ Question length validation (max 500 chars)

### Content API
- ✅ JSON content storage for flexibility
- ✅ Section-based access (hero, about, etc.)
- ✅ Upsert operations (create or update)
- ✅ Separate stats management

## Validation

All admin endpoints validate input data:
- Required fields checked
- String lengths enforced
- URLs validated
- Enums verified (categories, etc.)
- Ratings constrained (1-5)
- Integer constraints applied

Validation errors return 422 status with detailed error messages.

## Security

- All admin endpoints protected by JWT authentication
- Role-based access control (requires 'admin' role)
- Prepared statements prevent SQL injection
- CORS configured for frontend origins
- Input validation on all mutations

## Response Format

```json
{
  "success": true/false,
  "message": "Human-readable message",
  "data": { ... },
  "errors": { ... }  // only on validation errors
}
```

## HTTP Status Codes

- `200 OK` - Success
- `201 Created` - Resource created
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Missing/invalid token
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `500 Internal Server Error` - Server error

## Database Tables

- `services` + `service_features`
- `portfolio`
- `testimonials`
- `faq`
- `site_content`
- `site_stats`

See `/backend/database/migrations/20231113_initial.sql` for full schema.

## Documentation

For complete API documentation with request/response examples, see:
- `/docs/api.md` - Full API reference
- `/backend/docs/CONTENT_API.md` - Implementation guide
- `/backend/docs/AUTHENTICATION.md` - Auth guide

## Testing with Seed Data

The database includes seed data in `/backend/database/seeds/initial_data.sql` with:
- Sample services with features
- Portfolio items across all categories
- Approved and unapproved testimonials
- FAQ items
- Content sections
- Site statistics

## Next Steps

To complete the API implementation, consider adding:
- Orders management API
- Calculator configuration API
- Form fields configuration API
- Site settings API
- Integrations API (Telegram, email)
- File upload handling
- Pagination for large lists
- Search functionality
