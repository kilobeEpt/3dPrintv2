# Testing Framework Documentation

Comprehensive documentation for the 3D Print Pro testing framework.

## Overview

This project includes a robust testing framework with:
- **PHPUnit Integration Tests** - Automated API endpoint testing
- **Manual Testing Checklist** - Comprehensive scenario-based testing
- **Test Reporting** - Structured documentation of test results
- **CI/CD Ready** - Prepared for automated testing pipelines

## Test Suite Components

### 1. PHPUnit Integration Tests

Located in `backend/tests/Integration/`:

#### Test Files

| File | Purpose | Test Count | Coverage |
|------|---------|------------|----------|
| `AuthTest.php` | Authentication endpoints | 13 tests | Login, token validation, protected routes |
| `OrdersTest.php` | Order management | 21 tests | CRUD, validation, rate limiting, search |
| `SettingsTest.php` | Settings management | 15 tests | Public/admin settings, validation, updates |
| `ContentTest.php` | Content APIs | 18 tests | Services, portfolio, testimonials, FAQ |

#### Base Test Case

`backend/tests/TestCase.php` - Provides common functionality:
- Database connection management
- HTTP request helpers
- Authentication helpers
- Response assertion methods
- Test data creation helpers

#### Key Features

**HTTP Client**
```php
// Make public API request
$response = $this->makeRequest('GET', '/api/services');

// Make authenticated request
$response = $this->authenticatedRequest('POST', '/api/admin/services', $data);
```

**Assertions**
```php
// Assert successful response
$this->assertSuccessResponse($response);

// Assert error response
$this->assertErrorResponse($response);

// Assert validation error
$this->assertValidationError($response, 'field_name');
```

**Test Data Helpers**
```php
// Create test service
$service = $this->createTestService(['name' => 'Test']);

// Create test portfolio item
$portfolio = $this->createTestPortfolio();

// Create test order
$order = $this->createTestOrder();
```

### 2. Manual Testing Checklist

Comprehensive scenario-based testing covering:
- ✅ Public site features (content, calculator, forms)
- ✅ Admin workflows (login, CRUD, settings)
- ✅ Edge cases (validation, errors, security)
- ✅ Database integrity
- ✅ Performance and accessibility

**Location:** `docs/test-checklist.md`

**Usage:**
1. Print or open in editor
2. Execute each test scenario
3. Check checkbox when passed
4. Note any issues found
5. Update testing report

### 3. Testing Report

Structured template for documenting test results:
- Executive summary
- Test environment details
- Detailed test results by category
- Issues found with severity
- Performance observations
- Security audit findings
- Recommendations

**Location:** `docs/testing-report.md`

**Usage:**
1. Execute tests (automated and manual)
2. Fill in test results (pass/fail/skipped)
3. Document issues found
4. Update summary statistics
5. Get sign-off from stakeholders

### 4. Test Runner Script

Automated script to run all PHPUnit tests:

**Location:** `backend/run-tests.sh`

**Features:**
- ✅ Validates environment setup
- ✅ Checks server availability
- ✅ Runs all test suites
- ✅ Generates coverage report (if xdebug available)
- ✅ Color-coded output

**Usage:**
```bash
cd backend
./run-tests.sh
```

## Running Tests

### Prerequisites

Before running tests, ensure:

```bash
# 1. Backend server running
cd backend && composer start

# 2. Database seeded
mysql -u root -p < database/migrations/20231113_initial.sql
mysql -u root -p < database/seeds/initial_data.sql

# 3. Admin user created
php database/seeds/seed-admin-user.php

# 4. Environment configured
cp .env.example .env
nano .env  # Configure DB and JWT_SECRET
```

### Run All Tests

```bash
cd backend
./run-tests.sh
```

### Run Specific Test Suite

```bash
# Authentication tests only
vendor/bin/phpunit --filter AuthTest

# Orders tests
vendor/bin/phpunit --filter OrdersTest

# Settings tests
vendor/bin/phpunit --filter SettingsTest

# Content tests
vendor/bin/phpunit --filter ContentTest
```

### Run Single Test Method

```bash
vendor/bin/phpunit --filter testLoginWithValidCredentials
```

### Run with Coverage

```bash
# Requires Xdebug extension
vendor/bin/phpunit --coverage-html coverage --coverage-text
```

### Run in Watch Mode (for development)

```bash
# Install phpunit-watcher
composer require --dev spatie/phpunit-watcher

# Run watcher
vendor/bin/phpunit-watcher watch
```

## Test Structure

### Test Naming Conventions

```php
// Pattern: test{Action}{Condition}
public function testLoginWithValidCredentials(): void
public function testCreateOrderMissingRequiredFields(): void
public function testUpdateSettingsInvalidEmail(): void
```

### Test Organization

Each test follows AAA pattern:

```php
public function testExample(): void
{
    // Arrange - Set up test data
    $data = ['name' => 'Test'];
    
    // Act - Execute the action
    $response = $this->makeRequest('POST', '/api/endpoint', $data);
    
    // Assert - Verify the result
    $this->assertEquals(201, $response['status']);
    $this->assertSuccessResponse($response);
}
```

### Test Data Cleanup

Tests clean up after themselves:

```php
protected function tearDown(): void
{
    // Clean up created resources
    foreach ($this->createdOrderIds as $orderId) {
        $this->authenticatedRequest('DELETE', '/api/orders/' . $orderId);
    }
    
    parent::tearDown();
}
```

## Writing New Tests

### 1. Create Test Class

```php
<?php

namespace Tests\Integration;

use Tests\TestCase;

class MyNewTest extends TestCase
{
    private array $createdIds = [];
    
    public function testSomething(): void
    {
        // Test implementation
    }
    
    protected function tearDown(): void
    {
        // Cleanup
        parent::tearDown();
    }
}
```

### 2. Follow Best Practices

- ✅ One assertion focus per test
- ✅ Clear, descriptive test names
- ✅ Clean up test data in tearDown()
- ✅ Use helper methods for common operations
- ✅ Test both success and failure scenarios
- ✅ Test edge cases and boundary values

### 3. Add to Test Checklist

Update `docs/test-checklist.md` with manual test scenarios.

### 4. Update Coverage Report

Document in `docs/testing-report.md`.

## Test Data Management

### Creating Test Data

```php
// Use unique identifiers to avoid collisions
$uniqueEmail = 'test' . uniqid() . '@example.com';
$uniqueName = 'Test Service ' . uniqid();

// Use helper methods
$service = $this->createTestService(['name' => $uniqueName]);
```

### Cleaning Up Test Data

```php
// Track created IDs
$this->createdServiceIds[] = $response['body']['data']['id'];

// Clean up in tearDown
protected function tearDown(): void
{
    foreach ($this->createdServiceIds as $id) {
        try {
            $this->authenticatedRequest('DELETE', '/api/admin/services/' . $id);
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }
    parent::tearDown();
}
```

## Debugging Tests

### Enable Verbose Output

```bash
vendor/bin/phpunit --testdox --verbose
```

### Debug Single Test

```bash
vendor/bin/phpunit --filter testName --debug
```

### View API Responses

Add temporary debugging in test:

```php
$response = $this->makeRequest('GET', '/api/endpoint');
var_dump($response);  // Remove after debugging
```

### Check Backend Logs

```bash
tail -f backend/storage/logs/app.log
```

### Monitor Network Traffic

Use browser DevTools Network tab for manual tests.

## Common Test Patterns

### Testing Authentication

```php
// Test protected endpoint without auth
$response = $this->makeRequest('GET', '/api/admin/endpoint');
$this->assertEquals(401, $response['status']);

// Test with authentication
$response = $this->authenticatedRequest('GET', '/api/admin/endpoint');
$this->assertEquals(200, $response['status']);
```

### Testing Validation

```php
// Test missing required field
$response = $this->makeRequest('POST', '/api/endpoint', [
    // Missing 'name' field
]);
$this->assertValidationError($response, 'name');

// Test invalid format
$response = $this->makeRequest('POST', '/api/endpoint', [
    'email' => 'invalid-email'
]);
$this->assertValidationError($response, 'email');
```

### Testing CRUD Operations

```php
// Create
$createResponse = $this->authenticatedRequest('POST', '/api/admin/services', $data);
$this->assertEquals(201, $createResponse['status']);
$id = $createResponse['body']['data']['id'];

// Read
$getResponse = $this->authenticatedRequest('GET', '/api/admin/services/' . $id);
$this->assertEquals(200, $getResponse['status']);

// Update
$updateResponse = $this->authenticatedRequest('PUT', '/api/admin/services/' . $id, ['name' => 'Updated']);
$this->assertEquals(200, $updateResponse['status']);

// Delete
$deleteResponse = $this->authenticatedRequest('DELETE', '/api/admin/services/' . $id);
$this->assertEquals(200, $deleteResponse['status']);

// Verify deletion
$verifyResponse = $this->authenticatedRequest('GET', '/api/admin/services/' . $id);
$this->assertEquals(404, $verifyResponse['status']);
```

## Performance Testing

### Measuring Response Times

```php
$start = microtime(true);
$response = $this->makeRequest('GET', '/api/endpoint');
$duration = (microtime(true) - $start) * 1000; // milliseconds

$this->assertLessThan(500, $duration, 'Response time should be < 500ms');
```

### Load Testing

Use Apache Bench or wrk:

```bash
# Test 1000 requests with 10 concurrent connections
ab -n 1000 -c 10 http://localhost:8080/api/health

# Or use wrk
wrk -t10 -c10 -d30s http://localhost:8080/api/health
```

## Security Testing

