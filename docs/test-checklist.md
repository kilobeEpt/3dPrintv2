# QA Testing Checklist - 3D Print Pro

This comprehensive checklist covers all testing scenarios for manual and automated testing.

## Pre-Testing Setup

### Environment Preparation
- [ ] Backend server running on http://localhost:8080
- [ ] Database seeded with test data
- [ ] Admin user credentials configured
- [ ] .env file properly configured
- [ ] Composer dependencies installed
- [ ] Frontend served on http://localhost:8000
- [ ] Browser DevTools opened for network monitoring
- [ ] Test data backup created

### Test Data Verification
- [ ] At least 5 active services exist
- [ ] At least 3 portfolio items exist
- [ ] At least 2 approved testimonials exist
- [ ] At least 3 active FAQ items exist
- [ ] Calculator settings configured with materials and pricing
- [ ] Admin user can login successfully

---

## 1. Public Site Testing

### 1.1 Content Rendering

#### Hero Section
- [ ] Hero section loads without errors
- [ ] Hero title displays correctly
- [ ] Hero subtitle displays correctly
- [ ] CTA button is visible and styled
- [ ] Background image/animation works
- [ ] **API Call:** GET /api/content/hero returns 200
- [ ] **Loading State:** Spinner shown during API call
- [ ] **Error Handling:** Fallback content shown if API fails

#### Services Section
- [ ] Services section renders
- [ ] All active services displayed (count matches API)
- [ ] Service cards have: icon, name, description, price
- [ ] Features list displays for each service
- [ ] Service icons render correctly
- [ ] Hover effects work on service cards
- [ ] **API Call:** GET /api/services returns 200
- [ ] **Data Validation:** Only active=true services shown
- [ ] **Loading State:** Skeleton/spinner during load

#### Portfolio Section
- [ ] Portfolio grid renders
- [ ] Portfolio items display with images
- [ ] Image lazy loading works
- [ ] Category filters present (All, Prototype, Functional, Art, Industrial)
- [ ] Category filter "All" shows all items
- [ ] Category filter "Prototype" shows only prototype items
- [ ] Category filter "Functional" shows only functional items
- [ ] Category filter "Art" shows only art items
- [ ] Category filter "Industrial" shows only industrial items
- [ ] Click on portfolio item opens modal
- [ ] Modal displays: title, category, description, image, details
- [ ] Modal close button works
- [ ] Modal closes on backdrop click
- [ ] **API Call:** GET /api/portfolio returns 200
- [ ] **API Call:** GET /api/portfolio?category=prototype returns 200
- [ ] **Performance:** Images optimized and load quickly

#### Testimonials Section
- [ ] Testimonials section renders
- [ ] Only approved testimonials displayed
- [ ] Each testimonial shows: avatar, name, position, rating, text
- [ ] Rating stars render correctly (1-5 stars)
- [ ] Avatar images load
- [ ] Testimonials carousel/slider works (if applicable)
- [ ] **API Call:** GET /api/testimonials returns 200
- [ ] **Data Validation:** Only approved=true testimonials shown

#### FAQ Section
- [ ] FAQ section renders
- [ ] Only active FAQ items displayed
- [ ] FAQ accordion functionality works
- [ ] Click question to expand answer
- [ ] Click again to collapse
- [ ] Only one FAQ open at a time (or multiple, depending on design)
- [ ] Answer content renders with proper formatting
- [ ] **API Call:** GET /api/faq returns 200
- [ ] **Data Validation:** Only active=true FAQ items shown

#### Statistics Section
- [ ] Statistics section renders
- [ ] All stat counters present: projects, clients, years, awards
- [ ] Counter animation works (counting up effect)
- [ ] Numbers match data from API
- [ ] **API Call:** GET /api/stats returns 200

### 1.2 Calculator Functionality

#### Initialization
- [ ] Calculator section loads
- [ ] Materials dropdown populates
- [ ] Additional services checkboxes populate
- [ ] Quality level options populate
- [ ] Quantity input field present
- [ ] All options loaded from API (not hardcoded)
- [ ] **API Call:** GET /api/settings/public returns 200
- [ ] **Data Validation:** Calculator config present in response

#### Basic Calculation
- [ ] Select material: PLA
- [ ] Enter quantity: 100g
- [ ] Select quality: Standard
- [ ] Click "Calculate" button
- [ ] Price displays correctly (100 × material_price)
- [ ] Calculation result shows material cost breakdown
- [ ] **Calculation Check:** 100g × ₽50 = ₽5000

#### With Additional Services
- [ ] Select material and quantity
- [ ] Check service: "Покраска" (₽500)
- [ ] Check service: "Постобработка" (₽300)
- [ ] Calculate price
- [ ] Total includes base price + services
- [ ] **Calculation Check:** Base + ₽500 + ₽300

#### Quality Multiplier
- [ ] Select material: PLA (₽50/g)
- [ ] Enter quantity: 100g
- [ ] Select quality: Premium (1.5× multiplier)
- [ ] Calculate price
- [ ] Base price multiplied by quality factor
- [ ] **Calculation Check:** (100 × ₽50) × 1.5 = ₽7500

#### Volume Discounts
- [ ] Enter quantity that qualifies for discount (e.g., 500g for 10%)
- [ ] Calculate price
- [ ] Discount applied to base price
- [ ] Discount amount displayed
- [ ] Final price = base price - discount
- [ ] **Calculation Check:** Verify discount percentage applied

#### Validation
- [ ] Leave material empty, click Calculate → error message
- [ ] Enter 0 quantity → validation error
- [ ] Enter negative quantity → validation error
- [ ] Enter extremely large quantity (999999) → handles gracefully
- [ ] Enter decimal quantity (150.5) → accepts or shows error
- [ ] Clear form button resets all fields

