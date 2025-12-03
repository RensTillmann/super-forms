# Testing & Quality Assurance Guide

## Testing Philosophy

**The Golden Rule:** Every change must be tested in the actual environment before claiming it's fixed.

## PHPUnit Test Suites

Super Forms uses PHPUnit for automated testing with three main test suites:

### Automation System Test Suite

Location: `/tests/automations/` (renamed from `/tests/triggers/`)

**Purpose:** Tests automation system including event firing, action execution, spam detection, and integration features. Tests both legacy "trigger" terminology and new "automation" terminology.

**Key Test Files:**
- `test-event-firing.php` - Tests all 36 events fire correctly (form, entry, file, payment, session)
- `test-spam-detector.php` - Tests 5 spam detection methods (honeypot, time, IP, keywords, Akismet)
- `test-automation-executor.php` - Tests automation execution flow and condition evaluation (renamed from `test-trigger-executor.php`)
- `test-automation-scheduler.php` - Tests Action Scheduler integration for async execution (renamed from `test-trigger-scheduler.php`)
- `test-logging-system.php` - Tests Logger, Debugger, Performance, Compliance classes
- `test-api-security.php` - Tests Credentials, OAuth, Security, Permissions, API Keys
- `test-session-dal.php` - Tests session storage for progressive forms
- `test-session-cleanup.php` - Tests automated session cleanup (abandoned detection, expiration, event firing)
- `test-entry-dal.php` - Tests entry CRUD and backwards compatibility
- `test-terminology-migration.php` - Tests database migration from triggers to automations terminology

**Run Tests:**
```bash
# Via sync script (syncs code to dev server and runs tests)
./sync-to-webserver.sh --test automations

# Or run all automation tests
./sync-to-webserver.sh --test

# Via SSH on dev server
ssh -p 18765 -i ~/.ssh/id_sftp u2669-dvgugyayggy5@gnldm1014.siteground.biz
cd /home/u2669-dvgugyayggy5/www/f4d.nl/public_html/dev/wp-content/plugins/super-forms
php vendor/bin/phpunit --testsuite "automations"
```

**Documentation:** See `/tests/automations/README.md` for detailed test coverage information.

### Integration Test Suite

Location: `/tests/integration/`

**Purpose:** End-to-end integration tests for complex workflows (payment processing, multi-step forms, third-party integrations).

**Run Tests:**
```bash
./sync-to-webserver.sh --test integration
```

### Super Forms Test Suite

Location: `/tests/`

**Purpose:** Legacy tests for EAV migration, data access layer, and core functionality.

**Note:** This suite can exhaust memory on databases with many entries. Run separately if needed:
```bash
./sync-to-webserver.sh --test "Super Forms Test Suite"
```

## Testing Philosophy

**The Golden Rule:** Every change must be tested in the actual environment before claiming it's fixed.

- Untested code is not a solution—it's a guess
- "Looks correct" ≠ "Works correctly"
- If you can't test it, don't ship it
- User-reported bugs on untested "fixes" destroy trust

## WordPress-Specific Testing Requirements

### Frontend Validation

**ALWAYS test forms render correctly on frontend:**

1. Load the form on a public-facing page
2. Test form submission with various input types
3. Verify conditional logic works correctly
4. Check for JavaScript errors in browser console
5. Test on multiple browsers (Chrome, Firefox, Safari)
6. Test on mobile devices (responsive design)

**Testing Checklist:**
- [ ] Form renders without layout issues
- [ ] All field types display correctly
- [ ] Validation messages appear appropriately
- [ ] Form submission succeeds
- [ ] Success message displays
- [ ] Email notifications send correctly
- [ ] No JavaScript errors in console
- [ ] No PHP errors in debug.log

### Admin Functionality

**ALWAYS verify all admin features work without errors:**

1. Access WordPress admin area
2. Navigate to plugin settings/pages
3. Test AJAX operations (save, delete, update)
4. Verify UI updates correctly
5. Check for JavaScript errors in console
6. Check for PHP errors in debug.log

