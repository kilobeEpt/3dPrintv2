# 3D Print Pro API Documentation

## Overview

The 3D Print Pro API provides access to manage services, portfolio items, testimonials, FAQ, and site content. The API follows RESTful principles and returns JSON responses.

**Base URL**: `http://your-domain.com/api`

## Authentication

Admin endpoints require JWT authentication. Include the token in the Authorization header:

```
Authorization: Bearer <your_jwt_token>
```

To obtain a token, use the `/api/auth/login` endpoint.

## Response Format

All responses follow this format:

```json
{
  "success": true,
  "message": "Success message",
  "data": { ... }
}
```

Error responses (4xx, 5xx):

```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

## HTTP Status Codes

- `200 OK` - Request succeeded
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Missing or invalid token
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `500 Internal Server Error` - Server error

---

## Authentication Endpoints

### Login

**POST** `/api/auth/login`

Authenticate and receive JWT tokens.

**Request Body:**
```json
{
  "login": "admin",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "login": "admin",
      "name": "Admin User",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

### Get Current User

**GET** `/api/auth/me`

Get authenticated user profile. Requires authentication.

**Response (200):**
```json
{
  "success": true,
  "message": "User retrieved successfully",
  "data": {
    "id": 1,
    "login": "admin",
    "name": "Admin User",
    "email": "admin@example.com",
    "role": "admin",
    "lastLogin": "2023-11-13 10:30:00",
    "createdAt": "2023-01-01 00:00:00"
  }
}
```

### Refresh Token

**POST** `/api/auth/refresh`

Refresh an expired access token.

**Request Body:**
```json
{
  "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

---

## Services Endpoints

### List Services (Public)

**GET** `/api/services`

Get all active services with features. No authentication required.

**Response (200):**
```json
{
  "success": true,
  "message": "Services retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "3D Printing FDM",
      "slug": "3d-printing-fdm",
      "icon": "fa-cube",
      "description": "High-quality FDM 3D printing for prototypes and functional parts",
      "price": "от 50₽/г",
      "active": true,
      "featured": true,
      "display_order": 1,
      "created_at": "2023-11-13 10:00:00",
      "updated_at": "2023-11-13 10:00:00",
      "features": [
        {
          "id": 1,
          "service_id": 1,
          "feature_text": "Fast turnaround time",
          "display_order": 0
        },
        {
          "id": 2,
          "service_id": 1,
          "feature_text": "Wide material selection",
          "display_order": 1
        }
      ]
    }
  ]
}
```

### Get Service by ID (Public)

**GET** `/api/services/{id}`

Get a single service with features. No authentication required.

**Response (200):**
```json
{
  "success": true,
  "message": "Service retrieved successfully",
  "data": {
    "id": 1,
    "name": "3D Printing FDM",
    "slug": "3d-printing-fdm",
    "icon": "fa-cube",
    "description": "High-quality FDM 3D printing for prototypes and functional parts",
    "price": "от 50₽/г",
    "active": true,
    "featured": true,
    "display_order": 1,
    "features": [...]
  }
}
```

### List All Services (Admin)

**GET** `/api/admin/services`

Get all services including inactive ones. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):** Same as public list, but includes inactive services.

### Create Service (Admin)

**POST** `/api/admin/services`

Create a new service. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "3D Printing SLA",
  "slug": "3d-printing-sla",
  "icon": "fa-print",
  "description": "High-resolution SLA 3D printing for detailed models",
  "price": "от 100₽/г",
  "active": true,
  "featured": false,
  "display_order": 2,
  "features": [
    "Ultra-high precision",
    "Smooth surface finish",
    "Perfect for miniatures"
  ]
}
```

**Validation Rules:**
- `name`: required, string, 1-100 characters
- `slug`: optional (auto-generated from name), string, 1-100 characters, must be unique
- `icon`: required, string, 1-50 characters
- `description`: required, string
- `price`: required, string, 1-50 characters
- `active`: optional, boolean, default: true
- `featured`: optional, boolean, default: false
- `display_order`: optional, integer, default: 0
- `features`: optional, array of strings or objects

**Response (201):**
```json
{
  "success": true,
  "message": "Service created successfully",
  "data": {
    "id": 2,
    "name": "3D Printing SLA",
    "slug": "3d-printing-sla",
    "features": [...]
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": "Name is required",
    "icon": "Icon is required"
  }
}
```

### Update Service (Admin)

**PUT/PATCH** `/api/admin/services/{id}`

Update an existing service. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:** Same as create, all fields optional.

**Response (200):**
```json
{
  "success": true,
  "message": "Service updated successfully",
  "data": { ... }
}
```

### Delete Service (Admin)

**DELETE** `/api/admin/services/{id}`

