---
name: 05-performance-benchmarks
parent: h-implement-developer-tools-page
status: pending
created: 2025-11-02
---

# Phase 5: Performance Benchmarks

## Goal

Measure and validate the performance improvements of EAV storage over serialized storage. Provide visual proof of the claimed 30-60x improvements for listings/search and 10-100x improvements for CSV exports.

## Success Criteria

- [ ] CSV export benchmark measures both serialized and EAV methods
- [ ] Listings filter benchmark compares SUBSTRING_INDEX vs indexed JOIN
- [ ] Admin search benchmark compares serialized LIKE vs EAV indexed search
- [ ] Configurable entry count (10/100/1000/10000) for benchmarks
- [ ] Visual progress bars show relative performance
- [ ] Improvement multiplier calculated (e.g., "115.9x faster")
- [ ] Benchmark reports downloadable as JSON
- [ ] Comparison with previous runs available
- [ ] Results cached to prevent redundant benchmarking
- [ ] Timing accurate to milliseconds

## The 3 Performance Benchmarks

### 1. CSV Export Performance
Compare time to export N entries using serialized vs EAV storage.

**Expected Result**: 10-100x faster with EAV due to bulk query optimization.

### 2. Listings Filter Performance
Compare filtered query execution time (e.g., find entries where email = "@test.com").

**Expected Result**: 30-60x faster with EAV due to indexed JOINs vs SUBSTRING_INDEX.

### 3. Admin Search Performance
Compare search query execution time (e.g., keyword search across all fields).

**Expected Result**: 30-50x faster with EAV due to indexed field_value search.

## Implementation Requirements

### Files to Modify

1. **`/src/includes/class-developer-tools.php`** - Add benchmark methods
2. **`/src/includes/class-ajax.php`** - Add AJAX handler:
   - `dev_run_benchmarks` - Run selected benchmarks
3. **`/src/includes/admin/views/page-developer-tools.php`** - Add benchmarks UI section

## Technical Specifications

### Timing Wrapper Function

```php
private static function benchmark_operation($callback, $iterations = 1) {
    // Warm up (ignore first run)
    if (is_callable($callback)) {
        $callback();
    }

    // Actual timing
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }
    $end = microtime(true);

    return round(($end - $start) * 1000, 2); // Return milliseconds
}
```

### Benchmark 1: CSV Export Performance

```php
public static function benchmark_csv_export($entry_count = 100) {
    global $wpdb;

    // Get sample entry IDs
    $entry_ids = $wpdb->get_col($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'super_contact_entry'
        AND post_status IN ('publish', 'super_read', 'super_unread')
        ORDER BY ID ASC
        LIMIT %d
    ", $entry_count));

    if (count($entry_ids) < $entry_count) {
        return new WP_Error('insufficient_entries',
            sprintf('Only %d entries available, need %d', count($entry_ids), $entry_count));
    }

    // Benchmark serialized method (N+1 queries)
    add_filter('superforms_force_serialized_storage', '__return_true');
    $time_serialized = self::benchmark_operation(function() use ($entry_ids) {
        $data = array();
        foreach ($entry_ids as $entry_id) {
            // Simulates old export method
            $entry_data = get_post_meta($entry_id, '_super_contact_entry_data', true);
            $entry_data = maybe_unserialize($entry_data);
            $data[$entry_id] = $entry_data;
        }
        return $data;
    });
    remove_filter('superforms_force_serialized_storage', '__return_true');

    // Benchmark EAV method (single bulk query)
    add_filter('superforms_force_eav_storage', '__return_true');
    $time_eav = self::benchmark_operation(function() use ($entry_ids) {
        return SUPER_Data_Access::get_bulk_entry_data($entry_ids);
    });
    remove_filter('superforms_force_eav_storage', '__return_true');

    // Calculate improvement
    $improvement = $time_serialized > 0 ? round($time_serialized / $time_eav, 1) : 0;

    return array(
        'benchmark' => 'csv_export',
        'entry_count' => $entry_count,
        'time_serialized' => $time_serialized,
        'time_eav' => $time_eav,
        'improvement' => $improvement,
        'faster' => $improvement . 'x faster',
        'message' => sprintf(
            'CSV export: %dms (serialized) vs %dms (EAV) - %sx improvement',
            $time_serialized,
            $time_eav,
            $improvement
        )
    );
}
```

