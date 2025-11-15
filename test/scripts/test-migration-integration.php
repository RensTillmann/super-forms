#!/usr/bin/env php
<?php
/**
 * Migration Integration Tests
 *
 * Comprehensive test suite for EAV migration system
 * Can run via CLI or Developer Tools button
 *
 * @package Super_Forms
 * @since 6.4.127
 */

// Bootstrap WordPress if running via CLI
if (!defined('ABSPATH')) {
    require_once('/var/www/html/wp-config.php');
    require_once('/var/www/html/wp-load.php');
}

if (!class_exists('SUPER_Migration_Integration_Test')) :

class SUPER_Migration_Integration_Test {

    private $results = [];
    private $test_entries = [];
    private $start_time = 0;
    private $current_test = '';
    private $data_source = 'programmatic';
    private $import_file = '';
    private $imported_entry_ids = [];

    /**
     * Available tests
     */
    private $available_tests = [
        'all' => 'All Tests',
        'full_flow' => 'Full Migration Flow',
        'counter_accuracy' => 'Counter Accuracy',
        'data_preservation' => 'Data Preservation',
        'empty_entries' => 'Empty Entry Handling',
        'lock_mechanism' => 'Lock Mechanism',
        'resume_failure' => 'Resume from Failure'
    ];

    /**
     * Run selected test or all tests
     *
     * @param string $test_name Test to run ('all' or specific test)
     * @param string $data_source Data source ('programmatic', 'csv', or 'xml')
     * @param string $import_file Import file name (if using csv/xml)
     * @return array Test results
     */
    public function run($test_name = 'all', $data_source = 'programmatic', $import_file = '') {
        $this->data_source = $data_source;
        $this->import_file = $import_file;
        // Safety checks
        $safety_check = $this->safety_check();
        if ($safety_check !== true) {
            return $this->error_result($safety_check);
        }

        $this->log("🧪 Starting Migration Integration Tests");
        $this->log("Test: " . $this->available_tests[$test_name]);
        $this->log("Data Source: " . ucfirst($data_source));
        if ($data_source !== 'programmatic') {
            $this->log("Import File: " . $import_file);
        }
        $this->log("Date: " . date('Y-m-d H:i:s'));
        $this->log("========================================");

        // Import test data if using CSV/XML
        if ($data_source !== 'programmatic') {
            try {
                $this->import_test_data();
            } catch (Exception $e) {
                $this->log("❌ Import failed: " . $e->getMessage());
                return $this->error_result("Import failed: " . $e->getMessage());
            }
        }

        try {
            if ($test_name === 'all') {
                // Run all tests sequentially
                foreach ($this->available_tests as $key => $label) {
                    if ($key === 'all') continue;
                    $this->run_single_test($key);
                }
            } else {
                // Run single test
                $this->run_single_test($test_name);
            }
        } catch (Exception $e) {
            $this->log("❌ FATAL ERROR: " . $e->getMessage());
            $this->cleanup_all();
            return $this->error_result($e->getMessage());
        }

        // Final cleanup
        $this->cleanup_all();

        return $this->get_results();
    }

    /**
     * Run a single test by name
     *
     * @param string $test_name
     */
    private function run_single_test($test_name) {
        $this->current_test = $test_name;
        $this->start_time = microtime(true);

        $method = 'test_' . $test_name;
        if (!method_exists($this, $method)) {
            throw new Exception("Test method $method does not exist");
        }

        try {
            $this->$method();
            $duration = round((microtime(true) - $this->start_time) * 1000, 2);
            $this->log("✅ PASSED ({$duration}ms)");
        } catch (Exception $e) {
            $duration = round((microtime(true) - $this->start_time) * 1000, 2);
            $this->log("❌ FAILED ({$duration}ms): " . $e->getMessage());
            throw $e; // Re-throw to stop execution
        } finally {
            // Always cleanup test-specific data
            $this->cleanup_test_entries();
        }
    }

