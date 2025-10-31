---
name: 01-test-suite-foundation
status: pending
created: 2025-10-31
---

# Build PHPUnit Test Suite Foundation

## Problem/Goal

Phase 30 research identified ZERO automated tests in the Super Forms codebase. Before implementing the EAV migration, we must build a comprehensive PHPUnit test suite to cover the 10 critical gaps identified in the research. This ensures we can verify migration correctness, prevent regressions, and validate data integrity.

**Why This Must Be First:**
- Cannot verify migration without tests
- Cannot prove backwards compatibility without tests
- Cannot validate performance improvements without benchmarks
- Risk of silent data corruption without automated verification

## Success Criteria

- [ ] PHPUnit installed and configured in `/test/phpunit/`
- [ ] Test bootstrap file loads WordPress test environment
- [ ] All 10 critical test gaps from Phase 30 have test coverage
- [ ] Tests can run via `composer test` command
- [ ] Tests pass on clean install with sample data
- [ ] Test coverage report generated (minimum 80% coverage for new code)
- [ ] CI/CD integration configured (GitHub Actions or similar)

## 10 Critical Test Gaps to Cover

### 1. Entry Data Serialization/Deserialization
**File**: `tests/test-entry-serialization.php`

Test that entry data survives serialize → unserialize cycle:
```php
public function test_entry_data_serialization_preserves_all_fields() {
    $original_data = array(
        'name' => array('value' => 'John Doe', 'label' => 'Name', 'type' => 'text'),
        'email' => array('value' => 'john@example.com', 'label' => 'Email', 'type' => 'email'),
    );

    $serialized = serialize($original_data);
    $unserialized = unserialize($serialized);

    $this->assertEquals($original_data, $unserialized);
}

public function test_corrupt_serialized_data_handling() {
    $corrupt_data = 'a:2:{s:4:"name";s:'; // Incomplete serialized string
    $result = @unserialize($corrupt_data);
    $this->assertFalse($result);
}
```

### 2. SUBSTRING_INDEX Query Performance Baseline
**File**: `tests/test-query-performance.php`

Benchmark current SUBSTRING_INDEX performance:
```php
public function test_listings_filter_performance_baseline() {
    // Create 8,100 test entries
    $this->create_test_entries(8100);

    $start = microtime(true);
    $results = $this->run_listings_filter_query('email', '%@example.com%');
    $duration = microtime(true) - $start;

    // Document baseline (should be 15-20 seconds)
    $this->assertGreaterThan(10, $duration, 'Baseline performance documented');

    // Store for comparison after EAV migration
    update_option('superforms_perf_baseline_listings', $duration);
}
```

### 3. Data Access Layer Compatibility
**File**: `tests/test-data-access-layer.php`

Verify Data Access Layer returns identical format:
```php
public function test_data_access_layer_matches_serialized_format() {
    $entry_id = $this->create_test_entry();

    // Old method
    $serialized_data = unserialize(get_post_meta($entry_id, '_super_contact_entry_data', true));

    // New method (will use EAV after migration)
    $dal_data = SUPER_Data_Access::get_entry_data($entry_id);

    $this->assertEquals($serialized_data, $dal_data);
}
```

### 4. Concurrent Entry Updates
**File**: `tests/test-concurrent-updates.php`

Test race conditions:
```php
public function test_concurrent_save_last_write_wins() {
    $entry_id = $this->create_test_entry(array('name' => 'Original'));

    // Simulate two simultaneous updates
    $data1 = array('name' => array('value' => 'Update 1'));
    $data2 = array('name' => array('value' => 'Update 2'));

    SUPER_Data_Access::save_entry_data($entry_id, $data1);
    SUPER_Data_Access::save_entry_data($entry_id, $data2);

    $result = SUPER_Data_Access::get_entry_data($entry_id);
    $this->assertEquals('Update 2', $result['name']['value']);
}
```

### 5. Field Value Length Limits
**File**: `tests/test-field-value-limits.php`

Test LONGTEXT boundaries:
```php
public function test_large_text_field_storage() {
    $large_text = str_repeat('A', 100000); // 100KB text

    $entry_id = $this->create_test_entry(array(
        'description' => array('value' => $large_text)
    ));

    $retrieved = SUPER_Data_Access::get_entry_data($entry_id);
    $this->assertEquals($large_text, $retrieved['description']['value']);
}
```

### 6. Dynamic Groups/Repeater Fields
**File**: `tests/test-repeater-fields.php`

Test nested array structures:
```php
public function test_repeater_field_reconstruction() {
    $nested_data = array(
        'customer' => array(
            'value' => array(
                0 => array(
                    'first_name' => array('value' => 'John'),
                    'last_name' => array('value' => 'Doe'),
                ),
                1 => array(
                    'first_name' => array('value' => 'Jane'),
                    'last_name' => array('value' => 'Smith'),
                ),
            ),
            'type' => 'dynamic',
        ),
    );

    $entry_id = $this->create_test_entry($nested_data);
    $retrieved = SUPER_Data_Access::get_entry_data($entry_id);

    $this->assertEquals($nested_data, $retrieved);
}
```

### 7. Search/Filter Query Rewrites
**File**: `tests/test-query-rewrites.php`

Verify query correctness:
```php
public function test_admin_search_finds_all_matches() {
    $this->create_test_entry(array('email' => array('value' => 'john@example.com')));
    $this->create_test_entry(array('email' => array('value' => 'jane@example.com')));
    $this->create_test_entry(array('email' => array('value' => 'bob@different.com')));

    $results = $this->run_admin_search('example.com');

    $this->assertCount(2, $results);
}
```