**Testing Checklist:**
- [ ] Admin pages load without errors
- [ ] Settings save successfully
- [ ] List tables display data correctly
- [ ] Search and filters work
- [ ] Bulk actions complete successfully
- [ ] No JavaScript errors in console
- [ ] No PHP errors in debug.log

### Database Operations

**ALWAYS confirm all database queries execute properly:**

```bash
# Check for database errors in debug.log
tail -f wp-content/debug.log | grep -E "(Error|Warning|Notice)"

# Verify data was saved
wp db query "SELECT * FROM wp_superforms_entry_data WHERE entry_id = 123;"

# Check migration status
wp option get superforms_eav_migration --format=json

# Monitor queries with Query Monitor plugin
# Admin bar → Query Monitor → Database Queries
```

**Testing Checklist:**
- [ ] Data saves to correct tables
- [ ] Queries use prepared statements (no SQL injection)
- [ ] Indexes are used (check EXPLAIN query)
- [ ] No duplicate queries (N+1 problem)
- [ ] Transaction logic works correctly
- [ ] Foreign key constraints respected

### JavaScript Console

**ALWAYS check for JS errors in browser console:**

1. Open browser DevTools (F12)
2. Navigate to Console tab
3. Clear console
4. Perform the action/feature
5. Look for errors, warnings, and failed requests
6. Check Network tab for failed AJAX requests

**Common Issues to Check:**
- Uncaught TypeError/ReferenceError
- Failed network requests (404, 500 errors)
- CORS issues
- Nonce verification failures
- Missing dependencies (jQuery, etc.)

### Plugin Lifecycle

**ALWAYS test activation, deactivation, and uninstall processes:**

```bash
# Via WP-CLI
wp plugin activate super-forms
wp plugin deactivate super-forms
wp plugin uninstall super-forms --skip-delete

# Check database tables created
wp db query "SHOW TABLES LIKE '%superforms%';"

# Check options created
wp option list --search=super --format=table
```

**Testing Checklist:**
- [ ] Plugin activates without errors
- [ ] Database tables created correctly
- [ ] Default options set properly
- [ ] Plugin deactivates cleanly
- [ ] No fatal errors or warnings
- [ ] Reactivation works correctly
- [ ] Uninstall removes data (if configured)

### Security Verification

**ALWAYS confirm nonces, sanitization, and capability checks:**

1. **Nonce Verification:**
   - Remove or modify nonce in request
   - Should fail with "Security check failed"

2. **Capability Checks:**
   - Login as non-admin user
   - Try to access admin pages
   - Should fail with permission error

3. **Input Sanitization:**
   - Try SQL injection: `'; DROP TABLE wp_posts; --`
   - Try XSS: `<script>alert('xss')</script>`
   - Should be sanitized/escaped

4. **File Upload Security:**
   - Try uploading PHP file disguised as image
   - Should reject non-allowed file types

5. **SQL Injection Prevention (v6.4.126 hardening):**
   - All DELETE queries use `$wpdb->prepare()` with placeholders
   - TRUNCATE TABLE operations validate table existence first
   - No direct integer interpolation in SQL queries
   - Table names sanitized with `esc_sql()` after whitelist validation

6. **CSRF Protection (v6.5.0 changes):**
   - Frontend forms use Origin/Referer header validation (cache-compatible)
   - Admin operations use WordPress nonces (standard approach)
   - No per-user tokens in form HTML (works with CDN/Varnish caching)
   - Three protection modes: enabled/compatibility/disabled
   - Trusted origins support with wildcard matching

**Testing Checklist:**
- [ ] Frontend forms pass `verifyCSRF()` header check
- [ ] Admin operations verify nonces
- [ ] User capabilities checked
- [ ] All user input sanitized
- [ ] All output escaped
- [ ] SQL queries use prepared statements (including DELETE and cleanup queries)
- [ ] TRUNCATE operations validate table existence
- [ ] Table names validated against whitelist before use
- [ ] File uploads validate type and size
- [ ] Forms work on cached pages (test with Varnish/Cloudflare if available)

**Cross-Origin Protection Testing:**

Test the three protection modes and trusted origins configuration:

```bash
# 1. Test enabled mode (strict) - should reject missing headers
# Set cross_origin_protection to 'enabled'
wp option get super_settings --format=json | jq '.cross_origin_protection'

# Test with curl (no Origin/Referer headers)
curl -X POST https://example.com/wp-admin/admin-ajax.php \
  -d "action=super_submit_form&form_id=123" \
  # Expected: Security verification failed

# 2. Test compatibility mode - should allow missing headers
# Set cross_origin_protection to 'compatibility'
curl -X POST https://example.com/wp-admin/admin-ajax.php \
  -d "action=super_submit_form&form_id=123" \
  # Expected: Form processes normally

# 3. Test trusted origins with wildcard
# Add to trusted_origins setting:
# *.staging.example.com
# cdn.provider.com

# Test subdomain match
curl -X POST https://example.com/wp-admin/admin-ajax.php \
  -H "Origin: https://app.staging.example.com" \
  -d "action=super_submit_form&form_id=123" \
  # Expected: Form processes successfully

# 4. Test origin rejection
curl -X POST https://example.com/wp-admin/admin-ajax.php \
  -H "Origin: https://attacker.com" \
  -d "action=super_submit_form&form_id=123" \
  # Expected: Security verification failed

# 5. Check debug log for rejection details (requires WP_DEBUG=true)
tail -f wp-content/debug.log | grep "Cross-origin rejection"
```

**Protection Mode Test Matrix:**

| Mode | No Header | Valid Origin | Invalid Origin | Trusted Wildcard |
|------|-----------|--------------|----------------|------------------|
| enabled | ❌ Reject | ✅ Allow | ❌ Reject | ✅ Allow |
| compatibility | ✅ Allow | ✅ Allow | ❌ Reject | ✅ Allow |
| disabled | ✅ Allow | ✅ Allow | ✅ Allow | ✅ Allow |

**Wildcard Matching Tests:**

```php
// Test cases for *.example.com:
// ✅ sub.example.com
// ✅ app.sub.example.com
// ✅ example.com (bare domain)
// ❌ notexample.com
// ❌ example.com.attacker.com
```

### Performance Impact

**ALWAYS monitor query count and page load times:**

1. Install Query Monitor plugin
2. Access page/feature
3. Check Query Monitor → Database Queries
4. Look for:
   - Total query count (should be <50 for admin pages)
   - Slow queries (>0.05s)
   - Duplicate queries (N+1 problem)
   - Queries not using indexes

**Testing Checklist:**
- [ ] Page load time <2 seconds
- [ ] Database queries <50 per page
- [ ] No queries >0.1s (slow query)
- [ ] No duplicate queries
- [ ] Memory usage <64MB
- [ ] No memory leaks on repeated actions

## Local Testing Environment (wp-env)

### Setup

```bash
# Install dependencies
npm install

# Start WordPress environment
npm run env:start

# Access site
# Frontend: http://localhost:8888
# Admin: http://localhost:8888/wp-admin
# Username: admin
# Password: password
```

### Testing Workflow

1. **Make code changes** in `/src/` directory
2. **Changes are live** immediately (plugin mapped to wp-env)
3. **Test in browser** at localhost:8888
4. **Check debug log** at `wp-content/debug.log` (inside Docker)
5. **Reset environment** if needed: `npm run env:restart`

### Accessing wp-env Shell

```bash
# Enter WordPress container
docker exec -it $(docker ps -q -f name=wordpress) bash

# Run WP-CLI commands
wp plugin list
wp option get superforms_eav_migration --format=json
wp db query "SHOW TABLES LIKE '%superforms%';"

# View debug log
tail -f wp-content/debug.log
```

### Database Access

```bash
# Access MySQL container
docker exec -it $(docker ps -q -f name=mysql) bash

# Connect to database
mysql -u root -p
# Password: password

# Use WordPress database
USE wordpress;
SHOW TABLES LIKE '%superforms%';
SELECT * FROM wp_superforms_entry_data LIMIT 10;
```

## Manual Testing Procedures

### Test Plan Template