#### Edge Cases
- [ ] Change material after calculation → price updates
- [ ] Toggle services on/off → price recalculates
- [ ] Change quality level → price updates
- [ ] Multiple rapid calculations → no UI lag or errors

### 1.3 Contact/Order Form Submission

#### Contact Form - Valid Submission
- [ ] Navigate to contact form
- [ ] Fill name: "Иван Петров"
- [ ] Fill email: "ivan@example.com"
- [ ] Fill phone: "+79001234567"
- [ ] Fill message: "Интересует 3D печать"
- [ ] Submit form
- [ ] **Expected:** Success message displayed
- [ ] **Expected:** Form clears after submission
- [ ] **API Call:** POST /api/orders returns 201
- [ ] **Response:** Contains order_number, status=new, type=contact

#### Order Form - With Calculator Data
- [ ] Configure calculator with material, quantity, services
- [ ] Click "Заказать расчёт" or similar order button
- [ ] Modal opens with contact form
- [ ] Fill contact details
- [ ] Submit order
- [ ] **Expected:** Success message with order confirmation
- [ ] **API Call:** POST /api/orders with calculator_data
- [ ] **Response:** type=order, calculator_data preserved

#### Validation - Required Fields
- [ ] Submit form with empty name → error: "Name is required"
- [ ] Submit form with empty email → error: "Email is required"
- [ ] Submit form with empty phone → error: "Phone is required"
- [ ] All error messages displayed inline or in summary

#### Validation - Email Format
- [ ] Enter email: "invalid-email" → error: "Invalid email format"
- [ ] Enter email: "test@" → error
- [ ] Enter email: "@example.com" → error
- [ ] Enter valid email: "test@example.com" → no error

#### Validation - Phone Format
- [ ] Enter phone: "123" (too short) → error: "Phone must be at least 10 characters"
- [ ] Enter phone: "+7900" (too short) → error
- [ ] Enter phone: "+79001234567" → no error
- [ ] Enter phone: "8 (900) 123-45-67" → accepts or rejects based on validation

#### Validation - Name Length
- [ ] Enter name: "A" (1 char) → error: "Name must be at least 2 characters"
- [ ] Enter name: "AB" (2 chars) → no error
- [ ] Enter name: Very long name (101+ chars) → error or accepts with truncation

#### Validation - Message Length
- [ ] Enter very long message (5000+ chars) → error or accepts with limit
- [ ] Enter message with special characters: `<script>`, `<img>` → sanitized
- [ ] Enter message with quotes: "test" and 'test' → escaped properly

#### Rate Limiting
- [ ] Submit 5 orders rapidly from same IP
- [ ] 6th submission should be rejected with "Too many requests"
- [ ] **API Response:** 429 status code
- [ ] **UI:** User-friendly error message displayed

#### Loading States
- [ ] Submit form → button disabled during submission
- [ ] Loading spinner shown on button or form
- [ ] Form inputs disabled during submission
- [ ] Re-enable after success or error

#### Error Handling
- [ ] Stop backend server
- [ ] Try to submit form
- [ ] **Expected:** Network error message displayed
- [ ] **Expected:** Form remains filled (data not lost)
- [ ] Restart server and retry → submission works

### 1.4 General UI/UX

#### Navigation
- [ ] Smooth scroll to sections works
- [ ] Active section highlighted in navigation
- [ ] Mobile menu toggle works (if responsive)
- [ ] All navigation links functional

#### Responsive Design
- [ ] Test on desktop (1920×1080)
- [ ] Test on tablet (768×1024)
- [ ] Test on mobile (375×667)
- [ ] All content readable and accessible
- [ ] No horizontal scroll
- [ ] Touch targets adequate size on mobile

#### Performance
- [ ] Page load time < 3 seconds
- [ ] Images optimized and compressed
- [ ] API responses cached where appropriate
- [ ] No console errors in browser DevTools
- [ ] Lighthouse score > 80 (Performance, Accessibility)

#### Accessibility
- [ ] Keyboard navigation works (Tab through form fields)
- [ ] Focus indicators visible
- [ ] Alt text on images
- [ ] ARIA labels where appropriate
- [ ] Color contrast meets WCAG standards

---

## 2. Admin Panel Testing

### 2.1 Authentication

#### Login - Valid Credentials
- [ ] Navigate to admin.html
- [ ] Enter login: "admin"
- [ ] Enter password: "admin123"
- [ ] Click "Login" button
- [ ] **Expected:** Redirected to dashboard
- [ ] **API Call:** POST /api/auth/login returns 200
- [ ] **Storage:** JWT tokens saved in localStorage (admin_access_token, admin_refresh_token)
- [ ] **UI:** Admin username displayed in header/sidebar

#### Login - Invalid Credentials
- [ ] Enter login: "admin"
- [ ] Enter password: "wrongpassword"
- [ ] Submit form
- [ ] **Expected:** Error message "Invalid credentials"
- [ ] **Expected:** Remain on login screen
- [ ] **API Call:** POST /api/auth/login returns 401

#### Login - Empty Fields
- [ ] Leave login and password empty
- [ ] Submit form
- [ ] **Expected:** Validation errors displayed
- [ ] **Expected:** Form not submitted

#### Login - SQL Injection Attempt
- [ ] Enter login: "admin' OR '1'='1"
- [ ] Enter password: "anything"
- [ ] Submit form
- [ ] **Expected:** Login fails, no security breach
- [ ] **Security:** Prepared statements prevent SQL injection

#### Token Persistence
- [ ] Login successfully
- [ ] Reload page (F5)
- [ ] **Expected:** Remain logged in, dashboard loads
- [ ] **API Call:** GET /api/auth/me returns 200 on reload
- [ ] **Storage:** Tokens still present in localStorage

