---
name: 06-database-inspector-and-utilities
parent: h-implement-developer-tools-page
status: pending
created: 2025-11-02
---

# Phase 6: Database Inspector and Utilities

## Goal

Provide database visibility and developer utilities including:
1. Database statistics viewer (table sizes, row counts, indexes)
2. Safe cleanup functions with confirmation dialogs
3. Quick SQL executor with whitelisted queries
4. Migration logs viewer
5. PHP error log access
6. Query debugging toggle

## Success Criteria

- [ ] Database statistics display accurately (serialized count, EAV counts, table sizes)
- [ ] Index status shows all 5 EAV table indexes
- [ ] Sample entry data viewer displays field data
- [ ] Storage size comparison calculates correctly
- [ ] Test entry deletion only removes tagged entries
- [ ] Selective cleanup functions work (EAV only, serialized only)
- [ ] Reset functions require confirmation
- [ ] Optimize tables and rebuild indexes work
- [ ] SQL executor only runs whitelisted queries
- [ ] Common query templates available in dropdown
- [ ] Export migration status as JSON works
- [ ] Migration logs display with timestamps
- [ ] PHP error log reader shows recent errors
- [ ] Query debugging toggle enables SAVEQUERIES

## Implementation Requirements

### Files to Modify

1. **`/src/includes/class-developer-tools.php`** - Add database utility methods
2. **`/src/includes/class-ajax.php`** - Add AJAX handlers:
   - `dev_get_db_stats` - Get database statistics
   - `dev_execute_sql` - Execute whitelisted SQL
   - `dev_cleanup_data` - Cleanup operations
   - `dev_optimize_tables` - Database maintenance
3. **`/src/includes/admin/views/page-developer-tools.php`** - Add two UI sections

## Technical Specifications

### Database Statistics Queries

**Serialized Storage Count:**
```php
public static function get_serialized_count() {
    global $wpdb;
    return $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_super_contact_entry_data'
    ");
}
```

**EAV Table Statistics:**
```php
public static function get_eav_stats() {
    global $wpdb;

    $stats = array(
        'total_rows' => $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}superforms_entry_data
        "),
        'unique_entries' => $wpdb->get_var("
            SELECT COUNT(DISTINCT entry_id)
            FROM {$wpdb->prefix}superforms_entry_data
        "),
        'unique_fields' => $wpdb->get_var("
            SELECT COUNT(DISTINCT field_name)
            FROM {$wpdb->prefix}superforms_entry_data
        "),
        'avg_fields_per_entry' => $wpdb->get_var("
            SELECT AVG(field_count)
            FROM (
                SELECT COUNT(*) as field_count
                FROM {$wpdb->prefix}superforms_entry_data
                GROUP BY entry_id
            ) as subquery
        "),
        'table_size_mb' => $wpdb->get_var($wpdb->prepare("
            SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            AND table_name = %s
        ", $wpdb->prefix . 'superforms_entry_data'))
    );

    return $stats;
}
```

**Index Status:**
```php
public static function get_index_status() {
    global $wpdb;

    $indexes = $wpdb->get_results("
        SHOW INDEX FROM {$wpdb->prefix}superforms_entry_data
    ");

    $index_info = array();
    foreach ($indexes as $index) {
        $key = $index->Key_name;
        if (!isset($index_info[$key])) {
            $index_info[$key] = array(
                'name' => $key,
                'unique' => ($index->Non_unique == 0),
                'columns' => array()
            );
        }
        $index_info[$key]['columns'][] = $index->Column_name;
    }

    return $index_info;
}
```

**Sample Entry Data:**
```php
public static function get_sample_entry_data($entry_id) {
    global $wpdb;

    $data = $wpdb->get_results($wpdb->prepare("
        SELECT field_name, LEFT(field_value, 100) as value_preview, field_type
        FROM {$wpdb->prefix}superforms_entry_data
        WHERE entry_id = %d
        ORDER BY field_name ASC
        LIMIT 50
    ", $entry_id));

    return $data;
}
```

### Cleanup Functions