For each feature/bug fix:

1. **What changed?** (Brief description)
2. **How to test?** (Step-by-step instructions)
3. **Expected result?** (What should happen)
4. **Actual result?** (What actually happened)
5. **Pass/Fail?** (Did it work?)

**Example:**
```
Feature: Re-verify Failed Entries button

What changed:
- Added "Re-verify Failed Entries" button to Developer Tools
- Added AJAX handler for re-verification
- Fixed JSON encoding bug in data access layer

How to test:
1. Navigate to Super Forms > Developer Tools
2. Check "Failed Entries" section
3. Click "Re-verify Failed Entries" button
4. Wait for AJAX request to complete
5. Check if entries moved from failed to passed

Expected result:
- Button shows "Re-verifying..." during request
- Success message shows count of passed entries
- Failed entries table updates to remove passed entries
- Page reloads if entries passed verification

Actual result:
✅ Button disabled during request
✅ Success message appeared
✅ Failed count decreased from 14 to 0
✅ Page reloaded after 1.5 seconds

Pass/Fail: PASS
```

### Regression Testing

When fixing bugs, test related functionality:

**Example:** When fixing data access layer:
1. Test entry creation (frontend form submission)
2. Test entry editing (admin area)
3. Test entry deletion
4. Test CSV export
5. Test entry search/filtering
6. Test migration process
7. Test data integrity verification

## Automated Testing

### Current Status

- ✅ JSHint for JavaScript linting (`npm run jshint`)
- ✅ Migration integration tests (Developer Tools)
- ✅ Trigger system unit tests (`tests/triggers/`)
- ✅ Trigger system performance tests
- ❌ No E2E tests configured

### Migration Integration Tests

**Location:** Developer Tools > Migration Controls > Integration Tests
**File:** `/test/scripts/test-migration-integration.php`
**Class:** `SUPER_Migration_Integration_Test`

**Available Tests:**
1. **Full Migration Flow** - Creates entries, migrates, verifies completion
2. **Counter Accuracy** - Verifies live counter calculation matches database
3. **Data Preservation** - Validates complex data preserved during migration
4. **Empty Entry Handling** - Verifies empty entries marked for cleanup
5. **Lock Mechanism** - Tests migration lock prevents concurrent processing
6. **Resume from Failure** - Validates resumption from last_processed_id

**Test Data Sources:**
- **Programmatic (Generated)** - Creates synthetic test data in code
- **CSV Import** - Uses real production data from CSV files
- **XML Import** - Future support for WordPress XML exports (placeholder)

**Running Tests:**

Via Developer Tools UI:
1. Navigate to Super Forms > Developer Tools
2. Scroll to "Migration Controls > Integration Tests"
3. Select test data source (Programmatic/CSV/XML)
4. If CSV/XML, choose import file from dropdown
5. Select test to run from dropdown
6. Click "Run Selected Test"
7. View results in test output panel

Via WP-CLI:
```bash
# Run all tests with programmatic data
wp eval-file test/scripts/test-migration-integration.php

# Run specific test with CSV import
# (requires implementing CLI argument passing)
```

**CSV Test Data:**
Preloaded test files are available on the development server:
- `superforms-test-data-3943-entries.csv` (3.4 MB, 3,943 entries)
- `superforms-test-data-3596-entries.csv` (2.8 MB, 3,596 entries)
- `superforms-test-data-26581-entries.csv` (18 MB, 26,581 entries)

**Test Safety:**
- Only runs when `DEBUG_SF` is true
- Only runs on dev/localhost environments (hostname whitelist)
- All test data tagged with `_super_test_entry` meta
- Automatic cleanup after each test
- Independent test suites with isolated data
- Tests use temporary data, don't affect production entries

**Test Output:**
- Real-time progress logging
- Pass/fail status for each test
- Detailed error messages on failure
- Execution time tracking
- Test cleanup confirmation

### Trigger System Tests (since 6.5.0)

**Location:** `tests/triggers/`
**Framework:** PHPUnit 9.5 with WordPress test suite

