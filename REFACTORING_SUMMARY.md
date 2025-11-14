# Admin Orders API Refactoring Summary

## Overview
Refactored all order-related functionality in `admin.js` to use the backend REST API instead of local `database.js` storage.

## Files Modified

### 1. `admin.html`
- Added `<meta name="api-base-url" content="">` for API configuration
- Added `<script src="js/admin-api-client.js"></script>` before `admin.js`

### 2. `js/admin.js`

#### Constructor Changes
- Initialized `AdminAPIClient` instance as `this.api`
- Added pagination state variables:
  - `ordersPage`, `ordersPerPage`, `ordersTotalPages`, `ordersTotal`
- Added caching variables for dashboard:
  - `cachedOrders`, `lastOrdersFetch`

#### Refactored Methods

**Orders Management:**
- ✅ `loadOrders()` - Now async
- ✅ `renderOrdersTable()` - Fetches from `GET /api/orders` with filters, pagination, and loading spinner
- ✅ `quickChangeStatus(id, newStatus)` - Uses `PUT /api/orders/{id}`
- ✅ `viewOrder(id)` - Fetches from `GET /api/orders/{id}`
- ✅ `editOrder(id)` - Fetches order, updates via `PUT /api/orders/{id}`
- ✅ `deleteOrder(id)` - Deletes via `DELETE /api/orders/{id}`
- ✅ `markOrderCompleted(id)` - Updates status via `PUT /api/orders/{id}`
- ✅ `markOrderProcessed(id)` - Updates status via `PUT /api/orders/{id}`
- ✅ `resendToTelegram(id)` - Posts to `/api/orders/{id}/resend-telegram`
- ✅ `updateOrdersBadge()` - Fetches new orders count from API
- ✅ `bulkChangeStatus()` - Loops through selected orders, updates via API
- ✅ `bulkDeleteOrders()` - Loops through selected orders, deletes via API
- ✅ `exportOrders()` - Fetches orders from API for export

**New Methods:**
- ✅ `renderPagination(total, currentPage, totalPages)` - Renders pagination controls
- ✅ `createPaginationContainer()` - Creates pagination DOM element
- ✅ `goToOrdersPage(page)` - Handles page navigation

**Dashboard Methods:**
- ✅ `loadDashboard()` - Now async
- ✅ `loadDashboardStats()` - Fetches orders from API, computes stats
- ✅ `loadRecentOrders()` - Uses cached orders or fetches from API
- ✅ `loadActivityFeed()` - Uses cached orders
- ✅ `loadPopularServices()` - Computes from cached orders
- ✅ `loadChart()` - Computes from cached orders

**Helper Methods:**
- ✅ `initAdminPanel()` - Made async
- ✅ `loadPageData(pageId)` - Made async
- ✅ `initOrdersFilters()` - Event listeners now use async handlers

**Removed/Deprecated:**
- ⚠️ `sortOrders()` - Commented out (backend handles sorting via query params)

## API Integration Details

### Backend Field Mapping
| Frontend (old) | Backend (new) |
|---------------|---------------|
| `clientName` | `client_name` |
| `clientEmail` | `client_email` |
| `clientPhone` | `client_phone` |
| `orderNumber` | `order_number` |
| `createdAt` | `created_at` |
| `updatedAt` | `updated_at` |
| `telegramSent` | `telegram_sent` |
| `calculatorData` | `calculator_data` |

### Filters & Pagination
- Status filter: `?status=new|processing|completed|cancelled`
- Type filter: `?type=order|contact`
- Search filter: `?search={query}` (searches name, email, order number)
- Pagination: `?page={n}&per_page={n}`

### Loading States
- Spinner shown during data fetch: `<i class="fas fa-spinner fa-spin"></i> Загрузка заказов...`
- Empty state: "Заказов не найдено"
- Error state: "Ошибка загрузки: {error}"

### Error Handling
- All API calls wrapped in try-catch via `AdminAPIClient.fetch()`
- User-friendly notifications on errors
- 401 responses trigger automatic logout (handled by `AdminAPIClient`)

## Dashboard Statistics
- Fetches first 100 orders for stats computation
- Caches orders to avoid redundant API calls
- Stats include:
  - Total orders with month-over-month growth
  - Monthly revenue with growth
  - Unique clients count with growth
  - Orders in processing (as % of total)

## Testing Checklist
- [ ] Orders list loads from backend
- [ ] Filters (status, type, search) work correctly
- [ ] Pagination controls appear and function
- [ ] View order modal shows backend data
- [ ] Edit order saves to backend
- [ ] Quick status change updates backend
- [ ] Delete order removes from backend
- [ ] Resend Telegram notification triggers endpoint
- [ ] Dashboard stats display correctly
- [ ] Recent orders widget shows data
- [ ] Activity feed populates
- [ ] Popular services chart displays
- [ ] Orders chart renders
- [ ] New orders badge updates
- [ ] Bulk status change works
- [ ] Bulk delete works
- [ ] Export orders fetches from backend
- [ ] Loading spinners appear during async operations
- [ ] Error messages display appropriately
- [ ] 401 errors trigger logout

## Known Limitations
1. Dashboard stats computed from first 100 orders only (performance trade-off)
2. `addOrder()` method still uses `db.addItem()` - needs separate refactoring
3. Bulk operations process sequentially (could be optimized with Promise.all)
4. No retry logic for failed API calls
5. Cache invalidation is time-based (60s) rather than event-driven

## Future Improvements
- Add dedicated `/api/stats` endpoint for dashboard metrics
- Implement optimistic UI updates
- Add retry logic with exponential backoff
- Implement WebSocket for real-time order updates
- Add progress indicators for bulk operations
- Refactor `addOrder()` to create via API