#### Token Expiration
- [ ] Login successfully
- [ ] Wait for token to expire (or manually expire in localStorage)
- [ ] Make any admin action
- [ ] **Expected:** Auto logout, redirect to login with message
- [ ] **API Call:** 401 response triggers logout

#### Logout
- [ ] Login successfully
- [ ] Click "Logout" button in header/sidebar
- [ ] **Expected:** Redirected to login screen
- [ ] **API Call:** POST /api/auth/logout called (optional)
- [ ] **Storage:** Tokens removed from localStorage
- [ ] **Security:** Back button does not restore session

#### Concurrent Sessions
- [ ] Login in Browser A
- [ ] Login in Browser B with same credentials
- [ ] Verify both sessions work independently
- [ ] Logout from Browser A
- [ ] Verify Browser B session still active

### 2.2 Dashboard

#### Statistics Display
- [ ] Login and view dashboard
- [ ] Statistics cards present: Total Orders, Revenue, New Clients, etc.
- [ ] Numbers match data from backend
- [ ] Growth percentages displayed (if applicable)
- [ ] Icons and styling correct
- [ ] **API Call:** Data computed from GET /api/orders

#### Recent Orders List
- [ ] Recent orders table visible
- [ ] Shows last 10 orders (or configured limit)
- [ ] Columns: Order Number, Client, Status, Date, Amount
- [ ] Status badges color-coded
- [ ] Click order → view details modal opens
- [ ] **API Call:** GET /api/orders?limit=10&sort=created_at:desc

#### Activity Feed
- [ ] Activity feed widget visible
- [ ] Shows recent actions/events
- [ ] Timestamps relative (e.g., "2 hours ago")
- [ ] Icons match action types

#### Chart/Graph
- [ ] Chart section present (e.g., orders over last 7 days)
- [ ] Chart library renders without errors
- [ ] Data matches backend statistics
- [ ] Hovering over chart shows tooltips
- [ ] **Data Source:** Computed from orders data

#### Performance
- [ ] Dashboard loads within 2 seconds
- [ ] No lag when switching between sections
- [ ] API calls cached with 60-second TTL

### 2.3 Services Management

#### View Services List
- [ ] Navigate to Services section
- [ ] Services table displays
- [ ] All services shown (active and inactive)
- [ ] Columns: Name, Icon, Price, Status, Featured, Actions
- [ ] Active/Inactive badges displayed
- [ ] **API Call:** GET /api/admin/services returns 200

#### Create New Service
- [ ] Click "Add Service" button
- [ ] Modal/form opens
- [ ] Fill fields:
  - Name: "Тестовая Услуга"
  - Icon: "fa-cube"
  - Description: "Описание услуги"
  - Price: "1500₽/шт"
  - Features: "Функция 1, Функция 2, Функция 3" (comma-separated)
- [ ] Check "Active" checkbox
- [ ] Submit form
- [ ] **Expected:** Success message
- [ ] **Expected:** New service appears in list
- [ ] **API Call:** POST /api/admin/services returns 201
- [ ] **Verification:** GET /api/services includes new service

#### Edit Existing Service
- [ ] Click "Edit" button on a service
- [ ] Modal opens with pre-filled data
- [ ] Modify fields (change name, price, add feature)
- [ ] Submit form
- [ ] **Expected:** Success message
- [ ] **Expected:** Changes reflected in list
- [ ] **API Call:** PUT /api/admin/services/{id} returns 200

#### Delete Service
- [ ] Click "Delete" button on a service
- [ ] Confirmation dialog appears
- [ ] Confirm deletion
- [ ] **Expected:** Service removed from list
- [ ] **API Call:** DELETE /api/admin/services/{id} returns 200
- [ ] **Verification:** GET /api/services does not include deleted service

#### Toggle Active Status
- [ ] Click toggle switch on an active service
- [ ] Status changes to inactive
- [ ] **API Call:** PUT/PATCH /api/admin/services/{id} with active=false
- [ ] **Verification:** GET /api/services (public) no longer includes it
- [ ] Toggle back to active
- [ ] **Verification:** Service reappears in public API

#### Validation - Required Fields
- [ ] Click "Add Service"
- [ ] Leave name empty
- [ ] Submit form
- [ ] **Expected:** Validation error "Name is required"
- [ ] Fill name, leave icon empty
- [ ] **Expected:** Validation error "Icon is required"
- [ ] Fill all fields, submit
- [ ] **Expected:** Service created successfully

#### Validation - Slug Auto-generation
- [ ] Create service with Russian name: "Тестовая Услуга"
- [ ] Submit form
- [ ] **Expected:** Slug auto-generated (e.g., "testovaya-usluga-12345")
- [ ] **API Response:** Contains generated slug
- [ ] **Verification:** Slug is URL-friendly (lowercase, hyphens, no Cyrillic)

#### Features Management
- [ ] Create service with features: "Feature 1, Feature 2, Feature 3"
- [ ] **Expected:** Features stored as array in backend
- [ ] Edit service
- [ ] **Expected:** Features displayed as comma-separated in form
- [ ] Add new feature: "Feature 4"
- [ ] **Expected:** 4 features saved
- [ ] **API Response:** features array contains all 4 items

### 2.4 Portfolio Management

#### View Portfolio Grid
- [ ] Navigate to Portfolio section
- [ ] Portfolio items displayed in grid
- [ ] Each card shows: image, title, category
- [ ] Hover effect on cards
- [ ] **API Call:** GET /api/portfolio returns 200