Delete a service and all its features. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "success": true,
  "message": "Service deleted successfully",
  "data": null
}
```

---

## Portfolio Endpoints

### List Portfolio Items (Public)

**GET** `/api/portfolio`

Get all portfolio items. Supports optional category filter.

**Query Parameters:**
- `category` (optional): Filter by category (prototype, functional, art, industrial)

**Example:** `/api/portfolio?category=prototype`

**Response (200):**
```json
{
  "success": true,
  "message": "Portfolio items retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Prototype Engine Part",
      "category": "prototype",
      "description": "Complex engine prototype with precise tolerances",
      "image_url": "https://example.com/images/portfolio1.jpg",
      "details": "Printed in ABS with 0.1mm layer height...",
      "created_at": "2023-11-13 10:00:00",
      "updated_at": "2023-11-13 10:00:00"
    }
  ]
}
```

### Get Portfolio Categories

**GET** `/api/portfolio/categories`

Get list of all portfolio categories.

**Response (200):**
```json
{
  "success": true,
  "message": "Categories retrieved successfully",
  "data": ["prototype", "functional", "art", "industrial"]
}
```

### Get Portfolio Item by ID (Public)

**GET** `/api/portfolio/{id}`

Get a single portfolio item.

**Response (200):**
```json
{
  "success": true,
  "message": "Portfolio item retrieved successfully",
  "data": { ... }
}
```

### Create Portfolio Item (Admin)

**POST** `/api/admin/portfolio`

Create a new portfolio item. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Functional Gear Assembly",
  "category": "functional",
  "description": "Working gear mechanism for robotics project",
  "image_url": "https://example.com/images/gear.jpg",
  "details": "Printed in PETG for durability..."
}
```

**Validation Rules:**
- `title`: required, string, 1-200 characters
- `category`: required, enum (prototype, functional, art, industrial)
- `description`: required, string
- `image_url`: required, valid URL, max 500 characters
- `details`: optional, string

**Response (201):**
```json
{
  "success": true,
  "message": "Portfolio item created successfully",
  "data": { ... }
}
```

### Update Portfolio Item (Admin)

**PUT/PATCH** `/api/admin/portfolio/{id}`

Update an existing portfolio item. Requires admin authentication.

**Response (200):**
```json
{
  "success": true,
  "message": "Portfolio item updated successfully",
  "data": { ... }
}
```

### Delete Portfolio Item (Admin)

**DELETE** `/api/admin/portfolio/{id}`

Delete a portfolio item. Requires admin authentication.

**Response (200):**
```json
{
  "success": true,
  "message": "Portfolio item deleted successfully",
  "data": null
}
```

---

## Testimonials Endpoints

### List Testimonials (Public)

**GET** `/api/testimonials`

Get all approved testimonials. No authentication required.

**Response (200):**
```json
{
  "success": true,
  "message": "Testimonials retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "position": "CEO, Tech Company",
      "avatar_url": "https://example.com/avatars/john.jpg",
      "rating": 5,
      "text": "Excellent service! Fast turnaround and high quality prints.",
      "approved": true,
      "display_order": 1,
      "created_at": "2023-11-13 10:00:00",
      "updated_at": "2023-11-13 10:00:00"
    }
  ]
}
```

### Get Testimonial by ID (Public)

**GET** `/api/testimonials/{id}`

Get a single testimonial.

**Response (200):**
```json
{
  "success": true,
  "message": "Testimonial retrieved successfully",
  "data": { ... }
}
```

### List All Testimonials (Admin)

**GET** `/api/admin/testimonials`

Get all testimonials including unapproved ones. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):** Same as public list, but includes unapproved testimonials.

### Create Testimonial (Admin)

**POST** `/api/admin/testimonials`