**Test Files:**
- `test-performance.php` - Performance benchmarks for trigger lookup and execution
- `test-event-firing.php` - Event integration tests (23 test methods)
- `test-trigger-registry.php` - Registry registration and retrieval tests
- `test-trigger-executor.php` - Executor logic and error handling
- `test-trigger-scheduler.php` - Action Scheduler integration tests (Phase 2)
- `test-logging-system.php` - Logger, Debugger, Performance, Compliance tests (Phase 3)
- `test-spam-detector.php` - Spam detection action tests
- `test-entry-dal.php` - Entry DAL CRUD, meta methods, backwards compat tests (45 test methods)
- `test-session-dal.php` - Session DAL tests (client token recovery, anonymous session matching)
- `test-session-cleanup.php` - Session cleanup tests (abandoned detection, expiration cleanup, event firing, batch processing)
- `class-action-test-case.php` - Base test class with common utilities
- `actions/test-action-log-message.php` - Log message action tests (12 test methods)
- `actions/test-action-send-email.php` - Send email action tests (10 test methods)
- `actions/test-action-http-request.php` - HTTP request action tests (~50 test methods, wildcards, modifiers)
- `test-http-request-templates.php` - HTTP request template tests

**PHPUnit Testsuites** (defined in `phpunit.xml`):
- `triggers` - All trigger/action system unit tests in `tests/triggers/`
- `integration` - Integration tests in `tests/integration/`
- `Super Forms Test Suite` - EAV migration tests (excludes triggers/integration folders)

**Important:** Use `--testsuite` flag rather than running test files directly with hyphenated names (PHPUnit class naming quirk).

**Running Trigger Tests:**

Via shell script:
```bash
# From project root
./run-trigger-tests.sh
```

Via PHPUnit directly:
```bash
# Run all trigger tests (recommended)
./vendor/bin/phpunit --testsuite triggers

# Run specific test file
./vendor/bin/phpunit tests/triggers/test-performance.php

# Run specific test method
./vendor/bin/phpunit --filter test_trigger_lookup_performance tests/triggers/test-performance.php
```

**Performance Benchmarks:**
- Trigger lookup: <50ms for 100+ triggers
- Condition evaluation: <10ms for nested groups
- Action execution: Varies by action type (log: <1ms, email: ~50ms, webhook: varies)
- Event firing overhead: <100ms total

**Test Database Logging:**
The trigger tests use `SUPER_Test_DB_Logger` for persistent test result logging:
- Results stored in `wp_superforms_test_log` table
- Inspect results with: `./inspect-test-db.sh`
- See `tests/TEST_DATABASE.md` for documentation

**Developer Tools Integration:**
Section 8 "Trigger System Testing" in Developer Tools provides manual testing:
- Fire test events with custom context data
- View execution logs
- Clear trigger logs
- Test specific triggers by ID

**AJAX Endpoints for Testing:**
- `super_dev_test_trigger` - Fire a specific trigger with test data
- `super_dev_get_trigger_logs` - Retrieve execution logs
- `super_dev_clear_trigger_logs` - Clear all trigger logs

### Planned Testing Stack

**Unit Tests (PHP):**
- PHPUnit for WordPress plugin unit testing
- Mock WordPress functions
- Test business logic in isolation

**Integration Tests (PHP):**
- WordPress test suite
- Test database operations
- Test WordPress hooks/filters

**JavaScript Tests:**
- Jest for React components
- Test component rendering
- Test user interactions
- Test state management

**E2E Tests:**
- Playwright or Cypress
- Test critical user flows
- Test form submissions
- Test admin workflows

## Testing Checklist Template

Copy this for each feature/bug fix:

