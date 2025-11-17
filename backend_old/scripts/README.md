# Data Import Scripts

This directory contains scripts for importing data from localStorage exports into the MySQL database.

## Files

- **`import_local_data.php`** - Main CLI importer script
- **`validate-export.php`** - Export file validation tool
- **`sample-export.json`** - Sample export file for testing
- **`README.md`** - This file
- **`QUICKSTART.md`** - Quick start guide

## Quick Start

### 1. Test with Sample Data (Dry Run)

```bash
cd backend/scripts
php import_local_data.php --file=sample-export.json --dry-run --verbose
```

This will show you what would be imported without making any changes.

### 2. Import Sample Data

```bash
php import_local_data.php --file=sample-export.json --verbose
```

### 3. Validate Export File

Before importing, validate your export file:

```bash
php validate-export.php your-export.json
```

This checks:
- ✅ Valid JSON structure
- ✅ Required fields present
- ✅ Data types correct
- ✅ Enum values valid
- ✅ Record counts

### 4. Import Your Own Data

First, export your localStorage data from the browser:

```javascript
// In browser console on your current site
db.exportData();
```

Then validate and import it:

```bash
php validate-export.php your-export.json
php import_local_data.php --file=/path/to/your-export.json --dry-run
php import_local_data.php --file=/path/to/your-export.json
```

## Validation Tool

### Quick Validation

Before importing, always validate your export file:

```bash
php validate-export.php your-export.json
```

### What It Checks

- ✅ **JSON syntax** - Valid JSON structure
- ✅ **Required fields** - All mandatory fields present
- ✅ **Data types** - Correct types (string, number, array, object)
- ✅ **Enum values** - Valid enum values (categories, statuses, types)
- ✅ **Field constraints** - Ratings 1-5, positive numbers, etc.
- ✅ **Record counts** - Shows how many records will be imported
- ✅ **Structure validation** - Proper nesting and relationships

### Example Output

```
🔍 Loading export file: export.json

🔍 Validating export file structure...

📦 Validating services...
  ✅ 6 services validated
🖼️  Validating portfolio...
  ✅ 2 portfolio items validated
💬 Validating testimonials...
  ✅ 2 testimonials validated
❓ Validating FAQ...
  ✅ 3 FAQ items validated
⚙️  Validating settings...
  ✅ Settings validated
📄 Validating content...
  ✅ 2 content sections validated
📊 Validating stats...
  ✅ Stats validated
📝 Validating orders...
  ✅ 1 orders validated

============================================================
📊 VALIDATION RESULTS
============================================================

Records found:
  Services:                    6
  Service features:           16
  Portfolio:                   2
  Testimonials:                2
  FAQ:                         3
  Materials:                  10
  Additional services:         4
  Quality levels:              4
  Volume discounts:            3
  Form fields:                 6
  Settings:                    1
  Content:                     2
  Stats:                       1
  Orders:                      1

⚠️  WARNINGS (1):
  • Order #0: Missing 'orderNumber' field (will be auto-generated)

============================================================
✅ Validation passed! File is ready for import.
============================================================
```

If validation fails, you'll see detailed error messages:

```
❌ ERRORS (3):
  • Service #2: Missing required field 'name'
  • Portfolio #0: Invalid category 'invalid'. Must be: prototype, functional, art, industrial
  • Testimonial #1: Rating must be between 1 and 5

============================================================
❌ Validation failed. Please fix errors before importing.
============================================================
```

## Import Tool

### Usage

```bash
php import_local_data.php --file=<path> [options]
```

### Required Options

- `--file=<path>` - Path to JSON export file

### Optional Flags

- `--dry-run` - Preview import without making changes
- `--verbose` - Show detailed progress
- `--force` - Overwrite existing singleton data (settings, stats, content)
- `--skip-orders` - Skip importing orders
- `--skip-portfolio` - Skip importing portfolio
- `--skip-services` - Skip importing services
- `--skip-testimonials` - Skip importing testimonials
- `--skip-faq` - Skip importing FAQ
- `--skip-settings` - Skip importing settings
- `--skip-content` - Skip importing content
- `--skip-stats` - Skip importing stats
- `--help, -h` - Show help message

## Examples

### Preview Import

```bash
php import_local_data.php --file=export.json --dry-run --verbose
```

### Import Only Services and Portfolio

```bash
php import_local_data.php --file=export.json \
  --skip-orders \
  --skip-testimonials \
  --skip-faq \
  --skip-settings \
  --skip-content \
  --skip-stats
```

### Force Overwrite Existing Settings

```bash
php import_local_data.php --file=export.json --force
```