#### Create Portfolio Item
- [ ] Click "Add Portfolio Item"
- [ ] Fill fields:
  - Title: "Тестовый Проект"
  - Category: "Prototype" (dropdown)
  - Description: "Описание проекта"
  - Image URL: "https://example.com/image.jpg"
  - Details: "Детали проекта"
- [ ] Submit form
- [ ] **Expected:** Item added to grid
- [ ] **API Call:** POST /api/admin/portfolio returns 201

#### Edit Portfolio Item
- [ ] Click "Edit" on an item
- [ ] Modify title and category
- [ ] Submit form
- [ ] **Expected:** Changes reflected in grid
- [ ] **API Call:** PUT /api/admin/portfolio/{id} returns 200

#### Delete Portfolio Item
- [ ] Click "Delete" on an item
- [ ] Confirm deletion
- [ ] **Expected:** Item removed from grid
- [ ] **API Call:** DELETE /api/admin/portfolio/{id} returns 200

#### Validation - Category
- [ ] Try to create item with invalid category (via API manipulation)
- [ ] **Expected:** 422 validation error
- [ ] **Valid categories:** prototype, functional, art, industrial

#### Validation - Image URL
- [ ] Create item with invalid URL: "not-a-url"
- [ ] **Expected:** Validation error "Invalid URL format"
- [ ] Enter valid URL: "https://example.com/image.jpg"
- [ ] **Expected:** No error

#### Category Filter (Public Site)
- [ ] Create portfolio items in different categories
- [ ] View public site portfolio section
- [ ] Test category filters
- [ ] **Expected:** Only items from selected category shown

### 2.5 Testimonials Management

#### View Testimonials List
- [ ] Navigate to Testimonials section
- [ ] All testimonials displayed (approved and pending)
- [ ] Columns: Name, Position, Rating, Status (Approved/Pending), Actions
- [ ] **API Call:** GET /api/admin/testimonials returns 200

#### Create Testimonial
- [ ] Click "Add Testimonial"
- [ ] Fill fields:
  - Name: "Мария Иванова"
  - Position: "Директор компании"
  - Avatar URL: "https://example.com/avatar.jpg"
  - Rating: 5 (select 5 stars)
  - Text: "Отличная работа!"
- [ ] Check "Approved" checkbox
- [ ] Submit form
- [ ] **Expected:** Testimonial added to list
- [ ] **API Call:** POST /api/admin/testimonials returns 201

#### Edit Testimonial
- [ ] Click "Edit" on a testimonial
- [ ] Modify text and rating
- [ ] Submit form
- [ ] **Expected:** Changes saved
- [ ] **API Call:** PUT /api/admin/testimonials/{id} returns 200

#### Delete Testimonial
- [ ] Click "Delete" on a testimonial
- [ ] Confirm deletion
- [ ] **Expected:** Testimonial removed
- [ ] **API Call:** DELETE /api/admin/testimonials/{id} returns 200

#### Approve/Unapprove Testimonial
- [ ] Click "Approve" button on pending testimonial
- [ ] Status changes to "Approved"
- [ ] **API Call:** PUT/PATCH /api/admin/testimonials/{id} with approved=true
- [ ] **Verification:** GET /api/testimonials (public) includes it
- [ ] Click "Unapprove"
- [ ] **Verification:** No longer in public testimonials

#### Validation - Rating Range
- [ ] Try to create testimonial with rating 0 (via API)
- [ ] **Expected:** 422 validation error
- [ ] Try rating 6
- [ ] **Expected:** 422 validation error
- [ ] Valid range: 1-5

#### Public Visibility
- [ ] Create unapproved testimonial
- [ ] View public site
- [ ] **Expected:** Testimonial NOT visible
- [ ] Approve testimonial
- [ ] **Expected:** Testimonial now visible

### 2.6 FAQ Management

#### View FAQ List
- [ ] Navigate to FAQ section
- [ ] All FAQ items displayed (active and inactive)
- [ ] Columns: Question, Answer (truncated), Status, Actions
- [ ] **API Call:** GET /api/admin/faq returns 200

#### Create FAQ Item
- [ ] Click "Add FAQ"
- [ ] Fill question: "Сколько стоит 3D печать?"
- [ ] Fill answer: "Цена зависит от материала и объема..."
- [ ] Check "Active" checkbox
- [ ] Submit form
- [ ] **Expected:** FAQ added to list
- [ ] **API Call:** POST /api/admin/faq returns 201

#### Edit FAQ Item
- [ ] Click "Edit" on an FAQ
- [ ] Modify question and answer
- [ ] Submit form
- [ ] **Expected:** Changes saved
- [ ] **API Call:** PUT /api/admin/faq/{id} returns 200

#### Delete FAQ Item
- [ ] Click "Delete" on an FAQ
- [ ] Confirm deletion
- [ ] **Expected:** FAQ removed
- [ ] **API Call:** DELETE /api/admin/faq/{id} returns 200

#### Toggle Active Status
- [ ] Click toggle switch on active FAQ
- [ ] Status changes to inactive
- [ ] **Verification:** GET /api/faq (public) excludes it
- [ ] Toggle back to active
- [ ] **Verification:** FAQ reappears in public API

### 2.7 Orders Management

#### View Orders List
- [ ] Navigate to Orders section
- [ ] Orders table displays
- [ ] Columns: Order#, Client, Email, Phone, Status, Type, Date, Amount, Actions
- [ ] Pagination controls present (if > 20 orders)
- [ ] **API Call:** GET /api/orders?page=1&per_page=20 returns 200

#### Pagination
- [ ] Navigate to page 2
- [ ] **API Call:** GET /api/orders?page=2
- [ ] Click "Next" button
- [ ] Click "Previous" button
- [ ] Jump to specific page number
- [ ] **Verification:** Pagination metadata correct (total, page, per_page, total_pages)

