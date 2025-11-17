# Database Setup Guide

Quick reference for setting up the MySQL database for 3D Print Pro.

## Quick Start

### 1. Create Database and Schema

```bash
mysql -u root -p < migrations/20231113_initial.sql
```

### 2. Load Initial Data

```bash
mysql -u root -p ch167436_3dprint < seeds/initial_data.sql
```

### 3. Verify Installation

```bash
mysql -u root -p ch167436_3dprint -e "SHOW TABLES;"
mysql -u root -p ch167436_3dprint -e "SELECT COUNT(*) FROM services;"
```

## Directory Structure

```
backend/database/
├── migrations/
│   └── 20231113_initial.sql    # Initial schema creation
├── seeds/
│   └── initial_data.sql        # Baseline content and configuration
└── README.md                   # This file
```

## Database Information

- **Database Name:** `ch167436_3dprint`
- **Character Set:** `utf8mb4`
- **Collation:** `utf8mb4_unicode_ci`
- **MySQL Version:** 8.0+

## Default Credentials

**Admin User:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT:** Change the admin password immediately after installation!

## What's Included

### Tables Created (17 total)

**Core Tables:**
- `users` - Admin authentication
- `orders` - Customer orders and submissions
- `services` - Service catalog
- `service_features` - Service feature lists
- `portfolio` - Project showcase
- `testimonials` - Customer reviews
- `faq` - Frequently asked questions

**Calculator Configuration:**
- `materials` - 3D printing materials and pricing
- `additional_services` - Extra services (modeling, painting, etc.)
- `quality_levels` - Quality settings with multipliers
- `volume_discounts` - Quantity-based discounts

**Site Configuration:**
- `site_settings` - Global site settings (singleton)
- `site_content` - Editable content sections
- `site_stats` - Site statistics (singleton)
- `integrations` - External service configs (Telegram, etc.)
- `form_fields` - Dynamic form configuration
- `audit_logs` - Change tracking (optional)

### Initial Data Seeded

- 1 admin user
- 6 services with features
- 4 customer testimonials
- 6 FAQ items
- 10 3D printing materials
- 4 additional services
- 4 quality levels
- 3 volume discount tiers
- 6 contact form fields
- Site settings, content, and stats

## Common Commands

### Backup

```bash
# Full backup
mysqldump -u root -p ch167436_3dprint > backup_$(date +%Y%m%d).sql

# Backup with compression
mysqldump -u root -p ch167436_3dprint | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Restore

```bash
# Restore from backup
mysql -u root -p ch167436_3dprint < backup_20231113.sql

# Restore from compressed backup
gunzip < backup_20231113.sql.gz | mysql -u root -p ch167436_3dprint
```

### Access Database

```bash
mysql -u root -p ch167436_3dprint
```

### Show Tables

```sql
SHOW TABLES;
```

### Check Record Counts

```sql
SELECT 
    'services' AS table_name, COUNT(*) AS count FROM services
UNION ALL SELECT 'orders', COUNT(*) FROM orders
UNION ALL SELECT 'portfolio', COUNT(*) FROM portfolio
UNION ALL SELECT 'testimonials', COUNT(*) FROM testimonials
UNION ALL SELECT 'faq', COUNT(*) FROM faq
UNION ALL SELECT 'materials', COUNT(*) FROM materials;
```

## Troubleshooting

### Error: Database already exists

If you need to recreate the database:

```sql
DROP DATABASE IF EXISTS ch167436_3dprint;
```

Then run the migration script again.

### Error: Access denied

Make sure your MySQL user has proper privileges:

```sql
GRANT ALL PRIVILEGES ON ch167436_3dprint.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

### Character encoding issues

Ensure your client connection uses utf8mb4:

```sql
SET NAMES utf8mb4;
```

## Documentation

For detailed schema documentation, including:
- Complete ER diagrams
- Field descriptions
- Index strategies
- Design decisions
- Migration instructions for different environments

See: **[/docs/db-schema.md](../../docs/db-schema.md)**

## Next Steps

After setting up the database:

1. **Change default admin password**
2. **Configure Telegram integration** (update `integrations` table)
3. **Customize site settings** (update `site_settings` table)
4. **Add your portfolio items** (populate `portfolio` table)
5. **Configure calculator pricing** as needed
6. **Set up automated backups**

## Support

For issues or questions about the database schema, refer to:
- `/docs/db-schema.md` - Comprehensive documentation
- `/docs/data-model.md` - Original data model specification

## Version

Schema Version: 1.0.0
Last Updated: 2023-11-13