### Import Without Orders

```bash
php import_local_data.php --file=export.json --skip-orders
```

## Features

### Automatic Handling

The importer automatically handles:

- ✅ **ID Regeneration** - localStorage IDs are replaced with MySQL auto-increment IDs
- ✅ **Slug Generation** - Missing slugs are auto-generated from names
- ✅ **Timestamp Population** - `created_at` and `updated_at` are auto-generated
- ✅ **Order Number Generation** - Missing order numbers are auto-generated
- ✅ **Service Features Normalization** - Service features are extracted to separate table
- ✅ **Calculator Config Mapping** - Calculator settings are split into multiple tables
- ✅ **Form Fields Mapping** - Form fields are normalized into `form_fields` table
- ✅ **JSON Structure Preservation** - Complex nested data stored as JSON columns
- ✅ **Transaction Safety** - All imports run in a transaction (rollback on error)
- ✅ **Error Reporting** - Detailed error messages for each failed record

### Data Mapping

| localStorage Table | MySQL Tables |
|-------------------|--------------|
| `services` | `services` + `service_features` |
| `portfolio` | `portfolio` |
| `testimonials` | `testimonials` |
| `faq` | `faq` |
| `settings` | `site_settings` + `materials` + `additional_services` + `quality_levels` + `volume_discounts` + `form_fields` + `integrations` |
| `content` | `site_content` (one row per section) |
| `stats` | `site_stats` |
| `orders` | `orders` |

### Field Mapping