**Delete Test Entries Only (Safe):**
```php
public static function delete_test_entries() {
    global $wpdb;

    // Find ONLY test entries
    $test_ids = $wpdb->get_col("
        SELECT post_id
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_super_test_entry'
        AND meta_value = '1'
    ");

    if (empty($test_ids)) {
        return array('deleted' => 0, 'message' => 'No test entries found');
    }

    $deleted = 0;
    foreach ($test_ids as $entry_id) {
        // Delete from EAV
        $wpdb->delete(
            $wpdb->prefix . 'superforms_entry_data',
            array('entry_id' => $entry_id),
            array('%d')
        );

        // Delete from serialized
        delete_post_meta($entry_id, '_super_contact_entry_data');
        delete_post_meta($entry_id, '_super_test_entry');

        // Delete WordPress post
        wp_delete_post($entry_id, true);

        $deleted++;
    }

    return array('deleted' => $deleted, 'message' => sprintf('Deleted %d test entries', $deleted));
}
```

**Delete All EAV Data (Keep Serialized):**
```php
public static function delete_all_eav_data() {
    global $wpdb;

    $deleted = $wpdb->query("
        DELETE FROM {$wpdb->prefix}superforms_entry_data
    ");

    return array('deleted' => $deleted, 'message' => sprintf('Deleted %d EAV rows', $deleted));
}
```

**Delete All Serialized Data (Keep EAV):**
```php
public static function delete_all_serialized_data() {
    global $wpdb;

    $deleted = $wpdb->query("
        DELETE FROM {$wpdb->postmeta}
        WHERE meta_key = '_super_contact_entry_data'
    ");

    return array('deleted' => $deleted, 'message' => sprintf('Deleted %d serialized entries', $deleted));
}
```

### Database Maintenance

**Optimize Tables:**
```php
public static function optimize_tables() {
    global $wpdb;

    $results = array();

    // Optimize EAV table
    $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}superforms_entry_data");
    $results[] = 'Optimized superforms_entry_data';

    // Optimize postmeta table
    $wpdb->query("OPTIMIZE TABLE {$wpdb->postmeta}");
    $results[] = 'Optimized postmeta';

    return array('message' => implode(', ', $results));
}
```

**Rebuild Indexes:**
```php
public static function rebuild_indexes() {
    global $wpdb;

    // Drop and recreate indexes
    $wpdb->query("
        ALTER TABLE {$wpdb->prefix}superforms_entry_data
        DROP INDEX idx_entry_id,
        DROP INDEX idx_field_name,
        DROP INDEX idx_entry_field,
        DROP INDEX idx_field_value
    ");

    $wpdb->query("
        ALTER TABLE {$wpdb->prefix}superforms_entry_data
        ADD INDEX idx_entry_id (entry_id),
        ADD INDEX idx_field_name (field_name),
        ADD INDEX idx_entry_field (entry_id, field_name),
        ADD INDEX idx_field_value (field_value(191))
    ");

    return array('message' => 'Indexes rebuilt successfully');
}
```

**Vacuum Orphaned Data:**
```php
public static function vacuum_orphaned_data() {
    global $wpdb;

    // Delete EAV data for entries that no longer exist
    $deleted = $wpdb->query("
        DELETE eav FROM {$wpdb->prefix}superforms_entry_data eav
        LEFT JOIN {$wpdb->posts} p ON p.ID = eav.entry_id
        WHERE p.ID IS NULL
    ");

    return array('deleted' => $deleted, 'message' => sprintf('Deleted %d orphaned EAV rows', $deleted));
}
```

### SQL Executor with Whitelist