```
## Feature/Bug: [Description]

### Pre-Test Setup
- [ ] wp-env running at localhost:8888
- [ ] WP_DEBUG enabled in wp-config.php
- [ ] Query Monitor plugin active
- [ ] Browser DevTools open (Console + Network tabs)
- [ ] debug.log accessible and cleared

### Frontend Testing
- [ ] Form renders correctly
- [ ] Form submits successfully
- [ ] Validation works
- [ ] No JavaScript errors
- [ ] No PHP errors
- [ ] Mobile responsive

### Backend Testing
- [ ] Admin page loads
- [ ] Settings save correctly
- [ ] AJAX operations work
- [ ] No JavaScript errors
- [ ] No PHP errors

### Database Testing
- [ ] Data saves correctly
- [ ] Queries use prepared statements
- [ ] No duplicate queries
- [ ] Indexes used
- [ ] No SQL errors

### Security Testing
- [ ] Nonces verified
- [ ] Capabilities checked
- [ ] Input sanitized
- [ ] Output escaped
- [ ] No XSS vulnerabilities
- [ ] No SQL injection vulnerabilities

### Performance Testing
- [ ] Page load <2 seconds
- [ ] Query count <50
- [ ] No slow queries (>0.1s)
- [ ] Memory usage <64MB

### Regression Testing
- [ ] Related features still work
- [ ] No new bugs introduced
- [ ] Previous fixes not broken

### Cross-Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile browsers

### Final Verification
- [ ] Feature works as expected
- [ ] All tests passed
- [ ] No errors in logs
- [ ] Code committed
- [ ] Documentation updated

**Tested by:** [Your Name]
**Date:** [YYYY-MM-DD]
**Result:** PASS/FAIL
**Notes:** [Any observations or issues]
```

## Debugging Failed Tests

### When Tests Fail

1. **Don't guess** - Read the actual error message
2. **Check logs** - debug.log, browser console, network tab
3. **Isolate issue** - Disable other plugins, use default theme
4. **Reproduce** - Can you trigger the bug consistently?
5. **Document** - Record exact steps to reproduce

### Common Test Failure Causes

**JavaScript Errors:**
- Missing dependencies (jQuery not loaded)
- Nonce expired (refresh page)
- AJAX URL incorrect (check `ajaxurl` variable)
- Syntax errors (check console)

**PHP Errors:**
- Missing file includes
- Undefined functions/classes
- Database connection errors
- Permission issues

**Database Errors:**
- Table doesn't exist (run migrations)
- Column missing (check schema version)
- SQL syntax error (check prepared statements)
- Duplicate entry (unique constraint violation)

**Performance Issues:**
- N+1 query problem (use bulk queries)
- Missing indexes (add to schema)
- Large dataset (add pagination)
- Slow query (optimize SQL)

## Test Coverage Goals

**Critical Paths (Must Test):**
- Form submission (frontend)
- Entry creation/editing (admin)
- Migration process
- Data export (CSV)
- Settings save

**Important Features (Should Test):**
- Form builder operations
- Conditional logic
- Email notifications
- Payment integrations
- Add-on functionality

**Nice-to-Have (Can Test):**
- UI animations
- Edge cases
- Error messages
- Loading states

## Testing Best Practices

1. **Test in clean environment** (fresh wp-env instance)
2. **Test with real data** (not just test entries)
3. **Test edge cases** (empty fields, long strings, special characters)
4. **Test error conditions** (network failure, database error)
5. **Test across user roles** (admin, editor, subscriber)
6. **Document test results** (what you tested, what worked/failed)
7. **Automate repetitive tests** (use WP-CLI scripts)

## Code Review Checklist

Before committing code:

- [ ] Code follows WordPress Coding Standards
- [ ] All functions have PHPDoc comments
- [ ] Input sanitized with WordPress functions
- [ ] Output escaped with WordPress functions
- [ ] Nonces verified on forms/AJAX
- [ ] Capabilities checked for admin actions
- [ ] Database queries use prepared statements
- [ ] No direct SQL interpolation
- [ ] Translation-ready strings (proper text domain)
- [ ] No hardcoded URLs or paths
- [ ] Follows project naming conventions (super_* prefix)
- [ ] JSHint passes (`npm run jshint`)
- [ ] ESLint passes (when configured)
- [ ] Tested in wp-env environment
- [ ] No errors in debug.log
- [ ] No JavaScript console errors
- [ ] Performance acceptable (Query Monitor)
- [ ] Documentation updated
- [ ] Commit message describes change clearly
