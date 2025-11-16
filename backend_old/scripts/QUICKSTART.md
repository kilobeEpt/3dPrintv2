# Quick Start - Data Import

Get up and running with data import in 5 minutes.

## Prerequisites Check

```bash
# 1. Check PHP version (7.4+ required)
php -v

# 2. Check database connection
cd backend
php test-connection.php

# 3. Verify migrations are run
mysql -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE -e "SHOW TABLES;"
```

## Export Data from Browser

Open your current site in browser, then:

```javascript
// Press F12, go to Console tab, paste this:
db.exportData();
```

A file like `3dprintpro_backup_2023-11-15.json` will download.

## Import Data

```bash
cd backend/scripts

# 1. Test with sample data first (dry run)
php import_local_data.php --file=sample-export.json --dry-run

# 2. Import sample data
php import_local_data.php --file=sample-export.json

# 3. Import your real data (dry run first!)
php import_local_data.php --file=path/to/your-backup.json --dry-run --verbose

# 4. Actual import
php import_local_data.php --file=path/to/your-backup.json --verbose
```

## Verify Import

```bash
# Check record counts
mysql -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE <<EOF
SELECT 'Services' as 'Table', COUNT(*) as 'Records' FROM services
UNION ALL SELECT 'Portfolio', COUNT(*) FROM portfolio
UNION ALL SELECT 'Testimonials', COUNT(*) FROM testimonials
UNION ALL SELECT 'FAQ', COUNT(*) FROM faq
UNION ALL SELECT 'Orders', COUNT(*) FROM orders;
EOF
```

Or use the API:

```bash
# Test public endpoints
curl http://localhost:8080/api/services
curl http://localhost:8080/api/portfolio
curl http://localhost:8080/api/testimonials
```

## Common Commands

```bash
# Preview without changes
--dry-run

# Show detailed progress
--verbose

# Skip importing orders
--skip-orders

# Overwrite existing settings
--force

# Get help
--help
```

## Full Example

```bash
# Complete import workflow
cd backend/scripts

# Step 1: Backup current database
mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > backup_$(date +%Y%m%d).sql

# Step 2: Dry run
php import_local_data.php --file=export.json --dry-run --verbose

# Step 3: Review output, then import
php import_local_data.php --file=export.json --verbose

# Step 4: Verify
curl http://localhost:8080/api/services | python3 -m json.tool
```

## Troubleshooting

| Problem | Solution |
|---------|----------|
| `php: command not found` | Install PHP 7.4+ or use full path: `/usr/bin/php` |
| `File not found` | Use absolute path: `--file=/full/path/to/export.json` |
| `Invalid JSON` | Validate at jsonlint.com |
| `Connection refused` | Check `.env` database credentials |
| `Duplicate entry` | Use `--force` or clear tables first |
| `Table doesn't exist` | Run migrations: `mysql < database/migrations/20231113_initial.sql` |

## Need More Help?

- Full guide: [docs/migration.md](../../docs/migration.md)
- Script README: [scripts/README.md](./README.md)
- Backend docs: [backend/README.md](../README.md)

## Quick Links

```bash
# Show help
php import_local_data.php --help

# Test database
php ../test-connection.php

# View sample export structure
cat sample-export.json | python3 -m json.tool | less
```
