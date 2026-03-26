# Scanner Testing Guide

This guide provides comprehensive instructions for testing the QR Gift Card Scanner feature, including automated tests, test data setup, and manual testing procedures.

## Table of Contents

- [Overview](#overview)
- [Test Suites](#test-suites)
- [Running Tests](#running-tests)
- [Test Data](#test-data)
- [Common Issues](#common-issues)
- [Manual Testing Checklist](#manual-testing-checklist)

---

## Overview

The scanner feature has three levels of automated testing:

1. **API Tests** - Unit tests for individual API endpoints
2. **Integration Tests** - Tests for complete user flows and business logic
3. **Browser Tests** - End-to-end tests using Laravel Dusk

All tests use SQLite in-memory database and are fully isolated with the `RefreshDatabase` trait.

---

## Test Suites

### 1. API Tests

**Location**: `tests/Feature/Api/V1/`

Tests individual API endpoints for the offline-first PWA:

- **CategoryApiTest.php** (3 tests)
  - GET `/api/v1/public/categories` - Public category listing
  - Cache headers validation
  - Response structure validation

- **GiftCardApiTest.php** (8 tests)
  - GET `/api/v1/gift-cards` - Authenticated user's gift cards
  - GET `/api/v1/public/gift-cards/search` - Public gift card search
  - Status filtering (active/inactive cards)
  - Error handling (not found, inactive cards)
  - Cache headers validation

- **DebitApiTest.php** (8 tests)
  - POST `/api/v1/debit` - Process debit transaction
  - Balance validation
  - Inactive card rejection
  - Insufficient balance handling
  - Transaction record creation
  - Response format validation

**Total API Tests**: 19 tests

### 2. Integration Tests

**Location**: `tests/Feature/`

Tests complete business flows:

- **ScannerScopeValidationTest.php** (8 tests)
  - Chain scope validation
  - Brand scope validation
  - Branch scope validation
  - Cross-branch validation
  - Error message validation

**Total Integration Tests**: 8 tests

### 3. Browser Tests

**Location**: `tests/Browser/`

End-to-end tests with real browser interaction:

- **ScannerTest.php** (9 tests)
  - Scanner page access control
  - QR code lookup flow
  - Debit processing flow
  - Balance validation
  - Error handling
  - Success notifications
  - Transaction history display

**Total Browser Tests**: 9 tests

---

## Running Tests

### Run All Tests

```bash
# Run all tests with Pest
composer test

# Run with verbose output
vendor/bin/pest --verbose

# Run with coverage
vendor/bin/pest --coverage
```

### Run Specific Test Suites

```bash
# API Tests only
vendor/bin/pest tests/Feature/Api/V1/

# Integration Tests only
vendor/bin/pest tests/Feature/ScannerScopeValidationTest.php

# Browser Tests only
php artisan dusk

# Specific browser test
php artisan dusk tests/Browser/ScannerTest.php
```

### Run Individual Tests

```bash
# Run specific test by name
vendor/bin/pest --filter "can lookup gift card by legacy_id"

# Run specific test file
vendor/bin/pest tests/Feature/Api/V1/GiftCardApiTest.php

# Watch mode (re-run on file changes)
vendor/bin/pest --watch
```

### Browser Test Setup

Before running browser tests, ensure ChromeDriver is installed:

```bash
# Install ChromeDriver
php artisan dusk:chrome-driver

# Start ChromeDriver (in separate terminal)
./vendor/laravel/dusk/bin/chromedriver-mac-arm64

# Run browser tests
php artisan dusk
```

---

## Test Data

### Test Users

The test suite creates users with different roles and branch assignments:

| Email | Password | Role | Branch Assignment | Status |
|-------|----------|------|-------------------|--------|
| `admin@example.com` | `password` | Admin | Branch 1 (CDMX Centro) | Active |
| `employee@example.com` | `password` | Employee | Branch 2 (Guadalajara) | Active |
| `inactive@example.com` | `password` | Employee | Branch 1 | Inactive |
| `no-branch@example.com` | `password` | Employee | None | Active |

### Test Gift Cards

Test gift cards are automatically created during test setup:

| Legacy ID | Category | Balance | Status | Scope | Assignment |
|-----------|----------|---------|--------|-------|------------|
| `APITST000001` | API Test | $1000.00 | Active | Chain | All branches |
| `APITST000002` | API Test | $500.00 | Inactive | Chain | All branches |
| `EMCAD000001` | Empleados | $2500.00 | Active | Branch | Branch 1 only |
| `EMCAD000002` | Empleados | $100.00 | Active | Brand | Brand 1 branches |
| `EMCAD000003` | Empleados | $50.00 | Active | Chain | All branches |

### Test Categories

Default categories created by migrations:

| Name | Prefix | Nature | Description |
|------|--------|--------|-------------|
| Empleados | EMCAD | payment_method | Default employee gift cards |
| API Test | APITST | payment_method | Test category for API tests |

### Test Branches

Branches are created with realistic organizational structure:

```
Chain: "Cadena Nacional"
└── Brand: "Marca Premium"
    ├── Branch 1: "CDMX Centro"
    └── Branch 2: "Guadalajara"
└── Brand: "Marca Económica"
    └── Branch 3: "Monterrey"
```

---

## Common Issues

### Issue: Tests Fail with "Database Not Found"

**Solution**: Ensure SQLite is installed and the test database is configured:

```bash
# Check SQLite installation
sqlite3 --version

# Verify phpunit.xml configuration
cat phpunit.xml | grep DB_CONNECTION
```

Expected configuration in `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Issue: Browser Tests Timeout

**Solution**: Increase timeout and ensure ChromeDriver is running:

```bash
# Check ChromeDriver is running
ps aux | grep chromedriver

# Start ChromeDriver if not running
./vendor/laravel/dusk/bin/chromedriver-mac-arm64 &

# Increase timeout in test
$browser->waitFor('@element', 10); // 10 seconds
```

### Issue: Permission Denied on QR Code Storage

**Solution**: Ensure storage directories are writable:

```bash
# Fix storage permissions
chmod -R 775 storage/
php artisan storage:link
```

### Issue: VAPID Keys Missing in Tests

**Solution**: Generate VAPID keys for testing:

```bash
# Generate VAPID keys
php artisan webpush:vapid --no-interaction

# Verify keys in .env
grep VAPID .env
```

### Issue: Sanctum Authentication Fails

**Solution**: Ensure Sanctum is properly configured:

```bash
# Publish Sanctum config
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Run migrations
php artisan migrate

# Clear config cache
php artisan config:clear
```

### Issue: Test Data Persists Between Tests

**Solution**: Always use `RefreshDatabase` trait:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
```

---

## Manual Testing Checklist

### Prerequisites

- [ ] Application running: `composer dev`
- [ ] Logged in as employee with branch assignment
- [ ] Test gift cards available in database
- [ ] QR codes generated for test cards

### Scanner Access Control

- [ ] **Without Login**: Redirected to login page
- [ ] **Without Branch**: See "No branch assigned" error
- [ ] **Inactive User**: Logged out and see error message
- [ ] **With Valid Access**: Scanner page loads

### QR Code Lookup

- [ ] Scan valid QR code → Card details display
- [ ] Scan by legacy ID → Card found
- [ ] Scan by UUID → Card found
- [ ] Scan inactive card → "Inactive card" error
- [ ] Scan non-existent code → "Not found" error
- [ ] Card balance displays correctly
- [ ] Card holder name displays
- [ ] QR image displays (if available)

### Debit Processing

- [ ] Enter valid amount → Debit processes successfully
- [ ] Enter amount > balance → "Insufficient balance" error
- [ ] Enter negative amount → Validation error
- [ ] Enter zero amount → Validation error
- [ ] Process debit → Balance updates immediately
- [ ] Success notification displays
- [ ] Transaction folio generated correctly
- [ ] Cashier name displays correctly
- [ ] Branch name displays correctly

### Scope Validation

- [ ] **Chain Scope**: Can be used at any branch
- [ ] **Brand Scope**: Can only be used at brand branches
- [ ] **Branch Scope**: Can only be used at assigned branch
- [ ] Wrong scope → See appropriate error message
- [ ] Error message mentions scope type

### Offline Mode Testing

- [ ] Disable network → "Offline" indicator shows
- [ ] Scan card offline → Lookup from IndexedDB
- [ ] Process debit offline → Transaction queued
- [ ] Enable network → Auto-sync triggers
- [ ] Sync queue empties
- [ ] "Synced" notification displays

### Transaction History

- [ ] Navigate to dashboard → Recent transactions display
- [ ] Each transaction shows:
  - [ ] Folio number
  - [ ] Gift card legacy ID
  - [ ] Amount
  - [ ] Balance before/after
  - [ ] Date and time
  - [ ] Branch name
  - [ ] Cashier name
- [ ] Pagination works (if >10 transactions)
- [ ] Filter by date works
- [ ] Search by folio works

### Mobile Responsiveness

- [ ] Scanner page renders correctly on mobile
- [ ] QR camera scanner works on mobile device
- [ ] Debit form is mobile-friendly
- [ ] Notifications display properly on mobile
- [ ] PWA install prompt shows (mobile only)
- [ ] Offline mode works on mobile

### Performance

- [ ] Scanner page loads in <2 seconds
- [ ] QR lookup completes in <500ms
- [ ] Debit processing completes in <1 second
- [ ] Offline lookup is instant
- [ ] IndexedDB stores cards (check DevTools)
- [ ] Service Worker caches assets (check DevTools)

### Error Recovery

- [ ] Network error during debit → Transaction queued
- [ ] Server error → User-friendly error message
- [ ] Validation error → Form highlights invalid field
- [ ] Session expired → Redirect to login
- [ ] Browser refresh → State preserved (offline session)

---

## Debugging Failed Tests

### Enable Detailed Error Output

```bash
# Run with verbose output
vendor/bin/pest --verbose

# Run specific test with debug
vendor/bin/pest --filter "test name" --debug
```

### Browser Test Screenshots

When browser tests fail, Dusk automatically captures screenshots:

```bash
# View screenshots
ls -la tests/Browser/screenshots/

# Open latest screenshot
open tests/Browser/screenshots/*.png
```

### Browser Console Logs

Access browser console logs in Dusk tests:

```php
// In browser test
$browser->script('console.log("Debug info")');

// View logs
$logs = $browser->driver->manage()->getLog('browser');
dd($logs);
```

### Database State Inspection

Inspect database state during tests:

```php
// In test
$this->assertDatabaseHas('gift_cards', [
    'legacy_id' => 'EMCAD000001',
    'status' => true,
]);

// Dump database records
dump(GiftCard::all()->toArray());
```

---

## Best Practices

1. **Always use RefreshDatabase**: Ensures clean state for each test
2. **Use descriptive test names**: `it('can process debit with valid amount')`
3. **Test both happy and sad paths**: Success cases and error cases
4. **Use factories for test data**: Consistent, reusable test data
5. **Mock external services**: Don't hit real APIs in tests
6. **Test offline behavior**: Simulate network conditions
7. **Clean up after tests**: Remove temporary files, clear caches
8. **Run tests before commits**: Catch regressions early
9. **Use `beforeEach` for setup**: Keep tests DRY
10. **Assert exact values**: Don't use `assertTrue()` when you can assert exact values

---

## Next Steps

After running all tests successfully:

1. Review test coverage report
2. Add tests for edge cases
3. Update tests when adding new features
4. Run tests in CI/CD pipeline
5. Monitor test execution time
6. Refactor slow tests

---

## Additional Resources

- [Pest Documentation](https://pestphp.com/)
- [Laravel Dusk Documentation](https://laravel.com/docs/dusk)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Assertions](https://phpunit.readthedocs.io/en/latest/assertions.html)
- [Sanctum Testing](https://laravel.com/docs/sanctum#testing)

---

**Last Updated**: 2026-02-09
**Version**: 1.0.0