#### Filter by Status
- [ ] Select filter: "New" orders
- [ ] **API Call:** GET /api/orders?status=new
- [ ] **Verification:** Only orders with status=new shown
- [ ] Test filters: Processing, Completed, Cancelled
- [ ] Select "All" to clear filter

#### Filter by Type
- [ ] Select filter: "Orders" (with calculator data)
- [ ] **API Call:** GET /api/orders?type=order
- [ ] **Verification:** Only orders with calculator_data shown
- [ ] Select filter: "Contact Forms"
- [ ] **Verification:** Only contact forms shown

#### Search Orders
- [ ] Enter search term: client name
- [ ] **API Call:** GET /api/orders?search=ClientName
- [ ] **Verification:** Orders matching name, email, or message shown
- [ ] Test search with email
- [ ] Test search with message keywords
- [ ] Test search with partial matches

#### Date Range Filter
- [ ] Set date_from: "2024-01-01"
- [ ] Set date_to: "2024-12-31"
- [ ] Click "Apply"
- [ ] **API Call:** GET /api/orders?date_from=2024-01-01&date_to=2024-12-31
- [ ] **Verification:** Only orders in date range shown

#### View Order Details
- [ ] Click "View" button on an order
- [ ] Modal opens with full order details
- [ ] All fields displayed: Order#, Client info, Status, Type, Message, Calculator data, Telegram sent status, Timestamps
- [ ] Calculator data formatted readably (if present)
- [ ] Close modal

#### Edit Order
- [ ] Click "Edit" button on an order
- [ ] Modal opens with editable form
- [ ] Change status from "New" to "Processing"
- [ ] Add admin notes
- [ ] Submit form
- [ ] **Expected:** Success message
- [ ] **Expected:** Order status updated in table
- [ ] **API Call:** PUT /api/orders/{id} returns 200

#### Quick Status Change
- [ ] In orders table, click status dropdown
- [ ] Select new status (e.g., "Processing")
- [ ] **Expected:** Status updates immediately without modal
- [ ] **API Call:** PUT /api/orders/{id} with status only

#### Delete Order
- [ ] Click "Delete" button on an order
- [ ] Confirmation dialog appears
- [ ] Confirm deletion
- [ ] **Expected:** Order removed from list
- [ ] **API Call:** DELETE /api/orders/{id} returns 200

#### Bulk Actions - Status Change
- [ ] Select multiple orders via checkboxes
- [ ] Select bulk action: "Mark as Processing"
- [ ] Click "Apply"
- [ ] **Expected:** All selected orders status updated
- [ ] **API Call:** Batch update via multiple PUT calls or single batch endpoint

#### Bulk Actions - Delete
- [ ] Select multiple orders
- [ ] Select bulk action: "Delete"
- [ ] Confirm deletion
- [ ] **Expected:** All selected orders deleted
- [ ] **API Call:** Batch delete

#### Export Orders
- [ ] Click "Export" button
- [ ] Select format: CSV or JSON
- [ ] **Expected:** File downloads with current filter/search results
- [ ] Open file and verify data

#### Resend Telegram Notification
- [ ] Find order with telegram_sent=false
- [ ] Click "Resend to Telegram" button
- [ ] **Expected:** Notification sent, telegram_sent=true
- [ ] **API Call:** POST /api/orders/{id}/resend-telegram returns 200

#### New Order Badge
- [ ] Submit new order from public site
- [ ] Check admin sidebar
- [ ] **Expected:** Badge with count of new orders appears
- [ ] Navigate to Orders section
- [ ] **Expected:** Badge count updates

### 2.8 Settings Management

#### Calculator Settings - Load
- [ ] Navigate to Calculator Settings
- [ ] **API Call:** GET /api/settings returns 200
- [ ] Materials table populated
- [ ] Additional services list populated
- [ ] Quality levels populated
- [ ] Volume discounts populated
- [ ] All data matches backend

#### Calculator Settings - Edit Material
- [ ] Click "Edit" on PLA material
- [ ] Change price from ₽50 to ₽55
- [ ] Submit
- [ ] **Expected:** Success message
- [ ] **API Call:** PUT /api/settings/calculator returns 200
- [ ] **Verification:** GET /api/settings/public reflects new price

#### Calculator Settings - Add Material
- [ ] Click "Add Material"
- [ ] Fill: material_key="abs", name="ABS", price=60, technology="fdm"
- [ ] Submit
- [ ] **Expected:** Material added to list
- [ ] **Verification:** Public calculator includes new material

#### Calculator Settings - Add Service
- [ ] Click "Add Service"
- [ ] Fill: service_key="polishing", name="Полировка", price=400, unit="шт"
- [ ] Submit
- [ ] **Expected:** Service added to list

#### Calculator Settings - Edit Quality Level
- [ ] Edit Premium quality multiplier from 1.5 to 1.6
- [ ] Submit
- [ ] **Expected:** Multiplier updated
- [ ] **Verification:** Calculator uses new multiplier

#### Calculator Settings - Add Volume Discount
- [ ] Click "Add Discount"
- [ ] Fill: min_quantity=1000, discount_percent=20
- [ ] Submit
- [ ] **Expected:** Discount tier added

#### Calculator Settings - Validation
- [ ] Try to set negative price → validation error
- [ ] Try to set multiplier to 0 → validation error (must be > 0)
- [ ] Try to set discount > 100% → validation error
- [ ] Try invalid technology → validation error

#### Form Settings - Load
- [ ] Navigate to Form Settings
- [ ] **API Call:** GET /api/settings returns 200
- [ ] Contact form fields displayed
- [ ] Order form fields displayed
- [ ] Field properties shown: name, label, type, required, enabled, options

