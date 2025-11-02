---
name: 04-automated-verification
parent: h-implement-developer-tools-page
status: pending
created: 2025-11-02
---

# Phase 4: Automated Verification

## Goal

Implement 10 automated tests that verify data integrity after migration. These tests validate that the EAV storage system preserves all data exactly as it existed in the serialized format, catching any migration bugs or data corruption issues.

## Success Criteria

- [ ] All 10 verification tests implemented and functional
- [ ] Tests can run individually or all together
- [ ] Pass/fail status displayed with timing for each test
- [ ] Detailed error reporting shows specific mismatches
- [ ] Summary displays total passed/failed/not run counts
- [ ] Test results exportable as JSON
- [ ] Test results exportable as CSV
- [ ] Progress updates during long-running tests
- [ ] Tests validate entries exist in both storage systems during migration
- [ ] Clear error messages identify which entries failed and why

## The 10 Verification Tests

### 1. Data Integrity (EAV ↔ Serialized)
Compare all field data between EAV and serialized storage for every entry.

### 2. Field Count Match
Verify each entry has the same number of fields in both storage methods.

### 3. Field Values Match
Verify field values are identical (normalized for arrays/objects).

### 4. CSV Export Byte-Comparison
Export entries using both methods, compare MD5 hashes of resulting CSV files.

### 5. CSV Import Roundtrip
Export from serialized, import, export from EAV, compare files.

### 6. Listings Query Accuracy
Run same filter query on both storage methods, verify same entry IDs returned.

### 7. Search Query Accuracy
Search for keyword in both storage methods, verify same results.

### 8. Bulk Fetch Consistency
Fetch 100 entries via bulk method, verify against individual fetches.

### 9. Empty Entry Handling
Verify entries with no data or only empty fields migrate correctly.

### 10. Special Characters Preservation
Verify UTF-8, emoji, and special characters preserved exactly.

## Implementation Requirements

### Files to Create/Modify

1. **`/src/includes/class-developer-tools.php`** - Add verification test methods
2. **`/src/includes/class-ajax.php`** - Add AJAX handler:
   - `dev_run_verification` - Run selected tests
3. **`/src/includes/admin/views/page-developer-tools.php`** - Add verification UI section

## Technical Specifications

### Test 1: Data Integrity (EAV ↔ Serialized)

Uses existing `SUPER_Data_Access::validate_entry_integrity()` method:

```php
public static function test_data_integrity($entry_ids = null) {
    global $wpdb;

    // Get all entry IDs if not provided
    if (empty($entry_ids)) {
        $entry_ids = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'super_contact_entry'
            AND post_status IN ('publish', 'super_read', 'super_unread')
            LIMIT 1000
        ");
    }

    $start_time = microtime(true);
    $result = SUPER_Data_Access::bulk_validate_integrity($entry_ids);
    $end_time = microtime(true);

    return array(
        'test' => 'data_integrity',
        'passed' => ($result['invalid_count'] === 0),
        'total_checked' => $result['total_checked'],
        'valid' => $result['valid_count'],
        'invalid' => $result['invalid_count'],
        'errors' => $result['errors'],
        'time_ms' => round(($end_time - $start_time) * 1000, 2),
        'message' => $result['invalid_count'] === 0
            ? 'All entries match between EAV and serialized'
            : $result['invalid_count'] . ' entries have mismatches'
    );
}
```

### Test 2: Field Count Match

```php
public static function test_field_count_match($entry_ids = null) {
    global $wpdb;

    if (empty($entry_ids)) {
        $entry_ids = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'super_contact_entry'
            LIMIT 1000
        ");
    }

    $start_time = microtime(true);
    $mismatches = array();

    foreach ($entry_ids as $entry_id) {
        // Get from serialized
        $serialized_data = get_post_meta($entry_id, '_super_contact_entry_data', true);
        $serialized_data = maybe_unserialize($serialized_data);
        $serialized_count = is_array($serialized_data) ? count($serialized_data) : 0;

        // Get from EAV
        $eav_data = SUPER_Data_Access::get_entry_data($entry_id);
        $eav_count = is_array($eav_data) && !is_wp_error($eav_data) ? count($eav_data) : 0;

        if ($serialized_count !== $eav_count) {
            $mismatches[$entry_id] = array(
                'serialized_count' => $serialized_count,
                'eav_count' => $eav_count
            );
        }
    }

    $end_time = microtime(true);

    return array(
        'test' => 'field_count_match',
        'passed' => empty($mismatches),
        'total_checked' => count($entry_ids),
        'mismatches' => count($mismatches),
        'errors' => $mismatches,
        'time_ms' => round(($end_time - $start_time) * 1000, 2),
        'message' => empty($mismatches)
            ? 'All entries have matching field counts'
            : count($mismatches) . ' entries have field count mismatches'
    );
}
```