### Benchmark 2: Listings Filter Performance

```php
public static function benchmark_listings_filter($entry_count = 100) {
    global $wpdb;

    $field_name = 'email';
    $field_value = '@test.com';

    // Ensure we have entries with test email
    $available = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT entry_id)
        FROM {$wpdb->prefix}superforms_entry_data
        WHERE field_name = %s
        AND field_value LIKE %s
        LIMIT %d
    ", $field_name, '%' . $wpdb->esc_like($field_value) . '%', $entry_count));

    if ($available < $entry_count) {
        return new WP_Error('insufficient_test_data',
            sprintf('Only %d entries match criteria, need %d', $available, $entry_count));
    }

    // Benchmark serialized method (SUBSTRING_INDEX scan)
    $time_serialized = self::benchmark_operation(function() use ($wpdb, $field_name, $field_value, $entry_count) {
        $field_length = strlen($field_name);
        $results = $wpdb->get_col($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
            WHERE p.post_type = 'super_contact_entry'
            AND m.meta_key = '_super_contact_entry_data'
            AND SUBSTRING_INDEX(
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX(m.meta_value, 's:4:\"name\";s:%d:\"%s\";s:5:\"value\";', -1),
                    '\";s:', 1
                ),
                ':\"', -1
            ) LIKE %s
            LIMIT %d
        ", $field_length, $field_name, '%' . $wpdb->esc_like($field_value) . '%', $entry_count));
        return $results;
    });

    // Benchmark EAV method (indexed JOIN)
    $time_eav = self::benchmark_operation(function() use ($wpdb, $field_name, $field_value, $entry_count) {
        $results = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->prefix}superforms_entry_data eav ON eav.entry_id = p.ID
            WHERE p.post_type = 'super_contact_entry'
            AND eav.field_name = %s
            AND eav.field_value LIKE %s
            LIMIT %d
        ", $field_name, '%' . $wpdb->esc_like($field_value) . '%', $entry_count));
        return $results;
    });

    // Calculate improvement
    $improvement = $time_serialized > 0 ? round($time_serialized / $time_eav, 1) : 0;

    return array(
        'benchmark' => 'listings_filter',
        'entry_count' => $entry_count,
        'time_serialized' => $time_serialized,
        'time_eav' => $time_eav,
        'improvement' => $improvement,
        'faster' => $improvement . 'x faster',
        'message' => sprintf(
            'Listings filter: %dms (serialized) vs %dms (EAV) - %sx improvement',
            $time_serialized,
            $time_eav,
            $improvement
        )
    );
}
```

### Benchmark 3: Admin Search Performance

```php
public static function benchmark_admin_search($entry_count = 100, $keyword = 'test') {
    global $wpdb;

    // Benchmark serialized method (LIKE on serialized meta_value)
    $time_serialized = self::benchmark_operation(function() use ($wpdb, $keyword, $entry_count) {
        $results = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
            WHERE p.post_type = 'super_contact_entry'
            AND m.meta_key = '_super_contact_entry_data'
            AND m.meta_value LIKE %s
            LIMIT %d
        ", '%' . $wpdb->esc_like($keyword) . '%', $entry_count));
        return $results;
    });

    // Benchmark EAV method (indexed field_value LIKE with prefix index)
    $time_eav = self::benchmark_operation(function() use ($wpdb, $keyword, $entry_count) {
        $results = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->prefix}superforms_entry_data eav ON eav.entry_id = p.ID
            WHERE p.post_type = 'super_contact_entry'
            AND eav.field_value LIKE %s
            LIMIT %d
        ", '%' . $wpdb->esc_like($keyword) . '%', $entry_count));
        return $results;
    });

    // Calculate improvement
    $improvement = $time_serialized > 0 ? round($time_serialized / $time_eav, 1) : 0;

    return array(
        'benchmark' => 'admin_search',
        'entry_count' => $entry_count,
        'keyword' => $keyword,
        'time_serialized' => $time_serialized,
        'time_eav' => $time_eav,
        'improvement' => $improvement,
        'faster' => $improvement . 'x faster',
        'message' => sprintf(
            'Admin search: %dms (serialized) vs %dms (EAV) - %sx improvement',
            $time_serialized,
            $time_eav,
            $improvement
        )
    );
}
```

