# Database Setup Validation Checklist

Use this checklist to verify your database setup is complete and correct.

## ✅ Pre-Installation

- [ ] MySQL 8.0+ installed and running
- [ ] Database user created with proper privileges
- [ ] Character set support verified (utf8mb4)
- [ ] Backup existing data (if applicable)

## ✅ Schema Installation

### Step 1: Run Migration Script

```bash
mysql -u root -p < backend/database/migrations/20231113_initial.sql
```

**Expected Results:**
- [ ] No SQL errors
- [ ] Database `ch167436_3dprint` created
- [ ] 17 tables created successfully

### Step 2: Verify Tables

```sql
USE ch167436_3dprint;
SHOW TABLES;
```

**Expected Output (17 tables):**
- [ ] additional_services
- [ ] audit_logs
- [ ] faq
- [ ] form_fields
- [ ] integrations
- [ ] materials
- [ ] orders
- [ ] portfolio
- [ ] quality_levels
- [ ] service_features
- [ ] services
- [ ] site_content
- [ ] site_settings
- [ ] site_stats
- [ ] testimonials
- [ ] users
- [ ] volume_discounts

### Step 3: Verify Table Structure

```sql
-- Check a few key tables
DESCRIBE users;
DESCRIBE orders;
DESCRIBE services;
DESCRIBE materials;
```

**Expected:**
- [ ] All columns present
- [ ] Correct data types
- [ ] Primary keys set
- [ ] Indexes created

### Step 4: Check Constraints

```sql
-- Check foreign keys
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'ch167436_3dprint'
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

**Expected:**
- [ ] service_features → services (FK exists)
- [ ] audit_logs → users (FK exists)

### Step 5: Verify Character Set

```sql
SELECT 
    TABLE_NAME,
    TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'ch167436_3dprint';
```

**Expected:**
- [ ] All tables use utf8mb4_unicode_ci

## ✅ Seed Data Installation

### Step 1: Load Seed Data

```bash
mysql -u root -p ch167436_3dprint < backend/database/seeds/initial_data.sql
```

**Expected Results:**
- [ ] No SQL errors
- [ ] All INSERT statements executed

### Step 2: Verify Record Counts

```sql
SELECT 'users' AS table_name, COUNT(*) AS count FROM users
UNION ALL SELECT 'services', COUNT(*) FROM services
UNION ALL SELECT 'service_features', COUNT(*) FROM service_features
UNION ALL SELECT 'testimonials', COUNT(*) FROM testimonials
UNION ALL SELECT 'faq', COUNT(*) FROM faq
UNION ALL SELECT 'materials', COUNT(*) FROM materials
UNION ALL SELECT 'additional_services', COUNT(*) FROM additional_services
UNION ALL SELECT 'quality_levels', COUNT(*) FROM quality_levels
UNION ALL SELECT 'volume_discounts', COUNT(*) FROM volume_discounts
UNION ALL SELECT 'form_fields', COUNT(*) FROM form_fields
UNION ALL SELECT 'site_settings', COUNT(*) FROM site_settings
UNION ALL SELECT 'site_content', COUNT(*) FROM site_content
UNION ALL SELECT 'site_stats', COUNT(*) FROM site_stats
UNION ALL SELECT 'integrations', COUNT(*) FROM integrations;
```

**Expected Counts:**
- [ ] users: 1
- [ ] services: 6
- [ ] service_features: 24
- [ ] testimonials: 4
- [ ] faq: 6
- [ ] materials: 10
- [ ] additional_services: 4
- [ ] quality_levels: 4
- [ ] volume_discounts: 3
- [ ] form_fields: 6
- [ ] site_settings: 1
- [ ] site_content: 2
- [ ] site_stats: 1
- [ ] integrations: 1

### Step 3: Verify Data Integrity

**Check Services:**
```sql
SELECT id, name, slug, active FROM services ORDER BY display_order;
```

**Expected:**
- [ ] 6 services present
- [ ] All slugs unique
- [ ] All marked as active

**Check Admin User:**
```sql
SELECT id, login, name, role FROM users WHERE role = 'admin';
```

**Expected:**
- [ ] Username: admin
- [ ] Role: admin
- [ ] Password hash present (not plaintext)

**Check Materials:**
```sql
SELECT material_key, name, price, technology FROM materials ORDER BY display_order;
```

**Expected:**
- [ ] 10 materials present
- [ ] Technologies: fdm, sla, sls
- [ ] Prices > 0

**Check JSON Columns:**
```sql
SELECT section_key, title, JSON_KEYS(content) as content_keys FROM site_content;
```

**Expected:**
- [ ] hero section exists
- [ ] about section exists
- [ ] JSON data valid

## ✅ Functional Tests

### Test 1: Insert Order

```sql
INSERT INTO orders (
    order_number, type, status,
    client_name, client_email, client_phone,
    service, message, amount
) VALUES (
    'TEST-001', 'contact', 'new',
    'Test User', 'test@example.com', '+7 999 123 4567',
    'FDM печать', 'Test order message', 500.00
);