### Test 3: Field Values Match

```php
public static function test_field_values_match($entry_ids = null) {
    // Similar to test_data_integrity but focuses on value comparison
    // Normalizes arrays and objects for comparison
    // Reports specific field-level mismatches
}
```

### Test 4: CSV Export Byte-Comparison

```php
public static function test_csv_export_comparison($entry_ids = null) {
    global $wpdb;

    if (empty($entry_ids)) {
        $entry_ids = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'super_contact_entry'
            LIMIT 100
        ");
    }

    $start_time = microtime(true);

    // Force serialized storage temporarily
    add_filter('superforms_force_serialized_storage', '__return_true');
    $csv_serialized = self::generate_csv_export($entry_ids);
    remove_filter('superforms_force_serialized_storage', '__return_true');

    // Force EAV storage temporarily
    add_filter('superforms_force_eav_storage', '__return_true');
    $csv_eav = self::generate_csv_export($entry_ids);
    remove_filter('superforms_force_eav_storage', '__return_true');

    $end_time = microtime(true);

    // Compare MD5 hashes
    $hash_serialized = md5($csv_serialized);
    $hash_eav = md5($csv_eav);
    $match = ($hash_serialized === $hash_eav);

    return array(
        'test' => 'csv_export_comparison',
        'passed' => $match,
        'total_checked' => count($entry_ids),
        'hash_serialized' => $hash_serialized,
        'hash_eav' => $hash_eav,
        'time_ms' => round(($end_time - $start_time) * 1000, 2),
        'message' => $match
            ? 'CSV exports are identical'
            : 'CSV exports differ (hashes do not match)'
    );
}

private static function generate_csv_export($entry_ids) {
    $csv_data = array();

    // Get all field names
    $field_names = array();
    foreach ($entry_ids as $entry_id) {
        $data = SUPER_Data_Access::get_entry_data($entry_id);
        if (is_array($data)) {
            $field_names = array_merge($field_names, array_keys($data));
        }
    }
    $field_names = array_unique($field_names);
    sort($field_names);

    // Header row
    $csv_data[] = implode(',', $field_names);

    // Data rows
    foreach ($entry_ids as $entry_id) {
        $data = SUPER_Data_Access::get_entry_data($entry_id);
        $row = array();
        foreach ($field_names as $field) {
            $value = isset($data[$field]['value']) ? $data[$field]['value'] : '';
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $row[] = '"' . str_replace('"', '""', $value) . '"';
        }
        $csv_data[] = implode(',', $row);
    }

    return implode("\n", $csv_data);
}
```

### Test 5: CSV Import Roundtrip

```php
public static function test_csv_import_roundtrip() {
    // 1. Export 10 entries to CSV from serialized
    // 2. Delete the entries
    // 3. Import from CSV (creates in EAV if migrated)
    // 4. Export again
    // 5. Compare CSVs byte-for-byte
}
```

### Test 6: Listings Query Accuracy

```php
public static function test_listings_query_accuracy() {
    global $wpdb;

    $start_time = microtime(true);

    // Test query: Find entries where email contains "@test.com"
    $field_name = 'email';
    $field_value = '@test.com';

    // Serialized query (old method with SUBSTRING_INDEX)
    add_filter('superforms_force_serialized_storage', '__return_true');
    $serialized_ids = $wpdb->get_col("
        SELECT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
        WHERE p.post_type = 'super_contact_entry'
        AND m.meta_key = '_super_contact_entry_data'
        AND m.meta_value LIKE '%{$field_value}%'
    ");
    remove_filter('superforms_force_serialized_storage', '__return_true');

    // EAV query (new method with indexed JOIN)
    add_filter('superforms_force_eav_storage', '__return_true');
    $eav_ids = $wpdb->get_col("
        SELECT DISTINCT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->prefix}superforms_entry_data eav ON eav.entry_id = p.ID
        WHERE p.post_type = 'super_contact_entry'
        AND eav.field_name = '{$field_name}'
        AND eav.field_value LIKE '%{$field_value}%'
    ");
    remove_filter('superforms_force_eav_storage', '__return_true');

    $end_time = microtime(true);

    // Compare results
    sort($serialized_ids);
    sort($eav_ids);
    $match = ($serialized_ids === $eav_ids);

    $only_in_serialized = array_diff($serialized_ids, $eav_ids);
    $only_in_eav = array_diff($eav_ids, $serialized_ids);

    return array(
        'test' => 'listings_query_accuracy',
        'passed' => $match,
        'serialized_count' => count($serialized_ids),
        'eav_count' => count($eav_ids),
        'only_in_serialized' => $only_in_serialized,
        'only_in_eav' => $only_in_eav,
        'time_ms' => round(($end_time - $start_time) * 1000, 2),
        'message' => $match
            ? 'Query results are identical'
            : 'Query results differ: ' . count($only_in_serialized) . ' only in serialized, ' . count($only_in_eav) . ' only in EAV'
    );
}
```