#### Form Settings - Edit Field
- [ ] Click "Edit" on "message" field
- [ ] Change label to "Ваше сообщение"
- [ ] Toggle required status (if not system field)
- [ ] Submit
- [ ] **Expected:** Success message
- [ ] **API Call:** PUT /api/settings/forms returns 200

#### Form Settings - Add Custom Field
- [ ] Click "Add Field"
- [ ] Fill: form_type="contact", field_name="company", label="Компания", field_type="text"
- [ ] Submit
- [ ] **Expected:** Field added
- [ ] **Verification:** Public contact form includes new field

#### Form Settings - Delete Custom Field
- [ ] Click "Delete" on custom field (not system field)
- [ ] Confirm deletion
- [ ] Submit changes
- [ ] **Expected:** Field removed
- [ ] **Verification:** Field no longer in public form

#### Form Settings - System Field Protection
- [ ] Try to toggle "required" off for "email" field
- [ ] **Expected:** Either prevented or warning shown
- [ ] System fields (name, email, phone, message) must remain required

#### Telegram Settings - Load Status
- [ ] Navigate to Telegram Settings
- [ ] **API Call:** GET /api/telegram/status returns 200
- [ ] Status displayed: Connected / Configured / Error / Not Configured
- [ ] If connected, bot info displayed (username, name)

#### Telegram Settings - Update Chat ID
- [ ] Edit chat_id field
- [ ] Enter new chat ID (e.g., "-1001234567890")
- [ ] Click "Save"
- [ ] **Expected:** Success message
- [ ] **API Call:** PUT /api/settings/telegram returns 200

#### Telegram Settings - Get Chat ID
- [ ] Click "Get Chat ID" button
- [ ] **API Call:** GET /api/telegram/chat-id returns 200
- [ ] Available chat IDs displayed (user chats, group chats)
- [ ] Click "Use this ID" button to populate field

#### Telegram Settings - Test Connection
- [ ] Click "Test Connection" button
- [ ] **API Call:** POST /api/telegram/test returns 200
- [ ] **Expected:** Success message "Test message sent"
- [ ] **Verification:** Check Telegram and confirm message received

#### Telegram Settings - Update Bot Token (if needed)
- [ ] Edit bot_token field (admin only, consider security)
- [ ] Enter new token (format: 1234567890:ABCD...)
- [ ] Save
- [ ] **Validation:** Token format validated
- [ ] **Expected:** Token saved (redacted in future GET responses)

#### General Settings - Load
- [ ] Navigate to General Settings
- [ ] **API Call:** GET /api/settings returns 200
- [ ] Site name populated
- [ ] Contact email populated
- [ ] Timezone populated
- [ ] Primary/secondary colors populated

#### General Settings - Update
- [ ] Edit site_name to "3D Print Pro Test"
- [ ] Edit contact_email to "info@3dprintpro.test"
- [ ] Change color_primary to "#FF5733"
- [ ] Click "Save"
- [ ] **Expected:** Success message
- [ ] **API Call:** PUT /api/settings returns 200

#### General Settings - Validation
- [ ] Enter invalid email format → validation error
- [ ] Enter invalid color code (not hex) → validation error
- [ ] Valid inputs → save successfully

---

## 3. Edge Cases and Error Handling

### 3.1 Authentication Edge Cases

#### Token Tampering
- [ ] Login successfully
- [ ] Open DevTools → Application → localStorage
- [ ] Modify admin_access_token (change a character)
- [ ] Make any admin API request
- [ ] **Expected:** 401 Unauthorized, auto logout

#### Missing Authorization Header
- [ ] Use curl or Postman
- [ ] Make request to GET /api/admin/services without Authorization header
- [ ] **Expected:** 401 response

#### Malformed Authorization Header
- [ ] Make request with header: `Authorization: InvalidFormat`
- [ ] **Expected:** 401 response with error message

#### Expired Token
- [ ] Login and wait for token expiration (or manipulate expiry)
- [ ] Make API request
- [ ] **Expected:** 401 response, logout triggered

### 3.2 Form Validation Edge Cases

#### XSS Prevention
- [ ] Submit form with name: `<script>alert('XSS')</script>`
- [ ] **Expected:** Script tags escaped or stripped
- [ ] View data in admin panel → no script execution
- [ ] **Security:** XSS attack prevented

#### SQL Injection
- [ ] Submit form with name: `'; DROP TABLE orders; --`
- [ ] **Expected:** Input safely escaped
- [ ] **Verification:** Database intact, orders table exists
- [ ] **Security:** Prepared statements prevent SQL injection

#### Unicode Characters (Cyrillic)
- [ ] Submit order with Russian text: "Тестовое сообщение"
- [ ] **Expected:** Characters preserved
- [ ] View in admin → Russian text displays correctly
- [ ] **Database:** UTF-8 encoding works

#### Special Characters
- [ ] Submit with quotes: `Name: O'Connor "The Great"`
- [ ] **Expected:** Quotes escaped and preserved
- [ ] View in admin → displayed correctly

#### Extremely Long Input
- [ ] Submit message with 10,000 characters
- [ ] **Expected:** Either accepted (if limit > 10k) or validation error
- [ ] **Database:** Field length constraints enforced

#### Boundary Values - Name
- [ ] Submit with 1-char name: "A" → validation error (min 2)
- [ ] Submit with 2-char name: "AB" → accepted
- [ ] Submit with 100-char name → accepted
- [ ] Submit with 101-char name → validation error (max 100)

#### Empty vs Null Values
- [ ] Submit order with telegram field as empty string ""
- [ ] **Database:** Check if stored as NULL or empty string
- [ ] **Consistency:** Backend handles both correctly

### 3.3 API Error Handling

