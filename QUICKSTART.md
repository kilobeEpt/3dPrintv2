# Quick Start Guide - 3D Print Pro

Get the database up and running in 5 minutes.

## Prerequisites

- MySQL 8.0+ installed
- Command-line access
- 5 minutes of your time

## Step 1: Install Database (2 minutes)

```bash
# Navigate to project directory
cd /path/to/3dprint-pro

# Create database and schema
mysql -u root -p < backend/database/migrations/20231113_initial.sql

# Load initial data
mysql -u root -p ch167436_3dprint < backend/database/seeds/initial_data.sql
```

**Enter your MySQL root password when prompted.**

## Step 2: Verify Installation (1 minute)

```bash
# Check tables were created
mysql -u root -p ch167436_3dprint -e "SHOW TABLES;"
```

Expected output: 17 tables

```bash
# Check data was loaded
mysql -u root -p ch167436_3dprint -e "SELECT COUNT(*) FROM services;"
```

Expected output: 6 services

## Step 3: Configure Application (2 minutes)

### Change Admin Password

⚠️ **IMPORTANT:** Default password is `admin123` - change immediately!

```sql
-- Connect to database
mysql -u root -p ch167436_3dprint

-- Update admin password (use bcrypt hash)
-- Generate hash using: bcrypt('your_new_password', 10)
UPDATE users 
SET password_hash = '$2a$10$YOUR_NEW_BCRYPT_HASH' 
WHERE login = 'admin';
```

### Configure Telegram Bot

1. Get bot token from [@BotFather](https://t.me/botfather)
2. Update integration:

```sql
UPDATE integrations 
SET config = JSON_SET(config, '$.chatId', 'YOUR_CHAT_ID')
WHERE integration_name = 'telegram';
```

## Done! 🎉

Your database is ready. Next steps:

- Browse documentation: `/docs/db-schema.md`
- Review validation checklist: `/backend/database/VALIDATION_CHECKLIST.md`
- Check ER diagram: `/backend/database/ER-DIAGRAM.md`

## Access Admin Panel

1. Open `admin.html` in browser
2. Login with: `admin` / `your_new_password`
3. Start managing content!

## Common Commands

**Backup database:**
```bash
mysqldump -u root -p ch167436_3dprint > backup_$(date +%Y%m%d).sql
```

**Restore from backup:**
```bash
mysql -u root -p ch167436_3dprint < backup_20231113.sql
```

**Access database:**
```bash
mysql -u root -p ch167436_3dprint
```

## Troubleshooting

**"Database already exists" error?**
```sql
DROP DATABASE IF EXISTS ch167436_3dprint;
```
Then run migration again.

**"Access denied" error?**
```sql
-- Grant privileges
GRANT ALL PRIVILEGES ON ch167436_3dprint.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

**Character encoding issues?**
```sql
SET NAMES utf8mb4;
```

## Need Help?

- Full docs: `/docs/db-schema.md`
- Setup guide: `/backend/database/README.md`
- Validation: `/backend/database/VALIDATION_CHECKLIST.md`
- Migration summary: `/MIGRATION_SUMMARY.md`

---

**Database:** ch167436_3dprint  
**Tables:** 17  
**Initial Records:** ~63  
**Schema Version:** 1.0.0
