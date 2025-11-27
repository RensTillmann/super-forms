# Trigger System Tests

This directory contains PHPUnit tests for the Super Forms trigger/action system.

## Test Files

- **test-event-firing.php** - Tests that all 34 events fire correctly (form, entry, file, payment)
- **test-trigger-registry.php** - Tests event/action registration
- **test-trigger-executor.php** - Tests trigger execution flow
- **test-trigger-scheduler.php** - Tests Action Scheduler integration (Phase 2)
- **test-logging-system.php** - Tests Logger, Debugger, Performance, Compliance classes (Phase 3)
- **test-api-security.php** - Tests Credentials, OAuth, Security, Permissions, API Keys classes (Phase 4)
- **test-spam-detector.php** - Tests spam detection integration
- **test-performance.php** - Performance benchmarks for trigger lookup and execution
- **test-http-request-templates.php** - Tests HTTP request template registration (Phase 5)
- **test-entry-dal.php** - Tests Entry DAL CRUD, meta methods, backwards compat (Phase 17, 45 tests)
- **test-session-dal.php** - Tests Session DAL operations (client token recovery, anonymous session matching)
- **class-action-test-case.php** - Base test class with common utilities
- **actions/test-action-log-message.php** - Log message action tests
- **actions/test-action-send-email.php** - Send email action tests
- **actions/test-action-http-request.php** - HTTP request action tests (wildcards, modifiers) (Phase 5)

## Running Tests

### Quick Start

From the Super Forms root directory:

```bash
# Run all trigger tests
./run-trigger-tests.sh

# Run with coverage report
./run-trigger-tests.sh --coverage

# Run specific test
./run-trigger-tests.sh --filter=test_form_before_submit_fires

# Run with verbose output
./run-trigger-tests.sh --verbose
```

### Via Composer

```bash
# Run all tests
composer test

# Run only trigger tests
vendor/bin/phpunit --testsuite "Triggers and Actions"

# Run with coverage
composer test:coverage
```

### Via SSH on Dev Server

```bash
# SSH into the server
ssh u2669-dvgugyayggy5@gnldm1014.siteground.biz -p 18765 -i ~/.ssh/id_sftp

# Navigate to plugin directory
cd /home/u2669-dvgugyayggy5/www/f4d.nl/public_html/dev/wp-content/plugins/super-forms

# Run tests
./run-trigger-tests.sh
```

## What Each Test File Covers

### test-event-firing.php

Tests that events fire correctly during form submission:

- ✅ `form.before_submit` fires with correct context
- ✅ `form.submitted` fires after validation
- ✅ `entry.created` fires after wp_insert_post()
- ✅ `entry.saved` fires for new and updated entries
- ✅ `entry.updated` fires only for updates
- ✅ `entry.status_changed` fires when status changes
- ✅ `form.spam_detected` fires on spam detection
- ✅ `form.validation_failed` fires on validation errors
- ✅ `form.duplicate_detected` fires on duplicate entries
- ✅ `file.uploaded` fires after file attachment
- ✅ Multiple events fire in correct sequence
- ✅ Event timestamps are sequential
- ✅ Event context includes all required fields
- ✅ WordPress action hooks fire alongside trigger events

**Total: 23 test methods**

### test-trigger-registry.php

Tests event and action registration:

- Event registration and retrieval
- Duplicate event detection
- Action registration
- Compatible actions lookup
- Event context validation

### test-trigger-executor.php

Tests trigger execution flow:

- Event firing
- Trigger matching
- Condition evaluation
- Action execution
- Execution logging

### test-trigger-scheduler.php

Tests Action Scheduler integration (Phase 2):

- Async action scheduling
- Delayed execution
- Retry mechanism with exponential backoff
- Queue management
- Failed action handling

### test-logging-system.php

Tests logging infrastructure (Phase 3):

- `SUPER_Trigger_Logger` - Log levels, database storage, console buffer
- `SUPER_Trigger_Debugger` - Debug data collection, event/trigger/action logging
- `SUPER_Trigger_Performance` - Timer start/end, memory tracking, slow execution detection
- `SUPER_Trigger_Compliance` - GDPR export/delete, PII scrubbing, audit trails

### test-api-security.php

Tests API security infrastructure (Phase 4):

- `SUPER_Trigger_Credentials` - AES-256-CBC encrypted credential storage and retrieval
- `SUPER_Trigger_OAuth` - OAuth 2.0 flows, PKCE support, token refresh, provider registration
- `SUPER_Trigger_Security` - Rate limiting, suspicious pattern detection, security event logging
- `SUPER_Trigger_Permissions` - WordPress capabilities, trigger ownership, scope-based access
- `SUPER_Trigger_API_Keys` - API key generation, validation, permission management, usage tracking

### test-session-dal.php

Tests Session DAL operations for progressive forms (Phase 1a Step 2):

- Session creation with `client_token` parameter
- Anonymous session recovery via `find_by_client_token()`
- `find_recoverable()` matching logic:
  - Logged-in users: Matches by `user_id`
  - Anonymous users: Matches by `client_token` (not IP)