Create a new testimonial. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Jane Smith",
  "position": "Product Manager",
  "avatar_url": "https://example.com/avatars/jane.jpg",
  "rating": 5,
  "text": "Outstanding quality and customer service!",
  "approved": true,
  "display_order": 2
}
```

**Validation Rules:**
- `name`: required, string, 1-100 characters
- `position`: required, string, 1-100 characters
- `avatar_url`: required, valid URL, max 500 characters
- `rating`: optional, integer between 1-5, default: 5
- `text`: required, string
- `approved`: optional, boolean, default: false
- `display_order`: optional, integer, default: 0

**Response (201):**
```json
{
  "success": true,
  "message": "Testimonial created successfully",
  "data": { ... }
}
```

### Update Testimonial (Admin)

**PUT/PATCH** `/api/admin/testimonials/{id}`

Update an existing testimonial. Requires admin authentication.

**Response (200):**
```json
{
  "success": true,
  "message": "Testimonial updated successfully",
  "data": { ... }
}
```

### Delete Testimonial (Admin)

**DELETE** `/api/admin/testimonials/{id}`

Delete a testimonial. Requires admin authentication.

**Response (200):**
```json
{
  "success": true,
  "message": "Testimonial deleted successfully",
  "data": null
}
```

---

## FAQ Endpoints

### List FAQ Items (Public)

**GET** `/api/faq`

Get all active FAQ items. No authentication required.

**Response (200):**
```json
{
  "success": true,
  "message": "FAQ items retrieved successfully",
  "data": [
    {
      "id": 1,
      "question": "What materials do you support?",
      "answer": "We support PLA, ABS, PETG, TPU, and many other materials...",
      "active": true,
      "display_order": 1,
      "created_at": "2023-11-13 10:00:00",
      "updated_at": "2023-11-13 10:00:00"
    }
  ]
}
```

### Get FAQ Item by ID (Public)

**GET** `/api/faq/{id}`

Get a single FAQ item.

**Response (200):**
```json
{
  "success": true,
  "message": "FAQ item retrieved successfully",
  "data": { ... }
}
```

### List All FAQ Items (Admin)

**GET** `/api/admin/faq`

Get all FAQ items including inactive ones. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):** Same as public list, but includes inactive items.

### Create FAQ Item (Admin)

**POST** `/api/admin/faq`

Create a new FAQ item. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "question": "How long does printing take?",
  "answer": "Printing time depends on size and complexity. Typical parts take 4-24 hours.",
  "active": true,
  "display_order": 2
}
```

**Validation Rules:**
- `question`: required, string, 1-500 characters
- `answer`: required, string
- `active`: optional, boolean, default: true
- `display_order`: optional, integer, default: 0

**Response (201):**
```json
{
  "success": true,
  "message": "FAQ item created successfully",
  "data": { ... }
}
```

### Update FAQ Item (Admin)

**PUT/PATCH** `/api/admin/faq/{id}`

Update an existing FAQ item. Requires admin authentication.

**Response (200):**
```json
{
  "success": true,
  "message": "FAQ item updated successfully",
  "data": { ... }
}
```

### Delete FAQ Item (Admin)

**DELETE** `/api/admin/faq/{id}`

Delete an FAQ item. Requires admin authentication.

**Response (200):**
```json
{
  "success": true,
  "message": "FAQ item deleted successfully",
  "data": null
}
```

---

## Content Endpoints

### List All Content Sections (Public)

**GET** `/api/content`

Get all content sections (hero, about, etc.). No authentication required.

**Response (200):**
```json
{
  "success": true,
  "message": "Content sections retrieved successfully",
  "data": [
    {
      "id": 1,
      "section_key": "hero",
      "title": "Welcome to 3D Print Pro",
      "content": {
        "heading": "Professional 3D Printing Services",
        "subheading": "Turn your ideas into reality",
        "cta_text": "Get Started",
        "cta_url": "#contact"
      },
      "created_at": "2023-11-13 10:00:00",
      "updated_at": "2023-11-13 10:00:00"
    },
    {
      "id": 2,
      "section_key": "about",
      "title": "About Us",
      "content": {
        "text": "We are a leading 3D printing service...",
        "features": ["Quality", "Speed", "Reliability"]
      },
      "created_at": "2023-11-13 10:00:00",
      "updated_at": "2023-11-13 10:00:00"
    }
  ]
}
```

### Get Content Section by Key (Public)

**GET** `/api/content/{section}`

Get a specific content section.

**Example:** `/api/content/hero`

**Response (200):**
```json
{
  "success": true,
  "message": "Content section retrieved successfully",
  "data": {
    "id": 1,
    "section_key": "hero",
    "title": "Welcome to 3D Print Pro",
    "content": { ... }
  }
}
```

### Update/Create Content Section (Admin)

**PUT/PATCH** `/api/admin/content/{section}`

Update or create a content section (upsert). Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Welcome to 3D Print Pro",
  "content": {
    "heading": "Professional 3D Printing Services",
    "subheading": "Turn your ideas into reality",
    "cta_text": "Get Started",
    "cta_url": "#contact"
  }
}
```

**Validation Rules:**
- `title`: optional, string, max 255 characters
- `content`: required, object/array

**Response (200):**
```json
{
  "success": true,
  "message": "Content section updated successfully",
  "data": { ... }
}
```

### Delete Content Section (Admin)

**DELETE** `/api/admin/content/{section}`

Delete a content section. Requires admin authentication.

**Response (200):**
```json
{
  "success": true,
  "message": "Content section deleted successfully",
  "data": null
}
```

---

## Stats Endpoints

### Get Site Stats (Public)

**GET** `/api/stats`

Get site statistics. No authentication required.

**Response (200):**
```json
{
  "success": true,
  "message": "Stats retrieved successfully",
  "data": {
    "id": 1,
    "total_projects": 500,
    "happy_clients": 250,
    "years_experience": 5,
    "awards": 10,
    "updated_at": "2023-11-13 10:00:00"
  }
}
```

### Update Site Stats (Admin)

**PUT/PATCH** `/api/admin/stats`

Update site statistics. Requires admin authentication.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "total_projects": 550,
  "happy_clients": 275,
  "years_experience": 6,
  "awards": 12
}
```