#### Database Connection Lost
- [ ] Stop MySQL database
- [ ] Make API request
- [ ] **Expected:** 503 Service Unavailable
- [ ] **Response:** Generic error message (no DB credentials leaked)
- [ ] Restart database → API recovers

#### Malformed JSON Request
- [ ] POST to /api/orders with invalid JSON: `{name: test` (missing quote and brace)
- [ ] **Expected:** 400 Bad Request with JSON parse error

#### Missing Required Fields
- [ ] POST /api/orders without client_email
- [ ] **Expected:** 422 Unprocessable Entity
- [ ] **Response:** Validation errors array with "client_email is required"

#### Invalid Field Types
- [ ] POST /api/admin/testimonials with rating="abc" (string instead of integer)
- [ ] **Expected:** 422 validation error

#### Invalid Enum Values
- [ ] POST /api/admin/portfolio with category="invalid"
- [ ] **Expected:** 422 validation error
- [ ] **Error:** Category must be one of: prototype, functional, art, industrial

#### Non-existent Resource
- [ ] GET /api/orders/999999 (non-existent ID)
- [ ] **Expected:** 404 Not Found
- [ ] **Response:** Resource not found message

#### Duplicate Key Violation
- [ ] Create service with slug "test-service"
- [ ] Create another service with same slug
- [ ] **Expected:** 422 validation error
- [ ] **Error:** Slug already exists

#### Foreign Key Violation
- [ ] Attempt to create service_feature with non-existent service_id
- [ ] **Expected:** 422 or 400 error
- [ ] **Error:** Referenced service does not exist

#### Concurrent Update Conflict
- [ ] Open same order in two browser tabs
- [ ] Edit and save in Tab 1
- [ ] Edit and save in Tab 2
- [ ] **Expected:** Last write wins or optimistic locking error

### 3.4 Telegram Integration Edge Cases

#### Invalid Bot Token
- [ ] Set invalid bot token in settings
- [ ] Submit order from public site
- [ ] **Expected:** Order created, telegram_sent=false
- [ ] **Logs:** Error logged with details
- [ ] **Non-blocking:** Order creation not prevented

#### Invalid Chat ID
- [ ] Set invalid chat ID
- [ ] Submit order
- [ ] **Expected:** Order created, telegram_sent=false
- [ ] **Logs:** Telegram API error logged

#### Telegram API Timeout
- [ ] Simulate network timeout (delay response)
- [ ] Submit order
- [ ] **Expected:** Order created after timeout
- [ ] telegram_sent=false after timeout

#### Resend After Failure
- [ ] Find order with telegram_sent=false
- [ ] Click "Resend to Telegram" in admin
- [ ] **Expected:** Notification sent, telegram_sent=true
- [ ] **API Call:** POST /api/orders/{id}/resend-telegram

#### Message Formatting Special Characters
- [ ] Submit order with message containing: `* _ [ ] ( ) ~ ` > # + - = | { } . !`
- [ ] **Expected:** Special characters escaped for Telegram Markdown
- [ ] **Verification:** Message displays correctly in Telegram

### 3.5 Calculator Edge Cases

#### Zero Quantity
- [ ] Enter quantity: 0
- [ ] Calculate
- [ ] **Expected:** Validation error or ₽0 result

#### Negative Quantity
- [ ] Enter quantity: -100
- [ ] Calculate
- [ ] **Expected:** Validation error, calculation prevented

#### Extremely Large Quantity
- [ ] Enter quantity: 999999999
- [ ] Calculate
- [ ] **Expected:** Either calculated or reasonable limit enforced

#### Decimal Quantities
- [ ] Enter quantity: 150.5g
- [ ] Calculate
- [ ] **Expected:** Correct calculation with decimals or validation error

#### Multiple Discount Tiers
- [ ] Configure quantity that matches multiple discount tiers
- [ ] **Expected:** Only highest discount applied (not cumulative)

#### No Services Selected
- [ ] Select material and quantity
- [ ] Leave all services unchecked
- [ ] Calculate
- [ ] **Expected:** Base price calculated, no service charges

---

## 4. Database Integrity Validation

### 4.1 CRUD Operation Integrity

#### Service Creation
- [ ] Create service via admin panel
- [ ] **SQL Query:** `SELECT * FROM services WHERE slug='test-service'`
- [ ] **Verification:** Record exists with correct data

#### Service Features Normalization
- [ ] Create service with 3 features
- [ ] **SQL Query:** `SELECT * FROM service_features WHERE service_id=?`
- [ ] **Verification:** 3 separate records in service_features table

#### Order Auto-generated Fields
- [ ] Submit order
- [ ] **SQL Query:** `SELECT order_number, created_at FROM orders WHERE id=?`
- [ ] **Verification:** order_number format: ORD-YYYYMMDD-XXXX
- [ ] **Verification:** created_at timestamp populated

#### Order Update Timestamp
- [ ] Update order status
- [ ] **SQL Query:** `SELECT created_at, updated_at FROM orders WHERE id=?`
- [ ] **Verification:** updated_at > created_at

#### Soft Deletes
- [ ] Deactivate service
- [ ] **SQL Query:** `SELECT active FROM services WHERE id=?`
- [ ] **Verification:** active=0, record not deleted

#### Settings Singleton
- [ ] Update general settings
- [ ] **SQL Query:** `SELECT COUNT(*) FROM site_settings`
- [ ] **Verification:** COUNT = 1 (only one row)

#### Calculator Materials
- [ ] Update calculator settings
- [ ] **SQL Query:** `SELECT * FROM materials`
- [ ] **Verification:** All materials with correct prices