See [docs/migration.md](../../docs/migration.md#data-mapping-reference) for complete field mapping reference.

## Output

The script provides color-coded output:

- 🚀 **Blue** - Info messages
- ✅ **Green** - Success messages
- ⚠️ **Yellow** - Warnings
- ❌ **Red** - Errors

### Dry Run Output Example

```
🚀 Starting data import...
🔍 DRY RUN MODE - No changes will be made

📦 Importing services...
  [DRY RUN] Would insert service: FDM печать
    [DRY RUN] Would insert feature: Быстрое изготовление
    [DRY RUN] Would insert feature: Низкая стоимость
  ✅ Imported 6 services

🖼️  Importing portfolio...
  [DRY RUN] Would insert portfolio: Прототип корпуса
  ✅ Imported 8 portfolio items

💬 Importing testimonials...
  ✅ Imported 4 testimonials

❓ Importing FAQ...
  ✅ Imported 6 FAQ items

⚙️  Importing settings...
    [DRY RUN] Would insert material: PLA
    [DRY RUN] Would insert service: 3D моделирование
  ✅ Settings imported

📄 Importing content...
  [DRY RUN] Would insert/update content section: hero
  ✅ Imported 2 content sections

📊 Importing stats...
  [DRY RUN] Would insert/update stats
  ✅ Stats imported

📝 Importing orders...
  ✅ Imported 0 orders

============================================================
📊 IMPORT SUMMARY
============================================================
  Services:                    6
  Service features:           24
  Portfolio:                   8
  Testimonials:                4
  FAQ:                         6
  Materials:                  10
  Additional services:         4
  Quality levels:              4
  Volume discounts:            3
  Form fields:                 6
  Settings:                    1
  Content:                     2
  Stats:                       1
------------------------------------------------------------
  Total records:              79
============================================================

💡 This was a dry run. No data was actually imported.
   Run without --dry-run to perform the actual import.
```

### Actual Import Output Example

```
🚀 Starting data import...

📦 Importing services...
  ✅ Imported 6 services

🖼️  Importing portfolio...
  ✅ Imported 8 portfolio items

💬 Importing testimonials...
  ✅ Imported 4 testimonials

❓ Importing FAQ...
  ✅ Imported 6 FAQ items

⚙️  Importing settings...
  ✅ Settings imported

📄 Importing content...
  ✅ Imported 2 content sections

📊 Importing stats...
  ✅ Stats imported

📝 Importing orders...
  ✅ Imported 5 orders

✅ Transaction committed successfully

============================================================
📊 IMPORT SUMMARY
============================================================
  Services:                    6
  Service features:           24
  Portfolio:                   8
  Testimonials:                4
  FAQ:                         6
  Materials:                  10
  Additional services:         4
  Quality levels:              4
  Volume discounts:            3
  Form fields:                 6
  Settings:                    1
  Content:                     2
  Stats:                       1
  Orders:                      5
------------------------------------------------------------
  Total records:              84
============================================================
```

## Error Handling

### Common Errors

#### File Not Found
```
❌ Error: File not found: export.json
```
**Solution**: Check file path, use absolute path if needed

#### Invalid JSON
```
❌ Error: Invalid JSON file
JSON Error: Syntax error
```
**Solution**: Validate JSON using jsonlint.com, check for trailing commas

#### Database Connection Failed
```
❌ Fatal error: Database connection failed: Access denied for user
```
**Solution**: Check `.env` file database credentials

#### Duplicate Entry
```
❌ Error: Service 'FDM печать': Duplicate entry 'fdm' for key 'slug'
```
**Solution**: Use `--force` flag or clear existing data first

#### Missing Required Fields
```
❌ Error: Service 'Test': Column 'name' cannot be null
```
**Solution**: Check export file has all required fields

### Error Recovery

If import fails midway, the transaction is rolled back automatically:

```
❌ Import failed: Duplicate entry 'test' for key 'slug'

⚠️  ERRORS (1):
  • Service 'Test Service': Duplicate entry 'test' for key 'slug'
```

No partial data is saved. Fix the error and re-run.

## Requirements

- PHP 7.4+
- PDO MySQL extension
- Composer dependencies installed
- Database configured in `.env`
- Database migrations executed

## Troubleshooting

### Script Won't Execute

```bash
# Make executable
chmod +x import_local_data.php

# Run with PHP explicitly
php import_local_data.php --file=export.json
```

### Permission Denied

```bash
# Check file permissions
ls -la import_local_data.php

# Should show: -rwxr-xr-x
```

### PHP Not Found

```bash
# Find PHP path
which php

# Use full path
/usr/bin/php import_local_data.php --file=export.json
```

### Memory Limit Exceeded

```bash
# Increase memory limit
php -d memory_limit=512M import_local_data.php --file=export.json
```

### Maximum Execution Time

```bash
# Increase max execution time
php -d max_execution_time=300 import_local_data.php --file=export.json
```

## Production Usage

### Best Practices

1. **Always backup first**:
   ```bash
   mysqldump -u username -p database > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Test with dry run**:
   ```bash
   php import_local_data.php --file=export.json --dry-run --verbose
   ```

3. **Review output carefully** before actual import

4. **Import in stages** if dealing with large datasets:
   ```bash
   # First import base content
   php import_local_data.php --file=export.json --skip-orders
   
   # Then import orders separately
   php import_local_data.php --file=export.json \
     --skip-services --skip-portfolio --skip-testimonials \
     --skip-faq --skip-settings --skip-content --skip-stats
   ```

5. **Verify after import**:
   ```bash
   mysql -u username -p database <<EOF
   SELECT 'Services' as table_name, COUNT(*) FROM services
   UNION SELECT 'Portfolio', COUNT(*) FROM portfolio
   UNION SELECT 'Orders', COUNT(*) FROM orders;
   EOF
   ```

### Automation Script

Create a bash script for automated imports:

```bash
#!/bin/bash
# auto-import.sh

set -e  # Exit on error

EXPORT_FILE=$1
DRY_RUN=${2:-false}

if [ -z "$EXPORT_FILE" ]; then
    echo "Usage: ./auto-import.sh <export-file> [dry-run]"
    exit 1
fi

if [ ! -f "$EXPORT_FILE" ]; then
    echo "Error: File not found: $EXPORT_FILE"
    exit 1
fi

echo "🔍 Validating JSON..."
if ! python3 -m json.tool "$EXPORT_FILE" > /dev/null 2>&1; then
    echo "❌ Invalid JSON file"
    exit 1
fi

echo "📡 Testing database connection..."
if ! php ../../test-connection.php > /dev/null 2>&1; then
    echo "❌ Database connection failed"
    exit 1
fi

if [ "$DRY_RUN" = "true" ]; then
    echo "🔍 Running dry run..."
    php import_local_data.php --file="$EXPORT_FILE" --dry-run --verbose
else
    echo "💾 Creating backup..."
    mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > \
        "backup_$(date +%Y%m%d_%H%M%S).sql"
    
    echo "📥 Importing data..."
    php import_local_data.php --file="$EXPORT_FILE" --verbose
    
    echo "✅ Import complete!"
fi
```

Usage:
```bash
chmod +x auto-import.sh
./auto-import.sh export.json true    # Dry run
./auto-import.sh export.json false   # Actual import
```

## See Also

- [Migration Guide](../../docs/migration.md) - Complete migration documentation
- [API Documentation](../../docs/api.md) - API endpoint reference
- [Backend README](../README.md) - Backend setup and configuration

## Support

For issues or questions:

1. Check the [Migration Guide](../../docs/migration.md) troubleshooting section
2. Review error messages in output
3. Check database logs
4. Verify `.env` configuration
5. Test with sample export file first
