# Data Migration Guide

This guide explains how to migrate data from the localStorage-based system to the new MySQL database backend.

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Step 1: Export localStorage Data](#step-1-export-localstorage-data)
- [Step 2: Prepare the Database](#step-2-prepare-the-database)
- [Step 3: Run the Importer](#step-3-run-the-importer)
- [Step 4: Verify the Import](#step-4-verify-the-import)
- [Troubleshooting](#troubleshooting)
- [Advanced Usage](#advanced-usage)

## Overview

The migration process involves three main steps:

1. **Export** - Export your current localStorage data to a JSON file
2. **Import** - Run the importer script to populate the MySQL database
3. **Verify** - Confirm all data was imported correctly

The importer automatically handles:
- ID collision detection and regeneration
- Timestamp population
- Data structure transformation (arrays → normalized tables/JSON columns)
- Feature normalization (service features)
- Calculator configuration mapping
- Form fields mapping

## Prerequisites

Before starting the migration, ensure you have:

- ✅ Access to your current 3D Print Pro website
- ✅ MySQL database set up and running
- ✅ Database migrations executed (`20231113_initial.sql`)
- ✅ PHP 7.4+ with PDO MySQL extension
- ✅ Composer dependencies installed (`composer install`)
- ✅ `.env` file configured with database credentials

## Step 1: Export localStorage Data

### From Browser Console

1. Open your current 3D Print Pro website
2. Open browser Developer Tools (F12)
3. Go to the **Console** tab
4. Run the export command:

```javascript
// Export current data
db.exportData();
```

This will download a file named `3dprintpro_backup_YYYY-MM-DD.json` containing all your data.

### From Admin Panel

If your admin panel has an export feature:

1. Log in to the admin panel
2. Navigate to **Settings** → **Data Management**
3. Click **Export Data**
4. Save the downloaded JSON file

### Manual Export

Alternatively, copy the localStorage data manually:

1. Open Developer Tools → **Application** tab
2. Expand **Local Storage** → Select your domain
3. Find the `3dprintpro_data` key
4. Copy its value
5. Save to a file named `export.json`

### Example Export Structure

The export file should have this structure:

```json
{
  "orders": [...],
  "portfolio": [...],
  "services": [...],
  "testimonials": [...],
  "faq": [...],
  "settings": [{...}],
  "content": [{...}],
  "stats": {...}
}
```

## Step 2: Prepare the Database

### Run Database Migrations

If not already done, create and initialize the database:

```bash
cd backend

# Create database and tables
mysql -u root -p < database/migrations/20231113_initial.sql

# Or if using environment variables
mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < database/migrations/20231113_initial.sql
```

### Seed Admin User

Create the admin user (if not already created):

```bash
cd backend

# Run admin user seeder
php database/seeds/seed-admin-user.php
```

Make sure your `.env` file has these variables:

```env
ADMIN_LOGIN=admin
ADMIN_PASSWORD=your_secure_password
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@yourdomain.com
```

### Verify Database Connection

Test the database connection:

```bash
cd backend
php test-connection.php
```

You should see:
```
✅ Database connection successful
```

## Step 3: Run the Importer

### Basic Import (Dry Run First)

**Always run a dry run first** to see what would be imported:

```bash
cd backend/scripts

# Dry run - no changes made
php import_local_data.php --file=/path/to/export.json --dry-run
```

Review the output carefully. You should see:

```
🚀 Starting data import...
🔍 DRY RUN MODE - No changes will be made

📦 Importing services...
  [DRY RUN] Would insert service: FDM печать
  [DRY RUN] Would insert service: SLA/SLS печать
  ...
  ✅ Imported 6 services

🖼️  Importing portfolio...
  ...

📊 IMPORT SUMMARY
===========================================================
  Services:                    6
  Service features:           24
  Portfolio:                   8
  Testimonials:                4
  FAQ:                         6
  Settings:                    1
  ...
  Total records:              85
===========================================================

💡 This was a dry run. No data was actually imported.
   Run without --dry-run to perform the actual import.
```

### Perform Actual Import

If the dry run looks good, run the actual import:

```bash
# Import all data
php import_local_data.php --file=/path/to/export.json --verbose
```

The `--verbose` flag shows detailed progress.

### Selective Import

Import only specific tables:

```bash
# Import only services and portfolio (skip everything else)
php import_local_data.php --file=export.json \
  --skip-orders \
  --skip-testimonials \
  --skip-faq \
  --skip-settings \
  --skip-content \
  --skip-stats

# Skip only orders (import everything else)
php import_local_data.php --file=export.json --skip-orders
```

### Force Overwrite Existing Data

By default, singleton tables (settings, stats, content) will not be overwritten if they already exist. Use `--force` to overwrite:

```bash
# Overwrite existing settings and stats
php import_local_data.php --file=export.json --force
```

⚠️ **Warning**: This will delete and replace existing settings/stats data!

## Step 4: Verify the Import

### Using MySQL

Connect to your database and verify the data:

```bash
mysql -u your_username -p your_database
```

```sql
-- Check services
SELECT COUNT(*) as total_services FROM services;
SELECT id, name, slug, active FROM services;

-- Check service features
SELECT COUNT(*) as total_features FROM service_features;
SELECT s.name, f.feature_text 
FROM service_features f 
JOIN services s ON f.service_id = s.id;

-- Check portfolio
SELECT COUNT(*) as total_portfolio FROM portfolio;
SELECT id, title, category FROM portfolio;

-- Check testimonials
SELECT COUNT(*) as total_testimonials FROM testimonials;
SELECT id, name, rating, approved FROM testimonials;

-- Check FAQ
SELECT COUNT(*) as total_faq FROM faq;
SELECT id, question, active FROM faq;

-- Check settings
SELECT * FROM site_settings;

-- Check stats
SELECT * FROM site_stats;

-- Check content sections
SELECT section_key, title FROM site_content;

-- Check calculator materials
SELECT COUNT(*) as total_materials FROM materials;
SELECT material_key, name, price, technology FROM materials;

-- Check additional services
SELECT COUNT(*) as total_services FROM additional_services;
SELECT service_key, name, price, unit FROM additional_services;

-- Check quality levels
SELECT COUNT(*) as total_quality FROM quality_levels;
SELECT quality_key, name, price_multiplier FROM quality_levels;

-- Check volume discounts
SELECT COUNT(*) as total_discounts FROM volume_discounts;
SELECT min_quantity, discount_percent FROM volume_discounts;

-- Check form fields
SELECT COUNT(*) as total_fields FROM form_fields;
SELECT form_type, field_name, label FROM form_fields;

-- Check orders (if imported)
SELECT COUNT(*) as total_orders FROM orders;
SELECT id, order_number, type, status, client_name FROM orders LIMIT 10;
```

### Using the API

Test the API endpoints to verify data:

```bash
# Check services
curl http://localhost:8080/api/services

# Check portfolio
curl http://localhost:8080/api/portfolio

# Check testimonials
curl http://localhost:8080/api/testimonials

# Check FAQ
curl http://localhost:8080/api/faq

# Check content
curl http://localhost:8080/api/content

# Check stats
curl http://localhost:8080/api/stats

# Check public settings
curl http://localhost:8080/api/settings/public

# Check orders (requires authentication)
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8080/api/orders
```

### Using the Admin Panel

1. Log in to the admin panel
2. Navigate through each section:
   - **Services** - Verify all services and features are present
   - **Portfolio** - Check portfolio items and images
   - **Testimonials** - Confirm testimonials are imported
   - **FAQ** - Verify FAQ items
   - **Orders** - Check order history (if imported)
   - **Settings** - Review site settings and calculator config
   - **Content** - Check content sections

## Troubleshooting

### Import Fails with "Connection refused"

**Problem**: Cannot connect to database

**Solution**: Check your `.env` configuration:

```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ch167436_3dprint
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Test connection:
```bash
php backend/test-connection.php
```

### "Table doesn't exist" Error

**Problem**: Database tables not created

**Solution**: Run the migration script first:

```bash
mysql -u root -p < backend/database/migrations/20231113_initial.sql
```

### "Duplicate entry" Error

**Problem**: Trying to import data that already exists

**Solution**: 
- Use `--force` flag to overwrite existing data
- Or delete specific records first:

```sql
-- Clear specific tables
DELETE FROM services;
DELETE FROM service_features;
DELETE FROM portfolio;
-- etc...

-- Or clear all tables (careful!)
DELETE FROM orders;
DELETE FROM portfolio;
DELETE FROM services;
DELETE FROM service_features;
DELETE FROM testimonials;
DELETE FROM faq;
DELETE FROM form_fields;
DELETE FROM materials;
DELETE FROM additional_services;
DELETE FROM quality_levels;
DELETE FROM volume_discounts;
DELETE FROM site_content;
DELETE FROM site_settings;
DELETE FROM site_stats;
DELETE FROM integrations;
```

### Invalid JSON Error

**Problem**: Export file is not valid JSON

**Solution**: 
1. Validate JSON using online validator (jsonlint.com)
2. Check for:
   - Trailing commas
   - Unescaped quotes
   - Missing brackets
3. Re-export from browser

### Calculator Config Not Imported

**Problem**: Materials/services not showing in calculator

**Solution**: 
- Ensure settings are imported (don't use `--skip-settings`)
- Verify calculator config in export file has this structure:

```json
{
  "settings": [{
    "calculator": {
      "materialPrices": {...},
      "servicePrices": {...},
      "qualityMultipliers": {...},
      "discounts": [...]
    }
  }]
}
```

### Service Features Missing

**Problem**: Services imported but features are missing

**Solution**: 
- Check that services have `features` array in export file
- Features can be either strings or objects with `text` property:

```json
{
  "services": [{
    "name": "FDM печать",
    "features": [
      "Быстрое изготовление",
      "Низкая стоимость"
    ]
  }]
}
```

### Timestamps Not Generated

**Problem**: `created_at` or `updated_at` are NULL

**Solution**: This shouldn't happen - timestamps are auto-generated by MySQL. Check:

```sql
SHOW CREATE TABLE services;
```

Should show:
```sql
`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
```

### Permission Denied

**Problem**: Cannot execute script

**Solution**: 
```bash
chmod +x backend/scripts/import_local_data.php
```

### PHP Memory Limit

**Problem**: Script runs out of memory with large datasets

**Solution**: Increase PHP memory limit:

```bash
php -d memory_limit=512M import_local_data.php --file=export.json
```

## Advanced Usage

### Import from Remote File

```bash
# Download export first
curl -o export.json https://yourdomain.com/backup.json

# Then import
php import_local_data.php --file=export.json
```

### Import Multiple Files

```bash
# Import services from one file, orders from another
php import_local_data.php --file=services_export.json \
  --skip-orders --skip-portfolio --skip-testimonials \
  --skip-faq --skip-settings --skip-content --skip-stats

php import_local_data.php --file=orders_export.json \
  --skip-services --skip-portfolio --skip-testimonials \
  --skip-faq --skip-settings --skip-content --skip-stats
```

### Automated Migration Script

Create a bash script to automate the process:

```bash
#!/bin/bash
# migrate.sh

echo "🚀 Starting migration process..."

# Step 1: Test database connection
echo "📡 Testing database connection..."
php backend/test-connection.php || exit 1

# Step 2: Dry run
echo "🔍 Running dry run..."
php backend/scripts/import_local_data.php \
  --file=export.json \
  --dry-run \
  --verbose

# Step 3: Confirm
read -p "Continue with actual import? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Import cancelled"
    exit 1
fi

# Step 4: Import
echo "📥 Importing data..."
php backend/scripts/import_local_data.php \
  --file=export.json \
  --verbose || exit 1

# Step 5: Verify
echo "✅ Verifying import..."
mysql -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE <<EOF
SELECT 'Services:' as table_name, COUNT(*) as count FROM services
UNION ALL SELECT 'Portfolio:', COUNT(*) FROM portfolio
UNION ALL SELECT 'Testimonials:', COUNT(*) FROM testimonials
UNION ALL SELECT 'FAQ:', COUNT(*) FROM faq
UNION ALL SELECT 'Orders:', COUNT(*) FROM orders;
EOF

echo "✅ Migration complete!"
```

Make it executable:
```bash
chmod +x migrate.sh
./migrate.sh
```

### Backup Before Import

Always backup before importing:

```bash
# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Then import
php import_local_data.php --file=export.json
```

### Restore from Backup

If something goes wrong:

```bash
mysql -u username -p database_name < backup_20231115_143022.sql
```

## Data Mapping Reference

### Services

| localStorage | MySQL Database |
|-------------|----------------|
| `id` | Auto-generated |
| `name` | `name` |
| `slug` | `slug` (auto-generated if missing) |
| `icon` | `icon` |
| `description` | `description` |
| `features[]` | `service_features` table (normalized) |
| `price` | `price` |
| `active` | `active` |
| `featured` | `featured` |
| `order` | `display_order` |
| `createdAt` | `created_at` (auto-generated) |
| `updatedAt` | `updated_at` (auto-generated) |

### Portfolio

| localStorage | MySQL Database |
|-------------|----------------|
| `id` | Auto-generated |
| `title` | `title` |
| `category` | `category` (enum) |
| `description` | `description` |
| `image` or `image_url` | `image_url` |
| `details` | `details` |
| `createdAt` | `created_at` (auto-generated) |
| `updatedAt` | `updated_at` (auto-generated) |

### Testimonials

| localStorage | MySQL Database |
|-------------|----------------|
| `id` | Auto-generated |
| `name` | `name` |
| `position` | `position` |
| `avatar` or `avatar_url` | `avatar_url` |
| `rating` | `rating` |
| `text` | `text` |
| `approved` | `approved` |
| `order` | `display_order` |
| `createdAt` | `created_at` (auto-generated) |
| `updatedAt` | `updated_at` (auto-generated) |

### FAQ

| localStorage | MySQL Database |
|-------------|----------------|
| `id` | Auto-generated |
| `question` | `question` |
| `answer` | `answer` |
| `active` | `active` |
| `order` | `display_order` |
| `createdAt` | `created_at` (auto-generated) |
| `updatedAt` | `updated_at` (auto-generated) |

### Settings

localStorage settings are split across multiple database tables:

**site_settings table:**
- `siteName` → `site_name`
- `siteDescription` → `site_description`
- `contactEmail` → `contact_email`
- `contactPhone` → `contact_phone`
- `address` → `address`
- `workingHours` → `working_hours`
- `timezone` → `timezone`
- `socialLinks` → `social_links` (JSON)
- `theme` → `theme`
- `colorPrimary` → `color_primary`
- `colorSecondary` → `color_secondary`
- `notifications` → `notifications` (JSON)

**materials table:**
- `calculator.materialPrices` → normalized rows

**additional_services table:**
- `calculator.servicePrices` → normalized rows

**quality_levels table:**
- `calculator.qualityMultipliers` → normalized rows

**volume_discounts table:**
- `calculator.discounts` → normalized rows

**form_fields table:**
- `formFields` → normalized rows

**integrations table:**
- `telegram` → config JSON

### Content

| localStorage | MySQL Database |
|-------------|----------------|
| `hero` | `site_content` row with `section_key='hero'` |
| `about` | `site_content` row with `section_key='about'` |
| Each section becomes a separate row with content as JSON |

### Stats

| localStorage | MySQL Database |
|-------------|----------------|
| `totalProjects` | `total_projects` |
| `happyClients` | `happy_clients` |
| `yearsExperience` | `years_experience` |
| `awards` | `awards` |

### Orders

| localStorage | MySQL Database |
|-------------|----------------|
| `id` | Auto-generated |
| `orderNumber` | `order_number` (auto-generated if missing) |
| `type` | `type` |
| `status` | `status` |
| `name` or `client_name` | `client_name` |
| `email` or `client_email` | `client_email` |
| `phone` or `client_phone` | `client_phone` |
| `telegram` | `telegram` |
| `service` | `service` |
| `subject` | `subject` |
| `message` | `message` |
| `amount` | `amount` |
| `calculator_data` | `calculator_data` (JSON) |
| `telegram_sent` | `telegram_sent` |
| `createdAt` | `created_at` (auto-generated) |
| `updatedAt` | `updated_at` (auto-generated) |

## Production Deployment

### Step-by-Step Production Migration

1. **Backup everything**:
   ```bash
   # Backup current site files
   tar -czf site_backup_$(date +%Y%m%d).tar.gz /path/to/site
   
   # Export localStorage data
   # (from browser console on live site)
   db.exportData()
   ```

2. **Set up new database on server**:
   ```bash
   # SSH to server
   ssh user@your-server.com
   
   # Create database
   mysql -u root -p
   CREATE DATABASE ch167436_3dprint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'printuser'@'localhost' IDENTIFIED BY 'secure_password';
   GRANT ALL PRIVILEGES ON ch167436_3dprint.* TO 'printuser'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   
   # Run migrations
   mysql -u printuser -p ch167436_3dprint < backend/database/migrations/20231113_initial.sql
   ```

3. **Configure environment**:
   ```bash
   cd backend
   cp .env.example .env
   nano .env  # Edit with production values
   ```

4. **Upload export file**:
   ```bash
   # From local machine
   scp export.json user@your-server.com:~/
   ```

5. **Run importer on server**:
   ```bash
   # SSH to server
   cd ~/backend/scripts
   
   # Dry run first
   php import_local_data.php --file=~/export.json --dry-run --verbose
   
   # Actual import
   php import_local_data.php --file=~/export.json --verbose
   ```

6. **Verify and test**:
   ```bash
   # Test API
   curl http://yourdomain.com/api/services
   
   # Login to admin panel
   # Navigate through all sections
   ```

7. **Update frontend** to use new API endpoints instead of localStorage

## Need Help?

If you encounter issues not covered in this guide:

1. Check the [Backend README](../backend/README.md)
2. Review [API Documentation](./api.md)
3. Check the script output for specific error messages
4. Enable verbose mode: `--verbose`
5. Test with a small dataset first
6. Run dry-run mode to preview changes

## Appendix: Sample Export File

Here's a minimal sample export file for testing:

```json
{
  "services": [
    {
      "id": "s1",
      "name": "FDM печать",
      "slug": "fdm",
      "icon": "fa-cube",
      "description": "3D печать методом FDM",
      "features": ["Быстро", "Качественно", "Доступно"],
      "price": "от 50₽/г",
      "active": true,
      "featured": false,
      "order": 1
    }
  ],
  "portfolio": [
    {
      "id": "p1",
      "title": "Прототип корпуса",
      "category": "prototype",
      "description": "Корпус для электронного устройства",
      "image": "https://example.com/image.jpg",
      "details": "Детальное описание"
    }
  ],
  "testimonials": [
    {
      "id": "t1",
      "name": "Иван Петров",
      "position": "Инженер",
      "avatar": "https://i.pravatar.cc/150?img=1",
      "rating": 5,
      "text": "Отличная работа!",
      "approved": true,
      "order": 1
    }
  ],
  "faq": [
    {
      "id": "f1",
      "question": "Какие материалы используете?",
      "answer": "PLA, ABS, PETG и другие",
      "active": true,
      "order": 1
    }
  ],
  "settings": [{
    "siteName": "3D Print Pro",
    "siteDescription": "Профессиональная 3D печать",
    "contactEmail": "info@example.com",
    "contactPhone": "+7 999 123-45-67",
    "address": "г. Москва",
    "workingHours": "Пн-Пт: 9:00 - 18:00",
    "timezone": "Europe/Moscow",
    "socialLinks": {
      "vk": "",
      "telegram": "https://t.me/example",
      "whatsapp": "",
      "youtube": ""
    },
    "theme": "light",
    "colorPrimary": "#6366f1",
    "colorSecondary": "#ec4899",
    "calculator": {
      "materialPrices": {
        "pla": { "name": "PLA", "price": 50, "technology": "fdm" }
      },
      "servicePrices": {
        "modeling": { "name": "3D моделирование", "price": 500, "unit": "час" }
      },
      "qualityMultipliers": {
        "normal": { "name": "Нормальное", "multiplier": 1.0, "time": 1.0 }
      },
      "discounts": [
        { "minQuantity": 10, "percent": 10 }
      ]
    }
  }],
  "content": [{
    "hero": {
      "title": "идеи в реальность",
      "subtitle": "Профессиональная 3D печать",
      "features": ["Быстро", "Качественно", "Доступно"]
    }
  }],
  "stats": {
    "totalProjects": 1500,
    "happyClients": 850,
    "yearsExperience": 12,
    "awards": 25
  },
  "orders": []
}
```

Save this as `sample-export.json` and test the importer with it.
