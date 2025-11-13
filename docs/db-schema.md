# Database Schema Documentation

## Overview

This document describes the MySQL database schema for the 3D Print Pro application. The schema is designed to support a 3D printing service website with order management, portfolio showcase, customer testimonials, FAQ system, and a comprehensive pricing calculator.

**Database Name**: `ch167436_3dprint`  
**Database Engine**: MySQL 8.0+  
**Character Set**: `utf8mb4` (full Unicode support including emojis)  
**Collation**: `utf8mb4_unicode_ci`

---

## Table of Contents

1. [Entity Relationship Overview](#entity-relationship-overview)
2. [Table Descriptions](#table-descriptions)
3. [Design Decisions](#design-decisions)
4. [Indexes and Performance](#indexes-and-performance)
5. [Data Integrity](#data-integrity)
6. [Migration Instructions](#migration-instructions)
7. [Backup and Restore](#backup-and-restore)
8. [Future Enhancements](#future-enhancements)

---

## Entity Relationship Overview

### Core Entities

```
┌─────────────────┐
│     USERS       │
│  (Admin Auth)   │
└────────┬────────┘
         │
         │ creates/modifies
         │
         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│     ORDERS      │     │    SERVICES     │────▶│SERVICE_FEATURES │
│  (Submissions)  │     │   (Catalog)     │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘

┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   PORTFOLIO     │     │  TESTIMONIALS   │     │      FAQ        │
│   (Projects)    │     │   (Reviews)     │     │  (Questions)    │
└─────────────────┘     └─────────────────┘     └─────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                    CALCULATOR CONFIGURATION                       │
├───────────────┬──────────────────┬────────────────┬──────────────┤
│   MATERIALS   │ADDITIONAL_SERVICES│ QUALITY_LEVELS │VOLUME_DISCOUNTS│
└───────────────┴──────────────────┴────────────────┴──────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                        SITE CONFIGURATION                         │
├───────────────┬──────────────────┬────────────────┬──────────────┤
│SITE_SETTINGS  │  SITE_CONTENT    │   SITE_STATS   │ INTEGRATIONS │
│  (Singleton)  │   (Sections)     │   (Singleton)  │  (Telegram)  │
└───────────────┴──────────────────┴────────────────┴──────────────┘

┌─────────────────┐     ┌─────────────────┐
│  FORM_FIELDS    │     │  AUDIT_LOGS     │
│ (Configuration) │     │  (Audit Trail)  │
└─────────────────┘     └─────────────────┘
```

### Relationships

- **services** → **service_features** (1:N) - Each service has multiple features
- **users** → **audit_logs** (1:N) - User actions are logged
- **orders** - Stores calculator_data as JSON (flexible structure)

---

## Table Descriptions

### 1. `users` - Admin Authentication

Stores user accounts for admin panel access.

**Key Fields:**
- `id` - Primary key
- `login` - Unique username (indexed)
- `password_hash` - bcrypt hashed password
- `email` - Unique email (indexed)
- `role` - ENUM('admin', 'manager', 'user')
- `active` - Boolean flag for account status

**Indexes:**
- `PRIMARY KEY` on `id`
- `UNIQUE` on `login`, `email`
- Index on `active`

**Default Credentials:**
- Username: `admin`
- Password: `admin123` (change in production!)

**Security Notes:**
- Passwords are stored as bcrypt hashes (never plaintext)
- Use strong passwords in production
- Implement password rotation policy
- Add MFA for enhanced security

---

### 2. `services` - Service Catalog

Main service offerings displayed on the website.

**Key Fields:**
- `id` - Primary key
- `name` - Service name (e.g., "FDM печать")
- `slug` - URL-friendly identifier (unique, indexed)
- `icon` - FontAwesome icon class
- `description` - Service description
- `price` - Display price text (e.g., "от 50₽/г")
- `active` - Visibility flag (soft delete)
- `featured` - Highlight as popular service
- `display_order` - Sort order (lower = higher priority)

**Related Tables:**
- `service_features` - List of features for each service

**Indexes:**
- `UNIQUE` on `slug`
- Index on `active`, `featured`, `display_order`

---

### 3. `service_features` - Service Features

Features/benefits for each service (normalized).

**Key Fields:**
- `id` - Primary key
- `service_id` - Foreign key to `services`
- `feature_text` - Feature description
- `display_order` - Sort order within service

**Relationships:**
- Foreign key to `services.id` with `CASCADE DELETE`

---

### 4. `portfolio` - Project Showcase

Completed projects displayed in portfolio gallery.

**Key Fields:**
- `id` - Primary key
- `title` - Project title
- `category` - ENUM('prototype', 'functional', 'art', 'industrial')
- `description` - Short description
- `image_url` - CDN URL or relative path
- `details` - Extended project details (optional)

**Indexes:**
- Index on `category` (for filtering)
- Index on `created_at DESC` (for chronological sorting)

**Image Management:**
- Store images in CDN or object storage (e.g., AWS S3, Cloudinary)
- Store only URL in database
- Generate thumbnails on upload for performance

---

### 5. `testimonials` - Customer Reviews

Customer testimonials with approval workflow.

**Key Fields:**
- `id` - Primary key
- `name` - Customer name
- `position` - Job title or company
- `avatar_url` - Profile image URL
- `rating` - Integer 1-5 (with CHECK constraint)
- `text` - Review text
- `approved` - Boolean approval flag
- `display_order` - Sort order

**Indexes:**
- Index on `approved` (filter public testimonials)
- Index on `rating`, `display_order`

**Workflow:**
- New testimonials default to `approved = FALSE`
- Admin must explicitly approve before public display
- Only approved testimonials shown on public site

---

### 6. `faq` - Frequently Asked Questions

FAQ items with visibility control.

**Key Fields:**
- `id` - Primary key
- `question` - Question text
- `answer` - Answer text (supports HTML)
- `active` - Visibility flag
- `display_order` - Sort order

**Indexes:**
- Index on `active`, `display_order`

---

### 7. `orders` - Orders and Contact Submissions

Central table for customer orders and contact form submissions.

**Key Fields:**
- `id` - Primary key
- `order_number` - Human-readable unique identifier (e.g., "ORD-1699876543210")
- `type` - ENUM('order', 'contact')
- `status` - ENUM('new', 'processing', 'completed', 'cancelled')
- `client_name`, `client_email`, `client_phone` - Customer info
- `telegram` - Optional Telegram username
- `service` - Service name/type
- `subject` - Contact form subject (optional)
- `message` - Customer message
- `amount` - Total order amount (DECIMAL)
- `calculator_data` - JSON field with full calculator results
- `telegram_sent` - Boolean notification status
- `telegram_sent_at` - Timestamp of Telegram notification

**Indexes:**
- `UNIQUE` on `order_number`
- Index on `type`, `status`, `created_at DESC`
- Composite index on `(status, created_at DESC)` for admin filtering
- `FULLTEXT` index on `(client_name, client_email, message)` for search

**Calculator Data Structure (JSON):**
```json
{
  "technology": "fdm|sla|sls",
  "material": "Material name",
  "weight": 100,
  "quantity": 5,
  "infill": 50,
  "quality": "normal",
  "materialCost": 250.00,
  "laborCost": 500.00,
  "additionalCost": 300.00,
  "discount": 75.00,
  "discountPercent": 10,
  "total": 975.00,
  "timeEstimate": "3 дня",
  "service": "FDM печать",
  "details": "Detailed calculation breakdown"
}
```

**Status State Machine:**
```
new → processing → completed
  ↓
cancelled
```

---

### 8. `materials` - 3D Printing Materials

Material pricing for calculator (normalized from settings).

**Key Fields:**
- `id` - Primary key
- `material_key` - Unique identifier (e.g., 'pla', 'abs')
- `name` - Display name
- `price` - Price per gram (DECIMAL)
- `technology` - ENUM('fdm', 'sla', 'sls')
- `active` - Enable/disable material
- `display_order` - Sort order

**Indexes:**
- `UNIQUE` on `material_key`
- Index on `technology`, `active`, `display_order`

---

### 9. `additional_services` - Additional Services

Extra services for calculator (modeling, painting, etc.).

**Key Fields:**
- `id` - Primary key
- `service_key` - Unique identifier
- `name` - Display name
- `price` - Service price (DECIMAL)
- `unit` - Pricing unit (e.g., 'час', 'шт', 'заказ')
- `active` - Enable/disable service
- `display_order` - Sort order

---

### 10. `quality_levels` - Print Quality Settings

Quality levels with pricing/time multipliers.

**Key Fields:**
- `id` - Primary key
- `quality_key` - Unique identifier (e.g., 'draft', 'normal', 'high', 'ultra')
- `name` - Display name
- `price_multiplier` - Price adjustment factor (DECIMAL)
- `time_multiplier` - Time estimate factor (DECIMAL)
- `active` - Enable/disable level
- `display_order` - Sort order

**Example Multipliers:**
- Draft: 0.80 price, 0.70 time
- Normal: 1.00 price, 1.00 time
- High: 1.30 price, 1.40 time
- Ultra: 1.60 price, 2.00 time

---

### 11. `volume_discounts` - Volume Discount Tiers

Quantity-based discount rules.

**Key Fields:**
- `id` - Primary key
- `min_quantity` - Minimum quantity for discount
- `discount_percent` - Discount percentage (DECIMAL)
- `active` - Enable/disable discount

**Default Tiers:**
- 10+ units: 10% discount
- 50+ units: 15% discount
- 100+ units: 20% discount

---

### 12. `form_fields` - Dynamic Form Configuration

Configurable form fields for contact and order forms.

**Key Fields:**
- `id` - Primary key
- `form_type` - ENUM('contact', 'order')
- `field_name` - Field identifier
- `label` - Display label
- `field_type` - ENUM('text', 'email', 'tel', 'textarea', 'select', etc.)
- `required` - Required field flag
- `enabled` - Enable/disable field
- `placeholder` - Placeholder text
- `display_order` - Sort order
- `options` - JSON array of options (for select fields)

**Indexes:**
- `UNIQUE` on `(form_type, field_name)`
- Index on `form_type`, `enabled`, `display_order`

**Options Field Example (for select):**
```json
["Расчет стоимости", "Консультация", "Партнерство", "Другое"]
```

---

### 13. `site_settings` - Global Site Settings (Singleton)

Global configuration for the website (should contain only one row).

**Key Fields:**
- `id` - Primary key
- `site_name`, `site_description` - Site metadata
- `contact_email`, `contact_phone`, `address` - Contact info
- `working_hours` - Business hours (multiline text)
- `timezone` - IANA timezone (e.g., 'Europe/Moscow')
- `social_links` - JSON object with social media URLs
- `theme` - UI theme preference
- `color_primary`, `color_secondary` - Hex color codes
- `notifications` - JSON object with notification preferences

**JSON Structures:**

**social_links:**
```json
{
  "vk": "https://vk.com/...",
  "telegram": "https://t.me/...",
  "whatsapp": "https://wa.me/...",
  "youtube": "https://youtube.com/..."
}
```

**notifications:**
```json
{
  "newOrders": true,
  "newReviews": true,
  "newMessages": true
}
```

**Singleton Pattern:**
- Only one row should exist
- Application should use ID=1
- Updates modify existing row (never insert new rows)

---

### 14. `integrations` - External Integration Settings

Configuration for external services (Telegram, email, SMS).

**Key Fields:**
- `id` - Primary key
- `integration_name` - Unique identifier (e.g., 'telegram')
- `enabled` - Enable/disable integration
- `config` - JSON object with integration-specific settings

**Telegram Config Example:**
```json
{
  "botToken": "123456:ABC-DEF...",
  "chatId": "123456789",
  "apiUrl": "https://api.telegram.org/bot",
  "contactUrl": "https://t.me/username"
}
```

**Security:**
- Store sensitive tokens in environment variables (not in database)
- Use config field only for non-sensitive settings
- Encrypt sensitive data if stored in database

---

### 15. `site_content` - Editable Site Content

Content sections for hero, about, and other site areas.

**Key Fields:**
- `id` - Primary key
- `section_key` - Unique identifier (e.g., 'hero', 'about')
- `title` - Section heading
- `content` - JSON object with section-specific structure

**Hero Content Example:**
```json
{
  "subtitle": "Professional 3D printing...",
  "features": [
    "Печать от 1 часа",
    "15+ материалов",
    "Гарантия качества"
  ]
}
```

**About Content Example:**
```json
{
  "description": "We are a team...",
  "features": [
    {
      "title": "Modern Equipment",
      "description": "Latest generation printers"
    },
    ...
  ]
}
```

---

### 16. `site_stats` - Site Statistics (Singleton)

Manually updated site statistics (should contain only one row).

**Key Fields:**
- `id` - Primary key
- `total_projects` - Total completed projects
- `happy_clients` - Number of clients
- `years_experience` - Years in business
- `awards` - Awards won

**Note:** These are manually updated (not auto-calculated).

---

### 17. `audit_logs` - Audit Trail (Optional)

Logs all changes to critical data for compliance and debugging.

**Key Fields:**
- `id` - Primary key
- `user_id` - Foreign key to `users` (nullable)
- `entity_type` - Table name (e.g., 'orders', 'settings')
- `entity_id` - Record ID
- `action` - ENUM('create', 'update', 'delete')
- `field_name` - Field that changed (for updates)
- `old_value`, `new_value` - Before/after values
- `ip_address`, `user_agent` - Request metadata
- `created_at` - Timestamp

**Relationships:**
- Foreign key to `users.id` with `SET NULL` on delete

**Use Cases:**
- Track order status changes
- Monitor pricing configuration changes
- Compliance and security audits
- Debugging data issues

---

## Design Decisions

### 1. Normalization vs. Denormalization

**Normalized:**
- `service_features` - Separate table for repeating features
- `materials`, `additional_services`, `quality_levels` - Separate tables for calculator config
- Easier to update, maintain referential integrity

**Denormalized (JSON columns):**
- `orders.calculator_data` - Complex nested calculation results
- `site_settings.social_links`, `notifications` - Rarely queried individually
- `form_fields.options` - Dynamic list for select fields
- `site_content.content` - Section-specific structures

**Rationale:**
- JSON columns reduce join complexity for nested data
- Better for data that's always fetched together
- Trade-off: Harder to query/filter on nested fields

### 2. Soft Deletes

Tables using `active` flag instead of hard deletes:
- `services` - Preserve historical references
- `testimonials` - Approval workflow
- `faq` - Temporarily hide without losing data
- `materials`, `additional_services`, `quality_levels` - Calculator config

**Benefits:**
- Preserve historical data
- Easy rollback
- Analytics on inactive items

### 3. ENUM vs. VARCHAR

**Using ENUM for:**
- Order status, order type (fixed set, rarely changes)
- Portfolio categories (stable taxonomy)
- Print technologies (industry standard)
- Form field types (web standards)

**Benefits:**
- Database-level validation
- Better performance (stored as integers internally)
- Self-documenting schema

**Caution:**
- Changing ENUMs requires ALTER TABLE
- Use VARCHAR if values change frequently

### 4. Timestamp Management

All tables have:
- `created_at` - Auto-set on INSERT
- `updated_at` - Auto-updated on UPDATE

**Benefits:**
- Audit trail
- Analytics on creation/update patterns
- Debugging data issues

### 5. Character Set: utf8mb4

**Why not utf8?**
- utf8 in MySQL only supports 3-byte characters
- Doesn't support emojis and some special characters
- utf8mb4 supports full Unicode (4-byte characters)

**Modern applications require utf8mb4.**

---

## Indexes and Performance

### Primary Indexes

Every table has:
- `PRIMARY KEY` on `id` (auto-increment)

### Unique Indexes

- `users.login`, `users.email`
- `services.slug`
- `orders.order_number`
- `materials.material_key`
- `additional_services.service_key`
- `quality_levels.quality_key`
- `form_fields.(form_type, field_name)`
- `integrations.integration_name`
- `site_content.section_key`

### Performance Indexes

**For Filtering:**
- `orders.status`, `orders.type`, `orders.client_email`
- `services.active`, `services.featured`
- `testimonials.approved`, `testimonials.rating`
- `faq.active`
- `materials.technology`, `materials.active`

**For Sorting:**
- `orders.created_at DESC` - Most recent orders first
- `portfolio.created_at DESC` - Latest projects
- `services.display_order`, `testimonials.display_order`, etc.

**Composite Indexes:**
- `orders.(status, created_at DESC)` - Admin filter + sort
- `service_features.(service_id, display_order)` - Features per service

**Full-Text Search:**
- `orders.(client_name, client_email, message)` - Search orders

### Query Optimization Tips

1. **Always filter by indexed columns** when possible
2. **Use composite indexes** for common filter+sort combinations
3. **Avoid SELECT *** - Fetch only needed columns
4. **Use LIMIT** for pagination
5. **Cache singleton tables** (settings, content, stats) in application memory
6. **Use EXPLAIN** to analyze query performance

---

## Data Integrity

### Foreign Keys

- `service_features.service_id` → `services.id` (CASCADE DELETE)
- `audit_logs.user_id` → `users.id` (SET NULL on delete)

### CHECK Constraints

- `testimonials.rating` - BETWEEN 1 AND 5

### NOT NULL Constraints

All required fields marked as `NOT NULL` for data integrity.

### UNIQUE Constraints

Prevent duplicate entries on:
- Usernames, emails
- Service slugs
- Order numbers
- Material/service/quality keys

### Default Values

Sensible defaults on:
- `active = TRUE` for most entities
- `status = 'new'` for orders
- `telegram_sent = FALSE`
- Timestamps auto-populated

---

## Migration Instructions

### Prerequisites

- MySQL 8.0 or higher
- Root or user with CREATE DATABASE privileges
- Command-line access or MySQL client

### Step 1: Create Database and Schema

**Option A: Command Line**
```bash
mysql -u root -p < backend/database/migrations/20231113_initial.sql
```

**Option B: MySQL Client**
```sql
SOURCE /path/to/backend/database/migrations/20231113_initial.sql;
```

**Option C: phpMyAdmin/MySQL Workbench**
1. Open SQL editor
2. Copy/paste contents of `20231113_initial.sql`
3. Execute

### Step 2: Verify Schema

```sql
USE ch167436_3dprint;
SHOW TABLES;
```

Expected output (17 tables):
```
+---------------------------+
| Tables_in_ch167436_3dprint|
+---------------------------+
| additional_services       |
| audit_logs               |
| faq                      |
| form_fields              |
| integrations             |
| materials                |
| orders                   |
| portfolio                |
| quality_levels           |
| service_features         |
| services                 |
| site_content             |
| site_settings            |
| site_stats               |
| testimonials             |
| users                    |
| volume_discounts         |
+---------------------------+
```

### Step 3: Load Seed Data

```bash
mysql -u root -p ch167436_3dprint < backend/database/seeds/initial_data.sql
```

### Step 4: Verify Seed Data

```sql
-- Check services
SELECT COUNT(*) FROM services; -- Should be 6

-- Check materials
SELECT COUNT(*) FROM materials; -- Should be 10

-- Check testimonials
SELECT COUNT(*) FROM testimonials; -- Should be 4

-- Check FAQ
SELECT COUNT(*) FROM faq; -- Should be 6

-- Check admin user
SELECT login, name FROM users WHERE role = 'admin'; -- Should show 'admin'
```

### Step 5: Test Connections

Create a test connection from your application:

**Node.js Example:**
```javascript
const mysql = require('mysql2/promise');

const connection = await mysql.createConnection({
  host: 'localhost',
  user: 'your_user',
  password: 'your_password',
  database: 'ch167436_3dprint',
  charset: 'utf8mb4'
});

const [rows] = await connection.execute('SELECT * FROM site_settings LIMIT 1');
console.log(rows);
```

**PHP Example:**
```php
$conn = new mysqli('localhost', 'user', 'password', 'ch167436_3dprint');
$conn->set_charset('utf8mb4');

$result = $conn->query('SELECT * FROM site_settings LIMIT 1');
$settings = $result->fetch_assoc();
print_r($settings);
```

---

## Environment-Specific Instructions

### Development Environment

1. **Use local MySQL server**
   ```bash
   # Install MySQL
   sudo apt-get install mysql-server  # Ubuntu/Debian
   brew install mysql                 # macOS
   
   # Start MySQL
   sudo systemctl start mysql         # Linux
   mysql.server start                 # macOS
   ```

2. **Create development user**
   ```sql
   CREATE USER 'dev_user'@'localhost' IDENTIFIED BY 'dev_password';
   GRANT ALL PRIVILEGES ON ch167436_3dprint.* TO 'dev_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Run migrations**
   ```bash
   mysql -u dev_user -p < backend/database/migrations/20231113_initial.sql
   mysql -u dev_user -p ch167436_3dprint < backend/database/seeds/initial_data.sql
   ```

### Staging Environment

1. **Use separate database**
   ```sql
   CREATE DATABASE ch167436_3dprint_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Apply same migrations**

3. **Use staging data** (not production copy initially)

### Production Environment

1. **Security hardening:**
   - Change default admin password immediately
   - Use strong database passwords
   - Restrict database access to application server only
   - Enable SSL for database connections
   - Regular security audits

2. **Backup before migration:**
   ```bash
   mysqldump -u root -p existing_db > backup_$(date +%Y%m%d).sql
   ```

3. **Apply migrations during maintenance window**

4. **Monitor after deployment:**
   - Check application logs
   - Verify all features working
   - Monitor database performance

---

## Backup and Restore

### Backup

**Full Database Backup:**
```bash
mysqldump -u root -p --single-transaction --routines --triggers \
  ch167436_3dprint > backup_$(date +%Y%m%d_%H%M%S).sql
```

**Backup Specific Tables:**
```bash
mysqldump -u root -p ch167436_3dprint orders portfolio > backup_content.sql
```

**Automated Daily Backups (Cron):**
```bash
# Add to crontab
0 2 * * * mysqldump -u backup_user -p'password' ch167436_3dprint > /backups/3dprint_$(date +\%Y\%m\%d).sql
```

### Restore

**Full Restore:**
```bash
mysql -u root -p ch167436_3dprint < backup_20231113_020000.sql
```

**Selective Table Restore:**
```bash
mysql -u root -p ch167436_3dprint < backup_orders_only.sql
```

### Backup Strategy Recommendations

1. **Daily automated backups** (retain 7 days)
2. **Weekly backups** (retain 4 weeks)
3. **Monthly backups** (retain 12 months)
4. **Before any major changes** (migrations, bulk updates)
5. **Test restore procedures** regularly
6. **Store backups off-site** (cloud storage)

---

## Future Enhancements

### Potential Schema Improvements

1. **User Roles and Permissions**
   - Add `roles` table with granular permissions
   - Add `user_permissions` join table
   - Support multi-tenant access control

2. **Advanced Analytics**
   - Add `order_events` table for status change tracking
   - Add `page_views` table for analytics
   - Add `conversion_funnel` tracking

3. **Payment Integration**
   - Add `payments` table with transaction details
   - Add `invoices` table with PDF storage
   - Add `payment_methods` table

4. **File Management**
   - Add `files` table for uploaded 3D models
   - Add `file_versions` for revision tracking
   - Link files to orders

5. **Customer Accounts**
   - Extend `users` to support customer accounts
   - Add `customer_addresses` table
   - Add `customer_preferences` table

6. **Email Campaigns**
   - Add `email_templates` table
   - Add `email_campaigns` table
   - Add `email_subscriptions` table

7. **Inventory Management**
   - Add `material_inventory` table
   - Add `material_usage` tracking
   - Add low-stock alerts

8. **Advanced Calculator**
   - Add `print_profiles` for machine-specific settings
   - Add `material_compatibility` rules
   - Add cost estimation history

---

## Troubleshooting

### Common Issues

**1. Character encoding errors**
```sql
-- Check current charset
SHOW VARIABLES LIKE 'character_set%';

-- Ensure utf8mb4
SET NAMES utf8mb4;
ALTER DATABASE ch167436_3dprint CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```

**2. Foreign key constraint errors**
```sql
-- Temporarily disable checks (use carefully!)
SET FOREIGN_KEY_CHECKS = 0;
-- Run your query
SET FOREIGN_KEY_CHECKS = 1;
```

**3. Duplicate entry errors**
```sql
-- Find duplicates
SELECT order_number, COUNT(*) 
FROM orders 
GROUP BY order_number 
HAVING COUNT(*) > 1;
```

**4. Performance issues**
```sql
-- Analyze slow queries
SHOW PROCESSLIST;

-- Check table sizes
SELECT 
    table_name AS 'Table',
    round(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'ch167436_3dprint'
ORDER BY (data_length + index_length) DESC;
```

---

## Support and Maintenance

### Database Maintenance Tasks

**Weekly:**
- Review slow query log
- Check table sizes
- Verify backup success

**Monthly:**
- Optimize tables: `OPTIMIZE TABLE orders, portfolio, testimonials;`
- Analyze query performance
- Review audit logs for anomalies
- Update statistics: `ANALYZE TABLE orders;`

**Quarterly:**
- Review and update indexes based on query patterns
- Clean up old audit logs (if retention policy applies)
- Security audit (user access, password policies)

### Monitoring Recommendations

1. **Database size growth** - Alert if growing too fast
2. **Query performance** - Track slow queries
3. **Connection pool** - Monitor active connections
4. **Replication lag** (if using replication)
5. **Backup success rate** - Alert on failures

---

## Schema Version Control

This schema is version 1.0.0 (initial release).

Future migrations should:
1. Be numbered sequentially (e.g., `20231115_add_user_preferences.sql`)
2. Include both UP and DOWN migrations (rollback capability)
3. Be tested on staging before production
4. Be documented in this file

**Migration Naming Convention:**
```
YYYYMMDD_description.sql
```

Example:
```
20231115_add_payment_table.sql
20231120_alter_orders_add_payment_id.sql
```

---

## Conclusion

This schema provides a solid foundation for the 3D Print Pro application, balancing normalization with practical performance considerations. The use of JSON columns for complex nested data allows flexibility while maintaining relational integrity for core entities.

For questions or issues, refer to the troubleshooting section or consult the development team.

**Last Updated:** 2023-11-13  
**Schema Version:** 1.0.0  
**Database:** ch167436_3dprint  
**MySQL Version:** 8.0+
