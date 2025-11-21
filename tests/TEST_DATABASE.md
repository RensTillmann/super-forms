# Test Database Logging System

## Overview

The Super Forms test suite includes a sophisticated database logging system that captures all test execution data in a dedicated table: `wp_superforms_test_log`.

This allows you to:
- 📋 **Inspect events** - See exactly what events fired and in what order
- ✅ **Review assertions** - View all assertions with expected vs actual values
- 🐛 **Debug failures** - Access detailed error messages and stack traces
- ⏱️ **Analyze performance** - Track execution times for each test
- 🔍 **Query test data** - Use SQL to analyze test results

## How It Works

### 1. Automatic Setup
When tests run via `./run-trigger-tests.sh`:
1. Table `wp_superforms_test_log` is **dropped** (if exists)
2. Fresh table is **created** with clean schema
3. Test run ID (UUID) is generated
4. All test activity is logged to this table

### 2. What Gets Logged

**Events Fired:**
```php
// When SUPER_Trigger_Executor::fire_event() is called
log_type: 'event'
event_id: 'form.submitted'
context_data: {"form_id": 123, "entry_id": 456, ...}
```

**Assertions:**
```php
// When $this->assertEquals() is called
log_type: 'assertion'
assertion_type: 'assertEquals'
expected_value: '4'
actual_value: '4'
status: 'pass'
```

**Errors:**
```php
// When exceptions or errors occur
log_type: 'error'
error_message: 'Failed asserting that...'
stack_trace: '#0 /path/to/test.php(123)'
```

**Test Execution:**
```php
// Start and end of each test
log_type: 'test_start' / 'test_end'
test_class: 'Test_Event_Firing'
test_method: 'test_form_before_submit_fires'
execution_time_ms: 23.45
status: 'pass'
```

### 3. Viewing Test Data

After tests complete, use the inspection script:

```bash
# View summary
./inspect-test-db.sh --summary

# View all events
./inspect-test-db.sh --events

# View failures
./inspect-test-db.sh --failures

# View errors
./inspect-test-db.sh --errors

# View test times
./inspect-test-db.sh --tests
```

## Table Schema

```sql
CREATE TABLE wp_superforms_test_log (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_run_id VARCHAR(50) NOT NULL,           -- UUID for this test run
    test_class VARCHAR(100),                     -- Test_Event_Firing
    test_method VARCHAR(100),                    -- test_form_before_submit_fires
    log_type ENUM('run_start', 'run_end', 'test_start', 'test_end', 'event', 'assertion', 'error', 'performance'),
    event_id VARCHAR(100),                       -- 'form.submitted'
    context_data LONGTEXT,                       -- JSON event context
    assertion_type VARCHAR(50),                  -- 'assertEquals', 'assertCount'
    expected_value TEXT,                         -- Expected value
    actual_value TEXT,                           -- Actual value
    status ENUM('pass', 'fail', 'skip'),        -- Result status
    execution_time_ms FLOAT,                     -- Execution time in ms
    error_message TEXT,                          -- Error description
    stack_trace TEXT,                            -- Full stack trace
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX (test_run_id),
    INDEX (test_class, test_method),
    INDEX (log_type),
    INDEX (created_at)
);
```

## Usage Examples

### Example 1: View All Events from Latest Run

```bash
./inspect-test-db.sh --events
```

Output:
```
📋 All Events Fired (Latest Run)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
id  test_method                      event_id           context              created_at
15  test_form_before_submit_fires    form.before_submit {"form_id":123,...}  2025-01-21 12:34:56
16  test_form_submitted_fires        form.submitted     {"form_id":456,...}  2025-01-21 12:34:56
17  test_entry_created_fires         entry.created      {"entry_id":101,...} 2025-01-21 12:34:56
```

### Example 2: Custom SQL Queries

