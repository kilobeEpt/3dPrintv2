# Quick Deployment Guide - 500 Error Fixes

## What Was Fixed

Fixed 6 endpoints that were returning 500 errors due to database schema mismatches:
1. ✅ **services.php** - Wrong column names (`feature` → `feature_text`, removed `active` check)
2. ✅ **portfolio.php** - Removed non-existent columns (`active`, `display_order`)
3. ✅ **testimonials.php** - Fixed column mapping (`author`↔`name`, `content`↔`text`, `active`↔`approved`)
4. ✅ **content.php** - Updated to use correct schema (`section_key`, JSON `content`)
5. ✅ **settings-public.php** - Rewrote to return safe defaults
6. ✅ **orders.php** - Fixed column names (`name`→`client_name`, etc.)

## Files Changed

```
backend/
├── api/
│   ├── services.php          ✏️ MODIFIED
│   ├── portfolio.php         ✏️ MODIFIED
│   ├── testimonials.php      ✏️ MODIFIED
│   ├── content.php           ✏️ MODIFIED
│   ├── settings-public.php   ✏️ MODIFIED
│   └── orders.php            ✏️ MODIFIED
├── database/migrations/
│   └── 20241117_fix_schema.sql  ⭐ NEW (optional)
├── .env                      ⭐ NEW (configure for your env)
├── FIXES_SUMMARY.md          📝 NEW (documentation)
└── DEPLOYMENT_FIX.md         📝 NEW (this file)
```

## Deployment Steps

### Option A: Quick Deploy (Recommended)
Upload only the fixed endpoint files:

```bash
# Upload these 6 files to your server
backend/api/services.php
backend/api/portfolio.php
backend/api/testimonials.php
backend/api/content.php
backend/api/settings-public.php
backend/api/orders.php
```

**That's it!** The fixes work with your existing database schema.

### Option B: Full Deploy (With Optional Enhancements)

1. **Upload all files**
   ```bash
   # Upload modified endpoint files (as above)
   # Plus configuration
   backend/.env  # Configure for your environment!
   ```

2. **Configure .env** (Important!)
   ```env
   DB_HOST=localhost
   DB_DATABASE=ch167436_3dprint
   DB_USERNAME=ch167436
   DB_PASSWORD=852789456
   
   JWT_SECRET=<generate 64+ char random string>
   APP_ENV=production
   APP_DEBUG=false
   
   CORS_ORIGIN=https://3dprint-omsk.ru
   ```

3. **Optional: Add settings JSON columns**
   ```bash
   mysql -u ch167436 -p852789456 ch167436_3dprint < backend/database/migrations/20241117_fix_schema.sql
   ```
   This adds `calculator_config`, `form_config`, `telegram_config`, `general_config` columns to `site_settings` table.

4. **Test endpoints**
   ```bash
   cd backend
   ./test-all.php https://3dprint-omsk.ru/backend/public
   ```
   Expected result: ✅ ALL TESTS PASSED - SYSTEM READY!

## Verification Checklist

Test each endpoint manually or with test script:

- [ ] `GET /api/services` returns 200 with services array
- [ ] `GET /api/portfolio` returns 200 with portfolio array
- [ ] `GET /api/testimonials` returns 200 with testimonials array
- [ ] `GET /api/content` returns 200 with content object
- [ ] `GET /api/settings/public` returns 200 with settings object
- [ ] `POST /api/orders` with valid data returns 200

## Testing Commands

### Using curl:
```bash
# Test services
curl https://3dprint-omsk.ru/backend/public/api/services

# Test portfolio
curl https://3dprint-omsk.ru/backend/public/api/portfolio

# Test testimonials
curl https://3dprint-omsk.ru/backend/public/api/testimonials

# Test content
curl https://3dprint-omsk.ru/backend/public/api/content

# Test settings
curl https://3dprint-omsk.ru/backend/public/api/settings/public

# Test orders
curl -X POST https://3dprint-omsk.ru/backend/public/api/orders \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","phone":"+79991234567","email":"test@test.com","message":"Test"}'
```

### Using test script:
```bash
cd backend
chmod +x test-all.php
./test-all.php https://3dprint-omsk.ru/backend/public
```

## Expected Results

### Before Fix:
```
❌ GET /api/services - 500 Internal Server Error
❌ GET /api/portfolio - 500 Internal Server Error
❌ GET /api/testimonials - 500 Internal Server Error
❌ GET /api/content - 500 Internal Server Error
❌ GET /api/settings/public - 500 Internal Server Error
❌ POST /api/orders - 500 Internal Server Error
```

### After Fix:
```
✅ GET /api/services - 200 OK
✅ GET /api/portfolio - 200 OK
✅ GET /api/testimonials - 200 OK
✅ GET /api/content - 200 OK
✅ GET /api/settings/public - 200 OK
✅ POST /api/orders - 200 OK
```

## Rollback Plan

If issues occur, restore original files from backup:
```bash
# You should have backups of:
backend/api/services.php.bak
backend/api/portfolio.php.bak
backend/api/testimonials.php.bak
backend/api/content.php.bak
backend/api/settings-public.php.bak
backend/api/orders.php.bak
```

## Troubleshooting

### Still getting 500 errors?

1. **Check .env file exists**
   ```bash
   ls -la backend/.env
   ```

2. **Check database connection**
   - Verify credentials in .env
   - Test connection: `mysql -u username -p -e "USE database_name; SELECT 1;"`

3. **Check PHP error logs**
   ```bash
   tail -f /var/log/php_errors.log
   # or
   tail -f /var/log/apache2/error.log
   ```

4. **Enable debug mode temporarily**
   In backend/.env:
   ```env
   APP_DEBUG=true
   ```
   This will show detailed error messages in API responses.

### Empty responses?

If endpoints return 200 but empty data:
- Check if database tables have data
- Run: `SELECT * FROM services;` etc.
- If tables empty, add sample data or import from backup

### Database errors?

If you see "Table doesn't exist" or "Column doesn't exist":
- Verify migration was applied: `backend/database/migrations/20231113_initial.sql`
- Check table structure matches schema
- Run: `SHOW TABLES;` and `DESCRIBE table_name;`

## Support

For detailed technical information, see:
- `FIXES_SUMMARY.md` - Complete technical details of all changes
- `README_NEW.md` - Overall backend architecture
- `test-all.php` - Comprehensive test suite

## Success Metrics

✅ All 6 endpoints return 200 status
✅ Test script shows 20/20 tests passed
✅ Frontend can fetch data from all endpoints
✅ Order form works (POST /api/orders)
✅ No 500 errors in server logs