```php
private static $allowed_queries = array(
    'count_eav_total' => "SELECT COUNT(*) as count FROM {wpdb_prefix}superforms_entry_data",
    'count_eav_entries' => "SELECT COUNT(DISTINCT entry_id) as count FROM {wpdb_prefix}superforms_entry_data",
    'count_serialized' => "SELECT COUNT(*) as count FROM {wpdb_prefix}postmeta WHERE meta_key = '_super_contact_entry_data'",
    'show_indexes' => "SHOW INDEX FROM {wpdb_prefix}superforms_entry_data",
    'table_stats' => "SELECT COUNT(*) as rows, COUNT(DISTINCT entry_id) as entries, COUNT(DISTINCT field_name) as fields FROM {wpdb_prefix}superforms_entry_data",
    'recent_entries' => "SELECT ID, post_title, post_date FROM {wpdb_prefix}posts WHERE post_type = 'super_contact_entry' ORDER BY post_date DESC LIMIT 10",
    'field_names' => "SELECT DISTINCT field_name, COUNT(*) as count FROM {wpdb_prefix}superforms_entry_data GROUP BY field_name ORDER BY count DESC",
    'entry_count_by_form' => "SELECT post_parent as form_id, COUNT(*) as entries FROM {wpdb_prefix}posts WHERE post_type = 'super_contact_entry' GROUP BY post_parent",
    'test_entry_count' => "SELECT COUNT(*) as count FROM {wpdb_prefix}postmeta WHERE meta_key = '_super_test_entry' AND meta_value = '1'",
);

public static function execute_whitelisted_sql($query_key) {
    global $wpdb;

    if (!isset(self::$allowed_queries[$query_key])) {
        return new WP_Error('query_not_allowed', 'Query not in whitelist');
    }

    $query = self::$allowed_queries[$query_key];

    // Replace placeholders
    $query = str_replace('{wpdb_prefix}', $wpdb->prefix, $query);

    // Execute query
    $results = $wpdb->get_results($query);

    if ($wpdb->last_error) {
        return new WP_Error('query_error', $wpdb->last_error);
    }

    return $results;
}
```

### AJAX Handlers

```php
public static function dev_get_db_stats() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $stats = array(
        'serialized_count' => SUPER_Developer_Tools::get_serialized_count(),
        'eav_stats' => SUPER_Developer_Tools::get_eav_stats(),
        'index_status' => SUPER_Developer_Tools::get_index_status()
    );

    wp_send_json_success($stats);
}

public static function dev_execute_sql() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $query_key = sanitize_text_field($_POST['query_key']);
    $result = SUPER_Developer_Tools::execute_whitelisted_sql($query_key);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success(array('results' => $result));
}

public static function dev_cleanup_data() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $action = sanitize_text_field($_POST['cleanup_action']);

    $result = null;
    switch ($action) {
        case 'delete_test_entries':
            $result = SUPER_Developer_Tools::delete_test_entries();
            break;
        case 'delete_all_eav':
            $result = SUPER_Developer_Tools::delete_all_eav_data();
            break;
        case 'delete_all_serialized':
            $result = SUPER_Developer_Tools::delete_all_serialized_data();
            break;
        case 'vacuum_orphaned':
            $result = SUPER_Developer_Tools::vacuum_orphaned_data();
            break;
    }

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success($result);
}

public static function dev_optimize_tables() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $action = sanitize_text_field($_POST['optimize_action']);

    $result = null;
    switch ($action) {
        case 'optimize':
            $result = SUPER_Developer_Tools::optimize_tables();
            break;
        case 'rebuild_indexes':
            $result = SUPER_Developer_Tools::rebuild_indexes();
            break;
    }

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success($result);
}
```

### UI Sections HTML