**Validation Rules:**
- `total_projects`: optional, integer, min: 0
- `happy_clients`: optional, integer, min: 0
- `years_experience`: optional, integer, min: 0
- `awards`: optional, integer, min: 0

**Response (200):**
```json
{
  "success": true,
  "message": "Stats updated successfully",
  "data": {
    "id": 1,
    "total_projects": 550,
    "happy_clients": 275,
    "years_experience": 6,
    "awards": 12,
    "updated_at": "2023-11-13 12:00:00"
  }
}
```

---

## Error Handling

All endpoints return consistent error responses:

### 400 Bad Request
```json
{
  "success": false,
  "message": "Invalid request data"
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Insufficient permissions"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": "Error message",
    "another_field": "Another error message"
  }
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## Complete Route List

### Public Routes (No Authentication)
- `GET /api/health` - Health check
- `GET /api` - API information
- `POST /api/auth/login` - Login
- `POST /api/auth/refresh` - Refresh token
- `GET /api/services` - List active services
- `GET /api/services/{id}` - Get service by ID
- `GET /api/portfolio` - List portfolio items
- `GET /api/portfolio/categories` - List portfolio categories
- `GET /api/portfolio/{id}` - Get portfolio item by ID
- `GET /api/testimonials` - List approved testimonials
- `GET /api/testimonials/{id}` - Get testimonial by ID
- `GET /api/faq` - List active FAQ items
- `GET /api/faq/{id}` - Get FAQ item by ID
- `GET /api/content` - List all content sections
- `GET /api/content/{section}` - Get content section by key
- `GET /api/stats` - Get site statistics

### Protected Routes (Require Authentication)
- `GET /api/auth/me` - Get current user

### Admin Routes (Require Admin Role)
- `GET /api/admin/services` - List all services
- `POST /api/admin/services` - Create service
- `PUT/PATCH /api/admin/services/{id}` - Update service
- `DELETE /api/admin/services/{id}` - Delete service
- `POST /api/admin/portfolio` - Create portfolio item
- `PUT/PATCH /api/admin/portfolio/{id}` - Update portfolio item
- `DELETE /api/admin/portfolio/{id}` - Delete portfolio item
- `GET /api/admin/testimonials` - List all testimonials
- `POST /api/admin/testimonials` - Create testimonial
- `PUT/PATCH /api/admin/testimonials/{id}` - Update testimonial
- `DELETE /api/admin/testimonials/{id}` - Delete testimonial
- `GET /api/admin/faq` - List all FAQ items
- `POST /api/admin/faq` - Create FAQ item
- `PUT/PATCH /api/admin/faq/{id}` - Update FAQ item
- `DELETE /api/admin/faq/{id}` - Delete FAQ item
- `PUT/PATCH /api/admin/content/{section}` - Update/create content section
- `DELETE /api/admin/content/{section}` - Delete content section
- `PUT/PATCH /api/admin/stats` - Update site statistics

---

## Testing the API

### Using cURL

**Login:**
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}'
```

**Get Services:**
```bash
curl http://localhost:8080/api/services
```

**Create Service (Admin):**
```bash
curl -X POST http://localhost:8080/api/admin/services \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "3D Printing SLA",
    "icon": "fa-print",
    "description": "High-resolution SLA printing",
    "price": "от 100₽/г",
    "features": ["High precision", "Smooth finish"]
  }'
```

### Using Postman

1. Import the API endpoints into Postman
2. Create an environment with `base_url` = `http://localhost:8080`
3. Set up authentication token in environment variables
4. Use `{{base_url}}/api/endpoint` for requests

---

## Rate Limiting

Currently, the API does not implement rate limiting. For production deployments, consider implementing rate limiting at the web server level (Nginx/Apache) or using middleware.

## Pagination

Currently, all list endpoints return all records. For large datasets, consider implementing pagination with `?page=1&limit=20` query parameters.

## CORS

The API supports Cross-Origin Resource Sharing (CORS). Configure allowed origins in the `.env` file:

```
CORS_ORIGIN=http://localhost:3000,https://your-frontend-domain.com
```

---

## Support

For questions or issues, contact: admin@3dprintpro.com