```bash
# Find all spam detection events
./inspect-test-db.sh --query "
SELECT test_method, event_id, context_data
FROM wp_superforms_test_log
WHERE event_id LIKE '%spam%'
"

# Count events by type
./inspect-test-db.sh --query "
SELECT event_id, COUNT(*) as count
FROM wp_superforms_test_log
WHERE log_type = 'event'
GROUP BY event_id
"

# Find slowest tests
./inspect-test-db.sh --query "
SELECT test_method, execution_time_ms
FROM wp_superforms_test_log
WHERE log_type = 'test_end'
ORDER BY execution_time_ms DESC
LIMIT 10
"
```

### Example 3: Verify Event Sequence

Check that events fire in correct order:

```bash
./inspect-test-db.sh --query "
SELECT
    test_method,
    event_id,
    created_at
FROM wp_superforms_test_log
WHERE test_method = 'test_multiple_events_fire_in_sequence'
AND log_type = 'event'
ORDER BY id ASC
"
```

Expected output:
```
test_method                            event_id            created_at
test_multiple_events_fire_in_sequence  form.before_submit  2025-01-21 12:34:56.001
test_multiple_events_fire_in_sequence  form.submitted      2025-01-21 12:34:56.002
test_multiple_events_fire_in_sequence  entry.created       2025-01-21 12:34:56.003
test_multiple_events_fire_in_sequence  entry.saved         2025-01-21 12:34:56.004
```

## Debugging Workflow

### Scenario: Test fails, need to investigate

**Step 1: Run tests**
```bash
./run-trigger-tests.sh
```

**Step 2: Check summary**
```bash
./inspect-test-db.sh --summary
```

Output shows:
```
Tests: 14 total, 13 passed, 1 failed
Assertions: 42 total, 41 passed, 1 failed
Events: 25 fired
```

**Step 3: View failures**
```bash
./inspect-test-db.sh --failures
```

Output shows:
```
test_method                      assertion_type  expected_value  actual_value  error_message
test_entry_created_fires         assertEquals    'entry.created' 'entry.saved' Failed asserting...
```

**Step 4: View events from that test**
```bash
./inspect-test-db.sh --query "
SELECT event_id, context_data
FROM wp_superforms_test_log
WHERE test_method = 'test_entry_created_fires'
AND log_type = 'event'
"
```

**Step 5: Check debug.log for additional context**
```bash
tail -f wp-content/debug.log
```

**Step 6: Fix code and re-run**
```bash
./run-trigger-tests.sh --filter=test_entry_created_fires
```

## Direct Database Access

If you prefer SQL over the inspection script:

```bash
# Connect to database
mysql -u root -p wordpress

# View latest test run
SELECT * FROM wp_superforms_test_log
WHERE test_run_id = (
    SELECT test_run_id FROM wp_superforms_test_log
    ORDER BY id DESC LIMIT 1
)
ORDER BY id ASC;

# Count events by type
SELECT event_id, COUNT(*) as count
FROM wp_superforms_test_log
WHERE log_type = 'event'
GROUP BY event_id;

# View test execution summary
SELECT
    test_class,
    test_method,
    status,
    execution_time_ms
FROM wp_superforms_test_log
WHERE log_type = 'test_end'
ORDER BY execution_time_ms DESC;
```

## Via SSH on Dev Server

```bash
# SSH in
ssh u2669-dvgugyayggy5@gnldm1014.siteground.biz -p 18765 -i ~/.ssh/id_sftp

# Navigate
cd /home/u2669-dvgugyayggy5/www/f4d.nl/public_html/dev/wp-content/plugins/super-forms

# Run tests
./run-trigger-tests.sh

# Inspect results
./inspect-test-db.sh --summary
./inspect-test-db.sh --events
./inspect-test-db.sh --failures
```

## Integration with Test Code

Tests automatically log to the database via `SUPER_Test_DB_Logger` class.

### Automatic Logging (Already Implemented)

Events and test lifecycle are logged automatically:

```php
class Test_Event_Firing extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();

        // Automatically logs test start
        SUPER_Test_DB_Logger::set_test_context(
            'Test_Event_Firing',
            $this->getName()
        );
    }

    public function capture_event($event_id, $context) {
        // Automatically logs each event
        SUPER_Test_DB_Logger::log_event($event_id, $context);
    }

    public function tearDown() {
        // Automatically logs test end
        SUPER_Test_DB_Logger::clear_test_context('pass');
        parent::tearDown();
    }
}
```

### Manual Logging (Optional)

You can add custom logging in tests:

```php
// Log custom assertion
SUPER_Test_DB_Logger::log_assertion(
    'assertEquals',
    $expected,
    $actual,
    $passed,
    'Verifying event fired'
);

// Log custom error
SUPER_Test_DB_Logger::log_error(
    'Something went wrong',
    debug_backtrace()
);

// Log performance metric
SUPER_Test_DB_Logger::log_performance(
    'event_firing_overhead',
    1.23,
    'ms'
);
```

## Maintenance

### Clear Old Test Data

The table is automatically dropped and recreated each test run, so no maintenance needed.

However, if you want to keep multiple test runs:

```bash
# Clear all except latest run
./inspect-test-db.sh --clear

# Or manually
mysql -u root -p wordpress -e "
DELETE FROM wp_superforms_test_log
WHERE test_run_id != (
    SELECT * FROM (
        SELECT test_run_id FROM wp_superforms_test_log
        ORDER BY id DESC LIMIT 1
    ) as t
)
"
```

### Backup Test Data

```bash
# Export test data for analysis
mysqldump -u root -p wordpress wp_superforms_test_log > test_data_backup.sql

# Import later
mysql -u root -p wordpress < test_data_backup.sql
```

## Best Practices

1. **Always run `./inspect-test-db.sh --summary` after tests** - Get quick overview
2. **Use `--events` to verify event firing order** - Catch sequence issues
3. **Check `--failures` first when debugging** - See what assertions failed
4. **Query context_data with JSON functions** - MySQL has JSON support:
   ```sql
   SELECT event_id, JSON_EXTRACT(context_data, '$.form_id') as form_id
   FROM wp_superforms_test_log
   WHERE log_type = 'event'
   ```
5. **Combine DB logs with debug.log** - Full picture of what happened

## Troubleshooting

### Table doesn't exist

Make sure tests ran at least once:
```bash
./run-trigger-tests.sh
```

### Can't connect to database

Check database credentials match WordPress config:
```bash
# View credentials
grep DB_ wp-config.php
```

### Empty results

Check that test run ID is correct:
```bash
./inspect-test-db.sh --query "
SELECT DISTINCT test_run_id, COUNT(*) as records
FROM wp_superforms_test_log
GROUP BY test_run_id
"
```

## Advanced: Query Templates

### Find events with specific context
```sql
SELECT * FROM wp_superforms_test_log
WHERE log_type = 'event'
AND context_data LIKE '%"form_id":123%';
```

### Execution time analysis
```sql
SELECT
    test_method,
    AVG(execution_time_ms) as avg_time,
    MIN(execution_time_ms) as min_time,
    MAX(execution_time_ms) as max_time
FROM wp_superforms_test_log
WHERE log_type = 'test_end'
GROUP BY test_method
ORDER BY avg_time DESC;
```

### Event firing frequency
```sql
SELECT
    event_id,
    COUNT(*) as times_fired,
    COUNT(DISTINCT test_method) as in_tests
FROM wp_superforms_test_log
WHERE log_type = 'event'
GROUP BY event_id;
```

## Summary

The test database logging system provides:
- ✅ Complete visibility into test execution
- ✅ Detailed event firing inspection
- ✅ Easy debugging with SQL queries
- ✅ Automatic cleanup (fresh start each run)
- ✅ Integration with existing test suite
- ✅ No performance impact on production code

Use it every time you run tests to ensure event firing works correctly! 🚀