- Session status transitions (draft → completed, draft → aborted)
- Session expiry and cleanup
- AJAX handler integration for session operations

### test-spam-detector.php

Tests multi-method spam detection system (Phase 1a Step 3):

- **Honeypot detection** - Tests 3 field variants (super_hp, website_url_hp, fax_number_hp)
- **Time-based detection** - Tests fast submissions (1s, 2s) and slow passes (5s, 10s)
- **IP blacklist** - Tests exact match, CIDR ranges (192.168.1.0/24), wildcards (192.168.1.*)
- **Keyword filtering** - Tests dual threshold logic (3+ matches OR 2+ unique keywords)
- **Akismet integration** - Mock tests (requires Akismet plugin in production)
- **Clean submissions** - Tests that legitimate submissions pass all checks
- **Form settings** - Tests per-form configuration and default settings
- **Event firing** - Tests `form.spam_detected` event with complete detection metadata
- **Session integration** - Tests time-based detection using session start timestamps

**Total: 25 test methods**

## Test Output Example

```
🧪 Super Forms Trigger System Tests
======================================

🚀 Running trigger tests...

PHPUnit 9.5.28 by Sebastian Bergmann and contributors.

Runtime:       PHP 7.4.33
Configuration: /path/to/phpunit.xml

............................                                     102 / 102 (100%)

Time: 00:01.500, Memory: 24.00 MB

OK (102 tests, 370 assertions)

✅ All tests passed!
```

## Test Requirements

### PHP Requirements
- PHP 7.4 or higher
- WordPress test environment (WP_PHPUNIT__DIR)
- Composer dependencies installed

### WordPress Requirements
- WordPress 6.4 or higher
- WordPress testing framework
- Database connection for integration tests

### Setup WordPress Test Environment

If not already set up:

```bash
# Install WordPress test suite
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Set environment variable
export WP_PHPUNIT__DIR=/tmp/wordpress-tests-lib
```

## Continuous Integration

These tests can be run automatically on:

- **Pre-commit hooks** - Via `composer pre-commit`
- **CI/CD pipelines** - GitHub Actions, GitLab CI, etc.
- **Local development** - Via `./run-trigger-tests.sh`

## Coverage Reports

Generate HTML coverage reports:

```bash
./run-trigger-tests.sh --coverage
```

View report:
```bash
open tests/coverage/triggers/index.html
```

## Debugging Failed Tests

### Verbose Output

```bash
./run-trigger-tests.sh --verbose
```

### Run Specific Test

```bash
./run-trigger-tests.sh --filter=test_form_before_submit_fires
```

### Check WordPress Logs

If tests fail due to WordPress errors:

```bash
tail -f /path/to/wordpress/wp-content/debug.log
```

## Adding New Tests

### Template for New Event Test

```php
/**
 * Test: your_event fires
 */
public function test_your_event_fires() {
    if ( class_exists( 'SUPER_Trigger_Executor' ) ) {
        SUPER_Trigger_Executor::fire_event(
            'your.event',
            array(
                'form_id' => 123,
                'data' => array('test' => 'value'),
            )
        );
    }

    $this->assertCount( 1, $this->fired_events );
    $this->assertEquals( 'your.event', $this->fired_events[0]['event_id'] );
}
```

### Test Naming Convention

- Test files: `test-{feature}.php`
- Test methods: `test_{what_it_tests}`
- Assertions: Use descriptive messages

## Performance Benchmarks

Expected performance for test suite:

- **Event firing tests:** < 0.5 seconds
- **Registry tests:** < 0.2 seconds
- **Executor tests:** < 0.3 seconds
- **Full suite:** < 1 second

If tests take longer, check for:
- Slow database queries
- Excessive event firing
- Memory leaks

## Troubleshooting

### "WordPress test environment not found"

Set the WP_PHPUNIT__DIR environment variable:
```bash
export WP_PHPUNIT__DIR=/path/to/wordpress-tests-lib
```

### "Class not found" errors

Ensure composer dependencies are installed:
```bash
composer install --dev
```

### "Database connection failed"

Check wp-tests-config.php database credentials:
```bash
cat tests/wp-tests-config.php
```

### Tests pass locally but fail on CI

Check:
- PHP version consistency
- WordPress version
- Database setup
- Environment variables

## Contributing

When adding new trigger functionality:

1. ✅ Write test first (TDD approach)
2. ✅ Implement feature
3. ✅ Run tests: `./run-trigger-tests.sh`
4. ✅ Ensure 100% pass rate
5. ✅ Check coverage: `./run-trigger-tests.sh --coverage`
6. ✅ Commit tests with feature code

## Related Documentation

- [Event Flow Documentation](../../sessions/tasks/h-implement-triggers-actions-extensibility/EVENT_FLOW_DOCUMENTATION.md)
- [Task Documentation](../../sessions/tasks/h-implement-triggers-actions-extensibility/README.md)

## Questions?

For questions about testing or the trigger system:
- Check documentation in `/sessions/tasks/h-implement-triggers-actions-extensibility/`
- Review existing test files for examples
- Contact the development team