### AJAX Handler

```php
public static function dev_run_benchmarks() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $benchmarks = isset($_POST['benchmarks']) ? (array) $_POST['benchmarks'] : array();
    $entry_count = isset($_POST['entry_count']) ? absint($_POST['entry_count']) : 100;

    if (empty($benchmarks)) {
        wp_send_json_error(array('message' => 'No benchmarks selected'));
    }

    $results = array();
    foreach ($benchmarks as $benchmark) {
        $method = 'benchmark_' . sanitize_key($benchmark);
        if (method_exists('SUPER_Developer_Tools', $method)) {
            $result = call_user_func(array('SUPER_Developer_Tools', $method), $entry_count);

            if (is_wp_error($result)) {
                $results[$benchmark] = array(
                    'error' => true,
                    'message' => $result->get_error_message()
                );
            } else {
                $results[$benchmark] = $result;
            }
        }
    }

    // Store results for comparison
    update_option('superforms_last_benchmark_results', $results);

    wp_send_json_success(array(
        'results' => $results,
        'timestamp' => current_time('mysql')
    ));
}
```

### UI Section HTML

```php
<!-- Performance Benchmarks Section -->
<div class="super-devtools-section">
    <h2><?php echo esc_html__('4. Performance Benchmarks', 'super-forms'); ?></h2>
    <p><?php echo esc_html__('Measure real-world performance improvements of EAV storage vs serialized storage.', 'super-forms'); ?></p>

    <p>
        <button id="run-all-benchmarks-btn" class="button button-primary">
            <?php echo esc_html__('▶️ Run All Benchmarks', 'super-forms'); ?>
        </button>
        <button id="run-selected-benchmarks-btn" class="button button-secondary">
            <?php echo esc_html__('Run Selected', 'super-forms'); ?>
        </button>
    </p>

    <p>
        <label>
            <?php echo esc_html__('Entry Count for Tests:', 'super-forms'); ?>
            <select id="benchmark-entry-count">
                <option value="10">10</option>
                <option value="100" selected>100</option>
                <option value="1000">1,000</option>
                <option value="10000">10,000</option>
            </select>
        </label>
    </p>

    <h3><?php echo esc_html__('Benchmarks:', 'super-forms'); ?></h3>

    <!-- CSV Export Benchmark -->
    <div class="benchmark-result" data-benchmark="csv_export" style="display: none;">
        <h4>
            <input type="checkbox" name="benchmarks[]" value="csv_export" checked>
            <?php echo esc_html__('CSV Export (N entries)', 'super-forms'); ?>
        </h4>
        <div class="benchmark-bars">
            <div class="benchmark-bar-row">
                <span class="benchmark-label">Serialized:</span>
                <div class="benchmark-bar-container">
                    <div class="benchmark-bar serialized" style="width: 0%"></div>
                </div>
                <span class="benchmark-time serialized">--</span>
            </div>
            <div class="benchmark-bar-row">
                <span class="benchmark-label">EAV:</span>
                <div class="benchmark-bar-container">
                    <div class="benchmark-bar eav" style="width: 0%"></div>
                </div>
                <span class="benchmark-time eav">--</span>
            </div>
        </div>
        <p class="benchmark-improvement">Improvement: <strong>--</strong></p>
    </div>

    <!-- Listings Filter Benchmark -->
    <div class="benchmark-result" data-benchmark="listings_filter" style="display: none;">
        <h4>
            <input type="checkbox" name="benchmarks[]" value="listings_filter" checked>
            <?php echo esc_html__('Listings Filter (field="email" value="@test.com")', 'super-forms'); ?>
        </h4>
        <div class="benchmark-bars">
            <div class="benchmark-bar-row">
                <span class="benchmark-label">Serialized:</span>
                <div class="benchmark-bar-container">
                    <div class="benchmark-bar serialized" style="width: 0%"></div>
                </div>
                <span class="benchmark-time serialized">--</span>
            </div>
            <div class="benchmark-bar-row">
                <span class="benchmark-label">EAV:</span>
                <div class="benchmark-bar-container">
                    <div class="benchmark-bar eav" style="width: 0%"></div>
                </div>
                <span class="benchmark-time eav">--</span>
            </div>
        </div>
        <p class="benchmark-improvement">Improvement: <strong>--</strong></p>
    </div>

    <!-- Admin Search Benchmark -->
    <div class="benchmark-result" data-benchmark="admin_search" style="display: none;">
        <h4>
            <input type="checkbox" name="benchmarks[]" value="admin_search" checked>
            <?php echo esc_html__('Admin Search (keyword="test")', 'super-forms'); ?>
        </h4>
        <div class="benchmark-bars">
            <div class="benchmark-bar-row">
                <span class="benchmark-label">Serialized:</span>
                <div class="benchmark-bar-container">
                    <div class="benchmark-bar serialized" style="width: 0%"></div>
                </div>
                <span class="benchmark-time serialized">--</span>
            </div>
            <div class="benchmark-bar-row">
                <span class="benchmark-label">EAV:</span>
                <div class="benchmark-bar-container">
                    <div class="benchmark-bar eav" style="width: 0%"></div>
                </div>
                <span class="benchmark-time eav">--</span>
            </div>
        </div>
        <p class="benchmark-improvement">Improvement: <strong>--</strong></p>
    </div>

    <p style="margin-top: 20px;">
        <button id="download-benchmark-report" class="button button-secondary" disabled>
            <?php echo esc_html__('Download Benchmark Report', 'super-forms'); ?>
        </button>
        <button id="compare-with-previous" class="button button-secondary" disabled>
            <?php echo esc_html__('Compare with Previous', 'super-forms'); ?>
        </button>
    </p>
</div>

<style>
.benchmark-result {
    background: #f9f9f9;
    padding: 15px;
    margin: 10px 0;
    border-left: 3px solid #0073aa;
}

.benchmark-result h4 {
    margin-top: 0;
}

.benchmark-bars {
    margin: 15px 0;
}

.benchmark-bar-row {
    display: flex;
    align-items: center;
    margin: 8px 0;
}

.benchmark-label {
    width: 100px;
    font-weight: bold;
}

.benchmark-bar-container {
    flex: 1;
    height: 30px;
    background: #e0e0e0;
    position: relative;
    margin: 0 10px;
}

.benchmark-bar {
    height: 100%;
    transition: width 0.5s ease;
}

.benchmark-bar.serialized {
    background: linear-gradient(90deg, #f44336, #d32f2f);
}

.benchmark-bar.eav {
    background: linear-gradient(90deg, #4caf50, #388e3c);
}

.benchmark-time {
    width: 100px;
    text-align: right;
    font-family: monospace;
}

.benchmark-improvement {
    font-size: 16px;
    margin: 10px 0;
}

.benchmark-improvement strong {
    color: #4caf50;
    font-size: 20px;
}
</style>
```

