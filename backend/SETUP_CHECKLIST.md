# Backend Setup Verification Checklist

Use this checklist to verify your backend setup is complete and working correctly.

## Prerequisites ✓

### Required Software

- [ ] **PHP 7.4+** installed
  ```bash
  php -v
  # Should show: PHP 7.4.x or higher
  ```

- [ ] **PDO MySQL extension** available
  ```bash
  php -m | grep pdo_mysql
  # Should show: pdo_mysql
  ```

- [ ] **Composer** installed
  ```bash
  composer --version
  # Should show: Composer version 2.x
  ```

- [ ] **MySQL 8.0+** running
  ```bash
  mysql --version
  # Should show: MySQL 8.0.x or higher
  
  sudo systemctl status mysql
  # Should show: active (running)
  ```

## Database Setup ✓

- [ ] **Database created**
  ```bash
  mysql -u root -p -e "SHOW DATABASES LIKE 'ch167436_3dprint';"
  # Should return: ch167436_3dprint
  ```

- [ ] **Migrations run**
  ```bash
  mysql -u root -p ch167436_3dprint -e "SHOW TABLES;"
  # Should show 17 tables
  ```

- [ ] **Seed data loaded**
  ```bash
  mysql -u root -p ch167436_3dprint -e "SELECT COUNT(*) FROM users;"
  # Should return at least 1 user
  ```

- [ ] **Database user created** (for production)
  ```sql
  -- Run in MySQL:
  CREATE USER 'api_user'@'localhost' IDENTIFIED BY 'strong_password';
  GRANT SELECT, INSERT, UPDATE, DELETE ON ch167436_3dprint.* TO 'api_user'@'localhost';
  FLUSH PRIVILEGES;
  ```

## Backend Installation ✓

- [ ] **Dependencies installed**
  ```bash
  cd backend
  composer install
  # Should complete without errors
  ```

- [ ] **Vendor directory exists**
  ```bash
  ls -la vendor/
  # Should show multiple directories (slim, vlucas, etc.)
  ```

- [ ] **Autoloader generated**
  ```bash
  ls -la vendor/autoload.php
  # Should exist
  ```

## Configuration ✓

- [ ] **.env file created**
  ```bash
  ls -la .env
  # Should exist (not .env.example)
  ```

- [ ] **Database credentials configured**
  ```bash
  grep "DB_" .env
  # Should show DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
  ```

- [ ] **JWT secret generated**
  ```bash
  # Generate with:
  openssl rand -base64 64
  
  # Check it's set:
  grep "JWT_SECRET" .env
  # Should NOT be the default from .env.example
  ```

- [ ] **CORS origin set**
  ```bash
  grep "CORS_ORIGIN" .env
  # Should match your frontend URL
  ```

- [ ] **Environment set correctly**
  ```bash
  grep "APP_ENV" .env
  # Development: APP_ENV=development
  # Production: APP_ENV=production
  ```

## File Structure ✓

- [ ] **Directory structure complete**
  ```bash
  cd backend
  ls -la
  # Should show: public/, src/, storage/, vendor/, .env, composer.json
  ```

- [ ] **Public directory has required files**
  ```bash
  ls -la public/
  # Should show: index.php, .htaccess
  ```

- [ ] **Source files present**
  ```bash
  ls -la src/
  # Should show: Bootstrap/, Config/, Middleware/, Helpers/
  ```

- [ ] **Storage directories exist**
  ```bash
  ls -la storage/
  # Should show: logs/, cache/
  ```

- [ ] **Storage permissions set** (Linux/Mac)
  ```bash
  chmod -R 775 storage
  # Ensure web server can write to logs
  ```

## Testing ✓

### Test 1: Database Connection

- [ ] **Run connection test**
  ```bash
  cd backend
  php test-connection.php
  ```
  
  **Expected output:**
  ```
  ✅ Environment variables loaded
  ✅ Required environment variables present
  ✅ Database connection successful!
  ✅ Found 17 tables
  ✅ ALL TESTS PASSED!
  ```

### Test 2: PHP Syntax

- [ ] **Check for syntax errors**
  ```bash
  find src/ -name "*.php" -exec php -l {} \;
  # Should show "No syntax errors detected" for each file
  ```

### Test 3: Start Development Server

- [ ] **Start server**
  ```bash
  cd backend
  php -S localhost:8080 -t public
  # Should start without errors
  ```

### Test 4: Health Check Endpoint

- [ ] **Test health endpoint (Terminal 2)**
  ```bash
  curl http://localhost:8080/api/health
  ```
  
  **Expected response:**
  ```json
  {
    "status": "healthy",
    "timestamp": "2023-11-13 10:30:00",
    "environment": "development",
    "database": {
      "connected": true,
      "message": "Database connection successful",
      "version": "8.0.35",
      "database": "ch167436_3dprint"
    }
  }
  ```

- [ ] **Test in browser**
  - Open: http://localhost:8080/api/health
  - Should see JSON response

### Test 5: API Info Endpoint

- [ ] **Test API endpoint**
  ```bash
  curl http://localhost:8080/api
  ```
  
  **Expected response:**
  ```json
  {
    "success": true,
    "message": "Welcome to 3D Print Pro API",
    "data": {
      "name": "3D Print Pro API",
      "version": "1.0.0",
      ...
    }
  }
  ```

### Test 6: 404 Handler