### Test 7: Search Query Accuracy

```php
public static function test_search_query_accuracy($keyword = 'test') {
    // Similar to listings test but uses LIKE search
    // Compares results from serialized vs EAV search
}
```

### Test 8: Bulk Fetch Consistency

```php
public static function test_bulk_fetch_consistency() {
    global $wpdb;

    $entry_ids = $wpdb->get_col("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'super_contact_entry'
        LIMIT 100
    ");

    $start_time = microtime(true);

    // Bulk fetch
    $bulk_data = SUPER_Data_Access::get_bulk_entry_data($entry_ids);

    // Individual fetches
    $individual_data = array();
    foreach ($entry_ids as $entry_id) {
        $individual_data[$entry_id] = SUPER_Data_Access::get_entry_data($entry_id);
    }

    $end_time = microtime(true);

    // Compare
    $mismatches = array();
    foreach ($entry_ids as $entry_id) {
        if ($bulk_data[$entry_id] != $individual_data[$entry_id]) {
            $mismatches[] = $entry_id;
        }
    }

    return array(
        'test' => 'bulk_fetch_consistency',
        'passed' => empty($mismatches),
        'total_checked' => count($entry_ids),
        'mismatches' => count($mismatches),
        'errors' => $mismatches,
        'time_ms' => round(($end_time - $start_time) * 1000, 2),
        'message' => empty($mismatches)
            ? 'Bulk fetch matches individual fetches'
            : count($mismatches) . ' entries differ between bulk and individual fetch'
    );
}
```

### Test 9: Empty Entry Handling

```php
public static function test_empty_entry_handling() {
    // Create entry with no fields
    // Create entry with only empty fields
    // Migrate
    // Verify both methods return same empty/null values
}
```

### Test 10: Special Characters Preservation

```php
public static function test_special_characters_preservation() {
    $test_strings = array(
        'José Müller',           // Accents
        '中文名字',               // Chinese
        'مرحبا',                 // Arabic
        '🚀 🎉 ❤️',             // Emoji
        '®™©',                  // Symbols
        'Line1\nLine2',         // Newlines
        '"Quoted"',             // Quotes
        "Tab\tSeparated"        // Tabs
    );

    // Create test entries with special characters
    // Migrate
    // Verify exact character preservation
}
```

### AJAX Handler

```php
public static function dev_run_verification() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $tests = isset($_POST['tests']) ? (array) $_POST['tests'] : array();
    $entry_limit = isset($_POST['entry_limit']) ? absint($_POST['entry_limit']) : 100;

    if (empty($tests)) {
        wp_send_json_error(array('message' => 'No tests selected'));
    }

    $results = array();
    foreach ($tests as $test) {
        $method = 'test_' . sanitize_key($test);
        if (method_exists('SUPER_Developer_Tools', $method)) {
            $results[$test] = call_user_func(array('SUPER_Developer_Tools', $method), null);
        }
    }

    // Calculate summary
    $passed = 0;
    $failed = 0;
    foreach ($results as $result) {
        if ($result['passed']) {
            $passed++;
        } else {
            $failed++;
        }
    }

    wp_send_json_success(array(
        'results' => $results,
        'summary' => array(
            'passed' => $passed,
            'failed' => $failed,
            'total' => count($results)
        )
    ));
}
```

### UI Section HTML