```php
<!-- Database Inspector Section -->
<div class="super-devtools-section">
    <h2><?php echo esc_html__('5. Database Inspector', 'super-forms'); ?></h2>
    <p><?php echo esc_html__('View database state and statistics.', 'super-forms'); ?></p>

    <button id="refresh-db-stats-btn" class="button button-secondary">
        <?php echo esc_html__('🔄 Refresh Statistics', 'super-forms'); ?>
    </button>

    <h3><?php echo esc_html__('wp_postmeta (Serialized):', 'super-forms'); ?></h3>
    <p>
        └─ _super_contact_entry_data: <strong class="serialized-count">--</strong> rows
    </p>

    <h3><?php echo esc_html__('wp_superforms_entry_data (EAV):', 'super-forms'); ?></h3>
    <ul class="eav-stats">
        <li>└─ Total rows: <strong class="eav-total-rows">--</strong></li>
        <li>└─ Unique entries: <strong class="eav-unique-entries">--</strong></li>
        <li>└─ Unique field names: <strong class="eav-unique-fields">--</strong></li>
        <li>└─ Avg fields per entry: <strong class="eav-avg-fields">--</strong></li>
        <li>└─ Table size: <strong class="eav-table-size">--</strong> MB</li>
    </ul>

    <h3><?php echo esc_html__('Index Status:', 'super-forms'); ?></h3>
    <ul class="index-status">
        <!-- Populated via JavaScript -->
    </ul>

    <p>
        <button id="run-analyze-table-btn" class="button button-secondary">
            <?php echo esc_html__('Run ANALYZE TABLE', 'super-forms'); ?>
        </button>
        <button id="view-sample-entry-btn" class="button button-secondary">
            <?php echo esc_html__('View Sample Entry Data', 'super-forms'); ?>
        </button>
    </p>
</div>

<!-- Cleanup & Reset Section -->
<div class="super-devtools-section">
    <h2 style="color: #d32f2f;"><?php echo esc_html__('6. Cleanup & Reset', 'super-forms'); ?></h2>
    <div class="sfui-notice" style="border-left-color: #d32f2f; background: #ffebee;">
        <h3>⚠️ <?php echo esc_html__('DANGER ZONE', 'super-forms'); ?></h3>
        <p><?php echo esc_html__('These actions affect the database. Use with caution.', 'super-forms'); ?></p>
    </div>

    <h3><?php echo esc_html__('Selective Cleanup:', 'super-forms'); ?></h3>
    <p>
        <button id="delete-test-entries-btn" class="button button-secondary">
            <?php echo esc_html__('Delete Test Entries Only', 'super-forms'); ?>
        </button>
        <span class="test-entries-count">(0 found)</span>
    </p>
    <p>
        <button id="delete-all-eav-btn" class="button button-secondary">
            <?php echo esc_html__('Delete All EAV Data', 'super-forms'); ?>
        </button>
        <span class="description">(keeps serialized)</span>
    </p>
    <p>
        <button id="delete-all-serialized-btn" class="button button-secondary">
            <?php echo esc_html__('Delete All Serialized Data', 'super-forms'); ?>
        </button>
        <span class="description">(keeps EAV)</span>
    </p>

    <h3><?php echo esc_html__('Database Maintenance:', 'super-forms'); ?></h3>
    <p>
        <button id="optimize-tables-btn" class="button button-secondary">
            <?php echo esc_html__('Optimize EAV Tables', 'super-forms'); ?>
        </button>
        <button id="rebuild-indexes-btn" class="button button-secondary">
            <?php echo esc_html__('Rebuild Indexes', 'super-forms'); ?>
        </button>
        <button id="vacuum-orphaned-btn" class="button button-secondary">
            <?php echo esc_html__('Vacuum Orphaned Data', 'super-forms'); ?>
        </button>
    </p>
</div>

<!-- Developer Utilities Section -->
<div class="super-devtools-section">
    <h2><?php echo esc_html__('7. Developer Utilities', 'super-forms'); ?></h2>

    <p>
        <button id="export-migration-status-btn" class="button button-secondary">
            <?php echo esc_html__('Export Migration Status (JSON)', 'super-forms'); ?>
        </button>
        <button id="view-migration-logs-btn" class="button button-secondary">
            <?php echo esc_html__('View Migration Logs', 'super-forms'); ?>
        </button>
        <button id="view-php-errors-btn" class="button button-secondary">
            <?php echo esc_html__('View PHP Error Log', 'super-forms'); ?>
        </button>
        <button id="toggle-query-debug-btn" class="button button-secondary">
            <?php echo esc_html__('Enable Query Debugging', 'super-forms'); ?>
        </button>
    </p>

    <h3><?php echo esc_html__('Quick SQL:', 'super-forms'); ?></h3>
    <p>
        <select id="quick-sql-templates" style="width: 400px;">
            <option value="">-- Select Query --</option>
            <option value="count_eav_total">Count EAV Total Rows</option>
            <option value="count_eav_entries">Count EAV Unique Entries</option>
            <option value="count_serialized">Count Serialized Entries</option>
            <option value="show_indexes">Show EAV Indexes</option>
            <option value="table_stats">EAV Table Statistics</option>
            <option value="recent_entries">Recent Entries</option>
            <option value="field_names">Field Names with Counts</option>
            <option value="entry_count_by_form">Entry Count by Form</option>
            <option value="test_entry_count">Test Entry Count</option>
        </select>
        <button id="execute-sql-btn" class="button button-secondary">
            <?php echo esc_html__('Execute Query', 'super-forms'); ?>
        </button>
    </p>

    <div class="sql-results" style="display: none;">
        <h4><?php echo esc_html__('Results:', 'super-forms'); ?></h4>
        <pre class="sql-results-content"></pre>
    </div>
</div>
```

