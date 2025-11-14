# Admin Authentication Testing Guide

## Overview

This guide covers testing the new JWT-based authentication system for the admin panel, which replaces the previous localStorage-based authentication.

## What Changed

### Before
- Simple localStorage check with hardcoded credentials (`admin` / `admin123`)
- No backend validation
- No session persistence across page reloads
- No token expiration handling

### After
- ✅ Backend JWT authentication via `/api/auth/login`
- ✅ Token validation via `/api/auth/me` on page load
- ✅ Automatic session restoration with valid tokens
- ✅ 401 response interceptor with automatic logout
- ✅ Backend error message display
- ✅ Password field reset on errors
- ✅ User info display from backend data
- ✅ Token refresh capability (future enhancement)

## Architecture

### AdminAPIClient Class (`js/admin.js`)

Handles all authenticated API requests:

```javascript
const adminApi = new AdminAPIClient();

// Login
await adminApi.login('admin', 'password');

// Get current user (validates token)
await adminApi.getCurrentUser();

// Logout
await adminApi.logout();

// Authenticated fetch wrapper
await adminApi.fetch('/api/admin/services');
```

**Key Features:**
- Stores JWT tokens in localStorage (`admin_access_token`, `admin_refresh_token`)
- Automatically adds `Authorization: Bearer <token>` header to all requests
- Intercepts 401 responses and triggers logout
- Dispatches `admin:unauthorized` event for global handling

### AdminPanel Integration

The `AdminPanel` class now uses `AdminAPIClient`:

```javascript
async checkAuth() {
    const token = this.api.getToken();
    
    if (token) {
        // Validate with backend
        const result = await this.api.getCurrentUser();
        
        if (result.success) {
            // Token valid - restore session
            this.currentUser = result.data.data;
            this.showDashboard();
        } else {
            // Token invalid - show login
            this.showLoginScreen();
        }
    } else {
        this.showLoginScreen();
    }
}
```

## Testing Scenarios

### 1. Fresh Login

**Steps:**
1. Open `admin.html` in browser
2. Enter valid credentials (from backend `.env` file)
3. Click "Войти"

**Expected Behavior:**
- ✅ Loading state shows ("Вход..." with spinner)
- ✅ Success notification appears
- ✅ Dashboard loads after 500ms
- ✅ User name/login displayed in sidebar and header
- ✅ Tokens stored in localStorage

**Verify in DevTools:**
```javascript
// Console
localStorage.getItem('admin_access_token')  // Should show JWT
localStorage.getItem('admin_refresh_token') // Should show JWT

// Network tab
// POST /api/auth/login should return 200 with tokens
```

### 2. Invalid Credentials

**Steps:**
1. Open `admin.html`
2. Enter invalid credentials
3. Click "Войти"

**Expected Behavior:**
- ✅ Error notification shows backend message
- ✅ Password field clears
- ✅ Password field shakes (animation)
- ✅ Password field gets focus
- ✅ Form re-enabled for retry
- ✅ No tokens stored

**Verify in DevTools:**
```javascript
// Console - should see error log
// Network tab - POST /api/auth/login returns 401
```

### 3. Page Reload with Valid Token

**Steps:**
1. Login successfully
2. Reload page (F5 or Ctrl+R)

**Expected Behavior:**
- ✅ Page loads directly to dashboard (no login screen)
- ✅ User data restored from backend
- ✅ GET /api/auth/me called successfully
- ✅ Dashboard data loads

**Verify in DevTools:**
```javascript
// Network tab
// GET /api/auth/me called with Authorization header
// Should return 200 with user data
```

### 4. Page Reload with Expired Token

**Steps:**
1. Login successfully
2. Wait for token to expire (1 hour by default) OR manually corrupt token
3. Reload page

**Expected Behavior:**
- ✅ GET /api/auth/me returns 401
- ✅ Tokens cleared from localStorage
- ✅ User redirected to login screen
- ✅ No dashboard data loaded

**Quick Test (Manual Token Corruption):**
```javascript
// In DevTools Console after login
localStorage.setItem('admin_access_token', 'invalid_token');
location.reload(); // Should redirect to login
```

### 5. Token Expiration During Session

**Steps:**
1. Login successfully
2. Keep page open for > 1 hour (or manually corrupt token)
3. Try to perform any admin action that calls the API

**Expected Behavior:**
- ✅ API request returns 401
- ✅ AdminAPIClient intercepts 401
- ✅ `admin:unauthorized` event dispatched
- ✅ Notification shown: "Сессия истекла..."
- ✅ Tokens cleared
- ✅ Redirected to login screen after 1 second

**Quick Test (Manual 401 Simulation):**
```javascript
// In DevTools Console after login
localStorage.setItem('admin_access_token', 'expired_token');
// Now try to navigate to any admin page or perform an action
// Should trigger logout
```

### 6. Manual Logout

**Steps:**
1. Login successfully
2. Click "Выход" button in sidebar
3. Confirm logout in dialog

**Expected Behavior:**
- ✅ Confirmation dialog appears
- ✅ POST /api/auth/logout called (optional)
- ✅ Tokens cleared from localStorage
- ✅ Success notification shown
- ✅ Redirected to login screen after 500ms

**Verify in DevTools:**
```javascript
// After logout
localStorage.getItem('admin_access_token')  // null
localStorage.getItem('admin_refresh_token') // null
```

### 7. Backend Connection Error

