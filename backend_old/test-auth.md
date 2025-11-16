# Authentication System Testing Guide

This document provides examples for testing the authentication system once the backend is running.

## Prerequisites

1. **Start the PHP development server:**
   ```bash
   cd backend
   php -S localhost:8080 -t public
   ```

2. **Ensure database is set up:**
   ```bash
   mysql -u root -p < database/migrations/20231113_initial.sql
   php database/seeds/seed-admin-user.php
   ```

## Test Endpoints

### 1. Health Check (No Auth Required)

```bash
curl -X GET http://localhost:8080/api/health
```

**Expected Response:**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-15 10:30:00",
  "environment": "development",
  "database": {
    "connected": true,
    "message": "Database connection successful",
    "version": "8.0.35",
    "database": "ch167436_3dprint"
  }
}
```

---

### 2. Login (No Auth Required)

**Valid Credentials:**

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "admin",
    "password": "admin123"
  }'
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2MzQ1Njc4OTAsImV4cCI6MTYzNDU3MTQ5MCwic3ViIjoxLCJsb2dpbiI6ImFkbWluIiwiZW1haWwiOiJhZG1pbkAzZHByaW50cHJvLnJ1Iiwicm9sZSI6ImFkbWluIn0.xxxxx",
    "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2MzQ1Njc4OTAsImV4cCI6MTYzNzE1OTg5MCwic3ViIjoxLCJ0eXBlIjoicmVmcmVzaCJ9.xxxxx",
    "user": {
      "id": 1,
      "login": "admin",
      "name": "Администратор",
      "email": "admin@3dprintpro.ru",
      "role": "admin"
    }
  }
}
```

**Invalid Credentials:**

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "admin",
    "password": "wrongpassword"
  }'
```

**Expected Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

**Missing Fields:**

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "admin"
  }'
```

**Expected Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Login and password are required",
  "errors": {
    "login": null,
    "password": "Password is required"
  }
}
```

---

### 3. Get Current User (Auth Required)

**Save the token from login response:**
```bash
TOKEN="your_token_here"
```

**Request:**
```bash
curl -X GET http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer ${TOKEN}"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "User retrieved successfully",
  "data": {
    "id": 1,
    "login": "admin",
    "name": "Администратор",
    "email": "admin@3dprintpro.ru",
    "role": "admin",
    "lastLogin": "2024-01-15 10:30:00",
    "createdAt": "2023-11-13 12:00:00"
  }
}
```

**No Token:**
```bash
curl -X GET http://localhost:8080/api/auth/me
```

**Expected Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Authorization header is missing"
}
```

**Invalid Token:**
```bash
curl -X GET http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer invalid_token"
```

**Expected Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

---

### 4. Refresh Token (No Auth Required)

```bash
REFRESH_TOKEN="your_refresh_token_here"

curl -X POST http://localhost:8080/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d "{
    \"refreshToken\": \"${REFRESH_TOKEN}\"
  }"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "new_access_token",
    "refreshToken": "new_refresh_token"
  }
}
```

---

### 5. Protected Route (Auth Required, Any Role)

```bash
curl -X GET http://localhost:8080/api/protected \
  -H "Authorization: Bearer ${TOKEN}"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "This is a protected route",
    "user": {
      "id": 1,
      "login": "admin",
      "name": "Администратор",
      "email": "admin@3dprintpro.ru",
      "role": "admin",
      "last_login_at": "2024-01-15 10:30:00",
      "created_at": "2023-11-13 12:00:00"
    }
  }
}
```

---

### 6. Admin-Only Route (Auth Required, Admin Role Only)

**As Admin User:**
```bash
curl -X GET http://localhost:8080/api/admin \
  -H "Authorization: Bearer ${TOKEN}"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "This is an admin-only route",
    "user": {
      "id": 1,
      "login": "admin",
      "name": "Администратор",
      "email": "admin@3dprintpro.ru",
      "role": "admin",
      "last_login_at": "2024-01-15 10:30:00",
      "created_at": "2023-11-13 12:00:00"
    }
  }
}
```

**As Non-Admin User (if you create one):**

**Expected Response (403 Forbidden):**
```json
{
  "success": false,
  "message": "Insufficient permissions"
}
```

---

### 7. Logout (Auth Optional)

```bash
curl -X POST http://localhost:8080/api/auth/logout \
  -H "Authorization: Bearer ${TOKEN}"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Logout successful",
  "data": null
}
```

**Note:** This endpoint is primarily for convention. The actual logout happens client-side by removing stored tokens.

---