    /**
     * Test 1: Full Migration Flow
     *
     * Creates entries, migrates them, verifies completion
     */
    private function test_full_flow() {
        $this->log("\n--- Test 1: Full Migration Flow ---");

        // Create 50 test entries
        $this->log("Creating 50 test entries...");
        for ($i = 0; $i < 50; $i++) {
            $entry_id = $this->create_test_entry([
                'name' => ['name' => 'name', 'value' => "Test User $i", 'type' => 'text', 'label' => 'Name'],
                'email' => ['name' => 'email', 'value' => "test{$i}@example.com", 'type' => 'email', 'label' => 'Email']
            ]);
            $this->test_entries[] = $entry_id;
        }

        $this->log("Created " . count($this->test_entries) . " test entries");

        // Reset migration state
        $this->log("Resetting migration state...");
        SUPER_Migration_Manager::reset_migration();

        // Start migration
        $this->log("Starting migration...");
        $start_result = SUPER_Migration_Manager::start_migration();
        $this->assert(!is_wp_error($start_result), 'Start migration should succeed');

        // Process all batches
        $this->log("Processing batches...");
        $batch_count = 0;
        $max_batches = 10;

        while ($batch_count < $max_batches) {
            $batch_result = SUPER_Migration_Manager::process_batch(10);
            $this->assert(!is_wp_error($batch_result), 'Batch processing should succeed');

            $batch_count++;
            $this->log("Batch {$batch_count}: Processed {$batch_result['processed']}, Remaining {$batch_result['remaining']}");

            if ($batch_result['remaining'] === 0) {
                break;
            }
        }

        // Complete migration
        $this->log("Completing migration...");
        $complete_result = SUPER_Migration_Manager::complete_migration();
        $this->assert($complete_result['success'], 'Complete migration should succeed');

        // Verify final state
        $this->log("Verifying final state...");
        $status = SUPER_Migration_Manager::get_migration_status();

        $this->assert($status['status'] === 'completed', 'Status should be completed');
        $this->assert($status['using_storage'] === 'eav', 'Should be using EAV storage');
        $this->assert($status['migrated_entries'] === 50, 'Should have migrated all 50 entries');
        $this->assert(empty($status['failed_entries']), 'Should have no failed entries');

        // Verify EAV table
        global $wpdb;
        $eav_count = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id)
             FROM {$wpdb->prefix}superforms_entry_data
             WHERE entry_id IN (" . implode(',', $this->test_entries) . ")
             AND field_name != '_cleanup_empty'"
        );
        $this->assert($eav_count === 50, 'EAV table should have all 50 entries');

        $this->log("Full migration flow completed successfully!");
    }

    /**
     * Test 2: Counter Accuracy
     *
     * Verifies live counter calculation matches actual database
     */
    private function test_counter_accuracy() {
        $this->log("\n--- Test 2: Counter Accuracy ---");

        // Create 30 test entries
        $this->log("Creating 30 test entries...");
        for ($i = 0; $i < 30; $i++) {
            $entry_id = $this->create_test_entry([
                'field' => ['name' => 'field', 'value' => "Value $i", 'type' => 'text', 'label' => 'Field']
            ]);
            $this->test_entries[] = $entry_id;
        }

        // Reset and start migration
        SUPER_Migration_Manager::reset_migration();
        SUPER_Migration_Manager::start_migration();

        // Migrate exactly 20 entries
        $this->log("Migrating 20 of 30 entries...");
        for ($i = 0; $i < 20; $i++) {
            $result = SUPER_Migration_Manager::migrate_entry($this->test_entries[$i]);
            $this->assert($result === true, "Entry $i should migrate successfully");
        }

        // Get status
        $status = SUPER_Migration_Manager::get_migration_status();

        // Count actual migrated entries in database
        global $wpdb;
        $actual_migrated = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id)
             FROM {$wpdb->prefix}superforms_entry_data
             WHERE entry_id IN (" . implode(',', $this->test_entries) . ")
             AND field_name != '_cleanup_empty'"
        );

        $this->log("Status reports: {$status['migrated_entries']} migrated");
        $this->log("Database has: $actual_migrated migrated");

        // Verify counters match
        $this->assert($status['migrated_entries'] === 20, 'Status should show 20 migrated');
        $this->assert($actual_migrated === 20, 'Database should have 20 entries');

        // Verify calculations
        $expected_remaining = 30 - 20;
        $actual_remaining = $status['total_entries'] - $status['migrated_entries'];
        $this->assert($actual_remaining === $expected_remaining, 'Remaining count should be accurate');

        $expected_progress = (20 / 30) * 100;
        $actual_progress = ($status['migrated_entries'] / $status['total_entries']) * 100;
        $this->assert(abs($actual_progress - $expected_progress) < 0.01, 'Progress calculation should be accurate');

        $this->log("Counter accuracy verified!");
    }

    /**
     * Test 3: Data Preservation
     *
     * Verifies complex data is preserved exactly during migration
     */
    private function test_data_preservation() {
        $this->log("\n--- Test 3: Data Preservation ---");

        // Create entry with complex data
        $complex_data = [
            'text_field' => [
                'name' => 'text_field',
                'value' => 'Sample text with special chars: <>&"\'',
                'type' => 'text',
                'label' => 'Text Field'
            ],
            'number_field' => [
                'name' => 'number_field',
                'value' => '42.5',
                'type' => 'number',
                'label' => 'Number Field'
            ],
            'textarea_field' => [
                'name' => 'textarea_field',
                'value' => "Multi-line\ntext\nvalue",
                'type' => 'textarea',
                'label' => 'Textarea Field'
            ],
            'checkbox' => [
                'name' => 'checkbox',
                'value' => 'option1,option2,option3',
                'type' => 'checkbox',
                'label' => 'Checkbox Field'
            ],
            'repeater' => [
                'name' => 'repeater',
                'value' => [
                    ['name' => 'John', 'age' => '30'],
                    ['name' => 'Jane', 'age' => '25']
                ],
                'type' => 'dynamic',
                'label' => 'Repeater Field'
            ]
        ];

        $this->log("Creating entry with complex data...");
        $entry_id = $this->create_test_entry($complex_data);
        $this->test_entries[] = $entry_id;

        // Migrate entry
        $this->log("Migrating entry...");
        $result = SUPER_Migration_Manager::migrate_entry($entry_id);
        $this->assert($result === true, 'Migration should succeed');

        // Read back from EAV
        $this->log("Reading data from EAV...");
        $eav_data = SUPER_Data_Access::get_entry_data($entry_id);

        // Verify each field
        foreach ($complex_data as $field_name => $field_data) {
            $this->assert(
                isset($eav_data[$field_name]),
                "Field $field_name should exist in EAV"
            );

            $original_value = $field_data['value'];
            $eav_value = $eav_data[$field_name]['value'];

            // Handle arrays (repeater fields)
            if (is_array($original_value)) {
                $this->assert(
                    json_encode($original_value) === json_encode($eav_value),
                    "Field $field_name array value should match exactly"
                );
            } else {
                $this->assert(
                    $original_value === $eav_value,
                    "Field $field_name value should match exactly"
                );
            }

            $this->assert(
                $field_data['type'] === $eav_data[$field_name]['type'],
                "Field $field_name type should match"
            );
        }

        $this->log("All data preserved accurately!");
    }

    /**
     * Test 4: Empty Entry Handling
     *
     * Verifies empty entries are marked for cleanup correctly
     */
    private function test_empty_entries() {
        $this->log("\n--- Test 4: Empty Entry Handling ---");

        // Create entry with NO data
        $this->log("Creating empty entry...");
        $entry_id = wp_insert_post([
            'post_type' => 'super_contact_entry',
            'post_title' => '__TEST_MIGRATION_' . time() . '_empty',
            'post_status' => 'publish'
        ]);
        $this->test_entries[] = $entry_id;

        // Add form_id but NO entry data
        add_post_meta($entry_id, '_super_form_id', 999);

        // Migrate entry
        $this->log("Migrating empty entry...");
        $result = SUPER_Migration_Manager::migrate_entry($entry_id);
        $this->assert($result === 'skipped', 'Empty entry should be skipped');

        // Verify cleanup marker
        global $wpdb;
        $has_cleanup_marker = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}superforms_entry_data
             WHERE entry_id = %d AND field_name = '_cleanup_empty'",
            $entry_id
        ));

        $this->assert($has_cleanup_marker > 0, 'Empty entry should have cleanup marker');

        // Verify NOT counted in migrated_entries
        SUPER_Migration_Manager::reset_migration();
        SUPER_Migration_Manager::start_migration();
        SUPER_Migration_Manager::migrate_entry($entry_id);

        $status = SUPER_Migration_Manager::get_migration_status();
        $migrated_in_status = (int) $status['migrated_entries'];

        $actual_migrated = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id)
             FROM {$wpdb->prefix}superforms_entry_data
             WHERE field_name != '_cleanup_empty'"
        );

        $this->assert($migrated_in_status === $actual_migrated, 'Empty entries should not be counted');

        $this->log("Empty entry handling verified!");
    }

    /**
     * Test 5: Lock Mechanism
     *
     * Verifies migration lock prevents concurrent processing
     */
    private function test_lock_mechanism() {
        $this->log("\n--- Test 5: Lock Mechanism ---");

        // Create test entries
        $this->log("Creating test entries...");
        for ($i = 0; $i < 10; $i++) {
            $entry_id = $this->create_test_entry([
                'field' => ['name' => 'field', 'value' => "Value $i", 'type' => 'text', 'label' => 'Field']
            ]);
            $this->test_entries[] = $entry_id;
        }

        // Reset and start migration
        SUPER_Migration_Manager::reset_migration();
        SUPER_Migration_Manager::start_migration();

        // Acquire lock manually
        $this->log("Acquiring migration lock...");
        set_transient(SUPER_Background_Migration::LOCK_KEY, time(), 300);

        // Try to process batch (should fail or skip)
        $this->log("Attempting to process batch while locked...");
        $result = SUPER_Migration_Manager::process_batch(5);

        // Check if lock was respected
        $lock_exists = get_transient(SUPER_Background_Migration::LOCK_KEY);
        $this->assert($lock_exists !== false, 'Lock should still exist');

        // Release lock
        $this->log("Releasing lock...");
        delete_transient(SUPER_Background_Migration::LOCK_KEY);

        // Now processing should work
        $this->log("Processing batch without lock...");
        $result2 = SUPER_Migration_Manager::process_batch(5);
        $this->assert(!is_wp_error($result2), 'Processing should work without lock');

        $this->log("Lock mechanism verified!");
    }

    /**
     * Test 6: Resume from Failure
     *
     * Verifies migration resumes from last_processed_id correctly
     */
    private function test_resume_failure() {
        $this->log("\n--- Test 6: Resume from Failure ---");

        // Create 30 test entries
        $this->log("Creating 30 test entries...");
        for ($i = 0; $i < 30; $i++) {
            $entry_id = $this->create_test_entry([
                'field' => ['name' => 'field', 'value' => "Value $i", 'type' => 'text', 'label' => 'Field']
            ]);
            $this->test_entries[] = $entry_id;
        }

        // Reset and start migration
        SUPER_Migration_Manager::reset_migration();
        SUPER_Migration_Manager::start_migration();

        // Process first batch (10 entries)
        $this->log("Processing first batch (10 entries)...");
        $batch1 = SUPER_Migration_Manager::process_batch(10);
        $this->assert($batch1['processed'] === 10, 'Should process 10 entries');

        // Get last_processed_id
        $status1 = SUPER_Migration_Manager::get_migration_status();
        $last_id_after_first = $status1['last_processed_id'];
        $this->log("Last processed ID after first batch: $last_id_after_first");

        // Process second batch (should start from last_processed_id + 1)
        $this->log("Processing second batch (10 entries)...");
        $batch2 = SUPER_Migration_Manager::process_batch(10);
        $this->assert($batch2['processed'] === 10, 'Should process 10 more entries');

        // Verify no duplicates
        global $wpdb;
        $migrated_count = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id)
             FROM {$wpdb->prefix}superforms_entry_data
             WHERE entry_id IN (" . implode(',', $this->test_entries) . ")
             AND field_name != '_cleanup_empty'"
        );

        $this->assert($migrated_count === 20, 'Should have exactly 20 unique entries (no duplicates)');

        // Verify last_processed_id advanced
        $status2 = SUPER_Migration_Manager::get_migration_status();
        $last_id_after_second = $status2['last_processed_id'];
        $this->assert($last_id_after_second > $last_id_after_first, 'Last processed ID should advance');

        $this->log("Resume from failure verified!");
    }

    /**
     * Create a test entry with data
     *
     * @param array $data Entry data
     * @return int Entry ID
     */
    private function create_test_entry($data = []) {
        $entry_id = wp_insert_post([
            'post_type' => 'super_contact_entry',
            'post_title' => '__TEST_MIGRATION_' . time() . '_' . uniqid(),
            'post_status' => 'publish'
        ]);

        if (!empty($data)) {
            add_post_meta($entry_id, '_super_contact_entry_data', serialize($data));
        }
        add_post_meta($entry_id, '_super_form_id', 999); // Test form ID

        return $entry_id;
    }

    /**
     * Assert condition is true
     *
     * @param bool $condition
     * @param string $message
     * @throws Exception if assertion fails
     */
    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception("Assertion failed: $message");
        }
    }

    /**
     * Cleanup test entries
     */
    private function cleanup_test_entries() {
        if (empty($this->test_entries)) {
            return;
        }

        global $wpdb;

        // Delete posts
        foreach ($this->test_entries as $entry_id) {
            wp_delete_post($entry_id, true);
        }

        // Delete EAV data
        if ($this->data_source === 'programmatic') {
            // Programmatic entries use form_id 999
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}superforms_entry_data WHERE form_id = %d",
                999
            ));
        } else {
            // Imported entries - delete by entry_id
            if (!empty($this->test_entries)) {
                $ids = implode(',', array_map('intval', $this->test_entries));
                $wpdb->query(
                    "DELETE FROM {$wpdb->prefix}superforms_entry_data
                     WHERE entry_id IN ($ids)"
                );
            }
        }

        $this->test_entries = [];
        $this->imported_entry_ids = [];
    }

    /**
     * Cleanup all test data
     */
    private function cleanup_all() {
        $this->cleanup_test_entries();

        // Reset migration state
        SUPER_Migration_Manager::reset_migration();

        // Clear any locks
        delete_transient(SUPER_Background_Migration::LOCK_KEY);
        delete_transient('super_migration_schedule_lock');
    }

    /**
     * Import test data from CSV or XML file
     *
     * @throws Exception if import fails
     */
    private function import_test_data() {
        $this->log("Importing test data from: {$this->import_file}");

        // Get upload directory
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $this->import_file;

        // Verify file exists
        if (!file_exists($file_path)) {
            throw new Exception("Import file not found: {$this->import_file}");
        }

        // Import based on file type
        if (strpos($this->import_file, '.csv') !== false) {
            $this->import_csv($file_path);
        } elseif (strpos($this->import_file, '.xml') !== false) {
            $this->import_xml($file_path);
        } else {
            throw new Exception("Unsupported file type: {$this->import_file}");
        }

        $this->log("✓ Imported " . count($this->imported_entry_ids) . " entries");

        // Use imported entries as test entries
        $this->test_entries = $this->imported_entry_ids;
    }

    /**
     * Import CSV file and create contact entries
     *
     * @param string $file_path Full path to CSV file
     * @throws Exception if import fails
     */
    private function import_csv($file_path) {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception("Failed to open CSV file: {$file_path}");
        }

        // Check for UTF-8 BOM
        $bom = "\xef\xbb\xbf";
        if (fgets($handle, 4) !== $bom) {
            rewind($handle);
        }

        $headers = [];
        $row = 0;
        $imported_count = 0;

        while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
            // First row is headers
            if ($row === 0) {
                $headers = $data;
                $row++;
                continue;
            }

            // Build entry data from CSV row
            $entry_data = [];
            $form_id = 0;
            $entry_title = '';
            $entry_date = '';
            $entry_status = 'super_unread';

            foreach ($data as $col_index => $value) {
                if (!isset($headers[$col_index])) {
                    continue;
                }

                $field_name = $headers[$col_index];

                // Handle special columns
                if ($field_name === 'entry_title') {
                    $entry_title = $value;
                    continue;
                } elseif ($field_name === 'entry_date') {
                    $entry_date = $value;
                    continue;
                } elseif ($field_name === 'entry_status') {
                    $entry_status = $value;
                    continue;
                } elseif ($field_name === 'hidden_form_id') {
                    $form_id = absint($value);
                    continue;
                } elseif (in_array($field_name, ['entry_id', 'contact_entry_id', 'hidden_contact_entry_id', 'entry_ip'])) {
                    // Skip these columns
                    continue;
                }

                // Skip empty values
                if ($value === '') {
                    continue;
                }

                // Add to entry data with correct structure
                $entry_data[$field_name] = [
                    'name' => $field_name,
                    'value' => $value,
                    'type' => 'text',
                    'label' => ucwords(str_replace('_', ' ', $field_name))
                ];
            }

            // Create contact entry if we have data
            if (!empty($entry_data)) {
                $post_args = [
                    'post_status' => $entry_status,
                    'post_type' => 'super_contact_entry',
                    'post_parent' => $form_id,
                    'post_title' => !empty($entry_title) ? $entry_title : 'Test Entry ' . ($row - 1)
                ];

                if (!empty($entry_date)) {
                    $post_args['post_date'] = $entry_date;
                    $post_args['post_date_gmt'] = get_gmt_from_date($entry_date);
                }

                $entry_id = wp_insert_post($post_args);

                if ($entry_id && !is_wp_error($entry_id)) {
                    // Save entry data in serialized format
                    add_post_meta($entry_id, '_super_contact_entry_data', serialize($entry_data));
                    add_post_meta($entry_id, '_super_form_id', $form_id);

                    // Tag as test entry for cleanup
                    add_post_meta($entry_id, '_super_test_entry', '1');

                    $this->imported_entry_ids[] = $entry_id;
                    $imported_count++;
                }
            }

            $row++;

            // Log progress for large imports
            if ($imported_count > 0 && $imported_count % 1000 === 0) {
                $this->log("  → Imported {$imported_count} entries...");
            }
        }

        fclose($handle);
    }

    /**
     * Import WordPress XML export and create contact entries
     *
     * @param string $file_path Full path to XML file
     * @throws Exception if import fails
     */
    private function import_xml($file_path) {
        // Use WordPress XML importer
        if (!class_exists('WP_Import')) {
            $importer_path = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
            if (file_exists($importer_path)) {
                require_once $importer_path;
            }

            $import_path = ABSPATH . 'wp-admin/includes/import.php';
            if (file_exists($import_path)) {
                require_once $import_path;
            }
        }

        // For now, just log that XML import is not yet implemented
        // Full XML import would require the WordPress importer plugin
        throw new Exception("XML import not yet implemented. Please use CSV format.");
    }

    /**
     * Safety checks before running tests
     *
     * @return bool|string True if safe, error message otherwise
     */
    private function safety_check() {
        // Check DEBUG_SF is enabled
        if (!defined('DEBUG_SF') || !DEBUG_SF) {
            return 'Tests only run when DEBUG_SF is enabled';
        }

        // Check hostname (only dev/localhost)
        $allowed_hosts = ['f4d.nl', 'localhost', '127.0.0.1'];
        $current_host = $_SERVER['HTTP_HOST'] ?? '';

        $is_allowed = false;
        foreach ($allowed_hosts as $allowed) {
            if (strpos($current_host, $allowed) !== false) {
                $is_allowed = true;
                break;
            }
        }

        if (!$is_allowed) {
            return 'Tests only run on dev/localhost environments';
        }

        // Check Migration Manager exists
        if (!class_exists('SUPER_Migration_Manager')) {
            return 'SUPER_Migration_Manager class not found';
        }

        // Check Background Migration exists
        if (!class_exists('SUPER_Background_Migration')) {
            return 'SUPER_Background_Migration class not found';
        }

        // Check Data Access exists
        if (!class_exists('SUPER_Data_Access')) {
            return 'SUPER_Data_Access class not found';
        }

        return true;
    }

    /**
     * Log message
     *
     * @param string $message
     */
    private function log($message) {
        $this->results['log'][] = $message;

        // Output to CLI if running in CLI
        if (php_sapi_name() === 'cli') {
            echo $message . "\n";
        }
    }

    /**
     * Get test results
     *
     * @return array
     */
    private function get_results() {
        return [
            'success' => true,
            'log' => $this->results['log'] ?? [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Create error result
     *
     * @param string $error
     * @return array
     */
    private function error_result($error) {
        return [
            'success' => false,
            'error' => $error,
            'log' => $this->results['log'] ?? [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get available tests
     *
     * @return array
     */
    public function get_available_tests() {
        return $this->available_tests;
    }
}

endif;

// CLI execution
if (php_sapi_name() === 'cli' && !empty($argv)) {
    $test_name = $argv[1] ?? 'all';
    $test = new SUPER_Migration_Integration_Test();
    $results = $test->run($test_name);

    echo "\n========================================\n";
    if ($results['success']) {
        echo "✅ ALL TESTS PASSED\n";
        exit(0);
    } else {
        echo "❌ TESTS FAILED: " . $results['error'] . "\n";
        exit(1);
    }
}