SELECT * FROM orders WHERE order_number = 'TEST-001';
```

**Expected:**
- [ ] Record inserted successfully
- [ ] Timestamps auto-populated
- [ ] Default values applied

### Test 2: Foreign Key Constraint

```sql
-- This should fail (referential integrity)
INSERT INTO service_features (service_id, feature_text, display_order)
VALUES (99999, 'Invalid feature', 1);
```

**Expected:**
- [ ] Insert fails with foreign key error
- [ ] Error message mentions service_id constraint

### Test 3: Unique Constraint

```sql
-- This should fail (duplicate slug)
INSERT INTO services (name, slug, icon, description, price, display_order)
VALUES ('Test Service', 'fdm', 'fa-test', 'Test', 'Test', 1);
```

**Expected:**
- [ ] Insert fails with duplicate key error
- [ ] Error message mentions slug uniqueness

### Test 4: JSON Column

```sql
UPDATE site_settings 
SET notifications = JSON_OBJECT('newOrders', TRUE, 'test', FALSE)
WHERE id = 1;

SELECT JSON_EXTRACT(notifications, '$.newOrders') FROM site_settings WHERE id = 1;
```

**Expected:**
- [ ] JSON update succeeds
- [ ] JSON_EXTRACT returns correct value

### Test 5: Full-Text Search

```sql
-- Add a test order with searchable content
INSERT INTO orders (
    order_number, type, status,
    client_name, client_email, client_phone,
    message, amount
) VALUES (
    'TEST-002', 'contact', 'new',
    'John Smith', 'john@example.com', '+7 999 111 2222',
    'Looking for custom 3D printing solutions', 100.00
);

-- Search
SELECT order_number, client_name, message
FROM orders
WHERE MATCH(client_name, client_email, message) 
AGAINST('custom printing' IN NATURAL LANGUAGE MODE);
```

**Expected:**
- [ ] Search returns TEST-002 order
- [ ] Full-text index working

### Cleanup Test Data

```sql
DELETE FROM orders WHERE order_number LIKE 'TEST-%';
```

## ✅ Performance Checks

### Check Indexes

```sql
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'ch167436_3dprint'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
```

**Expected:**
- [ ] Primary keys on all tables
- [ ] Unique indexes on slug, email, login, etc.
- [ ] Performance indexes on active, status, created_at, etc.

### Check Table Sizes

```sql
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'ch167436_3dprint'
ORDER BY (data_length + index_length) DESC;
```

**Expected:**
- [ ] All tables present
- [ ] Reasonable initial sizes

## ✅ Security Checks

### Step 1: Password Security

```sql
SELECT login, LENGTH(password_hash) as hash_length 
FROM users;
```

**Expected:**
- [ ] password_hash length = 60 (bcrypt format)
- [ ] No plaintext passwords

### Step 2: Admin Credentials

**Action Required:**
- [ ] Change default admin password immediately
- [ ] Use strong password (12+ chars, mixed case, numbers, symbols)

```sql
-- After changing password via application
SELECT login, last_login_at FROM users WHERE login = 'admin';
```

### Step 3: Database User Privileges

```sql
-- Run as root
SHOW GRANTS FOR 'your_app_user'@'localhost';
```

**Expected:**
- [ ] App user has necessary privileges on ch167436_3dprint
- [ ] App user does NOT have global privileges
- [ ] Follows principle of least privilege

### Step 4: Sensitive Data

- [ ] Telegram bot token in environment variables (not hardcoded)
- [ ] Database credentials in secure config file (not in repo)
- [ ] SSL/TLS enabled for database connections (production)

## ✅ Backup Verification

### Create Initial Backup

```bash
mysqldump -u root -p --single-transaction --routines --triggers \
  ch167436_3dprint > backup_initial_$(date +%Y%m%d).sql