```php
<!-- Automated Verification Section -->
<div class="super-devtools-section">
    <h2><?php echo esc_html__('3. Automated Verification', 'super-forms'); ?></h2>
    <p><?php echo esc_html__('Run automated tests to verify data integrity after migration.', 'super-forms'); ?></p>

    <p>
        <button id="run-all-tests-btn" class="button button-primary">
            <?php echo esc_html__('▶️ Run All Tests', 'super-forms'); ?>
        </button>
        <button id="run-selected-tests-btn" class="button button-secondary">
            <?php echo esc_html__('Run Selected Tests', 'super-forms'); ?>
        </button>
    </p>

    <h3><?php echo esc_html__('Tests:', 'super-forms'); ?></h3>
    <table class="widefat">
        <thead>
            <tr>
                <th style="width: 30px;"><input type="checkbox" id="select-all-tests"></th>
                <th><?php echo esc_html__('Test', 'super-forms'); ?></th>
                <th style="width: 100px;"><?php echo esc_html__('Status', 'super-forms'); ?></th>
                <th style="width: 100px;"><?php echo esc_html__('Time', 'super-forms'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><input type="checkbox" name="tests[]" value="data_integrity" checked></td>
                <td>Data Integrity (EAV ↔ Serialized)</td>
                <td class="test-status" data-test="data_integrity">⏸️ Idle</td>
                <td class="test-time" data-test="data_integrity">--</td>
            </tr>
            <tr>
                <td><input type="checkbox" name="tests[]" value="field_count_match" checked></td>
                <td>Field Count Match</td>
                <td class="test-status" data-test="field_count_match">⏸️ Idle</td>
                <td class="test-time" data-test="field_count_match">--</td>
            </tr>
            <tr>
                <td><input type="checkbox" name="tests[]" value="field_values_match" checked></td>
                <td>Field Values Match</td>
                <td class="test-status" data-test="field_values_match">⏸️ Idle</td>
                <td class="test-time" data-test="field_values_match">--</td>
            </tr>
            <tr>
                <td><input type="checkbox" name="tests[]" value="csv_export_comparison" checked></td>
                <td>CSV Export Byte-Comparison</td>
                <td class="test-status" data-test="csv_export_comparison">⏸️ Idle</td>
                <td class="test-time" data-test="csv_export_comparison">--</td>
            </tr>
            <tr>
                <td><input type="checkbox" name="tests[]" value="csv_import_roundtrip" checked></td>
                <td>CSV Import Roundtrip</td>
                <td class="test-status" data-test="csv_import_roundtrip">⏸️ Idle</td>
                <td class="test-time" data-test="csv_import_roundtrip">--</td>
            </tr>
            <tr>
                <td><input type="checkbox" name="tests[]" value="listings_query_accuracy" checked></td>
                <td>Listings Query Accuracy</td>
                <td class="test-status" data-test="listings_query_accuracy">⏸️ Idle</td>
                <td class="test-time" data-test="listings_query_accuracy">--</td>
            </tr>
            <tr>
                <td><input type="checkbox" name="tests[]" value="search_query_accuracy" checked></td>
                <td>Search Query Accuracy</td>
                <td class="test-status" data-test="search_query_accuracy">⏸️ Idle</td>
                <td class="test-time" data-test="search_query_accuracy">--</td>
            </tr>
            <tr>
                <td><input type="checkbox" name="tests[]" value="bulk_fetch_consistency" checked></td>
                <td>Bulk Fetch Consistency</td>
                <td class="test-status" data-test="bulk_fetch_consistency">⏸️ Idle</td>
                <td class="test-time" data-test="bulk_fetch_consistency">--</td>
            </tr>
            <tr>
                <td><input type="checkbox" name="tests[]" value="empty_entry_handling" checked></td>
                <td>Empty Entry Handling</td>
                <td class="test-status" data-test="empty_entry_handling">⏸️ Idle</td>
                <td class="test-time" data-test="empty_entry_handling">--</td>
            </tr>
            <tr>
                <td><input type="checkbox" name="tests[]" value="special_characters_preservation" checked></td>
                <td>Special Characters Preservation</td>
                <td class="test-status" data-test="special_characters_preservation">⏸️ Idle</td>
                <td class="test-time" data-test="special_characters_preservation">--</td>
            </tr>
        </tbody>
    </table>

    <p class="verification-summary">
        <strong><?php echo esc_html__('Summary:', 'super-forms'); ?></strong>
        <span class="summary-text">0/10 passed, 0 failed, 10 not run</span>
    </p>

    <div class="verification-results"></div>

    <p>
        <button id="download-test-report-json" class="button button-secondary" disabled>
            <?php echo esc_html__('Download Test Report (JSON)', 'super-forms'); ?>
        </button>
        <button id="export-test-report-csv" class="button button-secondary" disabled>
            <?php echo esc_html__('Export to CSV', 'super-forms'); ?>
        </button>
    </p>
</div>
```

