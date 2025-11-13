# Authentication Documentation

## Overview

The 3D Print Pro API uses **JWT (JSON Web Tokens)** for authentication. This document describes the authentication flow, endpoints, and best practices.

## Table of Contents

- [Authentication Flow](#authentication-flow)
- [Token Structure](#token-structure)
- [API Endpoints](#api-endpoints)
- [Frontend Integration](#frontend-integration)
- [User Management](#user-management)
- [Security Best Practices](#security-best-practices)

---

## Authentication Flow

```
┌─────────┐                                      ┌─────────┐
│ Client  │                                      │   API   │
└────┬────┘                                      └────┬────┘
     │                                                 │
     │  POST /api/auth/login                          │
     │  { login, password }                           │
     │────────────────────────────────────────────────>│
     │                                                 │
     │                      Verify credentials         │
     │                      Generate JWT               │
     │                                                 │
     │  200 OK                                         │
     │  { token, refreshToken, user }                 │
     │<────────────────────────────────────────────────│
     │                                                 │
     │  Store tokens (localStorage/sessionStorage)    │
     │                                                 │
     │  GET /api/auth/me                              │
     │  Authorization: Bearer <token>                 │
     │────────────────────────────────────────────────>│
     │                                                 │
     │                      Verify JWT                 │
     │                      Fetch user data            │
     │                                                 │
     │  200 OK                                         │
     │  { user }                                       │
     │<────────────────────────────────────────────────│
     │                                                 │
```

### Token Refresh Flow

```
┌─────────┐                                      ┌─────────┐
│ Client  │                                      │   API   │
└────┬────┘                                      └────┬────┘
     │                                                 │
     │  POST /api/auth/refresh                        │
     │  { refreshToken }                              │
     │────────────────────────────────────────────────>│
     │                                                 │
     │                      Verify refresh token       │
     │                      Generate new tokens        │
     │                                                 │
     │  200 OK                                         │
     │  { token, refreshToken }                       │
     │<────────────────────────────────────────────────│
     │                                                 │
```

---

## Token Structure

### Access Token

Access tokens are short-lived (default: 1 hour) and contain user information:

```json
{
  "iat": 1634567890,
  "exp": 1634571490,
  "sub": 1,
  "login": "admin",
  "email": "admin@3dprintpro.ru",
  "role": "admin"
}
```

**Fields:**
- `iat` - Issued at (Unix timestamp)
- `exp` - Expires at (Unix timestamp)
- `sub` - Subject (user ID)
- `login` - User login
- `email` - User email
- `role` - User role (admin, manager, user)

### Refresh Token

Refresh tokens are long-lived (default: 30 days) and used to obtain new access tokens:

```json
{
  "iat": 1634567890,
  "exp": 1637159890,
  "sub": 1,
  "type": "refresh"
}
```

---

## API Endpoints

### POST /api/auth/login

Authenticate a user and receive JWT tokens.

**Request:**
```json
{
  "login": "admin",
  "password": "admin123"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
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

**Error Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### GET /api/auth/me

Get the currently authenticated user's profile.

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (200 OK):**
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

**Error Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Authorization header is missing"
}
```

---

### POST /api/auth/logout

Logout the current user (client-side token removal).

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logout successful",
  "data": null
}
```

**Note:** This endpoint primarily serves as a convention. The actual logout happens client-side by removing the stored tokens. For server-side token blacklisting, you would need to implement a token blacklist (e.g., using Redis).

---

### POST /api/auth/refresh

Refresh an expired access token using a refresh token.

**Request:**
```json
{
  "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid refresh token"
}
```

---

## Frontend Integration

### Storing Tokens

Store tokens securely in the browser:

```javascript
// After successful login
const { token, refreshToken, user } = response.data;

// Option 1: localStorage (persists across sessions)
localStorage.setItem('accessToken', token);
localStorage.setItem('refreshToken', refreshToken);
localStorage.setItem('user', JSON.stringify(user));

// Option 2: sessionStorage (cleared when tab closes)
sessionStorage.setItem('accessToken', token);
sessionStorage.setItem('refreshToken', refreshToken);
sessionStorage.setItem('user', JSON.stringify(user));
```

### Making Authenticated Requests

Include the access token in the `Authorization` header:

```javascript
const accessToken = localStorage.getItem('accessToken');

fetch('http://localhost:8080/api/auth/me', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json'
  }
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### Handling Token Expiration

Implement automatic token refresh:

```javascript
async function fetchWithAuth(url, options = {}) {
  const accessToken = localStorage.getItem('accessToken');
  
  options.headers = {
    ...options.headers,
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json'
  };
  
  let response = await fetch(url, options);
  
  // If token expired, try to refresh
  if (response.status === 401) {
    const refreshToken = localStorage.getItem('refreshToken');
    
    const refreshResponse = await fetch('http://localhost:8080/api/auth/refresh', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refreshToken })
    });
    
    if (refreshResponse.ok) {
      const { token, refreshToken: newRefreshToken } = await refreshResponse.json();
      
      localStorage.setItem('accessToken', token);
      localStorage.setItem('refreshToken', newRefreshToken);
      
      // Retry original request with new token
      options.headers['Authorization'] = `Bearer ${token}`;
      response = await fetch(url, options);
    } else {
      // Refresh failed, redirect to login
      window.location.href = '/login.html';
      return null;
    }
  }
  
  return response;
}
```

### Logout Implementation

```javascript
function logout() {
  // Remove tokens from storage
  localStorage.removeItem('accessToken');
  localStorage.removeItem('refreshToken');
  localStorage.removeItem('user');
  
  // Optionally notify the server
  fetch('http://localhost:8080/api/auth/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
    }
  });
  
  // Redirect to login page
  window.location.href = '/login.html';
}
```

---

## User Management

### Creating a Default Admin User

The default admin user is created during database seeding. Use the seed script:

```bash
cd backend
php database/seeds/seed-admin-user.php
```

This script reads admin credentials from your `.env` file:

```env
ADMIN_LOGIN=admin
ADMIN_PASSWORD=admin123
ADMIN_NAME=Администратор
ADMIN_EMAIL=admin@3dprintpro.ru
```

**⚠️ Important:** Change the default password immediately after first login!

---

### Resetting User Passwords

Use the password reset CLI utility:

```bash
cd backend

# Interactive mode (password hidden)
php bin/reset-password.php admin

# Direct mode (password visible in command)
php bin/reset-password.php admin newSecurePassword123
```

**Interactive Mode:**
```
$ php bin/reset-password.php admin
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

---

### Creating Additional Admin Users

To create additional admin users, insert them directly into the database:

```sql
-- Generate a password hash
-- In PHP: password_hash('your_password', PASSWORD_BCRYPT);

INSERT INTO users (login, password_hash, name, email, role, active)
VALUES (
  'admin2',
  '$2y$10$...',  -- Use password_hash() in PHP to generate this
  'Second Admin',
  'admin2@3dprintpro.ru',
  'admin',
  TRUE
);
```

Or use a PHP script:

```php
<?php
require_once 'vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

Database::init([
    'host' => $_ENV['DB_HOST'],
    'port' => $_ENV['DB_PORT'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => $_ENV['DB_CHARSET']
]);

$db = Database::getConnection();

$passwordHash = password_hash('securePassword123', PASSWORD_BCRYPT);

$stmt = $db->prepare('
    INSERT INTO users (login, password_hash, name, email, role, active)
    VALUES (?, ?, ?, ?, ?, TRUE)
');

$stmt->execute([
    'newadmin',
    $passwordHash,
    'New Admin',
    'newadmin@3dprintpro.ru',
    'admin'
]);

echo "Admin user created successfully!\n";
```

---

## Security Best Practices

### 1. JWT Secret

Always use a strong, randomly generated JWT secret in production:

```bash
# Generate a secure secret
openssl rand -base64 64
```

Add to `.env`:
```env
JWT_SECRET=your_very_long_random_secret_here_at_least_64_characters
```

### 2. Password Requirements

Enforce strong passwords:
- Minimum 8 characters
- Mix of uppercase, lowercase, numbers, and symbols
- Not a common password

### 3. HTTPS Only

Always use HTTPS in production to prevent token interception:

```env
APP_URL=https://yourdomain.com
```

### 4. Token Expiration

Configure appropriate token lifetimes:

```env
# Short-lived access tokens (1 hour)
JWT_EXPIRATION=3600

# Long-lived refresh tokens handled in AuthService (30 days)
```

### 5. CORS Configuration

Restrict API access to your frontend domain:

```env
CORS_ORIGIN=https://yourdomain.com
```

For multiple domains:
```env
CORS_ORIGIN=https://yourdomain.com,https://admin.yourdomain.com
```

### 6. Rate Limiting

Implement rate limiting for auth endpoints to prevent brute force attacks (future enhancement).

### 7. Token Blacklisting

For critical applications, implement token blacklisting using Redis or a database table to invalidate tokens before expiration (future enhancement).

---

## Role-Based Access Control

### Available Roles

- `admin` - Full access to all resources
- `manager` - Limited administrative access
- `user` - Basic user access

### Protecting Routes by Role

Use the `AuthMiddleware` with role restrictions:

```php
// Admin-only route
$app->get('/api/admin/dashboard', [DashboardController::class, 'index'])
    ->add(new AuthMiddleware($authService, ['admin']));

// Admin or Manager
$app->get('/api/orders', [OrdersController::class, 'list'])
    ->add(new AuthMiddleware($authService, ['admin', 'manager']));

// Any authenticated user
$app->get('/api/profile', [ProfileController::class, 'show'])
    ->add(new AuthMiddleware($authService));
```

### Frontend Role Checks

```javascript
const user = JSON.parse(localStorage.getItem('user'));

if (user.role === 'admin') {
  // Show admin-only features
}
```

---

## Troubleshooting

### Invalid or Expired Token

**Error:**
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

**Solution:**
- Use the refresh endpoint to get a new token
- If refresh fails, redirect user to login

### Authorization Header Missing

**Error:**
```json
{
  "success": false,
  "message": "Authorization header is missing"
}
```

**Solution:**
- Ensure `Authorization: Bearer <token>` header is included
- Check CORS configuration if making cross-origin requests

### Insufficient Permissions

**Error:**
```json
{
  "success": false,
  "message": "Insufficient permissions"
}
```

**Solution:**
- User's role doesn't have access to the resource
- Verify user has the correct role in the database

---

## Example Test Routes

The API includes example protected routes for testing:

### GET /api/protected

Any authenticated user can access:

```bash
curl -X GET http://localhost:8080/api/protected \
  -H "Authorization: Bearer <your_token>"
```

### GET /api/admin

Only admin users can access:

```bash
curl -X GET http://localhost:8080/api/admin \
  -H "Authorization: Bearer <your_token>"
```

---

## Additional Resources

- [JWT.io](https://jwt.io/) - JWT debugger and documentation
- [PHP JWT Library](https://github.com/firebase/php-jwt) - Official Firebase JWT library
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)

---

## Questions?

For issues or questions, please refer to the main README or create an issue in the project repository.