#### Form Fields Dynamic Config
- [ ] Add custom form field
- [ ] **SQL Query:** `SELECT * FROM form_fields WHERE field_name=?`
- [ ] **Verification:** Field properties correct

#### Telegram Integration Table
- [ ] Update Telegram settings
- [ ] **SQL Query:** `SELECT config FROM integrations WHERE service='telegram'`
- [ ] **Verification:** JSON config contains bot_token, chat_id

#### Foreign Key Constraints
- [ ] Attempt to delete service with features
- [ ] **Verification:** Either features cascade deleted or deletion prevented

### 4.2 Data Consistency

#### UTF-8 Encoding
- [ ] Create testimonial with Russian text
- [ ] **SQL Query:** `SELECT text FROM testimonials WHERE id=?`
- [ ] **Verification:** Cyrillic characters intact

#### JSON Fields
- [ ] Submit order with calculator_data
- [ ] **SQL Query:** `SELECT calculator_data FROM orders WHERE id=?`
- [ ] **Verification:** Valid JSON, all data preserved

#### Unique Constraints
- [ ] Attempt duplicate slug
- [ ] **Verification:** Unique constraint violation error

#### Indexes
- [ ] **SQL Query:** `SHOW INDEXES FROM orders`
- [ ] **Verification:** Indexes on status, created_at, order_number

#### Full-Text Search Index
- [ ] **SQL Query:** `SHOW INDEXES FROM orders WHERE Index_type='FULLTEXT'`
- [ ] **Verification:** FULLTEXT index on client_name, client_email, message

---

## 5. Performance Testing

### Response Times
- [ ] GET /api/services - Target: < 200ms
- [ ] GET /api/orders - Target: < 500ms
- [ ] POST /api/orders - Target: < 1000ms
- [ ] GET /api/settings/public - Target: < 300ms

### Load Testing (Optional)
- [ ] Use Apache Bench or wrk to simulate 100 concurrent requests
- [ ] **Expected:** No errors, acceptable response times
- [ ] Monitor CPU and memory usage

### Caching
- [ ] Check API responses use caching where appropriate
- [ ] Browser caching headers set correctly
- [ ] In-memory caching works (e.g., apiClient.cache)

---

## 6. Security Audit

### JWT Security
- [ ] Token expiration enforced (1 hour default)
- [ ] Token signature validation works
- [ ] Invalid tokens rejected
- [ ] Token secrets not exposed in code

### Password Security
- [ ] Passwords hashed with bcrypt
- [ ] Password hashes never returned in API responses
- [ ] Strong password requirements enforced (optional)

### SQL Injection Prevention
- [ ] All queries use prepared statements
- [ ] No raw SQL concatenation with user input

### XSS Prevention
- [ ] User input sanitized/escaped
- [ ] HTML entities encoded in output
- [ ] Script tags stripped or escaped

### CORS Configuration
- [ ] CORS headers set correctly
- [ ] Only allowed origins can access API
- [ ] Preflight requests handled

### Rate Limiting
- [ ] Rate limiting enforced on public order submissions
- [ ] 429 status returned after limit exceeded

### Sensitive Data Exposure
- [ ] Bot tokens redacted in API responses
- [ ] Database credentials not leaked in errors
- [ ] Error messages don't reveal sensitive info

### HTTPS (Production)
- [ ] HTTPS enforced in production
- [ ] Mixed content warnings checked
- [ ] SSL certificate valid

---

## 7. Browser Compatibility

### Desktop Browsers
- [ ] Chrome (latest) - All features work
- [ ] Firefox (latest) - All features work
- [ ] Safari (latest) - All features work
- [ ] Edge (latest) - All features work

### Mobile Browsers
- [ ] Mobile Chrome - All features work
- [ ] Mobile Safari - All features work
- [ ] Mobile Firefox - All features work

### JavaScript Disabled
- [ ] Graceful degradation message shown
- [ ] Critical content accessible (optional)

---

## 8. Accessibility Testing

### Keyboard Navigation
- [ ] Tab through all form fields
- [ ] Enter to submit forms
- [ ] Escape to close modals
- [ ] Arrow keys navigate (where applicable)

### Screen Reader
- [ ] ARIA labels present
- [ ] Form labels associated with inputs
- [ ] Error messages announced
- [ ] Status updates announced

### Color Contrast
- [ ] Text readable against backgrounds
- [ ] Links distinguishable from text
- [ ] Focus indicators visible

---

## 9. Automated Test Execution

### PHPUnit Tests
- [ ] Run: `cd backend && ./run-tests.sh`
- [ ] All AuthTest tests pass
- [ ] All OrdersTest tests pass
- [ ] All SettingsTest tests pass
- [ ] All ContentTest tests pass
- [ ] **Expected:** 100% pass rate

### Test Coverage
- [ ] Generate coverage report (if xdebug installed)
- [ ] **Target:** > 70% code coverage

---

## 10. Documentation Verification

### API Documentation
- [ ] All endpoints documented in docs/api.md
- [ ] Request/response examples accurate
- [ ] Authentication requirements clear

### README Files
- [ ] Backend README.md up to date
- [ ] Frontend setup instructions accurate
- [ ] Deployment guides correct

### Migration Guide
- [ ] Migration steps accurate
- [ ] Data transformation documented
- [ ] Troubleshooting section helpful

---

## Test Execution Sign-off

**Tester Name:** _________________  
**Date:** _________________  
**Environment:** _________________  
**Overall Status:** ☐ Pass ☐ Fail ☐ Partial  

**Critical Issues Found:** _________________  
**Recommendations:** _________________  

**Approved for Deployment:** ☐ Yes ☐ No  
**Approver:** _________________  
**Date:** _________________  

---

**Checklist Version:** 1.0.0  
**Last Updated:** 2024-11-14