**Steps:**
1. Stop backend server (if running locally)
2. Try to login

**Expected Behavior:**
- ✅ Error notification: "Ошибка подключения к серверу"
- ✅ Password field clears
- ✅ Form re-enabled
- ✅ No tokens stored

**Network Response:**
- Request fails with network error or timeout

### 8. User Info Display

**Steps:**
1. Login successfully
2. Check sidebar and header

**Expected Behavior:**
- ✅ Sidebar shows user name (from backend `user.name`)
- ✅ Sidebar shows user login (from backend `user.login`)
- ✅ Header shows user name
- ✅ Avatar images displayed

**Verify in DevTools:**
```javascript
// Console after login
admin.currentUser
// Should show: { id, login, name, email, role, lastLogin, createdAt }
```

## API Endpoints Used

### POST /api/auth/login

**Request:**
```json
{
  "login": "admin",
  "password": "admin123"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGc...",
    "refreshToken": "eyJhbGc...",
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

**Response (Error - 401):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

### GET /api/auth/me

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (Success - 200):**
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

**Response (Error - 401):**
```json
{
  "success": false,
  "message": "Authorization header is missing"
}
```

### POST /api/auth/logout

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Logout successful",
  "data": null
}
```

## Browser DevTools Inspection

### Check Current Authentication State

```javascript
// In Console
console.log('Access Token:', localStorage.getItem('admin_access_token'));
console.log('Refresh Token:', localStorage.getItem('admin_refresh_token'));
console.log('Current User:', admin.currentUser);
```

### Decode JWT Token (without validation)

```javascript
// In Console
function decodeJWT(token) {
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    
    const payload = JSON.parse(atob(parts[1]));
    return {
        ...payload,
        issuedAt: new Date(payload.iat * 1000).toLocaleString(),
        expiresAt: new Date(payload.exp * 1000).toLocaleString()
    };
}

const token = localStorage.getItem('admin_access_token');
if (token) {
    console.log('Token Info:', decodeJWT(token));
}
```

### Monitor API Calls

```javascript
// In Console - log all fetch requests
const originalFetch = window.fetch;
window.fetch = function(...args) {
    console.log('Fetch:', args[0], args[1]);
    return originalFetch.apply(this, args);
};
```

### Test 401 Handling

```javascript
// In Console after login
// Corrupt token to trigger 401
localStorage.setItem('admin_access_token', 'invalid_token');

// Try to fetch user data
admin.api.getCurrentUser().then(result => {
    console.log('Result:', result);
});

// Should see:
// - Error logged
// - Tokens cleared
// - Unauthorized event dispatched
// - Notification shown
// - Redirect to login
```

## Troubleshooting

### Issue: Login Form Doesn't Submit

**Check:**
1. Is backend running? (Check Network tab for API calls)
2. Is CORS configured correctly? (Check Console for CORS errors)
3. Is `meta[name="api-base-url"]` tag present in `admin.html`?

### Issue: Token Validation Fails on Reload

**Check:**
1. Are tokens stored correctly? (Check localStorage)
2. Is token expired? (Decode and check `exp` field)
3. Is backend `/api/auth/me` endpoint working?
4. Are tokens being sent in Authorization header? (Check Network tab)

### Issue: 401 Not Triggering Logout

**Check:**
1. Is `admin:unauthorized` event listener registered? (Check AdminPanel.init)
2. Is AdminAPIClient.fetch method being used? (Check Network tab)
3. Are there JavaScript errors? (Check Console)

### Issue: User Info Not Displaying

**Check:**
1. Is `admin.currentUser` populated? (Check in Console)
2. Are DOM selectors correct? (Check updateUserDisplay method)
3. Is backend returning correct user data structure?

## Configuration

### API Base URL

Add to `admin.html` `<head>`:

```html
<!-- Same domain (relative paths) -->
<meta name="api-base-url" content="">

<!-- Different domain -->
<meta name="api-base-url" content="https://api.example.com">

<!-- Subfolder -->
<meta name="api-base-url" content="/backend/public">
```

### Token Expiration

Configured in backend `.env`:

```env
JWT_EXPIRATION=3600        # Access token (1 hour)
# Refresh token: 30 days (hardcoded in AuthService)
```

### CORS

Configure in backend `.env`:

```env
CORS_ORIGIN=http://localhost:3000,https://yourdomain.com
```

## Security Notes

1. **Token Storage**: Tokens stored in localStorage (vulnerable to XSS but necessary for SPA)
2. **Token Transmission**: Always use HTTPS in production
3. **Token Expiration**: Access tokens expire after 1 hour, refresh tokens after 30 days
4. **Password Handling**: Never logged or stored in frontend code
5. **401 Handling**: Automatic logout prevents unauthorized access
6. **Backend Validation**: All admin routes protected by AuthMiddleware

## Next Steps

After authentication is working:

1. ✅ Integrate admin CRUD operations with backend APIs
2. ✅ Replace `db.getData()` calls with `adminApi.fetch()` calls
3. ✅ Add token refresh logic before expiration
4. ✅ Implement role-based access control in frontend
5. ✅ Add loading states for all admin operations

## References

- Backend Authentication Docs: `backend/docs/AUTHENTICATION.md`
- API Documentation: `docs/api.md`
- Admin Panel Code: `js/admin.js`
- Backend Auth Endpoints: `backend/src/Controllers/AuthController.php`