### JavaScript Implementation

```javascript
// Refresh database statistics
$('#refresh-db-stats-btn').on('click', function() {
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'super_dev_get_db_stats',
            security: devtoolsNonce
        },
        success: function(response) {
            if (response.success) {
                var stats = response.data;

                // Update serialized count
                $('.serialized-count').text(stats.serialized_count.toLocaleString());

                // Update EAV stats
                $('.eav-total-rows').text(stats.eav_stats.total_rows.toLocaleString());
                $('.eav-unique-entries').text(stats.eav_stats.unique_entries.toLocaleString());
                $('.eav-unique-fields').text(stats.eav_stats.unique_fields);
                $('.eav-avg-fields').text(parseFloat(stats.eav_stats.avg_fields_per_entry).toFixed(1));
                $('.eav-table-size').text(stats.eav_stats.table_size_mb);

                // Update index status
                var indexHtml = '';
                $.each(stats.index_status, function(name, info) {
                    indexHtml += '<li>✓ ' + name + ' (' + info.columns.join(', ') + ')</li>';
                });
                $('.index-status').html(indexHtml);
            }
        }
    });
});

// Delete test entries
$('#delete-test-entries-btn').on('click', function() {
    if (confirm('Delete all test entries? This cannot be undone.')) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_cleanup_data',
                security: devtoolsNonce,
                cleanup_action: 'delete_test_entries'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
});

// Delete all EAV
$('#delete-all-eav-btn').on('click', function() {
    if (confirm('Delete ALL EAV data? Serialized data will be kept.\n\nThis cannot be undone!')) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'super_dev_cleanup_data',
                security: devtoolsNonce,
                cleanup_action: 'delete_all_eav'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
});

// Execute SQL query
$('#execute-sql-btn').on('click', function() {
    var queryKey = $('#quick-sql-templates').val();
    if (!queryKey) {
        alert('Please select a query');
        return;
    }

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'super_dev_execute_sql',
            security: devtoolsNonce,
            query_key: queryKey
        },
        success: function(response) {
            if (response.success) {
                $('.sql-results').show();
                $('.sql-results-content').text(JSON.stringify(response.data.results, null, 2));
            } else {
                alert('Error: ' + response.data.message);
            }
        }
    });
});

// Export migration status
$('#export-migration-status-btn').on('click', function() {
    var migrationStatus = <?php echo json_encode(SUPER_Migration_Manager::get_migration_status()); ?>;
    var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(migrationStatus, null, 2));
    var downloadAnchorNode = document.createElement('a');
    downloadAnchorNode.setAttribute("href", dataStr);
    downloadAnchorNode.setAttribute("download", "migration-status.json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
});

// Load stats on page load
$(document).ready(function() {
    $('#refresh-db-stats-btn').click();
});
```

## Testing Requirements

1. **Database Statistics** - Verify counts match reality
2. **Index Status** - All 5 indexes show correctly
3. **Test Entry Deletion** - Only removes tagged entries
4. **EAV Deletion** - Removes EAV, keeps serialized
5. **Serialized Deletion** - Removes serialized, keeps EAV
6. **Optimize Tables** - No errors, completes successfully
7. **Rebuild Indexes** - Indexes recreated correctly
8. **Vacuum Orphaned** - Removes orphaned rows
9. **SQL Executor** - Only whitelisted queries execute
10. **Export Status** - JSON downloads correctly

## Estimated Time

**2-3 hours** for implementation and testing

## Dependencies

- Phase 1 (page foundation)
- All previous phases provide context for what to inspect

## Security Notes

- **Critical**: SQL executor ONLY runs whitelisted queries
- All destructive actions require confirmation
- Test entry deletion verifies `_super_test_entry` flag
- Database operations logged to error_log
- No freeform SQL input allowed

## Notes

- Quick SQL templates cover common debugging queries
- Orphan vacuum prevents database bloat
- Index rebuilding fixes corruption issues
- Statistics help verify migration success
- JSON export useful for support debugging