### 8. Serialized to EAV Conversion Accuracy
**File**: `tests/test-migration-accuracy.php`

Verify migration data integrity:
```php
public function test_migration_preserves_all_data() {
    $original_data = $this->create_complex_test_data();
    $entry_id = $this->create_test_entry($original_data);

    // Run migration for this entry
    $migration = new SUPER_Migration_Manager();
    $migration->migrate_single_entry($entry_id);

    // Verify checksums match
    $original_checksum = md5(serialize($original_data));
    $migrated_data = SUPER_Data_Access::get_entry_data($entry_id);
    $migrated_checksum = md5(serialize($migrated_data));

    $this->assertEquals($original_checksum, $migrated_checksum);
}
```

### 9. Memory Usage with Large Entries
**File**: `tests/test-memory-usage.php`

Test resource consumption:
```php
public function test_large_entry_memory_usage() {
    $memory_before = memory_get_usage();

    // Create entry with 100 fields
    $large_entry = array();
    for ($i = 0; $i < 100; $i++) {
        $large_entry["field_$i"] = array('value' => str_repeat('X', 1000));
    }

    $entry_id = $this->create_test_entry($large_entry);
    $retrieved = SUPER_Data_Access::get_entry_data($entry_id);

    $memory_after = memory_get_usage();
    $memory_used = $memory_after - $memory_before;

    // Should not exceed 10MB for single entry
    $this->assertLessThan(10 * 1024 * 1024, $memory_used);
}
```

### 10. Conditional Logic Field References
**File**: `tests/test-conditional-logic.php`

Verify conditional logic still works:
```php
public function test_conditional_logic_field_access() {
    $entry_data = array(
        'email' => array('value' => 'john@example.com'),
        'age' => array('value' => '25'),
    );

    $entry_id = $this->create_test_entry($entry_data);
    $data = SUPER_Data_Access::get_entry_data($entry_id);

    // Test conditional logic can access fields
    $result = SUPER_Common::conditional_compare_check(
        $data['age']['value'],
        '>',
        '18'
    );

    $this->assertTrue($result);
}
```

## Implementation Plan

### 1. Install PHPUnit
```bash
cd /root/go/src/github.com/RensTillmann/super-forms
composer require --dev phpunit/phpunit:^9.0
```

### 2. Create Test Directory Structure
```
test/
├── phpunit/
│   ├── bootstrap.php
│   ├── phpunit.xml
│   └── tests/
│       ├── test-entry-serialization.php
│       ├── test-query-performance.php
│       ├── test-data-access-layer.php
│       ├── test-concurrent-updates.php
│       ├── test-field-value-limits.php
│       ├── test-repeater-fields.php
│       ├── test-query-rewrites.php
│       ├── test-migration-accuracy.php
│       ├── test-memory-usage.php
│       └── test-conditional-logic.php
```

### 3. Create Bootstrap File
**File**: `test/phpunit/bootstrap.php`
```php
<?php
// Load WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname(dirname(__DIR__)) . '/src/super-forms.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

require $_tests_dir . '/includes/bootstrap.php';
```

### 4. Create PHPUnit Configuration
**File**: `test/phpunit/phpunit.xml`
```xml
<?xml version="1.0"?>
<phpunit bootstrap="bootstrap.php">
    <testsuites>
        <testsuite name="Super Forms Test Suite">
            <directory>./tests</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory suffix=".php">../../src</directory>
        </whitelist>
    </filter>
</phpunit>
```

### 5. Add Composer Test Script
**File**: `composer.json` (add to scripts section)
```json
{
    "scripts": {
        "test": "phpunit -c test/phpunit/phpunit.xml"
    }
}
```

## Test Data Generators

Create helper methods for generating test data:

**File**: `test/phpunit/tests/class-test-helpers.php`
```php
<?php
class SUPER_Test_Helpers extends WP_UnitTestCase {

    protected function create_test_entry($data = array()) {
        $defaults = array(
            'name' => array('value' => 'Test User', 'label' => 'Name', 'type' => 'text'),
            'email' => array('value' => 'test@example.com', 'label' => 'Email', 'type' => 'email'),
        );

        $entry_data = wp_parse_args($data, $defaults);

        $entry_id = wp_insert_post(array(
            'post_type' => 'super_contact_entry',
            'post_title' => 'Test Entry',
            'post_status' => 'publish',
        ));

        add_post_meta($entry_id, '_super_contact_entry_data', serialize($entry_data));

        return $entry_id;
    }

    protected function create_test_entries($count) {
        $entry_ids = array();
        for ($i = 0; $i < $count; $i++) {
            $entry_ids[] = $this->create_test_entry(array(
                'email' => array('value' => "user{$i}@example.com"),
            ));
        }
        return $entry_ids;
    }
}
```

## Dependencies

- WordPress Test Library (wp-tests-lib)
- PHPUnit 9.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+

## Estimated Effort

**5-7 days**
- Day 1-2: PHPUnit setup, bootstrap configuration
- Day 3-4: Write all 10 test files
- Day 5: Test data generators and helpers
- Day 6: CI/CD integration
- Day 7: Documentation and verification

## Related Research

- Phase 30: Testing Infrastructure (identified 10 critical gaps)
- Phase 6: Data Validation (corrupt serialized data handling)
- Phase 12: Dynamic Groups (repeater field testing requirements)

## Notes

This subtask MUST be completed before any migration code is written. The test suite will serve as:
1. Baseline performance documentation
2. Migration verification tool
3. Regression prevention
4. Continuous integration foundation