## Frontend JavaScript Examples

### Login and Store Token

```javascript
async function login(login, password) {
  try {
    const response = await fetch('http://localhost:8080/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ login, password })
    });

    const result = await response.json();

    if (result.success) {
      // Store tokens
      localStorage.setItem('accessToken', result.data.token);
      localStorage.setItem('refreshToken', result.data.refreshToken);
      localStorage.setItem('user', JSON.stringify(result.data.user));
      
      console.log('Login successful:', result.data.user);
      return result.data;
    } else {
      console.error('Login failed:', result.message);
      return null;
    }
  } catch (error) {
    console.error('Login error:', error);
    return null;
  }
}

// Usage
login('admin', 'admin123');
```

### Make Authenticated Request

```javascript
async function getCurrentUser() {
  const token = localStorage.getItem('accessToken');

  if (!token) {
    console.error('No token found');
    return null;
  }

  try {
    const response = await fetch('http://localhost:8080/api/auth/me', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    const result = await response.json();

    if (result.success) {
      console.log('User:', result.data);
      return result.data;
    } else {
      console.error('Failed to get user:', result.message);
      return null;
    }
  } catch (error) {
    console.error('Error:', error);
    return null;
  }
}

// Usage
getCurrentUser();
```

### Logout

```javascript
function logout() {
  // Remove tokens from storage
  localStorage.removeItem('accessToken');
  localStorage.removeItem('refreshToken');
  localStorage.removeItem('user');
  
  // Optionally notify the server
  const token = localStorage.getItem('accessToken');
  if (token) {
    fetch('http://localhost:8080/api/auth/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    }).catch(err => console.log('Logout notification failed:', err));
  }
  
  // Redirect to login page
  window.location.href = '/login.html';
}

// Usage
logout();
```

---

## Password Management

### Reset Password via CLI

**Interactive Mode (Recommended):**
```bash
php bin/reset-password.php admin
```

Output:
```
Found user:
  ID: 1
  Login: admin
  Name: Администратор
  Email: admin@3dprintpro.ru
  Role: admin

Enter new password: ********
Confirm new password: ********
✓ Password updated successfully for user: admin

You can now login with:
  Login: admin
  Password: [the one you just set]
```

**Direct Mode:**
```bash
php bin/reset-password.php admin newSecurePassword123
```

---

## Validation Tests

### JWT Token Expiration

Wait for the token to expire (default: 1 hour), then try to access a protected route:

```bash
# This will fail after 1 hour
curl -X GET http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer ${TOKEN}"
```

**Expected Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

Use the refresh endpoint to get a new token:
```bash
curl -X POST http://localhost:8080/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d "{\"refreshToken\": \"${REFRESH_TOKEN}\"}"
```

---

## Troubleshooting

### CORS Issues

If testing from a browser and getting CORS errors, ensure `CORS_ORIGIN` in `.env` includes your frontend domain:

```env
CORS_ORIGIN=http://localhost:8000,http://127.0.0.1:8000
```

### Database Connection Errors

Check the health endpoint:
```bash
curl http://localhost:8080/api/health
```

If database is unhealthy, verify `.env` settings and ensure MySQL is running.

### Invalid JWT Secret

If getting "Invalid or expired token" immediately after login, check that `JWT_SECRET` is set in `.env`:

```env
JWT_SECRET=your_long_random_secret_key_here
```

Generate a secure secret:
```bash
openssl rand -base64 64
```

---

## Success Criteria Verification

✅ **Login with correct credentials returns JWT:**
- POST /api/auth/login with correct credentials returns 200 with token

✅ **Incorrect credentials return 401:**
- POST /api/auth/login with wrong password returns 401

✅ **Protected route rejects unauthenticated requests:**
- GET /api/protected without token returns 401
- GET /api/admin without token returns 401

✅ **Protected route allows valid tokens:**
- GET /api/protected with valid token returns 200
- GET /api/auth/me with valid token returns 200

✅ **Admin route enforces role-based access:**
- GET /api/admin with admin token returns 200
- GET /api/admin with non-admin token returns 403

✅ **JWT secret loaded from env:**
- Check JWT_SECRET in .env
- Tokens are signed with this secret

✅ **Tokens include expiry:**
- Decode token payload to see 'exp' field
- Expired tokens are rejected

✅ **Frontend can consume tokens:**
- Tokens work with fetch/axios from JavaScript
- CORS configured correctly

✅ **Password management documented:**
- README.md includes admin user creation
- bin/reset-password.php available for password rotation
- docs/AUTHENTICATION.md has full guide