```

**Verify:**
- [ ] Backup file created
- [ ] File size reasonable (not 0 bytes)
- [ ] Contains CREATE TABLE statements

### Test Restore (Optional)

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE test_restore;"

# Restore
mysql -u root -p test_restore < backup_initial_*.sql

# Verify
mysql -u root -p test_restore -e "SHOW TABLES;"

# Cleanup
mysql -u root -p -e "DROP DATABASE test_restore;"
```

**Expected:**
- [ ] Restore succeeds
- [ ] All tables present in restored database

## ✅ Documentation Review

- [ ] Read `/docs/db-schema.md` - Comprehensive schema documentation
- [ ] Read `/backend/database/README.md` - Quick setup guide
- [ ] Review `/backend/database/ER-DIAGRAM.md` - Visual schema overview
- [ ] Check `/docs/data-model.md` - Original data model specification

## ✅ Integration Tests

### Test 1: Application Connection

**Create test connection script (Node.js example):**
```javascript
const mysql = require('mysql2/promise');

async function testConnection() {
  const connection = await mysql.createConnection({
    host: 'localhost',
    user: 'your_user',
    password: 'your_password',
    database: 'ch167436_3dprint',
    charset: 'utf8mb4'
  });
  
  const [rows] = await connection.execute('SELECT * FROM site_settings LIMIT 1');
  console.log('Connection successful:', rows);
  
  await connection.end();
}

testConnection();
```

**Expected:**
- [ ] Connection succeeds
- [ ] Settings data returned
- [ ] No encoding issues

### Test 2: API Endpoint (if backend exists)

```bash
# Test health endpoint
curl http://localhost:3000/api/health

# Test services endpoint
curl http://localhost:3000/api/services
```

**Expected:**
- [ ] API responds
- [ ] Data matches database content
- [ ] JSON properly formatted

## ✅ Post-Installation Tasks

- [ ] **Configure Telegram integration**
  ```sql
  UPDATE integrations 
  SET config = JSON_SET(config, '$.chatId', 'YOUR_CHAT_ID')
  WHERE integration_name = 'telegram';
  ```

- [ ] **Customize site settings**
  ```sql
  UPDATE site_settings SET
    contact_email = 'your-email@domain.com',
    contact_phone = '+7 XXX XXX XXXX'
  WHERE id = 1;
  ```

- [ ] **Add your portfolio items**

- [ ] **Review and adjust calculator pricing**

- [ ] **Set up automated backups** (cron job)

- [ ] **Configure monitoring** (database size, query performance)

- [ ] **Set up application logs**

- [ ] **Test order submission workflow end-to-end**

- [ ] **Test admin panel CRUD operations**

## Summary

After completing this checklist:

- ✅ Database schema created correctly
- ✅ Seed data loaded successfully
- ✅ All constraints and indexes working
- ✅ Security measures in place
- ✅ Backups configured
- ✅ Application connected
- ✅ Ready for production use

## Troubleshooting

If any checks fail, refer to:
- `/docs/db-schema.md` - Troubleshooting section
- MySQL error log: Usually `/var/log/mysql/error.log`
- Application error logs

Common issues:
- Character encoding errors → Check charset settings
- Foreign key errors → Check referential integrity
- Permission errors → Review user privileges
- Connection errors → Verify host, port, credentials

## Support

For issues or questions:
1. Check `/docs/db-schema.md` documentation
2. Review MySQL error logs
3. Verify all steps completed in order
4. Check MySQL version compatibility (8.0+ required)

---

**Checklist Version:** 1.0.0  
**Last Updated:** 2023-11-13  
**Database Version:** 1.0.0