- [ ] **Test undefined route**
  ```bash
  curl http://localhost:8080/api/nonexistent
  ```
  
  **Expected response:**
  ```json
  {
    "success": false,
    "message": "Endpoint not found"
  }
  ```

### Test 7: CORS Headers

- [ ] **Test CORS preflight**
  ```bash
  curl -H "Origin: http://localhost:8000" \
       -H "Access-Control-Request-Method: POST" \
       -H "Access-Control-Request-Headers: Content-Type" \
       -X OPTIONS \
       http://localhost:8080/api/health
  ```
  
  **Expected headers in response:**
  - `Access-Control-Allow-Origin`
  - `Access-Control-Allow-Methods`
  - `Access-Control-Allow-Headers`

### Test 8: Error Handling

- [ ] **Test with invalid database credentials**
  - Temporarily change DB_PASSWORD in .env
  - Restart server
  - Access health endpoint
  - Should see: `"connected": false` with error message
  - Restore correct password

## Production Readiness ✓

### Security

- [ ] **Environment set to production**
  ```bash
  grep "APP_ENV=production" .env
  ```

- [ ] **Debug mode disabled**
  ```bash
  grep "APP_DEBUG=false" .env
  ```

- [ ] **Strong JWT secret**
  ```bash
  # Should be at least 32 characters, random
  grep "JWT_SECRET" .env | wc -c
  # Should show > 40
  ```

- [ ] **CORS restricted**
  ```bash
  # Should NOT be * in production
  grep "CORS_ORIGIN" .env
  # Should be your actual domain
  ```

- [ ] **.env file protected**
  ```bash
  chmod 600 .env
  # Only owner can read/write
  ```

- [ ] **Sensitive files in .gitignore**
  ```bash
  grep ".env" ../.gitignore
  grep "vendor/" ../.gitignore
  # Both should be listed
  ```

### Web Server

For production deployment:

- [ ] **Apache mod_rewrite enabled** (if using Apache)
  ```bash
  sudo a2enmod rewrite
  ```

- [ ] **Virtual host configured**
  - Document root points to `backend/public`
  - .htaccess is being read

- [ ] **HTTPS/SSL configured**
  ```bash
  # Install Let's Encrypt certificate
  sudo certbot --apache -d api.yourdomain.com
  ```

- [ ] **Firewall configured**
  ```bash
  sudo ufw allow 80/tcp
  sudo ufw allow 443/tcp
  ```

### Performance

- [ ] **OPcache enabled**
  ```bash
  php -i | grep opcache.enable
  # Should show: opcache.enable => On => On
  ```

- [ ] **Composer optimized**
  ```bash
  composer install --no-dev --optimize-autoloader
  ```

### Monitoring

- [ ] **Error logging configured**
  ```bash
  ls -la storage/logs/
  # Directory should be writable
  ```

- [ ] **Log rotation set up**
  ```bash
  cat /etc/logrotate.d/3dprint-api
  # Should exist in production
  ```

- [ ] **Database backups automated**
  ```bash
  crontab -l | grep backup
  # Should show backup cron job
  ```

## Documentation ✓

- [ ] **README.md reviewed**
  - [ ] Setup instructions clear
  - [ ] Environment variables documented
  - [ ] API endpoints documented

- [ ] **QUICKSTART.md read**
  - [ ] Quick setup steps followed
  - [ ] Troubleshooting section reviewed

- [ ] **DEPLOYMENT.md reviewed** (for production)
  - [ ] Deployment strategy chosen
  - [ ] Security checklist completed

## Common Issues ✓

If any tests fail, check:

### Database Connection Failed
- [ ] MySQL is running
- [ ] Database exists
- [ ] Credentials in .env are correct
- [ ] Database user has proper permissions

### 500 Internal Server Error
- [ ] Check PHP error logs
- [ ] Verify file permissions
- [ ] Ensure .env exists
- [ ] Check PHP extensions are installed

### CORS Errors
- [ ] CORS_ORIGIN matches frontend URL exactly
- [ ] Include protocol (http:// or https://)
- [ ] Check port number matches

### Composer Issues
- [ ] Composer is installed globally
- [ ] PHP version meets requirements (7.4+)
- [ ] Internet connection available

## Final Verification

- [ ] ✅ All prerequisites met
- [ ] ✅ Database set up and accessible
- [ ] ✅ Dependencies installed
- [ ] ✅ Configuration complete
- [ ] ✅ Health check returns success
- [ ] ✅ Database connection test passes
- [ ] ✅ CORS headers present
- [ ] ✅ Error handling works
- [ ] ✅ Documentation reviewed

## Next Steps

Once all items are checked:

1. **Development:**
   - Start adding API endpoints
   - Implement authentication
   - Connect frontend to API

2. **Production:**
   - Follow DEPLOYMENT.md guide
   - Complete security hardening
   - Set up monitoring and backups
   - Deploy to production server

3. **Testing:**
   - Test all endpoints
   - Verify CORS with frontend
   - Load test with expected traffic
   - Test error scenarios

## Get Help

If you're stuck:

1. Review [README.md](README.md) troubleshooting section
2. Run `php test-connection.php` for database diagnostics
3. Check server error logs
4. Verify all prerequisites are met
5. Ensure .env configuration is correct

---

**Date:** _______________  
**Completed by:** _______________  
**Environment:** [ ] Development [ ] Production  
**Status:** [ ] Ready [ ] Issues remaining  

**Notes:**
_______________________________________________
_______________________________________________
_______________________________________________
