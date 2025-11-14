# Frontend API Deployment Guide

This document describes the refactored frontend architecture and deployment requirements.

## Architecture Overview

The frontend has been refactored to use a PHP backend API instead of localStorage for business data:

- **API Client** (`js/apiClient.js`) - Handles all API communication with in-memory caching
- **Dynamic Configuration** (`config.js`) - Loads pricing, settings, and form fields from API
- **Async Data Loading** - All data fetched asynchronously with loading/error states
- **Backend Integration** - Order submission goes through `POST /api/orders` endpoint

## Key Changes

### 1. Data Source
- **Before**: Data stored in browser localStorage
- **After**: Data fetched from backend API endpoints

### 2. Configuration
- **Before**: Hardcoded prices, Telegram tokens in config.js
- **After**: Dynamic loading from `GET /api/settings/public`

### 3. Form Submission
- **Before**: Direct Telegram Bot API calls from browser
- **After**: Submissions to `POST /api/orders`, backend handles notifications

### 4. State Preservation
- **Theme preference**: Still stored in localStorage (UI-only)
- **Business data**: No longer persisted client-side

## API Endpoints Used

### Public Endpoints (No Authentication)
- `GET /api/services` - Service listings with features
- `GET /api/portfolio` - Portfolio items (supports ?category filter)
- `GET /api/testimonials` - Approved testimonials
- `GET /api/faq` - FAQ items
- `GET /api/content` - Content sections (hero, about, etc.)
- `GET /api/stats` - Site statistics
- `GET /api/settings/public` - Public configuration (no secrets)
- `POST /api/orders` - Submit order/contact form

## Deployment Configuration

### API Base URL

The frontend determines the API base URL using a meta tag in `index.html`:

```html
<meta name="api-base-url" content="">
```

**Configuration Options:**

1. **Same Domain** (default):
   ```html
   <meta name="api-base-url" content="">
   ```
   API calls will use relative paths (e.g., `/api/services`)

2. **Different Domain/Subdomain**:
   ```html
   <meta name="api-base-url" content="https://api.example.com">
   ```
   API calls will use full URLs (e.g., `https://api.example.com/api/services`)

3. **Subfolder**:
   ```html
   <meta name="api-base-url" content="/backend/public">
   ```
   API calls will use prefixed paths (e.g., `/backend/public/api/services`)

### Deployment Scenarios

#### Scenario 1: Monolithic (Same Server)
```
/var/www/html/
├── index.html          # Frontend
├── css/
├── js/
└── backend/
    └── public/
        └── index.php   # Backend API
```

**Configuration:**
```html
<meta name="api-base-url" content="/backend/public">
```

#### Scenario 2: Separate Subdomains
```
Frontend: https://www.example.com
Backend:  https://api.example.com
```

**Configuration:**
```html
<meta name="api-base-url" content="https://api.example.com">
```

**Important**: Backend must enable CORS for frontend domain.

#### Scenario 3: Frontend-Only Deployment
```
Frontend: Netlify/Vercel/GitHub Pages
Backend:  Separate hosting (Timeweb, etc.)
```

**Configuration:**
```html
<meta name="api-base-url" content="https://your-backend-domain.com">
```

## CORS Configuration

If frontend and backend are on different origins, configure backend CORS in `.env`:

```env
CORS_ORIGIN=https://your-frontend-domain.com
```

Multiple origins:
```env
CORS_ORIGIN=https://www.example.com,https://example.com
```

## Features

### Loading States
- Preloader shown during initial data load
- Loading spinners for async operations
- Graceful degradation on API failures

### Error Handling
- Network errors display user-friendly messages
- Fallback to default values when API unavailable
- Console logging for debugging

### Caching
- In-memory cache for API responses
- Reduces redundant requests
- Force refresh available with `forceRefresh` parameter

### Form Submission
- Validation before submission
- Loading indicator during POST
- Success/error notifications
- Backend handles Telegram notifications

## Testing

### Local Development

1. Start backend server:
   ```bash
   cd backend
   php -S localhost:8080 -t public
   ```

2. Update frontend meta tag:
   ```html
   <meta name="api-base-url" content="http://localhost:8080">
   ```

3. Serve frontend (use any static server):
   ```bash
   python3 -m http.server 3000
   ```

4. Open browser: `http://localhost:3000`

### Testing API Connection

Open browser console and check:
```javascript
// Check API client initialization
console.log(apiClient);

// Check CONFIG loading
console.log(CONFIG._loaded);
console.log(CONFIG.materialPrices);

// Check cached data
console.log(apiClient.cache);
```

## Browser Requirements

- **Fetch API**: Supported in all modern browsers
- **Async/Await**: Required (ES2017+)
- **Arrow Functions**: Required (ES2015+)

### Browser Support
- Chrome 55+
- Firefox 52+
- Safari 10.1+
- Edge 15+

## Migration from localStorage

For existing deployments with localStorage data:

1. **Export existing data**: Use browser console `db.exportData()`
2. **Import to backend**: Use `backend/scripts/import_local_data.php`
3. **Deploy new frontend**: Replace old files
4. **Configure API URL**: Update meta tag in index.html

See `backend/scripts/README.md` for import instructions.

## Troubleshooting

### Issue: "Failed to load settings"
- **Check**: Backend server is running
- **Check**: API base URL in meta tag is correct
- **Check**: CORS is configured for cross-origin requests

### Issue: Empty sections on page
- **Check**: Browser console for API errors
- **Check**: Backend logs for request errors
- **Check**: Database is populated with data

### Issue: Form submission fails
- **Check**: POST /api/orders endpoint is accessible
- **Check**: Payload format matches backend expectations
- **Check**: Rate limiting not exceeded (5 per hour per IP)

### Issue: Calculator prices are 0 or missing
- **Check**: CONFIG.loadFromAPI() completed successfully
- **Check**: Settings API returns calculator configuration
- **Check**: Material/service keys match between frontend and backend

## Security Notes

- **No secrets in frontend**: Telegram tokens not exposed
- **Backend validation**: All form data validated server-side
- **Rate limiting**: Order submissions limited by IP
- **XSS protection**: All user input escaped when rendered

## Performance

- **Initial load**: ~500ms (with backend preload)
- **Cached loads**: Instant (uses in-memory cache)
- **Form submission**: ~200-500ms (depends on backend)

## Support

For issues or questions:
- Check backend logs: `backend/storage/logs/`
- Enable verbose logging: `APP_DEBUG=true` in backend .env
- Review API documentation: `backend/docs/api.md`