### XSS Testing

```php
$xssPayload = '<script>alert("XSS")</script>';
$response = $this->makeRequest('POST', '/api/orders', [
    'client_name' => $xssPayload,
    'client_email' => 'test@example.com',
    'client_phone' => '+79001234567'
]);

$this->assertEquals(201, $response['status']);
$this->assertNotContains('<script>', $response['body']['data']['client_name']);
```

### SQL Injection Testing

```php
$sqlPayload = "'; DROP TABLE orders; --";
$response = $this->makeRequest('POST', '/api/orders', [
    'client_name' => $sqlPayload,
    'client_email' => 'test@example.com',
    'client_phone' => '+79001234567'
]);

$this->assertEquals(201, $response['status']);
// Verify database still intact
```

### Token Security Testing

```php
// Test with tampered token
$response = $this->makeRequest('GET', '/api/auth/me', null, [
    'Authorization: Bearer tampered.token.here'
]);
$this->assertEquals(401, $response['status']);
```

## CI/CD Integration

### GitHub Actions

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: ch167436_3dprint
          MYSQL_ROOT_PASSWORD: root
        ports:
          - 3306:3306
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: pdo, pdo_mysql
      
      - name: Install dependencies
        run: cd backend && composer install
      
      - name: Run migrations
        run: mysql -h 127.0.0.1 -u root -proot ch167436_3dprint < backend/database/migrations/20231113_initial.sql
      
      - name: Seed database
        run: mysql -h 127.0.0.1 -u root -proot ch167436_3dprint < backend/database/seeds/initial_data.sql
      
      - name: Run tests
        run: cd backend && vendor/bin/phpunit
```

### GitLab CI

Create `.gitlab-ci.yml`:

```yaml
test:
  image: php:7.4
  
  services:
    - mysql:8.0
  
  variables:
    MYSQL_DATABASE: ch167436_3dprint
    MYSQL_ROOT_PASSWORD: root
  
  before_script:
    - apt-get update && apt-get install -y git
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install
  
  script:
    - mysql -h mysql -u root -proot ch167436_3dprint < backend/database/migrations/20231113_initial.sql
    - mysql -h mysql -u root -proot ch167436_3dprint < backend/database/seeds/initial_data.sql
    - cd backend && vendor/bin/phpunit
```

## Test Coverage Goals

### Current Coverage

| Component | Target | Actual |
|-----------|--------|--------|
| Authentication | 90% | TBD |
| Orders API | 85% | TBD |
| Settings API | 80% | TBD |
| Content API | 80% | TBD |
| **Overall** | **80%** | **TBD** |

### Improving Coverage

1. Add tests for uncovered code paths
2. Test error handling branches
3. Test edge cases and boundary values
4. Add integration tests for complex workflows

## Troubleshooting

### Common Issues

**Issue:** Tests fail with "Server not running"
```bash
# Solution: Start backend server
cd backend && composer start &
```

**Issue:** Database connection errors
```bash
# Solution: Verify database running and credentials
php backend/test-connection.php
```

**Issue:** Authentication tests fail
```bash
# Solution: Re-create admin user
php backend/database/seeds/seed-admin-user.php
```

**Issue:** Rate limiting tests interfere
```bash
# Solution: Skip rate limiting tests
vendor/bin/phpunit --exclude-group slow
```

## Best Practices

### DO

- ✅ Write clear, descriptive test names
- ✅ Test both success and failure paths
- ✅ Clean up test data after each test
- ✅ Use unique identifiers for test data
- ✅ Assert specific error messages
- ✅ Test edge cases and boundary values
- ✅ Keep tests independent
- ✅ Mock external dependencies (e.g., Telegram API)

### DON'T

- ❌ Rely on test execution order
- ❌ Leave test data in database
- ❌ Hardcode test data (use generators)
- ❌ Test implementation details
- ❌ Skip important test scenarios
- ❌ Ignore failing tests
- ❌ Commit commented-out tests

## Resources

### Documentation

- **API Documentation:** `docs/api.md`
- **Testing Checklist:** `docs/test-checklist.md`
- **Testing Report:** `docs/testing-report.md`
- **Quick Start:** `docs/testing-quickstart.md`
- **Backend Guide:** `backend/TESTING_GUIDE.md`

### Tools

- **PHPUnit:** https://phpunit.de/
- **Apache Bench:** https://httpd.apache.org/docs/2.4/programs/ab.html
- **wrk:** https://github.com/wg/wrk
- **Postman:** https://www.postman.com/
- **Insomnia:** https://insomnia.rest/

### Community

- PHPUnit Documentation: https://phpunit.readthedocs.io/
- Testing Best Practices: https://martinfowler.com/testing/
- PHP Testing Resources: https://phptherightway.com/#testing

---

**Version:** 1.0.0  
**Last Updated:** 2024-11-14  
**Maintained By:** QA Team