### JavaScript Implementation

```javascript
// Run all benchmarks
$('#run-all-benchmarks-btn').on('click', function() {
    $('input[name="benchmarks[]"]').prop('checked', true);
    runBenchmarks();
});

// Run selected benchmarks
$('#run-selected-benchmarks-btn').on('click', function() {
    runBenchmarks();
});

function runBenchmarks() {
    var selectedBenchmarks = [];
    $('input[name="benchmarks[]"]:checked').each(function() {
        selectedBenchmarks.push($(this).val());
    });

    if (selectedBenchmarks.length === 0) {
        alert('Please select at least one benchmark');
        return;
    }

    var entryCount = parseInt($('#benchmark-entry-count').val());

    // Show benchmark result containers
    selectedBenchmarks.forEach(function(benchmark) {
        $('.benchmark-result[data-benchmark="' + benchmark + '"]').show();
    });

    // Disable buttons
    $('#run-all-benchmarks-btn, #run-selected-benchmarks-btn').prop('disabled', true).text('Running...');

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'super_dev_run_benchmarks',
            security: devtoolsNonce,
            benchmarks: selectedBenchmarks,
            entry_count: entryCount
        },
        success: function(response) {
            if (response.success) {
                displayBenchmarkResults(response.data.results);
                $('#download-benchmark-report, #compare-with-previous').prop('disabled', false);
                window.benchmarkResults = response.data;
            } else {
                alert('Error: ' + response.data.message);
            }

            $('#run-all-benchmarks-btn, #run-selected-benchmarks-btn').prop('disabled', false).text('Run');
        }
    });
}

function displayBenchmarkResults(results) {
    $.each(results, function(benchmark, result) {
        if (result.error) {
            $('.benchmark-result[data-benchmark="' + benchmark + '"] .benchmark-improvement strong')
                .text('Error: ' + result.message)
                .css('color', '#f44336');
            return;
        }

        var $container = $('.benchmark-result[data-benchmark="' + benchmark + '"]');

        // Calculate relative widths (max = 100%)
        var maxTime = Math.max(result.time_serialized, result.time_eav);
        var serWidth = (result.time_serialized / maxTime) * 100;
        var eavWidth = (result.time_eav / maxTime) * 100;

        // Update progress bars
        $container.find('.benchmark-bar.serialized').css('width', serWidth + '%');
        $container.find('.benchmark-bar.eav').css('width', eavWidth + '%');

        // Update time labels
        $container.find('.benchmark-time.serialized').text(result.time_serialized + 'ms');
        $container.find('.benchmark-time.eav').text(result.time_eav + 'ms');

        // Update improvement
        var improvementText = result.improvement + 'x faster';
        if (result.improvement >= 50) {
            improvementText += ' 🔥';
        } else if (result.improvement >= 10) {
            improvementText += ' ⚡';
        }
        $container.find('.benchmark-improvement strong').text(improvementText);
    });
}

// Download benchmark report
$('#download-benchmark-report').on('click', function() {
    var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(window.benchmarkResults, null, 2));
    var downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "benchmark-report.json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
});
```

## Testing Requirements

1. **CSV Export Benchmark** - With 1,000 entries, verify 10-100x improvement
2. **Listings Filter Benchmark** - With 1,000 entries, verify 30-60x improvement
3. **Admin Search Benchmark** - With 1,000 entries, verify improvement shown
4. **Entry Count Selection** - Test with 10, 100, 1000, 10000 entries
5. **Visual Bars** - Verify bars show relative performance correctly
6. **Improvement Calculation** - Verify multiplier calculation is accurate
7. **Download Report** - Verify JSON download contains all data
8. **Error Handling** - Test with insufficient test data

## Estimated Time

**2-3 hours** for implementation and testing

## Dependencies

- Phase 1 (page foundation)
- Phase 2 (test data generator - to create sufficient test entries)
- SUPER_Data_Access::get_bulk_entry_data() (already exists)

## Notes

- Warm-up run discarded to avoid cache effects
- Timing uses microtime(true) for millisecond accuracy
- Progress bars visually demonstrate performance difference
- Results cached in wp_options for comparison
- Emoji indicators (🔥 ⚡) make improvements clear at a glance
- Benchmarks validate the migration's performance claims