### JavaScript Implementation

```javascript
// Run all tests
$('#run-all-tests-btn').on('click', function() {
    $('input[name="tests[]"]').prop('checked', true);
    runVerificationTests();
});

// Run selected tests
$('#run-selected-tests-btn').on('click', function() {
    runVerificationTests();
});

// Select all tests
$('#select-all-tests').on('change', function() {
    $('input[name="tests[]"]').prop('checked', $(this).is(':checked'));
});

// Run verification tests
function runVerificationTests() {
    var selectedTests = [];
    $('input[name="tests[]"]:checked').each(function() {
        selectedTests.push($(this).val());
    });

    if (selectedTests.length === 0) {
        alert('Please select at least one test');
        return;
    }

    // Reset UI
    $('.test-status').text('⏳ Running...');
    $('.test-time').text('--');
    $('.verification-results').empty();
    $('#download-test-report-json, #export-test-report-csv').prop('disabled', true);

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'super_dev_run_verification',
            security: devtoolsNonce,
            tests: selectedTests
        },
        success: function(response) {
            if (response.success) {
                displayVerificationResults(response.data);
            } else {
                alert('Error: ' + response.data.message);
            }
        }
    });
}

// Display verification results
function displayVerificationResults(data) {
    var results = data.results;
    var summary = data.summary;

    // Update test statuses
    $.each(results, function(test, result) {
        var statusText = result.passed ? '✓ Passed' : '✗ Failed';
        var statusClass = result.passed ? 'test-passed' : 'test-failed';

        $('.test-status[data-test="' + test + '"]')
            .text(statusText)
            .removeClass('test-passed test-failed')
            .addClass(statusClass);

        $('.test-time[data-test="' + test + '"]').text(result.time_ms + 'ms');
    });

    // Update summary
    $('.summary-text').text(
        summary.passed + '/' + summary.total + ' passed, ' +
        summary.failed + ' failed, ' +
        (10 - summary.total) + ' not run'
    );

    // Display detailed results
    var resultsHtml = '<h3>Detailed Results:</h3>';
    $.each(results, function(test, result) {
        resultsHtml += '<div class="verification-result ' + (result.passed ? 'result-pass' : 'result-fail') + '">';
        resultsHtml += '<strong>' + (result.passed ? '✓' : '✗') + ' ' + test + '</strong>: ' + result.message;

        if (!result.passed && result.errors) {
            resultsHtml += '<ul>';
            if (Array.isArray(result.errors)) {
                $.each(result.errors, function(i, error) {
                    resultsHtml += '<li>' + error + '</li>';
                });
            } else {
                $.each(result.errors, function(entry_id, error) {
                    resultsHtml += '<li>Entry #' + entry_id + ': ' + JSON.stringify(error) + '</li>';
                });
            }
            resultsHtml += '</ul>';
        }

        resultsHtml += '</div>';
    });

    $('.verification-results').html(resultsHtml);

    // Enable export buttons
    $('#download-test-report-json, #export-test-report-csv').prop('disabled', false);

    // Store results for export
    window.verificationResults = data;
}

// Download JSON report
$('#download-test-report-json').on('click', function() {
    var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(window.verificationResults, null, 2));
    var downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "verification-report.json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
});
```

## Testing Requirements

1. **All Tests Pass** - With valid migrated data, all 10 tests should pass
2. **Individual Test Run** - Each test can run independently
3. **Batch Test Run** - All tests can run together
4. **Error Detection** - Tests correctly identify data mismatches
5. **Timing Accuracy** - Time measurements are reasonable
6. **Export JSON** - JSON report downloads with all test data
7. **Export CSV** - CSV export contains summary and errors

## Estimated Time

**3-4 hours** for implementation and testing

## Dependencies

- Phase 1 (page foundation)
- Phase 2 (test data generator - for creating test scenarios)
- SUPER_Data_Access::validate_entry_integrity() (already exists)
- SUPER_Data_Access::bulk_validate_integrity() (already exists)

## Notes

- Tests use temporary filters to force storage method selection
- Some tests may require test data with specific patterns
- Error details help identify specific migration bugs
- CSV export tests validate export functionality integrity
- Special characters test covers internationalization
